<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package	   Postgres_Lite
 * @author     Josh Turmel
 * @copyright  (c) 2009 LifeChurch.tv
 */
class Postgres_Lite_Core {

	// Database instances
	public static $instances = array();

	// Global benchmark
	public static $benchmarks = array();

	// Configuration
	protected $config = array
	(
		'benchmark'     => TRUE,
		'persistent'    => FALSE,
		'connection'    => '',
		'character_set' => 'utf8',
		'table_prefix'  => '',
		'object'        => TRUE,
		'escape'        => TRUE,
	);

	protected $link;

	// Un-compiled parts of the SQL query
	protected $set        = array();
	protected $where      = array();
	protected $last_query = '';

	/**
	 * Returns a singleton instance of Database.
	 *
	 * @param   mixed   configuration array or DSN
	 * @return  Postgres_Lite_Core
	 */
	public static function & instance($name = 'default', $config = NULL)
	{
		if ( ! isset(Postgres_Lite::$instances[$name]))
		{
			// Create a new instance
			Postgres_Lite::$instances[$name] = new Postgres_Lite($config === NULL ? $name : $config);
		}

		return Postgres_Lite::$instances[$name];
	}

	/**
	 * Returns the name of a given Postgres_Lite instance.
	 *
	 * @param   Postgres_Lite  instance of Postgres_Lite
	 * @return  string
	 */
	public static function instance_name(Postgres_Lite $db)
	{
		return array_search($db, Postgres_Lite::$instances, TRUE);
	}

	/**
	 * Sets up the Postgres_Lite configuration
	 *
	 * @throws  Kohana_Postgres_Lite_Exception
	 */
	public function __construct($config = array())
	{
		if (empty($config))
		{
			// Load the default group
			$config = Kohana::config('postgres_lite.default');
		}
		elseif (is_array($config) AND count($config) > 0)
		{
			if ( ! array_key_exists('connection', $config))
			{
				$config = array('connection' => $config);
			}
		}
		elseif (is_string($config))
		{
			// The config is a DSN string
			if (strpos($config, '://') !== FALSE)
			{
				$config = array('connection' => $config);
			}
			// The config is a group name
			else
			{
				$name = $config;

				// Test the config group name
				if (($config = Kohana::config('postgres_lite.'.$config)) === NULL)
					throw new Kohana_Database_Exception('postgres_lite.undefined_group', $name);
			}
		}

		// Merge the default config with the passed config
		$this->config = array_merge($this->config, $config);

		if (is_string($this->config['connection']))
		{
			// Make sure the connection is valid
			if (strpos($this->config['connection'], '://') === FALSE)
				throw new Kohana_Database_Exception('postgres_lite.invalid_dsn', $this->config['connection']);

			// Parse the DSN, creating an array to hold the connection parameters
			$db = array
			(
				'type'     => FALSE,
				'user'     => FALSE,
				'pass'     => FALSE,
				'host'     => FALSE,
				'port'     => FALSE,
				'socket'   => FALSE,
				'database' => FALSE
			);

			// Get the protocol and arguments
			list ($db['type'], $connection) = explode('://', $this->config['connection'], 2);

			if (strpos($connection, '@') !== FALSE)
			{
				// Get the username and password
				list ($db['pass'], $connection) = explode('@', $connection, 2);
				// Check if a password is supplied
				$logindata = explode(':', $db['pass'], 2);
				$db['pass'] = (count($logindata) > 1) ? $logindata[1] : '';
				$db['user'] = $logindata[0];

				// Prepare for finding the database
				$connection = explode('/', $connection);

				// Find the database name
				$db['database'] = array_pop($connection);

				// Reset connection string
				$connection = implode('/', $connection);

				// Find the socket
				if (preg_match('/^unix\([^)]++\)/', $connection))
				{
					// This one is a little hairy: we explode based on the end of
					// the socket, removing the 'unix(' from the connection string
					list ($db['socket'], $connection) = explode(')', substr($connection, 5), 2);
				}
				elseif (strpos($connection, ':') !== FALSE)
				{
					// Fetch the host and port name
					list ($db['host'], $db['port']) = explode(':', $connection, 2);
				}
				else
				{
					$db['host'] = $connection;
				}
			}
			else
			{
				// File connection
				$connection = explode('/', $connection);

				// Find database file name
				$db['database'] = array_pop($connection);

				// Find database directory name
				$db['socket'] = implode('/', $connection).'/';
			}

			// Reset the connection array to the database config
			$this->config['connection'] = $db;
		}

		Kohana::log('debug', 'Postgres_Lite Library initialized');
	}

	/**
	 * Simple connect method to get the database queries up and running.
	 *
	 * @return  void
	 */
	public function connect()
	{
		// A link can be a resource or an object
		if ( ! is_resource($this->link) AND ! is_object($this->link))
		{
			// Import the connect variables
			extract($this->config['connection']);

			// Persistent connections enabled?
			$connect = ($this->config['persistent'] == TRUE) ? 'pg_pconnect' : 'pg_connect';

			// Build the connection info
			$port = isset($port) ? 'port=\''.$port.'\'' : '';
			$host = isset($host) ? 'host=\''.$host.'\' '.$port : ''; // if no host, connect with the socket

			$connection_string = $host.' dbname=\''.$database.'\' user=\''.$user.'\' password=\''.$pass.'\'';

			// Make the connection and select the database
			if ($this->link = $connect($connection_string))
			{
				if ($charset = $this->config['character_set'])
				{
					$this->query('SET client_encoding TO '.pg_escape_string($this->link, $charset));
				}
		 
				// Clear password after successful connect
				$this->config['connection']['pass'] = NULL;
			}
			else
			{
				$this->link = FALSE;
			}

			if ( ! is_resource($this->link) AND ! is_object($this->link))
				throw new Kohana_Postgres_Lite_Exception('postgres_lite.connection', $this->driver->show_error());

			// Clear password after successful connect
			$this->config['connection']['pass'] = NULL;
		}
	}

	/**
	 * Runs a query and returns the result.
	 *
	 * @param   string  SQL query to execute
	 * @return  Postgres_Lite_Result
	 */
	public function query($sql = '')
	{
		if ($sql == '') return FALSE;

		// No link? Connect!
		$this->link or $this->connect();

		// Start the benchmark
		$start = microtime(TRUE);

		// Fetch the result
		$result = new Pgsql_Result(pg_query($this->link, $this->last_query = $sql), $this->link, $this->config['object'], $sql);

		// Stop the benchmark
		$stop = microtime(TRUE);

		if ($this->config['benchmark'] == TRUE)
		{
			// Benchmark the query
			self::$benchmarks[] = array('query' => $sql, 'time' => $stop - $start, 'rows' => count($result));
		}

		return $result;
	}

	/**
	 * Compiles an insert string and runs the query.
	 *
	 * @param   string  table name
	 * @param   array   array of key/value pairs to insert
	 * @return  Postgres_Lite_Result  Query result
	 */
	public function insert($table, $set = NULL)
	{
		if ( ! is_null($set))
		{
			$this->set($set);
		}

		if ($this->set == NULL)
			throw new Kohana_Postgres_Lite_Exception('postgres_lite.must_use_set');

		$table  = $this->config['table_prefix'].$table;
		$keys   = array_keys($this->set);
		$values = array_values($this->set);

		// Escape the column names
		foreach ($keys as $key => $value)
		{
			$keys[$key] = $this->escape_column($value);
		}

		$sql = 'INSERT INTO '.$this->escape_table($table).' ('.implode(', ', $keys).') VALUES ('.implode(', ', $values).')';

		$this->reset_write();

		return $this->query($sql);
	}

	/**
	 * Compiles an update string and runs the query.
	 *
	 * @param   string  table name
	 * @param   array   associative array of update values
	 * @param   array   where clause
	 * @return  Postgres_Lite_Result  Query result
	 */
	public function update($table, $set = NULL, $where = NULL)
	{
		if ( is_array($set))
		{
			$this->set($set);
		}

		if ( ! is_null($where))
		{
			$this->where($where);
		}

		if ($this->set == FALSE)
			throw new Kohana_Postgres_Lite_Exception('postgres_lite.must_use_set');

		$values = $this->set;
		$where  = $this->where;

		foreach ($values as $key => $val)
		{
			$valstr[] = $this->escape_column($key).' = '.$val;
		}

		$sql = 'UPDATE '.$this->escape_table($this->config['table_prefix'].$table).' SET '.implode(', ', $valstr).' WHERE '.implode(' ',$where);

		$this->reset_write();

		return $this->query($sql);
	}

	/**
	 * Compiles a delete string and runs the query.
	 *
	 * @param   string  table name
	 * @param   array   where clause
	 * @return  Postgres_Lite_Result  Query result
	 */
	public function delete($table, $where = NULL)
	{
		if (! is_null($where))
		{
			$this->where($where);
		}

		if (count($this->where) < 1)
		{
			throw new Kohana_Postgres_Lite_Exception('postgres_lite.must_use_where');
		}

		$sql = 'DELETE FROM '.$this->escape_table($this->config['table_prefix'].$table).' WHERE '.implode(' ', $this->where);

		$this->reset_write();

		return $this->query($sql);
	}

	protected function escape_column($column)
	{
		if (!$this->config['escape'])
			return $column;

		if (strtolower($column) == 'count(*)' OR $column == '*')
			return $column;

		// This matches any modifiers we support to SELECT.
		if ( ! preg_match('/\b(?:all|distinct)\s/i', $column))
		{
			if (stripos($column, ' AS ') !== FALSE)
			{
				// Force 'AS' to uppercase
				$column = str_ireplace(' AS ', ' AS ', $column);

				// Runs escape_column on both sides of an AS statement
				$column = array_map(array($this, __FUNCTION__), explode(' AS ', $column));

				// Re-create the AS statement
				return implode(' AS ', $column);
			}

			return preg_replace('/[^.*]+/', '"$0"', $column);
		}

		$parts = explode(' ', $column);
		$column = '';

		for ($i = 0, $c = count($parts); $i < $c; $i++)
		{
			// The column is always last
			if ($i == ($c - 1))
			{
				$column .= preg_replace('/[^.*]+/', '"$0"', $parts[$i]);
			}
			else // otherwise, it's a modifier
			{
				$column .= $parts[$i].' ';
			}
		}

		return $column;
	}

	protected function escape_table($table)
	{
		if (!$this->config['escape'])
		{
			return $table;
		}

		return '"'.str_replace('.', '"."', $table).'"';
	}

	/**
	 * Selects the where(s) for a database query.
	 *
	 * @param   string|array  key name or array of key => value pairs
	 * @param   string        value to match with key
	 * @param   boolean       disable quoting of WHERE clause
	 * @return  Postgres_Lite_Core        This Postgres_Lite object.
	 */
	public function where($key, $value = NULL, $quote = TRUE)
	{
		$quote = (func_num_args() < 2 AND ! is_array($key)) ? -1 : $quote;
		$keys  = is_array($key) ? $key : array($key => $value);

		foreach ($keys as $key => $value)
		{
			$key           = (strpos($key, '.') !== FALSE) ? $this->config['table_prefix'].$key : $key;
			$this->where[] = $this->driver_where($key, $value, 'AND ', count($this->where), $quote);
		}

		return $this;
	}

	/**
	 * Builds a WHERE portion of a query.
	 *
	 * @param   mixed    key
	 * @param   string   value
	 * @param   string   type
	 * @param   int      number of where clauses
	 * @param   boolean  escape the value
	 * @return  string
	 */
	protected function driver_where($key, $value, $type, $num_wheres, $quote)
	{
		$prefix = ($num_wheres == 0) ? '' : $type;

		if ($quote === -1)
		{
			$value = '';
		}
		else
		{
			if ($value === NULL)
			{
				if ( ! $this->has_operator($key))
				{
					$key .= ' IS';
				}

				$value = ' NULL';
			}
			elseif (is_bool($value))
			{
				if ( ! $this->has_operator($key))
				{
					$key .= ' =';
				}

				$value = ($value == TRUE) ? ' 1' : ' 0';
			}
			else
			{
				if ( ! $this->has_operator($key))
				{
					$key = $this->escape_column($key).' =';
				}
				else
				{
					preg_match('/^(.+?)([<>!=]+|\bIS(?:\s+NULL))\s*$/i', $key, $matches);
					if (isset($matches[1]) AND isset($matches[2]))
					{
						$key = $this->escape_column(trim($matches[1])).' '.trim($matches[2]);
					}
				}

				$value = ' '.(($quote == TRUE) ? $this->escape($value) : $value);
			}
		}

		return $prefix.$key.$value;
	}

	/**
	* Determines if the string has an arithmetic operator in it.
	*
	* @param string string to check
	* @return boolean
	*/
	protected function has_operator($str)
	{
		return (bool) preg_match('/[<>!=]|\sIS(?:\s+NOT\s+)?\b/i', trim($str));
	}

	/**
	 * Resets all private insert and update variables.
	 *
	 * @return  void
	 */
	protected function reset_write()
	{
		$this->set   = array();
		$this->from  = array();
		$this->where = array();
	}

	/**
	 * Allows key/value pairs to be set for inserting or updating.
	 *
	 * @param   string|array  key name or array of key => value pairs
	 * @param   string        value to match with key
	 * @return  Postgres_Lite_Core        This Postgres_Lite object.
	 */
	public function set($key, $value = '')
	{
		if ( ! is_array($key))
		{
			$key = array($key => $value);
		}

		foreach ($key as $k => $v)
		{
			// Add a table prefix if the column includes the table.
			if (strpos($k, '.'))
				$k = $this->config['table_prefix'].$k;

			$this->set[$k] = $this->escape($v);
		}

		return $this;
	}

	/**
	 * Escapes any input value.
	 *
	 * @param   mixed   value to escape
	 * @return  string
	 */
	public function escape($value)
	{
		if ( ! $this->config['escape'])
			return $value;

		switch (gettype($value))
		{
			case 'string':
				$value = '\''.$this->escape_str($value).'\'';
			break;
			case 'boolean':
				$value = (int) $value;
			break;
			case 'double':
				// Convert to non-locale aware float to prevent possible commas
				$value = sprintf('%F', $value);
			break;
			default:
				$value = ($value === NULL) ? 'NULL' : $value;
			break;
		}

		return (string) $value;
	}

	public function escape_str($str)
	{
		if (!$this->config['escape'])
			return $str;

		is_resource($this->link) or $this->connect();

		return pg_escape_string($this->link, $str);
	}
} // End Postgres_Lite Class

/**
 * Database_Result
 *
 */
abstract class Database_Result implements ArrayAccess, Iterator, Countable
{
	// Result resource, insert id, and SQL
	protected $result;
	protected $insert_id;
	protected $sql;

	// Current and total rows
	protected $current_row = 0;
	protected $total_rows  = 0;

	// Fetch function and return type
	protected $fetch_type;
	protected $return_type;

	/**
	 * Returns the SQL used to fetch the result.
	 *
	 * @return  string
	 */
	public function sql()
	{
		return $this->sql;
	}

	/**
	 * Returns the insert id from the result.
	 *
	 * @return  mixed
	 */
	public function insert_id()
	{
		return $this->insert_id;
	}

	/**
	 * Prepares the query result.
	 *
	 * @param   boolean   return rows as objects
	 * @param   mixed     type
	 * @return  Database_Result
	 */
	abstract function result($object = TRUE, $type = FALSE);

	/**
	 * Builds an array of query results.
	 *
	 * @param   boolean   return rows as objects
	 * @param   mixed     type
	 * @return  array
	 */
	abstract function result_array($object = NULL, $type = FALSE);

	/**
	 * Gets the fields of an already run query.
	 *
	 * @return  array
	 */
	abstract public function list_fields();

	/**
	 * Seek to an offset in the results.
	 *
	 * @return  boolean
	 */
	abstract public function seek($offset);

	/**
	 * Countable: count
	 */
	public function count()
	{
		return $this->total_rows;
	}

	/**
	 * ArrayAccess: offsetExists
	 */
	public function offsetExists($offset)
	{
		if ($this->total_rows > 0)
		{
			$min = 0;
			$max = $this->total_rows - 1;

			return ! ($offset < $min OR $offset > $max);
		}

		return FALSE;
	}

	/**
	 * ArrayAccess: offsetGet
	 */
	public function offsetGet($offset)
	{
		if ( ! $this->seek($offset))
			return FALSE;

		// Return the row by calling the defined fetching callback
		return call_user_func($this->fetch_type, $this->result, $this->return_type);
	}

	/**
	 * ArrayAccess: offsetSet
	 *
	 * @throws  Kohana_Database_Exception
	 */
	final public function offsetSet($offset, $value)
	{
		throw new Kohana_Database_Exception('database.result_read_only');
	}

	/**
	 * ArrayAccess: offsetUnset
	 *
	 * @throws  Kohana_Database_Exception
	 */
	final public function offsetUnset($offset)
	{
		throw new Kohana_Database_Exception('database.result_read_only');
	}

	/**
	 * Iterator: current
	 */
	public function current()
	{
		return $this->offsetGet($this->current_row);
	}

	/**
	 * Iterator: key
	 */
	public function key()
	{
		return $this->current_row;
	}

	/**
	 * Iterator: next
	 */
	public function next()
	{
		++$this->current_row;
		return $this;
	}

	/**
	 * Iterator: prev
	 */
	public function prev()
	{
		--$this->current_row;
		return $this;
	}

	/**
	 * Iterator: rewind
	 */
	public function rewind()
	{
		$this->current_row = 0;
		return $this;
	}

	/**
	 * Iterator: valid
	 */
	public function valid()
	{
		return $this->offsetExists($this->current_row);
	}


} // End Database Result Interface

/**
 * PostgreSQL Result
 */
class Pgsql_Result extends Database_Result {

	// Data fetching types
	protected $fetch_type  = 'pgsql_fetch_object';
	protected $return_type = PGSQL_ASSOC;

	/**
	 * Sets up the result variables.
	 *
	 * @param  resource  query result
	 * @param  resource  database link
	 * @param  boolean   return objects or arrays
	 * @param  string    SQL query that was run
	 */
	public function __construct($result, $link, $object = TRUE, $sql)
	{
		$this->result = $result;

		// If the query is a resource, it was a SELECT, SHOW, DESCRIBE, EXPLAIN query
		if (is_resource($result))
		{
			// Its an DELETE, INSERT, REPLACE, or UPDATE query
			if (preg_match('/^(?:delete|insert|replace|update)\b/iD', trim($sql), $matches))
			{
				$this->insert_id  = (strtolower($matches[0]) == 'insert') ? $this->insert_id() : FALSE;
				$this->total_rows = pg_affected_rows($this->result);
			}
			else
			{
				$this->current_row = 0;
				$this->total_rows  = pg_num_rows($this->result);
				$this->fetch_type = ($object === TRUE) ? 'pg_fetch_object' : 'pg_fetch_array';
			}
		}
		else
		{
			throw new Kohana_Database_Exception('database.error', pg_last_error().' - '.$sql);
		}

		// Set result type
		$this->result($object);

		// Store the SQL
		$this->sql = $sql;
	}

	/**
	 * Magic __destruct function, frees the result.
	 */
	public function __destruct()
	{
		if (is_resource($this->result))
		{
			pg_free_result($this->result);
		}
	}

	public function result($object = TRUE, $type = PGSQL_ASSOC)
	{
		$this->fetch_type = ((bool) $object) ? 'pg_fetch_object' : 'pg_fetch_array';

		// This check has to be outside the previous statement, because we do not
		// know the state of fetch_type when $object = NULL
		// NOTE - The class set by $type must be defined before fetching the result,
		// autoloading is disabled to save a lot of stupid overhead.
		if ($this->fetch_type == 'pg_fetch_object')
		{
			$this->return_type = (is_string($type) AND Kohana::auto_load($type)) ? $type : 'stdClass';
		}
		else
		{
			$this->return_type = $type;
		}

		return $this;
	}

	public function as_array($object = NULL, $type = PGSQL_ASSOC)
	{
		return $this->result_array($object, $type);
	}

	public function result_array($object = NULL, $type = PGSQL_ASSOC)
	{
		$rows = array();

		if (is_string($object))
		{
			$fetch = $object;
		}
		elseif (is_bool($object))
		{
			if ($object === TRUE)
			{
				$fetch = 'pg_fetch_object';

				// NOTE - The class set by $type must be defined before fetching the result,
				// autoloading is disabled to save a lot of stupid overhead.
				$type = (is_string($type) AND Kohana::auto_load($type)) ? $type : 'stdClass';
			}
			else
			{
				$fetch = 'pg_fetch_array';
			}
		}
		else
		{
			// Use the default config values
			$fetch = $this->fetch_type;

			if ($fetch == 'pg_fetch_object')
			{
				$type = (is_string($type) AND Kohana::auto_load($type)) ? $type : 'stdClass';
			}
		}

		while ($row = $fetch($this->result, NULL, $type))
		{
			$rows[] = $row;
		}

		return $rows;
	}

	public function insert_id()
	{
		if ($this->insert_id === NULL)
		{
			$query = 'SELECT LASTVAL() AS insert_id';

			// Disable error reporting for this, just to silence errors on
			// tables that have no serial column.
			$ER = error_reporting(0);

			$result = pg_query($query);
			$insert_id = pg_fetch_array($result, NULL, PGSQL_ASSOC);

			$this->insert_id = $insert_id['insert_id'];

			// Reset error reporting
			error_reporting($ER);
		}

		return $this->insert_id;
	}

	public function seek($offset)
	{
		if ( ! $this->offsetExists($offset))
			return FALSE;

		return pg_result_seek($this->result, $offset);
	}

	public function list_fields()
	{
		$field_names = array();
		while ($field = pg_field_name($this->result))
		{
			$field_names[] = $field->name;
		}

		return $field_names;
	}

	/**
	 * ArrayAccess: offsetGet
	 */
	public function offsetGet($offset)
	{
		if ( ! $this->seek($offset))
			return FALSE;

		// Return the row by calling the defined fetching callback
		$fetch = $this->fetch_type;
		return $fetch($this->result, NULL, $this->return_type);
	}

} // End Pgsql_Result Class

/**
 * Sets the code for a Postgres_Lite exception.
 */
class Kohana_Postgres_Lite_Exception extends Kohana_Exception {

	protected $code = E_DATABASE_ERROR;

} // End Kohana Postgres_Lite Exception
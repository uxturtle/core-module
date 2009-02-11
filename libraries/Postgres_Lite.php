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
			extract($this->db_config['connection']);

			// Persistent connections enabled?
			$connect = ($this->db_config['persistent'] == TRUE) ? 'pg_pconnect' : 'pg_connect';

			// Build the connection info
			$port = isset($port) ? 'port=\''.$port.'\'' : '';
			$host = isset($host) ? 'host=\''.$host.'\' '.$port : ''; // if no host, connect with the socket

			$connection_string = $host.' dbname=\''.$database.'\' user=\''.$user.'\' password=\''.$pass.'\'';

			// Make the connection and select the database
			if ($this->link = $connect($connection_string))
			{
				if ($charset = $this->db_config['character_set'])
				{
					echo $this->set_charset($charset);
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
		$result = new Pgsql_Result(pg_query($this->link, $this->last_query = $sql), $this->link, $this->db_config['object'], $sql);

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
		if (!$this->db_config['escape'])
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
		if (!$this->db_config['escape'])
		{
			return $table;
		}

		return '"'.str_replace('.', '"."', $table).'"';
	}
} // End Postgres_Lite Class

/**
 * Sets the code for a Postgres_Lite exception.
 */
class Kohana_Postgres_Lite_Exception extends Kohana_Exception {

	protected $code = E_DATABASE_ERROR;

} // End Kohana Postgres_Lite Exception
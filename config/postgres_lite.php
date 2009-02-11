<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package  Postgres_Lite
 *
 * Postgres_Lite connection settings, defined as arrays, or "groups". If no group
 * name is used when loading the Postgres_Lite library, the group named "default"
 * will be used.
 *
 * Each group can be connected to independently, and multiple groups can be
 * connected at once.
 *
 * Group Options:
 *  benchmark     - Enable or disable database benchmarking
 *  persistent    - Enable or disable a persistent connection
 *  connection    - Array of connection specific parameters; alternatively,
 *                  you can use a DSN though it is not as fast and certain
 *                  characters could create problems (like an '@' character
 *                  in a password):
 *                  'connection'    => 'mysql://dbuser:secret@localhost/kohana'
 *  character_set - Database character set
 *  table_prefix  - Database table prefix
 *  object        - Enable or disable object results
 *  cache         - Enable or disable query caching
 *	escape        - Enable automatic query builder escaping
 */
$config['default'] = array
(
	'benchmark'     => TRUE,
	'persistent'    => FALSE,
	'connection'    => array
	(
		'type'     => 'pgsql',
		'user'     => '',
		'pass'     => '',
		'host'     => FALSE,
		'port'     => FALSE,
		'socket'   => FALSE,
		'database' => ''
	),
	'character_set' => 'utf8',
	'table_prefix'  => '',
	'object'        => FALSE,
	'escape'        => TRUE
);

$config['bibletext'] = array
(
	'benchmark'     => TRUE,
	'persistent'    => FALSE,
	'connection'    => array
	(
		'type'     => 'pgsql',
		'user'     => '',
		'pass'     => '',
		'host'     => FALSE,
		'port'     => FALSE,
		'socket'   => FALSE,
		'database' => ''
	),
	'character_set' => 'utf8',
	'table_prefix'  => '',
	'object'        => FALSE,
	'escape'        => TRUE
);

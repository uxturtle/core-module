<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Memcache-based Cache driver that supports using memcached-tags.
 *
 * $Id$
 *
 * @package    Cache
 * @author     Josh Turmel
 */
class Cache_MemcacheTags_Driver implements Cache_Driver {

	// Cache backend object and flags
	protected $backend;
	protected $flags;

	public function __construct()
	{
		if ( ! extension_loaded('memcache'))
			throw new Kohana_Exception('cache.extension_not_loaded', 'memcache-tags');

		$this->backend = memcache_connect("127.0.0.1", 11211);//new Memcache;
		$this->flags = Kohana::config('cache_memcache_tags.compression') ? MEMCACHE_COMPRESSED : 0;

		$servers = Kohana::config('cache_memcache_tags.servers');

		foreach ($servers as $server)
		{
			// Make sure all required keys are set
			$server += array('host' => '127.0.0.1', 'port' => 11211, 'persistent' => FALSE);

			// Add the server to the pool
			$this->backend->addServer($server['host'], $server['port'], (bool) $server['persistent'])
				or Kohana::log('error', 'Cache: Connection failed: '.$server['host']);
		}
	}

	public function find($tag)
	{
		return FALSE;
	}

	public function get($id)
	{
		return (($return = $this->backend->get($id)) === FALSE) ? NULL : $return;
	}

	public function set($id, $data, $tags, $lifetime)
	{
		// Memcache-tags driver expects unix timestamp
		if ($lifetime !== 0)
		{
			$lifetime += time();
		}

		$set = $this->backend->set($id, $data, $this->flags, $lifetime);

		// Only try to set tags if it was successful setting key/value
		if ($set === TRUE && count($tags) > 0)
		{
			if (is_array($tags) === TRUE)
			{
				foreach ($tags as $tag)
				{
					$this->backend->tag_add($tag, $id);
				}
			}
			else // individual tag
			{
				$this->backend->tag_add($tags, $id);
			}
		}

		return $set;
	}

	/**
	 * Delete a cache item by id.
	 *
	 * @param   string         cache id
	 * @param   array|string   tags for this item
	 * @return  boolean
	 */
	public function delete($id, $tags = FALSE)
	{
		// Invalidate all items in Memcache
		if ($id === TRUE)
		{
			return $this->backend->flush();
		}

		if ($id !== FALSE)
		{
			$this->backend->delete($id);
		}
		
		if ($tags !== FALSE && count($tags) > 0)
		{
			$this->backend->tag_delete($tags);
		}

		return TRUE;
	}

	public function delete_expired()
	{
		return TRUE;
	}

} // End Cache Memcache Driver

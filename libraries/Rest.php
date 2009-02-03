<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Rest Library
 *
 * $Id: Rest.php 20 2008-05-19 20:37:49Z samsoffes $
 *
 * @author     Sam Soffes
 * @author     Josh Turmel
 * @copyright  (c) 2008 LifeChurch.tv
 */
class Rest_Core {

	private $use_cache;
	private $cache_lifetime; // Cache lifetime in seconds
	private $options;
	
	public $output;
	
	public function __construct($use_cache = false, $cache_lifetime = null)
	{
		$this->use_cache      = $use_cache;
		$this->cache_lifetime = $cache_lifetime;
		
		$this->cache          = new Cache;
		
		$this->setReturnTransfer(true);
		$this->setUserAgent(Kohana::user_agent() or true);
	}
	
	public function setReferer($val)
	{
		$this->options[CURLOPT_REFERER] = $val;
	}
	
	public function setReturnTransfer($val)
	{
		$this->options[CURLOPT_RETURNTRANSFER] = (bool) $val;
	}
	
	public function setUserAgent($val)
	{
		$this->options[CURLOPT_USERAGENT] = $val;
	}
	
	public function setUrl($url)
	{
		$this->options[CURLOPT_URL] = $url;
	}
	
	public function setUserPwd($user, $password)
	{
		$this->options[CURLOPT_USERPWD] = "$user:$password";
	}
	
	public function fetch($url)
	{
		// Set URL
		$this->setUrl($url);
		
		$cache_name = 'rest_'.md5(serialize($this->options));
	
		if ($this->use_cache && $cache = $this->cache->get($cache_name))
		{
			$this->output = unserialize($cache);
		
		} else {
			
			// Create connection
			$ch = curl_init();
			
			// Set the options
			curl_setopt_array($ch, $this->options);
			
			// Fetch the URL, and return output
			$this->output = curl_exec($ch);
			
			// Close the connection
			curl_close($ch);
			
			// Store in cache
			if ($this->use_cache)
			{
				$this->cache->set($cache_name, serialize($this->output), null, $this->cache_lifetime);
			}
		}
		
		return $this->output;
	}
}
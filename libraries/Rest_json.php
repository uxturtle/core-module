<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Rest Library
 *
 * @author     Sam Soffes
 * @author     Josh Turmel
 * @copyright  (c) 2008 LifeChurch.tv
 */
class Rest_json_Core extends Rest_Core {

	public function __construct($use_cache = FALSE, $cache_lifetime = NULL)
	{
		parent::__construct($use_cache, $cache_lifetime);
	}

	public function fetch($url, $levels = NULL)
	{
		parent::fetch($url);
		$this->output = json_encode($this->output);
		return $this->output;
	}
}
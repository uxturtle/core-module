<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Rest Library
 *
 * @author     Sam Soffes
 * @author     Josh Turmel
 * @copyright  (c) 2008 LifeChurch.tv
 */
class Rest_xml_Core extends Rest_Core {

	public function __construct($use_cache = false, $cache_lifetime = null)
	{
		parent::__construct($use_cache, $cache_lifetime);
	}

	public function fetch($url)
	{
		parent::fetch($url);

		if (stripos($this->output, '<?xml') === 0)
		{
			return new SimpleXMLElement($this->output);
		}
		else
		{
			return false;
		}
	}
}
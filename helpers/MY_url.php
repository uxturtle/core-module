<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package    core
 * @author     Sam Soffes
 * @copyright  (c) 2009 LifeChurch.tv
 */
class url extends url_Core {

	public static function referer($default = FALSE)
	{
		return ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : $default);
	}
}

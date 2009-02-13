<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package    core
 * @author     Sam Soffes
 * @copyright  (c) 2009 LifeChurch.tv
 */

class yaml_Core {

	public static function from_array(array $array, $indent = FALSE, $wordwrap = FALSE)
	{
		return Spyc::YAMLDump($array, $indent, $wordwrap);
	}

	public function to_array($yaml_string)
	{
		return Spyc::YAMLLoad($yaml_string);
	}
}

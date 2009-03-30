<?php defined('SYSPATH') or die('No direct script access.');
/**
 * bool
 *
 * @package core
 * @author Josh Turmel
 *
 *
 **/
class bool_Core {
	
	public static function to_string($value)
	{
		if (is_bool($value) === TRUE)
		{
			return ($value === TRUE) ? 'true' : 'false';
		}

		// If it's not a boolean just return value unchanged
		return $value;
	}
}
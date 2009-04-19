<?php defined('SYSPATH') or die('No direct script access.');
/**
 * bool
 *
 * @package core
 * @author Josh Turmel
 *
 *
 **/
class int_Core {
	
	public static function min($value, $min = 1)
	{
		return ((int) $value >= $min) ? (int) $value : (int) $min;
	}
}
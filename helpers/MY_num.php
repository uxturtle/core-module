<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @class num
 * @abstract Extends core num helper class
 * @author Josh Turmel
 */
class num extends num_Core {

	/**
	 * @method suffix
	 * @abstract Returns the ordinal suffix of a number
	 * @author Josh Turmel
	 * 
	 * @return string suffix
	 */
	public static function suffix($number)
	{
		if (!is_numeric($number))
		{
			return '';
		}

		$last_number = substr($number, -1);

		switch(true)
		{
			case $last_number == 1:
				return 'st';
			case $last_number == 2;
				return 'nd';
			case $last_number == 3;
				return 'rd';
			default:
				return 'th';
		}
	}
}
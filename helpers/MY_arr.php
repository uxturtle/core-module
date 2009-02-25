<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package    core
 * @author     Sam Soffes
 * @author     Josh Turmel
 * @author     Kevin Morey
 * @copyright  (c) 2009 LifeChurch.tv
 */

class arr extends arr_Core {

	public static $eol = "\n";
	
	public static $xml_default_tag = "item";
		
	public static function is_assoc(array $array)
	{
		// Keys of the array
		$keys = array_keys($array);

		// If the array keys of the keys match the keys, then the array must
		// be associative.
		return array_keys($keys) !== $keys;
	}
	
	public static function get($key, $array, $default = FALSE, $return_default_when_empty = TRUE)
	{
		if (!array_key_exists($key, $array))
		{
			return $default;
		}
		return ($return_default_when_empty === TRUE && $array[$key] === '') ? $default : $array[$key];
	}

	/**
	 * build_assoc
	 * 
	 * initializes an associative array to NULL
	 *
	 * @param array $keys the keys of the associative array
	 * @return array the resulting array
	 * @author Kevin Morey
	 */
	function build_assoc(array $keys)
	{		
		$arr = array();
		
		foreach ($keys as $key) { $arr[$key] = NULL; }
		
		return $arr;
	}

	/**
	* to_xml
	*
	* @param array $array 
	* @param string $default_tag 
	* @param bool $include_header 
	* @return void
	* @author Kevin Morey
	*/
	public static function to_xml(array $array, $root_tag = '')
	{
		$out = '<?xml version="1.0" encoding="UTF-8"?>'.arr::$eol;
		$out .= arr::parse_xml_value($array, $root_tag);
		return $out;
	}
	
	public static function parse_xml_value($value, $tag = '', $tab_level = 0)
	{
		$svalue = '';

		// Determine the type
		switch (true)
		{
			// bool
			case (is_bool($value)):
				$svalue = "<$tag>".($value ? "1" : "0")."</$tag>";
				break;

			// string
			case (is_string($value)):
				if (empty($value)) 
				{
					$svalue = "<$tag />";
				}
				else
				{
					// TODO: correctly parse &
					$svalue = "<$tag>".htmlspecialchars(iconv('UTF-8', 'UTF-8//IGNORE', $value), ENT_NOQUOTES, 'UTF-8')."</$tag>";
				}
				break;

			// assoc array
			case (is_array($value)):
				if (empty($value))
				{
					$svalue = "<$tag />";
				}
				elseif (arr::is_assoc($value))
				{
					if ($tag != '') 
					{ 
						$svalue = '<'.$tag.'>'.arr::$eol;
						$tab_level++;
					}
					foreach ($value as $vkey => $vvalue)
					{
						$svalue .= self::parse_xml_value($vvalue, $vkey, $tab_level);
					}
					if ($tag != '') 
					{ 
					   $tab_level--;
					   $svalue .= self::tabs($tab_level).'</'.$tag.'>'; 
					}
				}
				else 
				{
 					if ($tag != '') 
					{ 
						$svalue="<$tag>".arr::$eol;
						$tab_level++;
					}
					foreach ($value as $vvalue)
					{
						$svalue .= self::parse_xml_value($vvalue, arr::$xml_default_tag, $tab_level);
					}
					if ($tag != '') 
					{
						$tab_level--;
						$svalue.=self::tabs($tab_level)."</$tag>"; 
					}
				}
				break;
				
			// others 
			default:
				if (empty($value))
				{
					$svalue = "<$tag />";
				}
				else
				{
					$svalue = "<$tag>$value</$tag>";
				}
				break;
		}
		return self::tabs($tab_level).$svalue.arr::$eol;
	}
	
	private static function tabs($tab_level)
	{
		$tabs = '';
		for ($x = 0; $x < $tab_level; $x++)
		{
			$tabs .= "\t";
		}
		return $tabs;
	}
}
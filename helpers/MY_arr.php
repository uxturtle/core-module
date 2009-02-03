<?php defined('SYSPATH') OR die('No direct access allowed.');

class arr extends arr_Core {

	public static $plist_eol = "\n";

	public static function is_assoc(array $array)
	{
		// Keys of the array
		$keys = array_keys($array);

		// If the array keys of the keys match the keys, then the array must
		// be associative.
		return array_keys($keys) !== $keys;
	}

	public static function to_plist(array $array)
	{
		$out = '<?xml version="1.0" encoding="UTF-8"?>'.arr::$plist_eol;
		$out .= '<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">'.arr::$plist_eol;
		$out .= '<plist version="1.0">'.arr::$plist_eol;
		$out .= self::parse_plist_value($array);
		$out .= '</plist>'.arr::$plist_eol;
		return $out;
	}
	
	private static function parse_plist_value($value, $key = false, $tab_level = 0)
	{
		// Determine the type
		switch (true)
		{
			// integer
			case ((is_numeric($value) && is_float($value) === FALSE)):
				$svalue = '<integer>'.$value.'</integer>';
				break;

			// date
			case (!is_array($value) && preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}\:[0-9]{2}\:[0-9]{2}Z/', $value)):
				$svalue = '<date>'.$value.'</date>';
				break;

			// string
			case (is_string($value)):
				// TODO: correctly parse &
				$svalue = '<string>'.htmlspecialchars(iconv('UTF-8', 'UTF-8//IGNORE', $value), ENT_NOQUOTES, 'UTF-8').'</string>';
				break;

			// true
			case ($value === TRUE):
				$svalue = '<true />';
				break;

			// false
			case ($value === FALSE):
				$svalue = '<false />';
				break;

			// dict
			case (is_array($value) && arr::is_assoc($value)):
				$svalue = ($key ? arr::$plist_eol.self::plist_tabs($tab_level) : '').'<dict>'.arr::$plist_eol;
				$tab_level++;
				foreach ($value as $vkey => $vvalue)
				{
					$svalue .= self::parse_plist_value($vvalue, $vkey, $tab_level);
				}
				$tab_level--;
				$svalue .= self::plist_tabs($tab_level).'</dict>';
				$svalue;
				break;

			// array
			case (is_array($value) && !arr::is_assoc($value)):
				$svalue = ($key ? arr::$plist_eol : '').self::plist_tabs($tab_level).'<array>'.arr::$plist_eol;
				$tab_level++;
				foreach ($value as $vvalue)
				{
					$svalue .= self::parse_plist_value($vvalue, FALSE, $tab_level);
				}
				$tab_level--;
				$svalue .= self::plist_tabs($tab_level).'</array>';
				break;
		}
		return self::plist_tabs($tab_level).($key ? '<key>'.$key.'</key>' : '').$svalue.arr::$plist_eol;
	}
	
	private static function plist_tabs($tab_level)
	{
		$tabs = '';
		for ($x = 0; $x < $tab_level; $x++)
		{
			$tabs .= "\t";
		}
		return $tabs;
	}
}
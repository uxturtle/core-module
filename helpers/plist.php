<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package    core
 * @author     Sam Soffes
 * @copyright  (c) 2009 LifeChurch.tv
 */

class plist_Core {

	public static $eol = "\n";
	
	public static function to_array($xml_string)
	{
		$dom = new DomDocument;
		$dom->loadXML($xml_string);
		return plist::parse_dom($dom);
	}

	public static function to_array_from_path($filepath)
	{
		$dom = new DomDocument;
		$dom->load($filepath);
		return plist::parse_dom($dom);
	}

	public static function from_array(array $array)
	{
		$out = '<?xml version="1.0" encoding="UTF-8"?>'.plist::$eol;
		$out .= '<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">'.plist::$eol;
		$out .= '<plist version="1.0">'.plist::$eol;
		$out .= self::parse_plist_value($array);
		$out .= '</plist>'.plist::$eol;
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

			// real
			case ((is_numeric($value) && is_float($value) === TRUE)):
				$svalue = '<real>'.$value.'</real>';
				break;

			// date
			case (!is_array($value) && (preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}\:[0-9]{2}\:[0-9]{2}(Z|[\-\+][0-9]{2}\:[0-9]{2})/', $value))):
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
				$svalue = ($key ? plist::$eol.self::tabs($tab_level) : '').'<dict>'.plist::$eol;
				$tab_level++;
				foreach ($value as $vkey => $vvalue)
				{
					$svalue .= self::parse_plist_value($vvalue, $vkey, $tab_level);
				}
				$tab_level--;
				$svalue .= self::tabs($tab_level).'</dict>';
				$svalue;
				break;

			// array
			case (is_array($value) && !arr::is_assoc($value)):
				$svalue = ($key ? plist::$eol : '').self::tabs($tab_level).'<array>'.plist::$eol;
				$tab_level++;
				foreach ($value as $vvalue)
				{
					$svalue .= self::parse_plist_value($vvalue, FALSE, $tab_level);
				}
				$tab_level--;
				$svalue .= self::tabs($tab_level).'</array>';
				break;
		}
		return self::tabs($tab_level).($key ? '<key>'.$key.'</key>' : '').$svalue.plist::$eol;
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

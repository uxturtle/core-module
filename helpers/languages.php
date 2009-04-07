<?php defined('SYSPATH') or die('No direct script access.');
/**
 * languages
 *
 * @package core
 * @author Kevin Morey
 *
 * This class returns languages from the DB
 *
 **/
class languages {

	public static function get_list($db) {
		return $db->query('select * from languages order by language_name asc')->result_array(false);
	}

	public static function getLanguageByISO($db, $iso)
	{
		return $db->query('select * from languages where iso639_1 = '.$db->escape($iso).' limit 1')->current();
	}
}
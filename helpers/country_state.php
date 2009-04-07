<?php defined('SYSPATH') or die('No direct script access.');
/**
 * country_state
 *
 * @package core
 * @author Josh Turmel
 *
 * This class returns countries and states from the DB
 *
 **/
class country_state {

	public static function countries($db) {
		return $db->query('select * from countries')->result_array(false);
	}

	public static function getCountryByISO($db, $iso)
	{
		return $db->query('select * from countries where iso = '.$db->escape($iso).' limit 1')->current();
	}

	public static function states($db) {
		return $db->query('select * from states')->result_array(false);
	}
}
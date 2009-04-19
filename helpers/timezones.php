<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @class timezones
 * @abstract Provides functions for returning timezone information
 * @author Josh Turmel
 */
 
class timezones_Core {

	/**
	 * @method list
	 * @abstract Returns the list of zones based on country, utc, etc
	 * @author Josh Turmel
	 * 
	 * @return array
	 */
	public static function get_list($country_iso = false, $utc = false)
	{
		$list = self::zones();
		
		// Parse list and filter by country
		if ($country_iso !== false)
		{
			$country_iso = strtoupper($country_iso);

			foreach ($list as $key => $val)
			{
			    if ($val[0] !== $country_iso)
			    {
			    	unset($list[$key]);
			    }
			}
		}
		
		// Add in UTC if needed
		if ($utc !== false)
		{
			$list = array_merge($list, array('UTC' => array('', '(GMT+00:00) GMT', 0)));
		}
		
		uasort($list, array('self', 'so'));
		
		return $list;
	}
	
	public static function so ($a, $b)
	{
		switch (true)
		{
			case ($a[2] < $b[2]):
				return -1;
			case ($a[2] > $b[2]):
				return 1;
			default:
				return 0;
		}
	}  
	
	/**
	 * @method zones
	 * @abstract Returns array of zones
	 * @author Josh Turmel
	 * 
	 * @return array
	 */
	private static function zones()
	{
		return array(
			'Europe/Andorra'            => array('AD', '(GMT+01:00) Andorra', 1),
			'Asia/Kabul'                => array('AF', '(GMT+04:30) Kabul', 4.5),
			'America/Antigua'           => array('AG', '(GMT-04:00) Antigua', -4),
			'America/Anguilla'          => array('AI', '(GMT-04:00) Anguilla', -4),
			'Europe/Tirane'             => array('AL', '(GMT+01:00) Tirane', 1),
			'Asia/Yerevan'              => array('AM', '(GMT+04:00) Yerevan', 4),
			'America/Curacao'           => array('AN', '(GMT-04:00) Curacao', -4),
			'Africa/Luanda'             => array('AO', '(GMT+01:00) Luanda', 1),
			'Antarctica/Palmer'         => array('AQ', '(GMT-04:00) Palmer', -4),
			'Antarctica/Rothera'        => array('AQ', '(GMT-03:00) Rothera', -3),
			'Antarctica/Syowa'          => array('AQ', '(GMT+03:00) Syowa', 3),
			'Antarctica/Mawson'         => array('AQ', '(GMT+06:00) Mawson', 6),
			'Antarctica/Vostok'         => array('AQ', '(GMT+06:00) Vostok', 6),
			'Antarctica/Davis'          => array('AQ', '(GMT+07:00) Davis', 7),
			'Antarctica/Casey'          => array('AQ', '(GMT+08:00) Casey', 8),
			'Antarctica/DumontDUrville' => array('AQ', '(GMT+10:00) Dumont D\'Urville', 10),
			'Antarctica/McMurdo'        => array('AQ', '(GMT+12:00) Antarctica/McMurdo', 12),
			'Antarctica/South_Pole'     => array('AQ', '(GMT+12:00) Antarctica/South Pole', 12),
			'America/Buenos_Aires'      => array('AR', '(GMT-03:00) Buenos Aires', -3),
			'Pacific/Pago_Pago'         => array('AS', '(GMT-11:00) Pago Pago', -11),
			'Europe/Vienna'             => array('AT', '(GMT+01:00) Vienna', 1),
			'Australia/Perth'           => array('AU', '(GMT+08:00) Western Time - Perth', 8),
			'Australia/Adelaide'        => array('AU', '(GMT+09:30) Central Time - Adelaide', 9.5),
			'Australia/Darwin'          => array('AU', '(GMT+09:30) Central Time - Darwin', 9.5),
			'Australia/Brisbane'        => array('AU', '(GMT+10:00) Eastern Time - Brisbane', 10),
			'Australia/Hobart'          => array('AU', '(GMT+10:00) Eastern Time - Hobart', 10),
			'Australia/Melbourne'       => array('AU', '(GMT+10:00) Eastern Time - Melbourne, Sydney', 10),
			'America/Aruba'             => array('AW', '(GMT-04:00) Aruba', -4),
			'Europe/Helsinki'           => array('AX', '(GMT+02:00) Helsinki', 2),
			'Asia/Baku'                 => array('AZ', '(GMT+04:00) Baku', 4),
			'CET'                       => array('BA', '(GMT+01:00) Central European Time', 1),
			'America/Barbados'          => array('BB', '(GMT-04:00) Barbados', -4),
			'Asia/Dhaka'                => array('BD', '(GMT+06:00) Dhaka', 6),
			'Europe/Brussels'           => array('BE', '(GMT+01:00) Brussels', 1),
			'Asia/Bahrain'              => array('BH', '(GMT+03:00) Bahrain', 3),
			'Africa/Porto-Novo'         => array('BJ', '(GMT+01:00) Porto-Novo', 1),
			'Atlantic/Bermuda'          => array('BM', '(GMT-04:00) Bermuda', -4),
			'America/La_Paz'            => array('BO', '(GMT-04:00) La Paz', -4),
			'America/Nassau'            => array('BS', '(GMT-05:00) Nassau', -5),
			'Asia/Thimphu'              => array('BT', '(GMT+06:00) Thimphu', 6),
			'Europe/Minsk'              => array('BY', '(GMT+02:00) Minsk', 2),
			'America/Belize'            => array('BZ', '(GMT-06:00) Belize', -6),
			'America/Vancouver'         => array('CA', '(GMT-08:00) Pacific Time - Vancouver', -8),
			'America/Whitehorse'        => array('CA', '(GMT-08:00) Pacific Time - Whitehourse', -8),
			'America/Dawson_Creek'      => array('CA', '(GMT-07:00) Mountain Time - Dawson Creek', -7),
			'America/Edmonton'          => array('CA', '(GMT-07:00) Mountain Time - Edmonton', -7),
			'America/Yellowknife'       => array('CA', '(GMT-07:00) Mountain Time - Yellowknife', -7),
			'America/Regina'            => array('CA', '(GMT-06:00) Central Time - Regina', -6),
			'America/Winnipeg'          => array('CA', '(GMT-06:00) Central Time - Winnipeg', -6),
			'America/Iqaluit'           => array('CA', '(GMT-05:00) Eastern Time - Iqaluit', -5),
			'America/Montreal'          => array('CA', '(GMT-05:00) Eastern Time - Montreal', -5),
			'America/Toronto'           => array('CA', '(GMT-05:00) Eastern Time - Toronto', -5),
			'America/Halifax'           => array('CA', '(GMT-04:00) Atlantic Time - Halifax', -4),
			'America/St_Johns'          => array('CA', '(GMT-03:30) Newfoundland Time - St. Johns', -3.5),
			'Africa/Algiers'            => array('DZ', '(GMT+01:00) Algiers', 1),
			'Europe/London'             => array('GB', '(GMT+00:00) London', 0),
			'Asia/Tokyo'                => array('JP', '(GMT+09:00) Tokyo', 9),
			'America/Tijuana'           => array('MX', '(GMT-08:00) Pacific Time - Tijuana', -8),
			'America/Chihuahua'         => array('MX', '(GMT-07:00) Mountain Time - Chihauhua, Mazatlan', -7),
			'America/Hermosillo'        => array('MX', '(GMT-07:00) Mountain Time - Hermosillo', -7),
			'America/Mexico_City'       => array('MX', '(GMT-06:00) Central Time - Mexico City', -6),
			'Europe/Amsterdam'          => array('NL', '(GMT+01:00) Amsterdam', 1),
			'Pacific/Midway'            => array('UM', '(GMT-11:00) Midway', -11),
			'Pacific/Johnston'          => array('UM', '(GMT-10:00) Johnston', -10),
			'Pacific/Wake'              => array('UM', '(GMT+12:00) Wake', 12),
			'Pacific/Enderbury'         => array('UM', '(GMT+13:00) Enderbury', 13),
			'US/Hawaii'                 => array('US', '(GMT-10:00) Hawaii Time', -10),
			'US/Alaska'                 => array('US', '(GMT-09:00) Alaska Time', -9),
			'US/Pacific'                => array('US', '(GMT-08:00) Pacific Time', -8),
			'US/Mountain'               => array('US', '(GMT-07:00) Mountain Time', -7),
			'US/Arizona'                => array('US', '(GMT-07:00) Mountain Time - Arizona', -7),
			'US/Central'                => array('US', '(GMT-06:00) Central Time', -6),
			'US/Eastern'                => array('US', '(GMT-05:00) Eastern Time', -5),
			'Africa/Johannesburg'       => array('ZA', '(GMT+02:00) Johannesburg', 2)
		);
	}
}
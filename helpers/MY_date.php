<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @class date
 * @abstract Extends core date helper class
 * @author Josh Turmel
 */
 
class date extends date_Core {

	/**
	 * @method weekdays
	 * @abstract Returns an array of weekdays according with keys matching PHPs day numbering Sun = 0
	 * @author Josh Turmel
	 * 
	 * @return array days in the week
	 */
	public static function weekdays()
	{
		return array(
			array('Sun', 'Sunday'),
			array('Mon', 'Monday'),
			array('Tue', 'Tuesday'),
			array('Wed', 'Wednesday'),
			array('Thu', 'Thursday'),
			array('Fri', 'Friday'),
			array('Sat', 'Saturday')
		);
	}
	
	/**
	 * @method offset
	 * @abstract Returns an array with 'Sun,Mon,etc' as key and offset from Sunday as number
	 * @author Josh Turmel
	 *
	 * @param string $start_day
	 * 
	 * @return array days with offset from Sunday
	 */
	public static function offset($start_day)
	{
		$days = array();
	
		$weekdays = self::weekdays();
	
		$day_of_week = date('w', strtotime($start_day)); // 4
		
		// If Sunday, do nothing, return normal PHP week day ordering
		if ($day_of_week === 0)
		{
			foreach ($weekdays as $key => $day)
			{
				$days[$day[0]] = $key;
			}
			
			return $days;
		}
		
		$offset = $day_of_week - 7; //  -2
		
		foreach ($weekdays as $key => $day)
		{
			$c_day_of_week = $key;
		
			$diff = ($c_day_of_week - $day_of_week);
			
			switch (true)
			{
				case ($c_day_of_week >= $day_of_week):
					$day_num = $diff + $offset;
					break;
				default:
					$day_num = $key;
			}
			
			$days[$day[0]] = $day_num;
		}
		
		return $days;
	}
	
	/**
	 * @method quarter_range
	 * @abstract Returns the start and end of the quarter as Unix timestamps from the date or year and quarter
	 * @author Josh Turmel
	 *
	 * @param string $date OR
	 * @param int $quarter
	 * @param int $year
	 * 
	 * @return array start and end of yearly quarter
	 */
	public static function quarter_range()
	{
		if (func_num_args() === 1)
		{
			$time = strtotime(func_get_arg(0));
	
			$month = date('n', $time);
			$year  = date('Y', $time);
		
		} elseif (func_num_args() === 2) {
			
			$month = (int) func_get_arg(0) * 3;
			$year  = (int) func_get_arg(1);
		
		} else {
		
			$time = time();
			
			$month = date('n', $time);
			$year  = date('Y', $time);
		}
		
		switch (true)
		{			
			case $month < 4:
				return array(mktime(0, 0, 0, 1, 0, $year), mktime(0, 0, 0, 3, 31, $year));
			case $month < 7:
				return array(mktime(0, 0, 0, 4, 0, $year), mktime(0, 0, 0, 6, 30, $year));
			case $month < 10:
				return array(mktime(0, 0, 0, 7, 0, $year), mktime(0, 0, 0, 9, 30, $year));
			default:
				return array(mktime(0, 0, 0, 10, 0, $year), mktime(0, 0, 0, 12, 31, $year));
		}
	}
	
	public static function quarter($time = false)
	{
		if ($time === false)
		{
			$time = time();
		}
		
		$month = date('n', $time);
	
		switch (true)
		{			
			case $month < 4:
				return 1;
			case $month < 7:
				return 2;
			case $month < 10:
				return 3;
			default:
				return 4;
		}
	}
}
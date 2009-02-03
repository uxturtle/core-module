<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @class social
 * @author Josh Turmel
 */
 
class social_Core {

	/**
	 * @method share
	 * @abstract Builds URL to link to Facebook share page
	 * @author Josh Turmel
	 * @
	 * @return array
	 */
	public static function facebook()
	{
		return 'http://www.facebook.com/share.php?u='.rawurlencode(Kohana::config('social.link'));
	}
	
	public static function myspace()
	{
		return 'http://www.myspace.com/Modules/PostTo/Pages/?c='.rawurlencode(Kohana::config('social.link'));
	}

	public static function stumbleupon()
	{
		return 'http://www.stumbleupon.com/submit?url='.rawurlencode(Kohana::config('social.link')).'&amp;title='.rawurlencode(Kohana::config('social.title'));
	}
	
	public static function delicious()
	{
		return 'http://del.icio.us/new/CHANGEME?v=4&amp;jump=close&amp;url='.rawurlencode(Kohana::config('social.link')).'&amp;title='.rawurlencode(Kohana::config('social.title'));
	}
	
	public static function digg()
	{
		$settings = Settings::instance(1);
		return $settings->get('digg');
	}
}
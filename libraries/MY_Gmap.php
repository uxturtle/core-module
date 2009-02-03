<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Google Maps API integration.
 *
 * $Id: MY_Gmap.php 20 2008-05-19 20:37:49Z samsoffes $
 *
 * @package    Gmaps
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Gmap extends Gmap_Core {

	/**
	 * Retrieves the XML geocode address lookup.
	 * ! Results of this method are cached for 1 day.
	 *
	 * @param   string  adress
	 * @return  object  SimpleXML
	 */
	public static function address_to_xml($address)
	{
		static $cache;

		// Load Cache
		($cache === NULL) and $cache = Cache::instance();

		// Address cache key
		$key = 'gmap-address-'.sha1($address);

		if ($xml = $cache->get($key))
		{
			// Return the cached XML
			return simplexml_load_string($xml);
		}
		else
		{
			// Get the API key
			$api_key = Config::item('gmaps.api_key');

			// Send the address URL encoded
			$addresss = rawurlencode($address);

			// Construct the url
			$url = 'http://maps.google.com/maps/geo?'.
				'&output=xml'.
				'&key='.$api_key.
				'&q='.rawurlencode($address);
							
			// Start Curl
			$ch = curl_init();
			
			// Setup options
			$options[CURLOPT_URL] = $url;
			$options[CURLOPT_RETURNTRANSFER] = true;
			curl_setopt_array($ch, $options);
			
			// Curl and create a SimpleXML object
			$output = curl_exec($ch);
			curl_close($ch);
			$xml = new SimpleXMLElement($output);

			if (is_object($xml) AND ($xml instanceof SimpleXMLElement) AND (int) $xml->Response->Status->code === 200)
			{
				// Cache the XML
				$cache->set($key, $xml->asXML(), array('gmaps'), 86400);
			}
			else
			{
				// Invalid XML response
				$xml = FALSE;
			}
		}

		return $xml;
	}

} // End Gmap
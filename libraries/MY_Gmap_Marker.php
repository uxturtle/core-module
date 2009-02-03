<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Google Maps Marker
 *
 * $Id: MY_Gmap_Marker.php 20 2008-05-19 20:37:49Z samsoffes $
 *
 * @author     Sam Soffes
 * @author     Josh Turmel
 * @copyright  (c) 2008 LifeChurch.tv
 */
class Gmap_Marker extends Gmap_Marker_Core {

	public function render($tabs = 0)
	{
		// Create the tabs
		$tabs = empty($tabs) ? '' : str_repeat("\t", $tabs);

		$output = array();
		$var = 'm'.mt_rand();
		$output[] = 'var '.$var.' = new GMarker(new GLatLng('.$this->latitude.', '.$this->longitude.'));';
		if ($html = $this->html)
		{
			$output[] = 'GEvent.addListener('.$var.', "click", function()';
			$output[] = '{';
			$output[] = "\t".$var.'.openInfoWindowHtml(';
			$output[] = "\t\t'".implode("'+\n\t\t$tabs'", explode("\n", $html))."'";
			$output[] = "\t);";
			$output[] = '});';
		}
		$output[] = 'map.addOverlay('.$var.');';

		return implode("\n".$tabs, $output);
	}

} // End Gmap Marker
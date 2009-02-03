<?php defined('SYSPATH') or die('No direct script access.');
/**
 * country_state
 *
 * @package core
 * @author Josh Turmel
 *
 * This class converts data to and from different formats
 *
 **/
class convert_Core {
	
	public static function xmlToJson($xml, $levels = null) {

		if ($xml == null)
			return false;
			
		$xml = new SimpleXMLElement($xml);
		$json = false; 

		// Let us convert the XML structure into PHP array structure.
		$array = convert::simpleXmlToArray($xml);

		if ($levels !== null && is_array($levels))
		{
			foreach ($levels as $level)
			{
				$array = $array[$level];
			}
		}

		if (($array != null) && (sizeof($array) > 0))
		{
			$json = json_encode($array);
		}
		return $json;
	}
	
	public static function simpleXmlToArray($xml, &$recursion_depth = 0)
	{
		if ($recursion_depth > 25)
		{
			// Fatal error. Exit now.
			return null;
		}

		if ($recursion_depth == 0)
		{
			if (get_class($xml) != 'SimpleXMLElement')
			{
			    // If the external caller doesn't call this function initially 
			    // with a SimpleXMLElement object, return now. 
			    return null; 
			}
			else
			{
			    // Store the original SimpleXmlElementObject sent by the caller.
			    // We will need it at the very end when we return from here for good.
			    $provided_xml = $xml;
			}
		}
		
		
		if (get_class($xml) == 'SimpleXMLElement')
		{
			// Get a copy of the simpleXmlElementObject
			$copy_xml = $xml;
			// Get the object variables in the SimpleXmlElement object for us to iterate.
			$xml = get_object_vars($xml);
		}
		
		
		// It needs to be an array of object variables.
		if (is_array($xml))
		{
			// Initialize the result array.
			$result_array = array();
			
			// Is the input array size 0? Then, we reached the rare CDATA text if any.
			if (count($xml) <= 0)
			{
				// Let us return the lonely CDATA. It could even be
				// an empty element or just filled with whitespaces.
				return trim(strval($copy_xml));
			}
			
			
			// Let us walk through the child elements now.
			foreach ($xml as $key => $value)
			{
				// When this block of code is commented, XML attributes will be
				// added to the result array.
				// Uncomment the following block of code if XML attributes are 
				// NOT required to be returned as part of the result array. 
				/*
				if((is_string($key)) && ($key == SIMPLE_XML_ELEMENT_OBJECT_PROPERTY_FOR_ATTRIBUTES)) {
				  continue;
				}
				*/
				
				// Let us recursively process the current element we just visited.
				// Increase the recursion depth by one.
				$recursion_depth++; 
				$result_array[$key] = convert::simpleXmlToArray($value, $recursion_depth);
				
				
				// Decrease the recursion depth by one.
				$recursion_depth--;
			}
			
			if ($recursion_depth == 0)
			{
			  // That is it. We are heading to the exit now.
			  // Set the XML root element name as the root [top-level] key of
			  // the associative array that we are going to return to the caller of this
			  // recursive function.
			  $temp_array = $result_array;
			  $result_array = array();
			  $result_array[$provided_xml->getName()] = $temp_array;
			}
			
			return ($result_array);
		}
		else
		{
		  // We are now looking at either the XML attribute text or
		  // the text between the XML tags.
		  return trim(strval($xml));
		} // End of else
	}
}
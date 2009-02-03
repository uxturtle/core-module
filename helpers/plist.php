<?php defined('SYSPATH') OR die('No direct access allowed.');

class plist_Core {

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

	public static function parse_dom($dom)
	{
		$plist_node = $dom->documentElement;
		$root = $plist_node->firstChild;

		// Skip any text nodes before the first value node
		while ($root->nodeName == "#text")
		{
			$root = $root->nextSibling;
		}
		return plist::parse_value($root);
	}

	public static function to_json(string $xml_string)
	{
		return json_encode(plist::to_array($xml_string));
	}

	public static function to_json_from_path(string $filepath)
	{
		return json_encode(plist::to_array_from_path($filepath));
	}

	public static function parse_value($valueNode)
	{
		$valueType = $valueNode->nodeName;
		$value = NULL;

		// Determine the correct way to parse
		switch ($valueType)
		{
			case 'integer':
			case 'string':
			case 'date':
			case 'data': // TODO: make data work correctly
				$value = $valueNode->textContent;
				break;

			case 'true':
				$value = TRUE;
				break;

			case 'false':
				$value = FALSE;
				break;

			case 'dict':
				$value = array();
				// For each child of this node 
				for ($node = $valueNode->firstChild; $node != null; $node = $node->nextSibling)
				{
					if ($node->nodeName == 'key')
					{ 
						$key = $node->textContent;
						$dictValueNode = $node->nextSibling;

						// Skip text nodes
						while ($dictValueNode->nodeType == XML_TEXT_NODE)
						{
							$dictValueNode = $dictValueNode->nextSibling;
						}
			
						// Recursively parse the children
						$value[$key] = plist::parse_value($dictValueNode);
					}
				}
				break;

			case 'array':
				$value = array();
				for ($node = $valueNode->firstChild; $node != null; $node = $node->nextSibling)
				{
					if ($node->nodeType == XML_ELEMENT_NODE)
					{
						// Recursively parse the children
						array_push($value, plist::parse_value($node));
					}
				}
				break;
		}
		return $value;
	}
}
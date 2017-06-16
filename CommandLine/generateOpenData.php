<?php

// Move the following defines to the library\config file
define("OPENDATA_OUTPUT_DIR", 'c:\\hillsborough_extracts\\opendata\\'); // Location for generated XML and CSV files

define("OPENDATA_XML_OUTPUT_FILE", 'Hillsborough-Disclosure.xml'); // Filename for generated XML file
define("OPENDATA_CSV_OUTPUT_FILE", 'Hillsborough-Disclosure.csv'); // Filename for generated CSV file

define("OPENDATA_TEMPLATE_FILE", 'OpenDataTemplate.xml'); // Filename for XML template file (deployed to the same dir as this script)

define("LANDING_PAGE_PATH", "http://hillsborough.independent.gov.uk/repository/");
define("DOCUMENT_PATH", "http://hillsborough.independent.gov.uk/repository/docs/");

define("REDACTED_LOOKUP_TABLE", "redactedgroups"); // redacted reason lookups

// Column names that have to be kept in step with the OpenDataTemplate.xml
define("FIELD_NOT_DISCLOSED_FULL", "reason_not_disclosed_on_site");	// disclosed_material.out_of_scope_reason
define("FIELD_DOC_ID", "unique_id");	// disclosed_material.begin_doc_id
define("FIELD_FORMAT", "format");	// disclosed_material.format

// Description: generateOpenData reads information from the autopopulatelookup and disclosed_material tables,
// based on the fields referenced in the OpenDataTemplate.xml file, and outputs that data in CSV and XML formats
// Input: The OpenDataTemplate.xml file, which should be in the same directory as this script

require_once("library/config.php");
require_once("library/database.php");

//  |
//  +- processTemplate()
//      |
//      +- processTemplateElement()
//          |
//          + ProcessRecord()
//          |  |
//          |  +- extractFieldNames()
//          |  |
//          |  (database query)
//          |  |
//          |  +- processRecordTemplateElement()
//          |      |
//          |      +- processRecordTemplateElement() (recursive call to handle child elements)
//          |
//          +- processTemplateElement() (recursive call to handle child elements)
//
	
// xmlentities utility function 'cos the ENT_XML1 flag for htmlentities wasn't introduced until PHP 5.4
// (this code should be moved to a common location)
if(!function_exists('xmlentities')) {
    function xmlentities($string) {
//        $not_in_list = "A-Z0-9a-z\s_,-";	// \s appears to let through an invalid character hence...
        $not_in_list = "A-Z0-9a-z \t\r_,-";
        return preg_replace_callback("/[^{$not_in_list}]/" , 'get_xml_entity_at_index_0' , $string);
    }
    function get_xml_entity_at_index_0($CHAR) {
        if(!is_string($CHAR[0]) || (strlen( $CHAR[0] ) > 1)) {
            die("function: 'get_xml_entity_at_index_0' requires data type: 'char' (single character). '{$CHAR[0]}' does not match this type.");
        }
        switch($CHAR[0]) {
            case "'":    case '"':    case '&':    case '<':    case '>':
                return htmlspecialchars($CHAR[0], ENT_QUOTES);
				break;
            default:
                return numeric_entity_4_char($CHAR[0]);
				break;
        }
    }
    function numeric_entity_4_char($char) {
        return "&#".str_pad(ord($char), 3, '0', STR_PAD_LEFT).";";
    }
}

function lookupVals($input, $lookup, &$lookupFailures) {
	$ids = explode(";", $input);
//echo var_dump($ids);
	unset($formattedString);
	foreach ($ids as $id) {
		if (strlen($id) > 0) {
			$pos = strpos($id, "(");
			if ($pos === FALSE) {
				$idx = $id;
			} else {
				$idx = substr($id, 0, $pos);
			}
			if (!isset($lookup[$idx])) {
				if (!isset($lookupFailures)) {
					$lookupFailures = array();
				}
				if (!in_array($idx, $lookupFailures)) {
//					echo "ID not in lookup: ", $idx, "\r\n";
					$lookupFailures[] = $idx;
				}
			} else {
				if(isset($formattedString)) {
					$formattedString = $formattedString."; ".$lookup[$idx];
				} else {
					$formattedString = $lookup[$idx];
				}
			}
		}
	}
	
	if (!isset($formattedString)) {
		$formattedString = "";
	}
	
	return $formattedString;
}

function lookupRedactedVals($input, $lookup) {
	$formattedString = "";

	if ($input > 0) {
		if (!isset($lookup[$input])) {
			echo "Error: Unable to match redacted reason '$input'\r\n";
		} else {
			$formattedString = $lookup[$input];
		}
	}
	
	return $formattedString;
}


// Taken from: http://www.toao.net/48-replacing-smart-quotes-and-em-dashes-in-mysql
function replaceSmartQuotes($text) {
// First, replace UTF-8 characters.
	$text = str_replace(
		array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
		array("'", "'", '"', '"', '-', '--', '...'),
		$text);
		
// Next, replace their Windows-1252 equivalents.
	$text = str_replace(
		array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
		array("'", "'", '"', '"', '-', '--', '...'),
		$text);
		
	return ($text);
}

function getFieldValue($row, $name, $action, $format, $lookup, &$lookupFailures, $redactedLookup) {

	if (isset($row[$name])) {
		if (isset($format)) {
			if (strcasecmp($format, "date") == 0) {
				$unixDate = trim($row[$name]);
				if ($unixDate > 0) {
					$value = date("Y-m-d", $unixDate);
				} else {
					$value = "";
				}
			} elseif (strcasecmp($format, "lookup") == 0) {
				$value = lookupVals($row[$name], $lookup, $lookupFailures);
			} elseif (strcasecmp($format, "redacted_lookup") == 0) {
				$value = lookupRedactedVals($row[$name], $redactedLookup);
			} elseif (strcasecmp($format, "page_url") == 0) {
				$value = LANDING_PAGE_PATH.$row[$name].".html";
			} elseif (strcasecmp($format, "file_url") == 0) {
				$value = "";
				// Check to see if the doc is in scope
				if (isset($row[FIELD_NOT_DISCLOSED_FULL])) {
					$outOfScope = trim($row[FIELD_NOT_DISCLOSED_FULL]);
					if ((strlen($outOfScope) < 1) || (strcasecmp($outOfScope, "[NEWLINE]") == 0)) {
						// Check the doc format
						$docExtension = ".pdf";
						if (isset($row[$name])) {
							if ($row[$name] != false) {	// Assume column contains values from is_av
								$docExtension = ".mp4";
								// Current belief is that all AV files have an mp4 extension, otherwise we could use the following...
/*								if (isset($row[FIELD_FORMAT])) {
									$docFormat = $row[FIELD_FORMAT];
									if (strcasecmp($docFormat, "Photograph") == 0) {
										$docExtension = ".jpg";
									} elseif (strcasecmp($docFormat, "Video") == 0) {
										$docExtension = ".mp4";
									} elseif (strcasecmp($docFormat, "Audio") == 0) {
										$docExtension = ".m4a";
									}
								}
*/
							}
						}
						$value = DOCUMENT_PATH.$row[FIELD_DOC_ID].$docExtension;
					}
				}
			}
		} else {
			$value = replaceSmartQuotes(trim($row[$name]));
		}
		return $value;		
	}
	else
	{
		return null;
	}
}

function processRecordTemplateElement($templateElement, &$outputXML, &$outputCSVRow, $row, $lookup, &$lookupFailures, $redactedLookup, $inheritedAction = "") {
	//This function is basically the same as processTemplateElement, but with added database record processing

	unset($action);
	$format = null;
	// Look for any template_action or template_format attributes
	foreach($templateElement->attributes() as $attrName => $attrValue) {
		if (strcmp($attrName, "template_action") == 0) {
			$action = $attrValue;
		} elseif (strcmp($attrName, "template_format") == 0) {
			$format = $attrValue;
		}
	}
		
	if (!isset($action))
		$action = $inheritedAction;

	$generateXML = true;
	$generateCSV = true;

	if (strcasecmp($action, "csv_only") == 0) {
		$generateXML = false;
	}
	if (strcasecmp($action, "xml_only") == 0) {
		$generateCSV = false;
	}
	
	$name = $templateElement->getName();
	$value = getFieldValue($row, $name, $action, $format, $lookup, $lookupFailures, $redactedLookup);
	if (!isset($value)) {
		$value = trim((string)$templateElement);
	}

	if ($generateXML) {
		// Include field in XML output
		$newElement = $outputXML->addChild($name, xmlentities($value));
		
		// Copy across the attributes (other than those that start 'template_')
		foreach($templateElement->attributes() as $attrName => $attrValue) {
		switch ($attrName) {
			case "template_comment" :
				// Ignore comment
				break;
			case "template_action" :
				// Ignore action (handled above)
				break;
			case "template_format" :
				// Ignore format (handled above)
				break;
			default:
				// Otherwise preserve the attribute in the output
				$newElement->addAttribute($attrName, $attrValue);
				break;
			}
		}				
	} else {
		$newElement = $outputXML;
		// Required for when we feed $newElement to the children, below
	}

	if ($generateCSV) {
		// Include field in CSV output
		if (strlen($value) > 0) {
			$value = "\"".str_replace("\"", "\"\"", $value)."\"";
		}
		
		if ((strlen($value) > 0) || (isset($row[$name]))) {
		// Only fields variable fields (i.e. from the DB) are included in the CSV output if they are empty
			if (isset($outputCSVRow)) {
				$outputCSVRow = $outputCSVRow.",".$value;
			} else {
				$outputCSVRow = $value;
			}
		}
	}
	
	// Process the children
	$children = $templateElement->children();	
	foreach($children as $child) {
		processRecordTemplateElement($child, $newElement, $outputCSVRow, $row, $lookup, $lookupFailures, $redactedLookup, $action);	
	}
}

function extractFieldNames($templateElement, &$fieldNames) {
	if (!isset($fieldNames)) {
		$fieldNames = array();
	}

	// Look for SQL field names in the format [table].[field]
	// (We're only going to support a single field (and nothing else) as the value)
	$val = trim((string)$templateElement);
	if ((strcmp(substr($val, 0, 1), "`") == 0) && (strcmp(substr($val, -1, 1), "`") == 0)) {
		$name = $templateElement->getName();
		$fieldNames[$name] = $val. " AS ".$name;
	}

	// Process children
	$children = $templateElement->children();	
	foreach($children as $child) {
		$fieldNames = extractFieldNames($child, $fieldNames);
	}

	return ($fieldNames);
}

function getColumnHeadings($templateElement) {
	$columnHeadings = array();

	// Process children
	foreach($templateElement->children() as $child) {
		$columnHeadings[] = $child->getName();
	}

	return ($columnHeadings);
}

function processRecord($templateElement, &$outputXML) {
	// Scan child elements for SQL table.field names
	extractFieldNames($templateElement, $fieldNames);
	
	$db = new Database(DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS);
	
	// Load look-up values into an array
	$sql = "SELECT `id`, `presentation_format` FROM `".AUTOPOPULATE_LOOKUP_TABLE."`";
	$fetchResults = $db->dbFetch($sql);
	$lookup = array();
	foreach($fetchResults as $row) {
		$lookup[$row["id"]] = $row["presentation_format"];
	}

	// Load 'redacted reason' look-up values into an array
	$sql = "SELECT `redacted_id`, `redacted_group_name` FROM `".REDACTED_LOOKUP_TABLE."`";
	$fetchResults = $db->dbFetch($sql);
	$redactedLookup = array();
	foreach($fetchResults as $row) {
		$redactedLookup[$row["redacted_id"]] = $row["redacted_group_name"];
	}

	// Query the database
	$sql = "SELECT ".implode($fieldNames, ",")." FROM `".DISCLOSED_MATERIAL_TABLE."` ORDER BY `".DISCLOSED_MATERIAL_TABLE."`.`begin_doc_id`";
//echo $sql;
	$fetchResults = $db->dbFetch($sql);

	$fileHandle = fopen(OPENDATA_OUTPUT_DIR.OPENDATA_CSV_OUTPUT_FILE, "wb");

	// Write a heading row to the CSV file
	unset($headings);
	foreach (getColumnHeadings($templateElement) as $name => $val) {
		// Beautify the field names
		$formattedName = str_replace(" Url", " URL", ucwords(str_replace("_", " ", $val)));
		$formattedName = str_replace(" Of ", " of ", $formattedName);
		//Nasty bodge, sorry...
		if (strcmp($formattedName, "Unique Id") == 0) {
			$formattedName = "Unique ID";
		} elseif (strcmp($formattedName, "Sub Folder Title") == 0) {
			$formattedName = "Sub-folder Title";
		}
		
		if (isset($headings)) {
			$headings = $headings."\",\"".$formattedName;
		} else {
			$headings = "\"".$formattedName;
		}
	}
	$headings .= "\"\r\n";
	fwrite($fileHandle, $headings);
	
	// Process the child elements once per returned record
	foreach($fetchResults as $row) {
//var_dump($row);

		unset($outputCSVRow);		
		processRecordTemplateElement($templateElement, $outputXML, $outputCSVRow, $row, $lookup, $lookupFailures, $redactedLookup);
		
		// Write $outputCSVRow
		fwrite($fileHandle, $outputCSVRow."\r\n");
	}
	
	if (isset($lookupFailures)) {
		asort($lookupFailures);
		echo "Error! The following IDs are not in the autopopulate_lookup table: ", implode(", ", $lookupFailures), "\r\n";
	}
	
	fclose($fileHandle);
}

function processTemplateElement($templateElement, &$outputXML, $root) {
	unset($action);
	// Look for any template_action attributes
	foreach($templateElement->attributes() as $attrName => $attrValue) {
		if (strcmp($attrName, "template_action") == 0) {
			$action = $attrValue;
			break;
		}
	}

	if ((isset($action)) && (strcasecmp($action, "repeating_group") == 0)) {
		processRecord($templateElement, $outputXML);
		return;
	}

	// Add the element to the output
	if (!$root) {
		$name = $templateElement->getName();
		if ((isset($action)) && (strcasecmp($action, "today") == 0)) {
			$value = date("Y-m-d");
		}
		else {
			$value = trim((string)$templateElement);
		}
		$newElement = $outputXML->addChild($name, $value);
	} else {
		$newElement = $outputXML;
	}
	
	// Copy across the attributes (other than those that start 'template_')
	foreach($templateElement->attributes() as $attrName => $attrValue) {
		switch ($attrName) {
			case "template_comment" :
				// Ignore comment
				break;
			case "template_action" :
				if (strcasecmp($action, "today") != 0) {
					// Warn, as we should have already handled the only action valid here
					echo "Warning! Action not recognised or not valid at this point in the template (", $attrValue, ")\r\n";
				}
				break;
			default:
				// Otherwise preserve the attribute in the output
				$element->addAttribute($attrName, $attrValue);
				break;
		}				
	}
	
	// Process the children
	$children = $templateElement->children();	
	foreach($children as $child) {
		processTemplateElement($child, $newElement, false);
	}
}

function processTemplate($templateFilename) {
	$templateXML = simplexml_load_file($templateFilename);

	// God I hate this stupid, half-arsed programming language.
	// This ugliness wouldn't be necessary if the SimpleXMLElement class was well designed or I could at least declare a statically typed variable.
	$name = $templateXML->getName();
	$value = trim((string)$templateXML);
	$outputXML = new SimpleXMLElement("<".$name.">".$value."</".$name.">");

	processTemplateElement($templateXML, $outputXML, true);
	
	file_put_contents(OPENDATA_OUTPUT_DIR.OPENDATA_XML_OUTPUT_FILE, $outputXML->asXML());
}

echo "Running generateOpenData...\r\n";

$pathinfo = pathinfo($_SERVER["SCRIPT_NAME"]);
$templateFile = $pathinfo["dirname"]."\\".OPENDATA_TEMPLATE_FILE;

processTemplate($templateFile);

echo "... generateOpenData complete\r\n";

?>
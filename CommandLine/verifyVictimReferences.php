<?php
/****
* File:      verifyVictimReferences.php
* Author:    zqdmjm
* Date:      22 May 2012
****/

setlocale(LC_ALL, 'en_UK.UTF8'); 
date_default_timezone_set('Europe/London');

require_once("library/database.php");
require_once("library/config.php");
require_once("library/functions.php");



function GetDocIDList()
{
	global $db;
	$sql = "SELECT begin_doc_id, short_title, description FROM Disclosed_Material WHERE out_of_scope_reason = ''";// and begin_doc_id = 'SYP000109180001'";
	$lookup_list = $db->dbFetch($sql);
	return $lookup_list;	
}

function ReadOCRFile($document_id)
{
   	$ocr = null;
   	$file = OCR_DATA_DIR . $document_id . ".txt";
   	if (file_exists($file))
		$ocr = file_get_contents($file);
			
	$oldocr = " " . $ocr . " ";
	$ocr = preg_replace('!\s+!', ' ', $oldocr);			
		
	//return strtolower(convert_ascii($ocr));
	return convert_ascii($ocr);
}
    	
function TextualiseBoolean($bool)
{
	if ($bool)
		return "Yes";
	else 
		return "No";
}

function GetLookupData($type)
{
	global $db;
	$sql = "SELECT * FROM " . AUTOPOPULATE_LOOKUP_TABLE . " WHERE type = " . $type . " ";
	$lookup_list = $db->dbFetch($sql);
	
	$list = array();
	$count = 0;
	
	foreach($lookup_list as $lookup)
	{
		$variants = explode(";", $lookup['lookup_variants']);	
		$list[$count] = array();
		
		foreach($variants as $origvarrecord)
		{
			$varrecord = trim($origvarrecord);
			if ((sizeof($list[$count])<1)||((sizeof($list[$count])>0)&&(!in_array($varrecord, $list[$count]))))
					$list[$count][] = $varrecord;	
		}
		$count++;
	}	
	return $list;
}

function PerformMatch($textToSearch, $textToMatch)
{
	$result = array();
	if (strpos($textToSearch, $textToMatch)!==FALSE)
	{
		$matches = array();
		preg_match_all("/[^0-9a-zA-Z]".$textToMatch."[^0-9a-zA-Z]/", $textToSearch, $matches);

		if (sizeof($matches[0])>0)
		{
			$result[$textToMatch] = sizeof($matches[0]);
			//var_dump($matches[0]);
		}
	}
	return $result;		
}

function BuildMetadata($docid, $f, $text, $lookup, $fieldName, $title, $description)
{
	$id= -1;
	$oid = 0;

	
	foreach($lookup as $victim)
	{
		$matchInTitle = false;
		$matchInDescription = false;
		
		$matchOnBody = false;
		$singleInitialSurname = false;
		$forenameSurname = false;
		$forenameMiddleSurname = false;
	
		$result = array();
		$value = $victim[0];
		//echo "Looking for references to: " . $value . "\r\n";
		foreach($victim as $variant)
		{
			
			
			if (($variant!="") && ($variant!=null))
			{
				$t = PerformMatch($text, $variant);
				if (sizeof($t)>0)
				
					$result[] = $t;
					
				$t = PerformMatch($title, $variant);
				if (sizeof($t)>0)
				{
					$result[] = $t;
					$matchInTitle = true;
				}
				
				$t = PerformMatch($description, $variant);
				if (sizeof($t)>0)
				{
					$result[] = $t;
					$matchInDescription = true;
				}
			}
		}
		
		
		//A match was found so... 
		if (sizeof($result)>0)
		{
			$records = array();
			$records[] = $docid;
			$records[] = $victim[0];
			
			$matchedTerm = "";
			$total = 0;
			
			//var_dump($result);
			//exit;
			
			$bodyref = false;
			
			foreach($result as $t)
			{
				foreach($t as $term=>$val)
				{
					//var_dump("[",$term,$val,"]");
					$matchedTerm .= $term . " (" . $val . "), ";
					$total += $val; 
					$termParts = explode(" ", $term);
					
					$containsInitial = false;
					$wordCount=0;
					foreach($termParts as $token)
					{
						if (strlen($token)<=2)
						{
							if (!is_numeric($token))
								$containsInitial = true;
						}
						else 
							$wordCount++;
					}
	
					if ($containsInitial && ($wordCount==1))
						$singleInitialSurname = true;
						
					if ($wordCount==2)
						$forenameSurname = true;
	
					if ($containsInitial && ($wordCount==2))
						$forenameMiddleSurname = true;
					
					if ($wordCount>2)
						$forenameMiddleSurname = true;
						
					if (strpos($term, "body")!==false)
						$bodyref=true;
				}
			}
			
			// parts of name
			
			//initial surname
			
			$records[] = $matchedTerm;
			$records[] = $total;
			$records[] = TextualiseBoolean($singleInitialSurname);
			$records[] = TextualiseBoolean($forenameSurname);
			$records[] = TextualiseBoolean($forenameMiddleSurname);
			$records[] = TextualiseBoolean($bodyref);
			$records[] = TextualiseBoolean($matchInTitle);
			$records[] = TextualiseBoolean($matchInDescription);
			
			fputcsv($f, $records, ",");
			
		}
		
		
	}
	
	
//	foreach($lookup as $value => $vid)
//	{
//		if (($value!="") && ($value!=null))
//		{
//			if (strpos($text, $value)!==FALSE)
//			{
//				$match = array();
//				preg_match_all("/[^0-9a-zA-Z]".$value."[^0-9a-zA-Z]/", $text, $matches);
//				var_dump($matches);
//				if (sizeof($matches[0])>1)
//				{
//					echo "More than one found:::";
//					exit;
//				}
//				
//
//				if (preg_match("/[^0-9a-zA-Z]".$value."[^0-9a-zA-Z]/",$text))
//				{
//					
//					$start = strpos($text, $value);
//					if (($start-50)<0)
//						$start= 0;
//					else 
//						$start-=50;
//						
//					$end = strlen($value) + $start + 50;
//					
//					$snippet = substr($text, $start, ($end-$start)+50);
//						
//					//If not already there, add it.
//					if ($oid!=$id)
//					{
//						$oid = $id;
//						//echo $docid . ",\"" . $value . "\"," . $fieldName . ",\"" . $snippet . "\"\r\n";
//						
//						$records = array();
//						$records[] = $docid;
//						$records[] = $value;
//						$records[] = $fieldName;
//						$records[] = $snippet;
//						
//						fputcsv($f, $records, ",");
//					}
//					$id = $vid;
//				}				
//			}
//		}
//	}
}


/**************************************************/
/************* START OF MAIN PROCESS **************/
/**************************************************/


$start_time = microtime(true);
$db = new Database( DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS );

$row = 0;
$totalRecords = 0;

$victims = GetLookupData(1);
//$corporatebodies = GetLookupData(2);
//$persons = GetLookupData(3);

$docIDList = GetDocIDList();

//Create CSV file
$fp = fopen('c:\\victim_reference.csv', 'w');

$title = array();
$title[] = "Barcode ID";
$title[] = "Presentation format";	
$title[] = "Matched terms";
$title[] = "Match total";	
$title[] = "Single initial/surname";	
$title[] = "Forename surname";	
$title[] = "Forename initial/middlename surname";	
$title[] = "Body reference";	
$title[] = "Match in title";	
$title[] = "Match in descripton";

fputcsv($fp, $title, ",");

foreach($docIDList as $doc) 
{			
	echo "Processing: " . $doc['begin_doc_id'] . "\r\n";
	$ocr = ReadOCRFile($doc['begin_doc_id']);
	
	if($ocr!=null)
	{
		BuildMetadata($doc['begin_doc_id'], $fp, $ocr, $victims, "ap_victim_name", $doc['short_title'], $doc['description']);
		//BuildMetadata($doc['begin_doc_id'], $fp, $ocr, $corporatebodies, "ap_corporate_body");
		//BuildMetadata($doc['begin_doc_id'], $fp, $ocr, $persons, "ap_person");
	}
	
	$row++;
}

fclose($fp);

$end_time = microtime(true);
	
$runningTime = $end_time - $start_time;
echo "\r\n\r\nProcess ran for " . sprintf( "%02.2d:%02.2d", floor( $runningTime / 60 ), $runningTime % 60 ) . "\r\n";
	
echo "\r\n\r\n";

?>
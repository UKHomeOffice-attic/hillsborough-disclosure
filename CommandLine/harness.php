<?php
setlocale(LC_ALL, 'en_UK.UTF8'); 
date_default_timezone_set('Europe/London');

require_once("DisclosedRecord.php");
require_once("library/database.php");
require_once("library/hillsboroughlog.php");
require_once("library/schema.php");
require_once("library/config.php");
require_once("library/functions.php");

define("OCRPATH", "c:\\hillsborough_extracts\\ocr\\");
//define("PDFPATH", "C:\\IL2Delivery\\AppContent\\repository\\docs\\");
define("PDFPATH", "c:\\hillsborough_extracts\\pdf\\");
define("SOLRPATH", "c:\\hillsborough_extracts\\searchdata\\");


//$file = "c:\\hillsborough_extracts\\csv\\masterlist.csv";
$file = "/hillsborough_extracts/csv/masterlist.csv";

if (!file_exists($file))
	Die("Failed to find the Lextranet CSV @ " . $file);
		
function GetVictimLookupData($type)
{
	global $db;
	$sql = "SELECT * FROM " . AUTOPOPULATE_LOOKUP_TABLE . " WHERE type = " . $type . " ";
	$lookup_list = $db->dbFetch($sql);
	
	$list = array();
	$count = 0;
	
	foreach($lookup_list as $lookup)
	{
		$variants = explode(";", $lookup['lookup_variants']);	
		$id = $lookup['id'];
		$list[$id] = array();
		
		foreach($variants as $origvarrecord)
		{
			$varrecord = trim($origvarrecord);
			if ((sizeof($list[$id])<1)||((sizeof($list[$id])>0)&&(!in_array($varrecord, $list[$id]))))
					$list[$id][] = $varrecord;	
		}
		$count++;
	}	
	return $list;
}
	

function GetLookupData($type)
{
	global $db;
	$sql = "SELECT * FROM " . AUTOPOPULATE_LOOKUP_TABLE . " WHERE type = " . $type;
	$lookup_list = $db->dbFetch($sql);
	
	$list = array();
	
	foreach($lookup_list as $lookup)
	{
		$variants = explode(";", $lookup['lookup_variants']);
		$variants[] = $lookup['presentation_format'];
		$variants[] = $lookup['full_title'];
	
		foreach($variants as $origvarrecord)
		{
			$varrecord = trim(strtolower($origvarrecord));
			$list[$varrecord] = $lookup['id'];	
		}
	}
	
	return $list;
}

function GetPersonLookupData($type)
{
    global $db;
    $sql = "SELECT * FROM " . AUTOPOPULATE_LOOKUP_TABLE . " WHERE type = " . $type;
    $lookup_list = $db->dbFetch($sql);
        
    $list = array();
        
    foreach($lookup_list as $lookup)
    {
        $list[$lookup['id']]["high"] = $lookup['high_variants'];
        $list[$lookup['id']]["medium"] = $lookup['medium_variants'];
        $list[$lookup['id']]["low"] = $lookup['low_variants'];
    }
        
    return $list;
}
    
function GetPersonSurnameLookupData($type)
{
    global $db;
    $sql = "SELECT id, lookup_variants FROM " . AUTOPOPULATE_LOOKUP_TABLE . " WHERE type = " . $type ;
    $lookup_list = $db->dbFetch($sql);
    
    $list = array();
    
    foreach($lookup_list as $lookup)
    {
        $list[$lookup['id']] = $lookup['lookup_variants'];
    }
        
    return $list;
}



/****************************************************
 * Start of main code block here!!!
 ****************************************************/

$orgs = null;

$db = new Database( DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS );

$victims = GetVictimLookupData(1);
$corporatebodies = GetLookupData(2);
$persons = GetPersonLookupData(3);
$personSurnames = GetPersonSurnameLookupData(3);

$record = new DisclosedRecord(OCRPATH, PDFPATH, SOLRPATH, $victims, $corporatebodies, $persons, $personSurnames, $db, $orgs);
//$record->testSetDocID("AGO000000070001");
$record->testSetDocID("SYP000010190001");
$record->ProcessOCR();


?>

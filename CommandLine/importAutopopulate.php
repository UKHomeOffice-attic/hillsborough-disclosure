<?php

require_once("library/database.php");
require_once("library/hillsboroughlog.php");
require_once("library/schema.php");
require_once("library/config.php");
require_once("library/functions.php");

if ((!isset($argv[1]))||(!isset($argv[2])))
{
	echo "No params supplied (expecting filename and importtype) so exiting.\r\n";
	exit();
}

$db = new Database( DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS );
//type 1 = victim, 2, corporate body, 3 person
$file = $argv[1];
$type = $argv[2];

echo "Importing autopopulate data from ".$file." for type " . $type . "\r\n";

$autoimport_log = new HillsboroughLog( "autopopulate-import", LOG_DIR );
$importtype = "autopopulate";//'ID','Full title','Presentation format','Lookup Variants','URL'
$importarray = array('id','full_title','presentation_format','lookup_variants','url', 'description');
$deleteSql = "DELETE FROM " . AUTOPOPULATE_LOOKUP_TABLE . " WHERE type = " . $type;try{	if ($db->dbUpdate($deleteSql))	{		$autoimport_log->write("Deleted original records");	}}catch(Exception $ex){	var_dump($ex, $deleteSql);	exit;}
$start_time = microtime(true);

$row = 0;
$buildrow = 0;
$build = array();

$autoimport_log->write("Importing file: $file");

// start csv import
if (($handle = fopen($file, "r")) !== FALSE) 
{
	while (($data = fgetcsv($handle, 0, ",")) !== FALSE) 
	{	
	
		if ($row++ > 0 && $data[0] != '')
		{
						// cycle csv row
			foreach ($data as $key => $value)
			{
				$build[$buildrow][$importarray[$key]] = trim($value);
			}
			$buildrow++;
		}
	}
}
else
{
	$error_log->write("File: $file was not found. Finishing.");
	die("File not found");
}

foreach ($build as $row)
{//	var_dump($row);	$sql = "SELECT id FROM " . AUTOPOPULATE_LOOKUP_TABLE . " WHERE type = '$type' AND full_title = '" . addslashes($row['full_title']) . "' LIMIT 0,1";
	
	try {
		$check = $db->dbFetch($sql, FALSE);
	}
	catch(Exception $ex)
	{
		var_dump($sql);
		exit();
	}
	
	if (empty($check)) // not found
	{
		$sql = "INSERT INTO " . AUTOPOPULATE_LOOKUP_TABLE . " (id, type, full_title, presentation_format, lookup_variants, url_name, description ) VALUES ( NULL, '$type', '" . addslashes(convert_ascii($row['full_title'])) . "', '" . addslashes(convert_ascii($row['presentation_format'])) . "', '" . addslashes(convert_ascii($row['lookup_variants'])) . "', '" . nameConvert(convert_ascii($row['full_title'])) . "', '" . addslashes(convert_ascii($row['description'])) . "')";
	}
	else
	{
		$sql = "UPDATE " . AUTOPOPULATE_LOOKUP_TABLE . " SET full_title = '" . addslashes(convert_ascii($row['full_title'])) . "', presentation_format = '" . addslashes(convert_ascii($row['presentation_format'])) . "', lookup_variants = '" . addslashes(convert_ascii($row['lookup_variants'])) . "', description = '" . addslashes(convert_ascii($row['description'])) . "' WHERE id = '" . $check['id'] . "'";
	}
		
	if ($db->dbUpdate($sql))
	{
		$autoimport_log->write("Added lookup data for " . $row['full_title'] . " : type $type");
	}	
}

$importcomplete = "Import completed in " . (microtime(true) - $start_time) . " : " . $db->getQueryCount() . " queries executed";
$autoimport_log->write($importcomplete);

echo "Importing autopopulate completed\r\n";
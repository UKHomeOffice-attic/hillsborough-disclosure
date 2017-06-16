<?php

require_once("library/database.php");
require_once("library/hillsboroughlog.php");
require_once("library/schema.php");
require_once("library/config.php");
require_once("library/functions.php");

if (!isset($argv[1]))
{
	echo "No params supplied (expecting filename) so exiting.\r\n";
	exit();
}

$db = new Database( DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS );

$file = $argv[1];

echo "Importing contributor data from ".$file."\r\n";
$autoimport_log = new HillsboroughLog( "autopopulate-import", LOG_DIR );
$importtype = "autopopulate";
$importarray = array('owning_organisation','see_also', 'short_title','description','nondisclosed_reason', 'nondisclosed_summary', 'dir_name','lextranet_title','non_contributing', 'comments');
$uniqueID = array();
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
		{			// cycle csv row			$fieldcount=0;
			foreach ($data as $key => $value)
			{				if ($fieldcount<count($importarray))				{					$build[$buildrow][$importarray[$key]] = convert_smartquotes(trim($value));				}				$fieldcount++;
			}			echo "Read: " . $build[$buildrow]['owning_organisation'] . "\r\n";
			$buildrow++;
		}			}
}
else
{
	$error_log->write("File: $file was not found. Finishing.");
	die("File not found");
}$count = 0;$db->dbUpdate("TRUNCATE TABLE Organisations");echo "Cleared down the organisation table";
foreach ($build as $row)
{	/* Validate record */	if (($row['owning_organisation']=="")		// || ($row['lextranet_title']=="")		|| ($row['non_contributing']=="")		|| ($row['dir_name']=="")		// || ($row['description']=="")		)	{		echo "\r\n>>>>>>>> Error: " . $row['owning_organisation'] . " (row: " . ($count+1) . ") is not a complete record.\r\n\r\n";//		var_dump($row);//		exit();	}	elseif(($row['non_contributing']!="0")&&($row['non_contributing']!="1")&&($row['non_contributing']!="2"))	{		echo "\r\n>>>>>>>> Error: " . $row['owning_organisation'] . " (row: " . ($count+1) . ") does not have a valid non-contributing value.\r\n\r\n";	}	else 	{		echo "Info : " . $row['owning_organisation'] . " (row: " . ($count+1) . ") appears valid.\r\n";				$recordUniqueID = "";		if (array_key_exists($row['owning_organisation'], $uniqueID))		{			$recordUniqueID = $uniqueID[$row['owning_organisation']];		}		else 		{			$newID = (sizeof($uniqueID)+1) . "0001";			while (strlen($newID)<12)			{				$newID = "0" . $newID;			}			$recordUniqueID = "ZZZ" . $newID;			$uniqueID[$row['owning_organisation']] = $recordUniqueID;		}				$count++;				$sql = "INSERT INTO organisations " . 			"(owning_organisation,short_title,description,dir_name,lextranet_title,non_contributing, non_disclosed_summary, non_disclosed,unique_id) " . 			"VALUES " . 			"(" . 						"'" . addslashes($row['owning_organisation']) . "'," . 				"'" . addslashes($row['see_also']) . "'," . 				"'" . addslashes($row['description']) . "'," . 				"'" . addslashes($row['dir_name']) . "'," . 				"'" . addslashes($row['lextranet_title']) . "'," . 				"'" . addslashes($row['non_contributing']) . "'," . 				"'" . addslashes($row['nondisclosed_reason']) . "'," . 				"'" . addslashes($row['nondisclosed_summary']) . "'," . 				"'" . addslashes($recordUniqueID) . "'" . 			")";	
		
		if ($db->dbUpdate($sql))
		{
			$autoimport_log->write("Added contributor: " . $row['owning_organisation']);			echo "Added contributor: " . $row['owning_organisation'] . "\r\n";
		}	}	}

$importcomplete = "Import completed in " . (microtime(true) - $start_time) . " : " . $db->getQueryCount() . " queries executed";
$autoimport_log->write($importcomplete);

echo "Importing contributors completed\r\n";
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

$oosfolderfile = "c:\\hillsborough_extracts\\oosfolder\\oosfolders.csv";
$redactionfile = "c:\\hillsborough_extracts\\redaction\\redactions.csv";
$materialreferencedfile = "c:\\hillsborough_extracts\\ReferencedInReport\\referencedInReport.csv";
$file = "c:\\hillsborough_extracts\\csv\\masterlist.csv";
$seriesOrderFile = "c:\\hillsborough_extracts\\seriesFolders\\seriesFolders.csv";

// Check all files exist
if (!file_exists($seriesOrderFile))
	Die("Failed to find the series ordering CSV @ " . $seriesOrderFile);

//if (!file_exists($oosfolderfile))
//	Die("Failed to find the out of scope folders CSV @ " . $oosfolderfile);

if (!file_exists($redactionfile))
	Die("Failed to find the redaction CSV @ " . $redactionfile);

if (!file_exists($materialreferencedfile))
	Die("Failed to find the material referenced in report CSV @ " . $materialreferencedfile);

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

//A.S. Swapped things round to use ID as the key for the array 'cos surname is not unique
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

function GetFolderOrdering()
{
	global $db;
	global $seriesOrderFile;
	
	echo "Importing \"Series ordering\" from ".$seriesOrderFile."\r\n";
	$importarray = array('owning_organisation','series_title', 'sub_series_title', 'archive_ref_id', 'order');
	$row = 0;
	$buildrow = 0;
	$build = array();
	
	
	// start csv import
	if (($handle = fopen($seriesOrderFile, "r")) !== FALSE) 
	{
		while (($data = fgetcsv($handle, 0, ",")) !== FALSE) 
		{		
			if ($row++ > 0 && $data[0] != '')
			{	
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
		die("File not found");
	}
	
	$counter = array();
	$lastFolder = "";
	
	foreach ($build as $row)
	{	
		if ($lastFolder!=$row['series_title'])
		{
			if (isset($counter[$row['owning_organisation']]))
				$counter[$row['owning_organisation']]++;
			else 
				$counter[$row['owning_organisation']]=1;			
			
			//we need to insert just the folder ref with no sub-folder due to the naff design for folders/sub-folders
					$sql = "UPDATE serieslookup SET archive_ref_id = '".$row['archive_ref_id']."', " .
					"archiveorder = " . $counter[$row['owning_organisation']] . " " . 
					"WHERE owning_organisation = '".addslashes($row['owning_organisation'])."'" .
					"AND   series_title = '".addslashes($row['series_title'])."'" . 
					"AND series_sub_title = ''";
			
			if ($db->dbUpdate($sql))
			{
				echo "Added top level folder " . $row['series_title'] . "\r\n";
			}
			
			$lastFolder = $row['series_title'];		
		}
		
		$sql = "SELECT count(*) as recordsfound FROM serieslookup " . 
				"WHERE owning_organisation = '".addslashes($row['owning_organisation'])."'" .
				"AND   series_title = '".addslashes($row['series_title'])."'" . 
				"AND series_sub_title = '".addslashes($row['sub_series_title'])."'";
		
		$check = $db->dbFetch($sql, FALSE);
		
		if ($check['recordsfound']=="0")
		{
			echo "No match found for folder [" . $row['series_title'] . "/" . $row['sub_series_title'] . "] of " . $row['owning_organisation'] . "\r\n";
		}
		else 
		{
			
			if (isset($counter[$row['owning_organisation']]))
				$counter[$row['owning_organisation']]++;
			else 
				$counter[$row['owning_organisation']]=1;			
				
			$sql = "UPDATE serieslookup SET archive_ref_id = '".$row['archive_ref_id']."', " .
					"archiveorder = " . $counter[$row['owning_organisation']] . " " . 
					"WHERE owning_organisation = '".addslashes($row['owning_organisation'])."'" .
					"AND   series_title = '".addslashes($row['series_title'])."'" . 
					"AND series_sub_title = '".addslashes($row['sub_series_title'])."'";
			
			if ($db->dbUpdate($sql))
			{
				echo "Added sub folder " . $row['sub_series_title'] . "\r\n";
			}
		}
	}
}

function ClearDownLogs()
{
	global $db;
	$sql = "TRUNCATE TABLE logging";
	$lookup_list = $db->dbUpdate($sql);
}

// Added this step as a number of occurences have happended when search has contained things 
// that haven't beencleaned down previously 
function ClearSOLRFolder()
{
	$start = microtime(true);
	echo "Clearing down SOLR search folder\r\n";
	foreach (scandir(SOLRPATH) as $item) 
	{
	    if ($item == '.' || $item == '..') continue;
	    unlink(SOLRPATH.DIRECTORY_SEPARATOR.$item);
	}			
	$end = microtime(true);
	
	$runningTime = $end - $start;
	
	echo "Finished clearing down SOLR index files (in " . sprintf( "%02.2d:%02.2d", floor( $runningTime / 60 ), $runningTime % 60 ) . ")\r\n";
}

function ClearTables()
{
	global $db;
	$sql = "TRUNCATE TABLE materialreferenced_import";
	$lookup_list = $db->dbUpdate($sql);
	$sql = "TRUNCATE TABLE out_of_scope_folders";
	$lookup_list = $db->dbUpdate($sql);
	$sql = "TRUNCATE TABLE serieslookup";
	$lookup_list = $db->dbUpdate($sql);
	$sql = "TRUNCATE TABLE cataloguemerge";
	$lookup_list = $db->dbUpdate($sql);
	$sql = "TRUNCATE TABLE disclosed_material";
	$lookup_list = $db->dbUpdate($sql);
	$sql = "TRUNCATE TABLE redaction_import";
	$lookup_list = $db->dbUpdate($sql);
}

function ProcessRedactionImport()
{
	global $db;
	global $redactionfile;
	
	$rrow=0;
	
	if (file_exists($redactionfile))
	{
		if (($rhandle = fopen($redactionfile, "r")) !== FALSE) 
		{
			echo "Opened redaction file for processing\r\n";
			while (($data = fgetcsv($rhandle, 0, ",")) !== FALSE)
			{
				//ignore header row
				if ($rrow!=0)
				{
					$docID = $data[0];
					$redactionReason = $data[1];
					if (trim($redactionReason)!="")
					{
						$sql = "INSERT INTO redaction_import (begin_doc_id, redacted_value) values ('".$docID."', '".addslashes($redactionReason)."')";
						$insert_redaction = $db->dbUpdate($sql);
						if (!$insert_redaction)
						{	
							echo "ERROR: Failed to insert redaction value\r\n";
							die();
						}
					}
				}
				$rrow++;
			} 
		}
		
		echo "Processed redaction file and inserted ".$rrow." redacted items\r\n";
	}
	else
	{
		echo "ERROR: Redaction file not found at(".$redactionfile.") so exiting.\r\n";
		die();
	}
}

function ProcessMaterialReferencedImport()
{
	global $db;
	global $materialreferencedfile;
	
	$rrow=0;
	
	$matref = array();
	
	if (file_exists($materialreferencedfile))
	{
		if (($rhandle = fopen($materialreferencedfile, "r")) !== FALSE) 
		{
			echo "Opened material referenced file for processing\r\n";
			while (($data = fgetcsv($rhandle, 0, ",")) !== FALSE)
			{
				//ignore headrer row
				if ($rrow!=0)
				{
					$docID = $data[0];
					if (strlen($docID)!=15)
					{
						echo "WARNING: Invaid length for " . $docID;
						die;
					}
					$chapterref = $data[1];
					if (trim($chapterref)!="")
					{
						if(isset($matref[$docID]))
							$matref[$docID] .= ";" . $chapterref;
						else 
							$matref[$docID] = $chapterref;
					}
				}
				$rrow++;
			} 
		}
		
		foreach($matref as $id=>$chaps)
		{
			$sql = "INSERT INTO materialreferenced_import (begin_doc_id, chapterref) values ('".$id."', '".$chaps."')";
			$insert_ref = $db->dbUpdate($sql);
			if (!$insert_ref)
			{	
				echo "ERROR: Failed to insert redaction value (". $id . ": " . $chaps . "\r\n";
				die();
			}
		}
		echo "Processed material referenced file and inserted ".$rrow." referenced items\r\n";
	}
	else
	{
		echo "ERROR: Material referenced file not found at(".$materialreferencedfile.") so exiting.\r\n";
		die();
	}
}

function CreateSOLREntryForOOSOrgs()
{
	echo "Creating SOLR XML files for OOS Organisations\r\n";

	global $db;
	$sql = "SELECT * FROM Organisations WHERE non_disclosed != ''";
	$organisations = $db->dbFetch($sql);
	
	foreach($organisations as $organisation)
	{
		echo " - writing out of scope organisation: " . $organisation['owning_organisation'] . "\r\n";
		$filename = SEARCH_DATA_DIR . $organisation["unique_id"] . ".xml";
		$curl = "<add>\r\n"
				. "<doc boost=\"0\">\r\n"
				. "<field name=\"hip_uid\">" . $organisation['unique_id'] . "</field>\r\n"
				. "<field name=\"hip_location\">/repository/outofscopeorg/" . $organisation['unique_id'] . ".html</field>\r\n"					
				. "<field name=\"hip_series_title\"></field>\r\n"
				. "<field name=\"hip_title\">" . htmlspecialchars($organisation['owning_organisation']) . "</field>\r\n"
				. "<field name=\"hip_format\"></field>\r\n"
				. "<field name=\"hip_description\">" . htmlspecialchars($organisation['description']) . "</field>\r\n"
				. "<field name=\"hip_series_subtitle\"></field>\r\n"
				. "<field name=\"hip_victim\"></field>\r\n"
				. "<field name=\"hip_person\"></field>\r\n"
				. "<field name=\"hip_corporate\"></field>\r\n"
				. "<field name=\"hip_contrib_org\">".htmlspecialchars($organisation['owning_organisation'])."</field>\r\n"	
				. "<field name=\"hip_chapter\"></field>\r\n"
				. "<field name=\"hip_archive_ref\"></field>\r\n"
				. "<field name=\"hip_outofscope_reason\">".$organisation['non_disclosed']."</field>\r\n"
				. "<field name=\"hip_report\">false</field>\r\n"
				. "</doc>\r\n</add>\r\n";		
		
		$savefile = fopen($filename, "w");
		fwrite($savefile, $curl);
		fclose($savefile);
	}	
}
/*
function CreateSOLREntryForOOSFolders()
{
	echo "Creating SOLR XML files for OOS Folders\r\n";

	global $db;
	$sql = "SELECT * FROM out_of_scope_folders";
	$folders = $db->dbFetch($sql);
	
	foreach($folders as $folder)
	{
		echo " - writing out of scope folder for: " . $folder['owning_organisation'] . "\r\n";
		$filename = SEARCH_DATA_DIR . $folder["begin_doc_id"] . ".xml";
		$curl = "<add>\r\n"
				. "<doc boost=\"0\">\r\n"
				. "<field name=\"hip_uid\">" . $folder['begin_doc_id'] . "</field>\r\n"
				. "<field name=\"hip_location\">/repository/outofscopefolder/" . $folder['begin_doc_id'] . ".html</field>\r\n"					
				. "<field name=\"hip_series_title\">" . htmlspecialchars($folder['series_title']) . "</field>\r\n"
				. "<field name=\"hip_title\">" . htmlspecialchars($folder['owning_organisation']) . "</field>\r\n"
				. "<field name=\"hip_format\"></field>\r\n"
				. "<field name=\"hip_description\">" . htmlspecialchars($folder['description']) . "</field>\r\n"
				. "<field name=\"hip_series_subtitle\">" . htmlspecialchars($folder['sub_series_title']) . "</field>\r\n"
				. "<field name=\"hip_victim\"></field>\r\n"
				. "<field name=\"hip_person\"></field>\r\n"
				. "<field name=\"hip_corporate\"></field>\r\n"
				. "<field name=\"hip_contrib_org\">".htmlspecialchars($folder['owning_organisation'])."</field>\r\n"	
				. "<field name=\"hip_chapter\"></field>\r\n"
				. "<field name=\"hip_archive_ref\">" . htmlspecialchars($folder['archive_ref_id']) . "</field>\r\n"
				. "<field name=\"hip_outofscope_reason\">" . htmlspecialchars($folder['out_of_scope_reason']) . "</field>\r\n"
				. "<field name=\"hip_report\">false</field>\r\n"
				. "</doc>\r\n</add>\r\n";		
		
		$savefile = fopen($filename, "w");
		fwrite($savefile, $curl);
		fclose($savefile);
	}
	
}

function ProcessOOSFoldersImport()
{
	global $db;
	global $oosfolderfile;
	
	$rrow=0;
	
	if (file_exists($oosfolderfile))
	{
		if (($rhandle = fopen($oosfolderfile, "r")) !== FALSE) 
		{
			echo "Opened folder file for processing\r\n";
			while (($data = fgetcsv($rhandle, 0, ",")) !== FALSE)
			{
				//ignore header row
				if ($rrow!=0)
				{					
					$sql = "INSERT INTO out_of_scope_folders (begin_doc_id,owning_organisation,archive_ref_id,short_title,description,series_title,sub_series_title,out_of_scope_reason) " . 
						"values (" . 
							"'".$data[0]."', " . 					// begin_doc_id
							"'".addslashes($data[1])."', " . 		// owning org
							"'".addslashes($data[2])."', " . 		// archive_id
							"'".addslashes($data[3])."', " . 		// title
							"'".addslashes($data[4])."', " . 		// series title
							"'".addslashes($data[5])."', " . 		// sub series title
							"'".addslashes($data[6])."', " . 		// description
							"'".addslashes($data[7])."') ";			// oos reason
					$insert_folder = $db->dbUpdate($sql);
					if (!$insert_folder)
					{	
						echo "ERROR: Failed to insert folder value\r\n";
						die();
					}
				}
				$rrow++;
			} 
		}
		
		echo "Processed oos folders file and inserted ".$rrow." folder items\r\n";
	}
	else
	{
		echo "ERROR: oosfolderfile file not found at(".$oosfolderfile.") so exiting.\r\n";
		die();
	}
}
*/

function CloneDisclosedMaterialTable()
{
	global $db, $expertBuild;

	// bulk copy of disclosed
	$matsql = 	"insert into cataloguemerge (begin_doc_id,owning_organisation,archive_ref_id, " . 
				"short_title,description,out_of_scope_reason,formatted_outofscope, linetype) " . 
				"select begin_doc_id,owning_organisation,archive_ref_id, " . 
				"short_title,description,out_of_scope_reason,formatted_outofscope, 'ITEM' as linetype " . 
	 			"from disclosed_material";
	
	
	// reformat orgs into cataloguepage
	$orgsql = 	"insert into hillsborough.cataloguemerge (begin_doc_id,owning_organisation,archive_ref_id, " . 
				"short_title,description,out_of_scope_reason,formatted_outofscope, linetype) ".  
				"select distinct unique_id as begin_doc_id,owning_organisation,'' as archive_ref_id,'' as short_title, " . 
				"description,'OOS_ORG' as out_of_scope_reason, non_disclosed as formatted_outofscope, 'ORGANISATION' as linetype " . 	  
				"from hillsborough.organisations " .
				"where non_disclosed!='' and unique_id !=''";

// Changed the following line (above) to reflect 
//				"description,'OOS_ORG' as out_of_scope_reason,non_disclosed as formatted_outofscope, 'ORGANISATION' as linetype " . 	  

				
	// reformat oos folder into cataloguepage
//	$foldersql = 	"insert into hillsborough.cataloguemerge (begin_doc_id,owning_organisation,archive_ref_id, " . 
//				"short_title,description,out_of_scope_reason,formatted_outofscope, linetype) " . 
//				"select begin_doc_id, owning_organisation, archive_ref_id, short_title, " . 
//				"description, out_of_scope_reason, 'Other - seen by Panel' as formatted_outofscope, 'FOLDER' as linetype " .
//				"from hillsborough.out_of_scope_folders";
	
	if (!$expertBuild)
	{
		$executeStatement = $db->dbUpdate($orgsql);
		if (!$executeStatement)
			throw new Exception("Failed to create cataloguemerge table (organisations) on import");
	}
		
	$executeStatement = $db->dbUpdate($matsql);
	if (!$executeStatement)
		throw new Exception("Failed to create cataloguemerge table (disclosed items) on import");
	
	/*KH: Not needed any more
	if (!$expertBuild)
	{
		$executeStatement = $db->dbUpdate($foldersql);
		if (!$executeStatement)
			throw new Exception("Failed to create cataloguemerge table (oos folders) on import");
	}
	*/
}

// Replace 'All other documents' as Series and Sub-series names with 'All documents' for
// Contributors without any other Series and Contributor/Series without any other Sub-series
function SeriesAndSubSeriesAllDocuments()
{
	global $db;
	
	$sql = "DROP TABLE IF EXISTS tempseries";
	
	$executeStatement = $db->dbUpdate($sql);
	if (!$executeStatement)
		throw new Exception("Failed to drop tempseries table (1)");

	$sql = "CREATE TEMPORARY TABLE tempseries SELECT owning_organisation, series_title, series_sub_title " .
			"FROM serieslookup WHERE series_sub_title <> 'All other documents' AND series_sub_title <> ''";

	$executeStatement = $db->dbUpdate($sql);
	if (!$executeStatement)
		throw new Exception("Failed to create tempseries table (1)");

	$sql = "UPDATE serieslookup AS sl LEFT OUTER JOIN tempseries AS ts " .
			"ON sl.owning_organisation = ts.owning_organisation AND sl.series_title = ts.series_title " .
			"SET sl.series_sub_title = 'All documents' WHERE sl.series_sub_title = 'All other documents' AND ts.series_sub_title IS NULL";

	$executeStatement = $db->dbUpdate($sql);
	if (!$executeStatement)
		throw new Exception("Failed to update series_sub_title in serieslookup (1)");
		
	$sql = "UPDATE disclosed_material AS dm INNER JOIN serieslookup AS sl ON dm.owning_organisation = sl.owning_organisation " .
			"AND dm.series_title = sl.series_title SET dm.series_sub_title = 'All documents' WHERE sl.series_sub_title = 'All documents'";

	$executeStatement = $db->dbUpdate($sql);
	if (!$executeStatement)
		throw new Exception("Failed to update series_sub_title in disclosed_material (1)");
			
	$sql = "DROP TABLE IF EXISTS tempseries";
	
	$executeStatement = $db->dbUpdate($sql);
	if (!$executeStatement)
		throw new Exception("Failed to drop tempseries table (2)");

	$sql = "CREATE TEMPORARY TABLE tempseries SELECT owning_organisation, series_title " .
			"FROM serieslookup WHERE series_title <> 'All other documents' AND series_title <> ''";
			
	$executeStatement = $db->dbUpdate($sql);
	if (!$executeStatement)
		throw new Exception("Failed to create tempseries table (2)");

	$sql = "UPDATE serieslookup AS sl LEFT OUTER JOIN tempseries AS ts " .
			"ON sl.owning_organisation = ts.owning_organisation SET sl.series_title = 'All documents' " .
			"WHERE sl.series_title = 'All other documents' AND ts.series_title IS NULL";

	$executeStatement = $db->dbUpdate($sql);
	if (!$executeStatement)
		throw new Exception("Failed to update series_title in serieslookup (2)");
		
	$sql = "UPDATE disclosed_material AS dm INNER JOIN serieslookup AS sl ON dm.owning_organisation = sl.owning_organisation " .
			"SET dm.series_title = 'All documents' WHERE sl.series_title = 'All documents'";

	$executeStatement = $db->dbUpdate($sql);
	if (!$executeStatement)
		throw new Exception("Failed to update series_title in disclosed_material (2)");		
}

/****************************************************
 * Start of main code block here!!!
 ****************************************************/
$start_time = microtime(true);

$testmode = false;
$updatemode = false;
$updatesolrmode = false;
$expertBuild = false;

$orgs = null;
if (!isset($argv[1]))
{
	echo "No param supplied so all orgs will be processed\r\n";
//	exit();
}
elseif ($argv[1]=="--testrun")
{
	echo "Running in test mode - no commits will be made\r\n";
	$testmode = true;
	
}
elseif ($argv[1]=="--update")
{
	echo "Running in update mode - no initial cleardown will take place\r\n";
	$updatemode = true;	
}
elseif ($argv[1]=="--updatesolr")
{
	echo "Running in update SOLR mode - no initial cleardown will take place and only updating SOLR xml files\r\n";
	$updatesolrmode = true;	
}
else 
{ 
	$orgs = "";
	$c = 1;
	do
	{
		$orgs .= $argv[$c] . ",";
		$c++;
	} while(isset($argv[$c]));
	echo "The following orgs will be processed: ";
	echo $orgs . "\r\n";
	
	$expertBuild = true;
}





$db = new Database( DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS );

ClearDownLogs();

if ((!$testmode)&&(!$updatemode)&&(!$updatesolrmode))
{
	ClearTables();
	ClearSOLRFolder();
	ProcessRedactionImport();
	ProcessMaterialReferencedImport();
	CreateSOLREntryForOOSOrgs();
	
	/*Surprise, this is now not needed at all so removing.
	// We don't want the OOS stuff on the Expert Laptops
	if ($expertBuild)
	{
		echo "Excluding Out of Scope Organisations\r\n";
	}
	else
	{
		ProcessOOSFoldersImport();
		CreateSOLREntryForOOSFolders();
	}
	*/
}


$row = 0;
$insertcount = 0;
$totalRecords = 0;

$victims = GetVictimLookupData(1);
$corporatebodies = GetLookupData(2);

$persons = GetPersonLookupData(3);
//var_dump($persons);
//exit;
//$persons = $persons;
//$personsMedium = $persons["medium"];
//$personsLow = $persons["low"];
$personSurnames = GetPersonSurnameLookupData(3);

//var_dump($persons, $personSurnames);
//exit();



// start csv import
if (($handle = fopen($file, "r")) !== FALSE) 
{
	echo "Opened file\r\n";
	while (($data = fgetcsv($handle, 0, ",")) !== FALSE) 
	{			
		if ($row++ > 0)
		{		
			$record = new DisclosedRecord(OCRPATH, PDFPATH, SOLRPATH, $victims, $corporatebodies, $persons, $personSurnames, $db, $orgs);

			$record->LoadRecord($data);
			if (!$record->IsCorrupt())
			{
				$record->Validate();
				if ($record->IsValid())
				{
					if (!$testmode)
					{
						$record->ProcessOCR();
						
						if (!$updatesolrmode)
						{
							if (!$updatemode)
								$record->SaveRecord();
							else
								$record->UpdateRecord();
						}
						//$record->PersistLog();
					}
					//					echo $record->VerboseOutput();
					$insertcount++;
				}
				else 
				{
					if (!$testmode)
					{
						echo $record->VerboseOutput();
					}
					//$record->PersistLog();
				}
			}
			$record->PersistLog();
			$totalRecords++;
		}
		$row++;
	}
	
	SeriesAndSubSeriesAllDocuments();
	
	echo "Cloning disclosed_material for performance on catalogue page\r\n";
	CloneDisclosedMaterialTable();
	GetFolderOrdering();
	$end_time = microtime(true);
	
	$runningTime = $end_time - $start_time;
	echo "\r\n\r\nProcess ran for " . sprintf( "%02.2d:%02.2d", floor( $runningTime / 60 ), $runningTime % 60 ) . "\r\n";
	
	echo "\r\n\r\n";
	
	echo "Found " . $totalRecords . " records in import file.\r\n";
	echo "Imported " . $insertcount . " records.\r\n";
}
else 
{
	echo "Error opening CSV file for import";
}


?>

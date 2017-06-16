<?php
setlocale(LC_ALL, 'en_UK.UTF8'); 
date_default_timezone_set('Europe/London');

require_once("library/functions.php");


$organisations = array();
$stats = array();
$noFiles = 0;

function LoadOrgs($file)
{
	global $organisations;
	
	echo "Importing Organisations from $file\r\n\r\n";
	
//	$importFields = array('Organisation', 'Variations');

	// start csv import
	if (($handle = fopen($file, "r")) !== FALSE) 
	{
		while (($data = fgetcsv($handle, 0, ",")) !== FALSE) 
		{
			$org = trim($data[0]);
			$vars = trim($data[1]);
			
			if ((!empty($org)) && (!empty($vars)))
			{
				if (substr($vars, -1) == ";")
				{
					$vars = substr($vars, 0, -1);
				}
				$variations = explode(";", $vars);
				$variations[] = $org;
				$organisations[] = array($org, $variations);
			}
		}
		
		fclose($handle);
	}
	else
	{
		die("$file file not found");
	}	
}


function ScanFile($file)
{
	global $organisations;
	global $stats;
	global $noFiles;

	
	$contents = file_get_contents($file);

	if ($contents === FALSE)
	{
		echo "Unable to open: $file\r\n";
		return;
	}
	
	echo "Scanning: $file\r\n";
	$noFiles++;

	$contents = " " . $contents . " ";

// Remove fullstops, commas and parentheses as they'll just complicate matching
//	$contents = preg_replace('/[.,()]/', ' ', $contents);
	$contents = preg_replace('!\s+!', ' ', $contents);
	$contents = strtolower(convert_ascii($contents));
		
	foreach($organisations as $oid => $organisation)
	{
		$noMatches = 0;

		foreach($organisation[1] as $variation)
		{
			$noMatches += PerformMatch($contents, $variation);
		}
		
		if (!isset($stats[$oid]))
		{
			$stats[$oid] = $noMatches;
		}
		else
		{
			$stats[$oid] += $noMatches;
		}
	}
	
//	echo $contents;
}

function PerformMatch($textToSearch, $textToMatch)
{
	$result = 0;
	$textToMatch = strtolower(trim($textToMatch));
	
	if (strpos($textToSearch, $textToMatch)!==FALSE)
	{
		$matches = array();
		preg_match_all("/[^0-9a-zA-Z]".$textToMatch."[^0-9a-zA-Z]/", $textToSearch, $matches);
	
		if (sizeof($matches[0])>0)
		{
			$result = sizeof($matches[0]);
			//var_dump($matches[0]);
		}
	}
	return $result;		
}

function OutputResults()
{
	global $organisations;
	global $stats;

	foreach($organisations as $oid => $org)
	{
		echo $stats[$oid], ", ", $org[0], "\r\n";
	}
}

function ProcessDir($dir)
{
	$dirContents = scandir($dir);
	// Loop through the directory's contents looking for .txt files (or sub-directories?)
	foreach($dirContents as $entry)
	{
		if (($entry != ".") && ($entry != ".."))
		{
			$entry = $dir."/".$entry;
			if ((is_file($entry)) && (strcasecmp(substr($entry, -4), ".txt") == 0))
			{
				ScanFile($entry);
			}
//			elseif (is_dir($entry))
//			{
//				processDir($entry);
//			}
		}
	}
}

function ProcessReferencedDocs($refLookup, $ocrDir)
{
	$docs = file($refLookup);

	foreach($docs as $doc)
	{
		// Bodge to cater for the fact that I couldn't put all the docs in a single FAT32 directory
//		if (strcasecmp($doc, "SYP000127650001") < 0)
//		{
			ScanFile($ocrDir."/".trim($doc).".txt");
//		}
//		else
//		{
//			ScanFile($ocrDir."2/".trim($doc).".txt");
//		}
	}
}

/****************************
 * Start of main code block *
 ****************************/
 
$start_time = microtime(true);

LoadOrgs("OrganisationList.csv");

//ProcessDir("G:\Report");

ProcessReferencedDocs("Refv2_120815.txt", "c:\hillsborough_extracts\ocr");

//var_dump($organisations);
//var_dump($stats);

$end_time = microtime(true);
	
$runningTime = $end_time - $start_time;
echo "\r\n\r\nScanned $noFiles files in $runningTime seconds.\r\n\r\n";


echo "No of Refs, Organisation\r\n";
//echo "-+--+--+--+--+--+--+--+--+--+--------\r\n";

OutputResults();

//Dump($people);



?>

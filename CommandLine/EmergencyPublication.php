<?php
/****
 * File:         EmergencyPublication.php
 * Author:       zqdmjm
 * Date:         11 May 2012
 * 
 * Description:  
 * 
 */

include_once 'library/config.php';
include_once 'library/database.php';
//include "dom.php";

define("WEBSITE", "c:\\Builds\\Temp\\HTML\\");
define("REDACTION_PATH", "c:\\Builds\\Temp\\Redaction\\");

/**
 * Display functions
 */

function DisplayTitle()
{
	echo "Emergency publication\r\n";	
	echo "=====================\r\n\r\n";
}

function DisplayHelp() 

{
	echo "Syntax: php EmergencyPublication.php -mode [Document ID | CSV list]\r\n";
	echo "    -mode: buidlindex - generate a list of all pages and what documents are referenced on each page\r\n";
	echo "           takedown   - generate new pdf, redact DLP and referencing pages\r\n";
	echo "           putback    - put back original documents\r\n";
	echo "           replace    - generate new document information\r\n";
	echo "\r\n";
}

/**
 * Main mode methods
 */

function BuildIndex()
{
	//$path = "c:\\Builds\\Temp\\HTML\\";
	$path = WEBSITE;
	echo "Building index for site located at ". $path . "\r\n";

	TruncateDocuments();

	$filecount = 0;
	$fileprocessed = 0;
	
	$Directory = new RecursiveDirectoryIterator($path);
	$Iterator = new RecursiveIteratorIterator($Directory);
	
	$filenames = new RegexIterator($Iterator, '/^.+\.html$/i', RecursiveRegexIterator::GET_MATCH);

	
	foreach($filenames as $filename)
	{
		$updated = ProcessDocument($filename[0]);
		$filecount++;
		if ($updated)
			$fileprocessed++;
	}
	
	echo "Processed " . $filecount . " files in the system and found " . $fileprocessed . " matches\r\n";
}

function CreateFolderStructure($folder)
{
	mkdir(REDACTION_PATH . $folder);
	mkdir(REDACTION_PATH . $folder . "\\original");
	mkdir(REDACTION_PATH . $folder . "\\redacted");
	mkdir(REDACTION_PATH . $folder . "\\original\\pdf");		
	mkdir(REDACTION_PATH . $folder . "\\original\\index");		
	mkdir(REDACTION_PATH . $folder . "\\redacted\\pdf");
	mkdir(REDACTION_PATH . $folder . "\\redacted\\index");		
}


function CreateSnapshot($docID, $results)
{
	echo "Removing document references to: " . $docID . "\r\n";
	echo "There are " . sizeof($results) . " matching pages for this document\r\n";
	
	$folder = $docID;
	
	$count = 1;
	while(file_exists(REDACTION_PATH . $folder))
	{
		$folder = $docID . "_$count";
		$count++;
	}
	
	echo "Redaction process writing to: " . REDACTION_PATH . $folder . "\r\n";
	echo "Creating before and after checkpoints... ";
	CreateFolderStructure($folder);

	// copy HTML pages
	foreach($results as $record)
	{
		$filename = $record["docs"]; 
		$file = basename($filename);
		$path = str_replace($file, "", $filename); 
		if (!file_exists(REDACTION_PATH . $folder . "\\original\\html\\" . $path))
		{
			echo "making: " . REDACTION_PATH . $folder . "\\original\\html\\" . $path . "\r\n";
			mkdir(REDACTION_PATH . $folder . "\\original\\html\\" . $path, 0700,true);
		}
		if (!file_exists(REDACTION_PATH . $folder . "\\redacted\\html\\" . $path))
		{
			echo "making: " . REDACTION_PATH . $folder . "\\redacted\\html\\" . $path . "\r\n";
			mkdir(REDACTION_PATH . $folder . "\\redacted\\html\\" . $path, 0700,true);
		}		
		echo "copying: " . REDACTION_PATH . $folder . "\\redacted\\html\\" . $path  . $file . "\r\n";
		copy(WEBSITE . $record["docs"], REDACTION_PATH . $folder . "\\original\\html\\" . $path . $file);
		echo "copying: " . REDACTION_PATH . $folder . "\\redacted\\html\\" . $path . $file . "\r\n";
		copy(WEBSITE . $record["docs"], REDACTION_PATH . $folder . "\\redacted\\html\\" . $path . $file);
	}
	
	// copy PDF
	copy(PDF_DIR . $docID . ".pdf", REDACTION_PATH . $folder . "\\original\\pdf\\" . $docID . ".pdf");
	copy(REDACTION_PATH . "redacted.pdf", REDACTION_PATH . $folder . "\\redacted\\pdf\\" . $docID . ".pdf");
	
	// copy INDEX
	copy(SEARCH_DATA_DIR . $docID . ".xml", REDACTION_PATH . $folder . "\\original\\index\\" . $docID . ".xml");
	copy(SEARCH_DATA_DIR . $docID . ".xml", REDACTION_PATH . $folder . "\\redacted\\index\\" . $docID . ".xml");
    echo "done.\r\n";
   
	return $folder;
}

function GetValueFromHtml($start, $end, $html)
{
    $s1 = strpos($html, $start)+strlen($start);
    $currentText = substr($html, $s1);
    $s1 = strpos($currentText, $end);
    $currentText = substr($currentText, 0, $s1);
    return $currentText;
}

function Takedown($docID)
{
	global $db;
	$results = $db->dbFetch("SELECT URL as docs from emergencypublication.documents where DocumentID = '".addslashes($docID)."'");
		
	$folder = CreateSnapshot($docID, $results);
	
    //Get fields
    $dlp = file_get_contents(REDACTION_PATH . $folder . "\\redacted\\html\\repository\\".$docID.".html");
    $titleStart = "<header>\r\n<h1>";
    $titleEnd = "</h1>";
    $descStart = "<div role=\"main\">\r\n<p>";
    $descEnd = "</p>\r\n\r\n<table";
    $synopsisStart = "<p>\r\n";
    $synopsisEnd = "</p>\r\n";
    
    //title
    $currentTitle = GetValueFromHtml($titleStart, $titleEnd, $dlp);
	$currentDescription = GetValueFromHtml($descStart, $descEnd, $dlp);

	echo "found: title of " . $currentTitle . "\r\n";
	echo "found: description of " . $currentDescription . "\r\n";
	
	$nodescription = false;
	
	if ($currentDescription=="")
		$nodescription = true;	

    //Synopsis 
    $synopsis = substr($currentDescription,0,200);
    if (strlen($currentDescription)>200)
    	$synopsis .= " ... more";
    
    $newTitle = $currentTitle;
    $newDescription = $currentDescription;    
    $newSynopsis = $synopsis;
    
	// Redact pages
	$redactionReason = prompt("What is the redaction reason to appear in the description: ");
	if ($redactionReason=="")
		$redactionReason = "REMOVED. " . "<br/>";
	else 
		$redactionReason = "REMOVED: " . $redactionReason . "<br/>";
	
	$suppressyn = prompt("Do you want to suppress person, victim and corporate body? (Y/N)", true);
	
	$titleyn = prompt("Do you want to modify the title? (Y/N)", true);
	if (strtolower($titleyn)=="y")
	{
	    echo "Current title:  " . $currentTitle . "\r\n";
		$newTitle = prompt("New title: ");
		if ($newTitle=="")
			$new = $currentTitle;
	}
			
	$descyn = prompt("Do you want to modify the description? (Y/N)");
	if (strtolower($descyn)=="y")
	{
	    echo "Current description (takedown version):  " . $currentDescription . "\r\n";
		$newDescription = prompt("New description: ");
		if ($newDescription=="")
			$newDescription = $currentDescription;
		else 
		{
		    $newSynopsis = substr($newDescription,0,200);
	    	if (strlen($newDescription)>200)
    			$newSynopsis .= " ... more";
		}
	}

	
	//Update redacted pages
	foreach($results as $record)
	{
		$synFix = true;
		
		$filename = $record["docs"]; 
		$file = basename($filename);
		$path = str_replace($file, "", $filename); 
		
		$original = file_get_contents(WEBSITE . $record["docs"]);
		$temppage = $original;
	
		if ((strtolower($suppressyn)=="y")&&
			(strpos($path, "by-corporate-body")!=false) ||
			(strpos($path, "by-person")!=false) ||
			(strpos($path, "by-name-of-deceased")!=false) )
		{
			$temppage = RemoveLinkToDocument($temppage, $docID);		
//			echo "Found a page to suppress\r\n";
//			exit();
		}
		else
		{	
			if (strtolower($titleyn)=="y")
				$temppage = str_replace($currentTitle, $newTitle, $temppage);	

			if ($nodescription)
			{
				$synFix = false;
				$temppage = str_replace("<div role=\"main\">\r\n\r\n<table", "<div role=\"main\">\r\n<p>" . $redactionReason . $newDescription . "</p>\r\n<table", $temppage);
			}
			else 
			{
				$synFix = false;
				$temppage = str_replace($currentDescription, $redactionReason . $newDescription, $temppage);
			}

			if ($synFix)
				$temppage = str_replace($synopsis, $redactionReason . $newSynopsis, $temppage);
		}
			
		file_put_contents(REDACTION_PATH . $folder . "\\redacted\\html\\" . $path . $file, $temppage);
		if ($temppage!=$original)
			echo "Updated: \\redacted\\html\\" . $path . $file . "\r\n";	
	}

	// Update index
	$original = file_get_contents(REDACTION_PATH . $folder . "\\redacted\\index\\" . $docID . ".xml");
	
	$temppage = $original;
	if (strtolower($titleyn)=="y")
		$temppage = str_replace($currentTitle, $newTitle, $temppage);
	$temppage = str_replace($currentDescription, $redactionReason . $newDescription, $temppage);
	$temppage = str_replace($synopsis, $redactionReason . $synopsis, $temppage);
	$temppage = RemovelookupFromIndex($temppage);	
	file_put_contents(REDACTION_PATH . $folder . "\\redacted\\index\\" . $docID . ".xml", $temppage);

	// Update DLP
	$original = file_get_contents(REDACTION_PATH . $folder . "\\redacted\\html\\repository\\".$docID.".html");
	$temppage = $original;
	$temppage = str_replace(GetValueFromHtml("<tr>\r\n<th scope=\"row\">Deceased name:</th>\r\n<td>\r\n", "</td>\r\n</tr>", $temppage), "", $temppage);
	$temppage = str_replace(GetValueFromHtml("<tr>\r\n<th scope=\"row\">Person:</th>\r\n<td>\r\n", "</td>\r\n</tr>", $temppage), "", $temppage);
	$temppage = str_replace(GetValueFromHtml("<tr>\r\n<th scope=\"row\">Organisation:</th>\r\n<td>\r\n", "</td>\r\n</tr>", $temppage), "", $temppage);
	file_put_contents(REDACTION_PATH . $folder . "\\redacted\\html\\repository\\".$docID.".html", $temppage);
	
	
	PackageAndEncrypt("");
	
}

function RemoveLinkToDocument($temppage, $docID)
{
/*
<article class=\"result format-pdf\" >\r\n    <header>\r\n      <h1><a href=\"/repository/\" . $docID . ".html".html">
						
        Internal home Office note: Taylor LJ Report recommendations; Draft contribution to submission on electronically monitored curfews        </a></h1>
        
      <time>28 June 1990              </time>
    </header>
    
    
        	<p>Note dated 28 June 1990 from Mr Grant, C2 Division, to Mr Goddard, F8 Division, regarding Mr Goddard's note of 22 June 1990; Draft contributions to Mr Goddard's submission regarding electronically mon ...</p>
        
    <footer>
      <ul>

	    	        <li><b>Contributing organisation ref:</b> n/a</li>
	          </ul>
    </footer>
</article>

 */	
}

function RemoveLookupFromIndex($xml)
{
	$doc = new DOMDocument();
	$doc->loadXML($xml);

	$fields = $doc->getElementsByTagName("field");
	foreach($fields as $field)
	{
		if (  ($field->getAttribute("name")=="hip_victim")
			||($field->getAttribute("name")=="hip_person")
			||($field->getAttribute("name")=="hip_corporate")
			||($field->getAttribute("name")=="hip_content"))
		{
			$field->nodeValue = "";		
		}
	}
	return $doc->saveXML();
}

function PackageAndEncrypt($folder)
{
	//$archive_path = REDACTION_PATH . $folder . "\\redaction_patch.tgz";
	//$toarchive_path = REDACTION_PATH . $folder . "\\redacted\\*.*";
	//exec("tar cvzf " . $archive_path . " " . $toarchive_path);
	//exec("openssl enc -aes-256-cbc -in " . REDACTION_PATH . $folder . "\\redaction_patch.tgz -out redaction_patch.tgz.enc -k \"Sweeny Fleet Act Now London 1979\" -iv \"AE132D3DE132ABC132879BFDDD1324AA\"");
	//exec("openssl dgst -sha1 -c " . REDACTION_PATH . $folder . "\\redaction_patch.tgz.enc > redaction_patch.tgz.enc.sha1");
}

function prompt($msg, $yesno = FALSE)
{
	do 	
	{
	    echo $msg . ": ";
		$line = stream_get_line(STDIN, 1024, PHP_EOL);
	} while (($yesno)&&(strtolower($line)!="y")&&(strtolower($line)!="n"));

	return $line;
}

function ProcessDocument($filename)
{
	$content = file_get_contents($filename);
	$docIDs = FindDocumentID($content);
	if (isset($docIDs))
	{
		$filename = str_replace(WEBSITE, "", $filename);
		
		foreach($docIDs as $doc)
		{
			$sql = "insert into emergencypublication.documents (DocumentID, URL) values ('".addslashes($doc)."','".addslashes($filename)."')";
			global $db;
			$db->dbUpdate($sql);
		}
		//debug("processed: " . $filename);
		return true;
	}
	return false;
}

/**
 * Utilities
 */

function TruncateDocuments()
{
	global $db;
	$sql = "truncate table emergencypublication.documents";
	$db->dbUpdate($sql);
}

function debug($msg)
{
	echo $msg . "\r\n";
}

function FindDocumentID($content)
{
	//original: $regex = "/([a-z])([a-z])([a-z])(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)/is";
	$regex = "/([a-z][a-z][a-z][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]0001)/i";
	
	if ($c=preg_match_all ($regex, $content, $matches))
	{
		return array_unique($matches[0]);
	}

	return null;
} 


/***
 * Main block
 */

DisplayTitle();

if (!isset($argv[1]))
{
	DisplayHelp();
	exit();
}

$db = new Database( DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS );


switch ($argv[1])
{
	case "-buildindex":
		BuildIndex();
		break;

	case "-takedown":
		Takedown($argv[2]);
		break;
		
		
	default:
		DisplayHelp();
}



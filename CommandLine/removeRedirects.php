<?php

// Domains to be stripped from redirect URLs (no trailing slashes!)
$domains = array("http://drugs.homeoffice.gov.uk");

$rootDir;
$noWarnings = 0;
$noErrors = 0;
$noFatalErrors = 0;
$noRemoved = 0;

function processFile($file)
{
	global $domains, $rootDir;
	global $noWarnings, $noErrors, $noFatalErrors, $noRemoved;
	
	$body = file_get_contents($file);

	// Look for 'refresh' meta tag
	$regexp = '/<meta http-equiv\=\"refresh\"(.+>)/i';
	
	if (preg_match($regexp, $body, $tags) <  1)
	{
		// This is not the file we're looking for
		return;
	}
	
	// Extract the URL from the tag
	$regexp = '/url\=(.+\")/i'; 

	if (preg_match($regexp, $tags[0], $urls) < 1)
	{
		echo "Warning! Malformed redirect in ", $file, "\r\n";
		$noWarnings++;
		return;
	}

	$redirectURL = substr($urls[0], 4, -1);

	// Strip acceptable domains from redirectURL
	foreach($domains as $domain)
	{
		if (strcasecmp(substr($redirectURL, 0, strlen($domain)), $domain) == 0)
		{
			$redirectURL = substr($redirectURL, strlen($domain));
			break;
		}
	}
	$target = $rootDir.$redirectURL;
	
	// If we're redirecting to a folder assume it contains an index.html
	if (strcasecmp(substr($target, -1), "/") == 0)
	{
		$target .= "index.html";
	}

	// Windows so the slashes should be backwards
	$target = str_replace("/","\\",$target);
	// .. .and Windows tends include a trailing backslash on directories...
	$target = str_replace("\\\\","\\",$target);
	
	// Check target file exists
	if (!is_file($target))
	{
		if (!file_exists($target))
		{
			echo "Error! Redirect target, ", $target, ", in ", $file, " does not exist\r\n";
		}
		else
		{
			echo "Error! Redirect target in ", $file, " is not a file\r\n";
		}
		$noErrors++;
		return;
	}
	
	// Rename redirect file
	$rename = substr($file, 0, -4)."bak";
	if (file_exists($rename))
	{
		$suffix = 1;
		$rename .= $suffix;
		while (file_exists($rename) && ($suffix < 10))
		{
			$rename = substr($rename, 0, -1).$suffix;
		}
		if (file_exists($rename))
		{
			// Give up
			echo "Error! Too many renamed ", $file, " .bak files\r\n";
			$noErrors++;
			return;			
		}
	}
	
	
	if (!rename($file, $rename))
	{
		echo "Error! Unable to rename ", $file, " as ", $rename, "\r\n";
		$noErrors++;
		return;
	}
	
	// Copy target file
	if (!copy($target, $file))
	{
		echo "Error! Unable to copy ", $target, " to ", $file, ". Restoring original... ";

		if (!rename($rename, $file))
		{
			echo "\r\nFatal Error! Unable to restore ", $rename, " as ", $file, "\r\n";
			$noFatalErrors++;
			return;
		}
		
		echo "File restored\r\n";
		
		$noErrors++;
		return;
	}
	
	echo "Replaced ", $file, "\r\n";
	$noRemoved++;	
	
	// Tidy up: remove back-up file
	unlink($rename);
}

function processDir($dir)
{
	$dirContents = scandir($dir);
	// Loop through the directory's contents looking for .html files or sub-directories
	foreach($dirContents as $entry)
	{
		if (($entry != ".") && ($entry != ".."))
		{
//			$entry = $dir."\\".$entry;
			$entry = $dir."/".$entry;
			if ((is_file($entry)) && (strcasecmp(substr($entry, -5), ".html") == 0))
			{
				processFile($entry);
			}
			elseif (is_dir($entry))
			{
				processDir($entry);
			}
		}
	}
}

function _main($argv, $argc)
{
	global $rootDir;
	global $noWarnings, $noErrors, $noFatalErrors, $noRemoved;
	
	if ($argc != 2)
	{
		die ("Usage: php removeRedirects.php directory\r\n");
	}

	$rootDir = $argv[1];

	if (!is_dir($rootDir))
	{
		die ($rootDir." is not a directory!\r\nUsage: php removeRedirects.php directory\r\n");
	}
	
	processDir($rootDir);

	echo "\r\n";
	echo "Redirects replaced: ", $noRemoved, "\r\n";
	echo "Number of warnings: ", $noWarnings, "\r\n";
	echo "Number of errors: ", $noErrors, "\r\n";
	echo "Number of fatal errors: ", $noFatalErrors, "\r\n";
}

// ********************
//  SCRIPT ENTRY POINT
// ********************
_main($argv, $argc);


?>

<?php

//========================================================================
// Clean up and validation script
// Keith Halcrow, Dec 2011.
//========================================================================

define("LOG_PATH", "c:\\IL2Delivery\\logs\\");
define("APP_URL", "http://hillsborough.disclosure");
define("APP_WEBSITE_PATH", "C:\\My Web Sites\\APP - IL2\\");
define("CMS_URL", "http://hillsborough.disclosure");
define("CMS_WEBSITE_PATH", "C:\\My Web Sites\\CMS - IL2\\");



// Grab files from folder
function ListFiles($dir) {

    if($dh = opendir($dir)) {

        $files = Array();
        $inner_files = Array();

        while($file = readdir($dh)) {
            if($file != "." && $file != ".." && $file[0] != '.') {
                if(is_dir($dir . "/" . $file)) {
                    $inner_files = ListFiles($dir . "/" . $file);
                    if(is_array($inner_files)) $files = array_merge($files, $inner_files); 
                } else {
                    array_push($files, $dir . "/" . $file);
                }
            }
        }

        closedir($dh);
        return $files;
    }
}

// Write to a log
function logit($msg, $lf)
{
	//global $logfile;
	$str = date("[d/m/Y H:i:s] ", time()) . $msg . "\r\n";
	//KH Speed up process activities:  fwrite($lf, $str); 
	echo $str;
}

// We force HTTrack to use absolute URLs to avoid tree climbing but the solution must use relative
function _ReplaceAbsoluteUrl($body, $url)
{
	$body = str_replace($url, "/", $body);
	return $body;
}

// We know there are known link issues (.html extensions missing, etc).  These are a result of the jquery UI usually
function _ReplaceKnownBrokenLinks($body)
{
	global $BASE_PATH;
	
	$links = array(
		// Fix APP content
		//////////////////
		"/perpage/20\"" => "/perpage/20.html\"",
		"/perpage/100\"" => "/perpage/100.html\"",
		"/perpage/500\"" => "/perpage/500.html\"",
		"\"/help/help.html#report\"" => "\"/help/index.html#report\"",
		);

	for($i=1; $i<300; $i++)
		$links["/page/".$i."\""] = "/page/".$i.".html\"";
		
	foreach($links as $original => $replacement)
	{
		$body = str_replace($original, $replacement, $body);
	}
	
	
	// some repository links not working
	$regexp = '/\/repository\/[a-zA-Z0-9]+0001\"/'; // (href=\"/repository/)[a-zA-Z0-9]0001\""; 
	if(preg_match_all($regexp, $body, $repolinks)) 
	{
		foreach($repolinks[0] as $repolink)
		{
			$body=str_replace($repolink, substr($repolink, 0, strlen($repolink)-1).".html\"",$body);
		}
	}
	
	// check for any others which are not ended in HTML where their html page exists.
	$regexp = '/href\=\"(\/[a-zA-Z0-9\/\-]+\")/'; 
	if(preg_match_all($regexp, $body, $repolinks)) 
	{
		foreach($repolinks[1] as $repolink)
		{
			if (substr($repolink, strlen($repolink)-2, 1)=="/")
			{	
				if (file_exists($BASE_PATH . substr($repolink, 0, strlen($repolink)-1)."index.html\""))
				{
					//echo "Changing to index page\n";
					$body=str_replace($repolink, substr($repolink, 0, strlen($repolink)-1)."index.html\"", $body);
				}
			}
			else
			{
				if (file_exists($BASE_PATH . substr($repolink, 0, strlen($repolink)-1) .".html"))
				{
					//echo $repolink . " => " . substr($repolink, 0, strlen($repolink)-1).".html\"\n";
					
					$body=str_replace($repolink, substr($repolink, 0, strlen($repolink)-1).".html\"", $body);

				}
				else
				{
				//die();
				}
			}
		}
	}
	
	
	
	return $body;
}

function GetLinks($content)
{
	$broken = array();
	$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>"; 
	if(preg_match_all("/$regexp/siU", $content, $matches, PREG_SET_ORDER)) 
	{ 
		foreach($matches as $match) 
		{ 
			//echo $match[2] . " => " . $match[3] . "\n";
			$broken[] = $match[2];
		} 
	}
	return $broken;
}

function ProcessFileList($filenames)
{
	global $BASE_PATH;
	
	
	$urls = array();
	foreach($filenames as $filename)
	{
		$urls[] = str_replace($BASE_PATH, "", $filename);
	}
	return $urls;
}

function _BrokenLinks($content, $files)
{
	$links = GetLinks($content);

	$broken = array();
	foreach($links as $link)
	{
		if ((!in_array($link, $files))&&(!in_array($link."index.html", $files))&&($link!="/")&&(substr($link, 0, 1)!="#"))
		{
			//logit("broken link detected: " . $link);
			$broken[] = $link;
		}
	}
	
	return $broken;
}

if (!isset($argv[1]))
{
	echo "Usage: process_app_cpntent.php <path to HTML>\r\n";
	exit();
}

$BASE_PATH = $argv[1]; //"c:\\IL2Delivery\\AppContent\\";



//Replace pages to be populated by CMS content with placeholders
$placeholder = array(
	"about-panel.html",
	"accessibility.html",
	"contact-us.html",
	"disclosure-process.html",
	"glossary.html",
	"guides.html",
	"help.html",
	"index.html",
	"privacy.html",
	"report.html",
	"site-map.html",
	"useragreement.html",
	"user-agreement.html",
	"website-accessibility",
	"about-panel/index.html",
	"contact-us/index.html",
	"disclosure-process/index.html",
	"glossary/index.html",
	"guides/index.html",
	"report/index.html",
	"report/reporthelp.html",
	"site-map/index.html",
	"help/index.html",
	);
	
//KH Speed up process activities:  $logfile = fopen(LOG_PATH.date("Ymd-His", time()). ".log", "w");
$logfile = "";
logit("App and CMS content processing", $logfile);
logit("==============================", $logfile);

logit("Crawling APP folder", $logfile);

/*
// Have commented out this loop as the placeholder copy routine had already been commented out

//mkdir($BASE_PATH . "repository\\docs");
$pdfs = ListFiles("C:\\hillsborough_extracts\\pdf\\");
foreach($pdfs as $pdf)
{
//	$pdfName = explode("\\", $pdf);
//	$barcode = explode("/", $pdfName[sizeof($pdfName)-1]);
	$barcode = basename($pdf);
	
	//var_dump($barcode[1]);

//	if (!file_exists($BASE_PATH . "repository\\docs\\".$barcode[1]))
//		copy("c:\\IL2Delivery\\placeholders\\temp-pdf.pdf", $BASE_PATH . "repository\\docs\\".$barcode[1]);
		
}
*/

//foreach($placeholder as $filename)
//	copy("c:\\IL2Delivery\\placeholders\\no-content.html", $BASE_PATH . $filename);
		
//copy("c:\\IL2Delivery\\placeholders\\report.pdf", $BASE_PATH . "repository\\report.pdf");
	
$filenames = ListFiles($BASE_PATH);
if (count($filenames)!=0)
{
	$processingCount=0;
	$ignoreCount=0;

	$expectedURLs = ProcessFileList($filenames);
	$brokenLinks = array();

	foreach($filenames as $filename)
	{
		if ((substr($filename, -4)=="html") || (substr($filename, -3)=="htm"))
		{
			$tfilename = $filename;
			if (substr($filename, -3)=="htm")
			{
				$tfilename = str_replace(".htm", ".html", $filename);
				rename($filename, $tfilename);
				logit("ERROR: found [" . $filename . "] so renaming to [" . $tfilename . "]", $logfile);
			}

//			logit("processing: " . $tfilename, $logfile);
			$processingCount++;
			
			$original = file_get_contents($tfilename);
			
			$content = $original;
			
			// Tidy and fix HTML

			$content = _ReplaceAbsoluteUrl($content, "http://drugs.homeoffice.gov.uk/");		
			$content = _ReplaceAbsoluteUrl($content, "http://hillsborough.disclosure/");		
			$content = _ReplaceAbsoluteUrl($content, "http://hip.localhost/");		
			$content = _ReplaceAbsoluteUrl($content, "http://il0app.localhost/");		

			$content = _ReplaceKnownBrokenLinks($content);
			
			/*KH: Removed to see if performance boost
			$tBrokenLinks = _BrokenLinks($content, $expectedURLs);
			foreach($tBrokenLinks as $tlink)
			{
				if (!in_array($tlink, $brokenLinks))
				{
					$brokenLinks[] = $tlink;
				}
			}

			if (count($tBrokenLinks)>0)
			{
				logit("Found " . count($tBrokenLinks) . " broken links in file", $logfile);
			}
			*/
			
			if ($content!=$original)
			{
				file_put_contents($tfilename, $content);
				logit("updating " . $tfilename, $logfile);
			}
		}
		elseif(substr($filename, -3)=="css")
		{
//			logit("processing: " . $filename, $logfile);
			$processingCount++;
			
			$original = file_get_contents($filename);
			
			$content = $original;
			
			// Tidy and fix HTML
			$content = _ReplaceAbsoluteUrl($content, "http://drugs.homeoffice.gov.uk/");		
			$content = _ReplaceAbsoluteUrl($content, "http://hillsborough.disclosure/");		
			$content = _ReplaceAbsoluteUrl($content, "http://hip.localhost/");		
			$content = _ReplaceAbsoluteUrl($content, "http://il0app.localhost/");		

			if ($content!=$original)
			{
				file_put_contents($filename, $content);
				logit("updating " . $filename, $logfile);
			}		
		}
		else
		{
			logit("ignoring: " . $filename, $logfile);
			$ignoreCount++;
		}
		
	}
	
	logit("Replaced " . count($placeholder). " CMS based pages with blank placeholders", $logfile);	
	logit("=>    Summary processed: " . $processingCount, $logfile);
	logit("=>      Summary ignored: " . $ignoreCount, $logfile);
	logit("=>    Summary processed: " . $processingCount, $logfile);
	/*KH: Removed as not needed and slows process down doing link checking
	logit("=> Summary broken links: " . count($brokenLinks), $logfile);
	
	foreach($brokenLinks as $link)
	{
		logit("detected broken link: " . $link, $logfile);
	}
	*/
}
else
{
	logit("No files found to process", $logfile);
}

//KH Speed up process activities:  fclose($logfile);
?>
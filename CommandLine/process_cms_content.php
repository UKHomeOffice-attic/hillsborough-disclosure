<?php

//========================================================================
// Variables go here for environment specific stuff

//define("$BASE_PATH", "c:\\Builds\\temp\\cms_html\\");
define("LOG_PATH", "c:\\builds\\temp\\logs\\");

// End of global vars
//========================================================================



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

function logit($msg, $lf)
{
	//global $logfile;
	$str = "[" . date("d/m/Y H:i:s", time()) . "] " . $msg . "\n";
	fwrite($lf, $str); 
	echo $str;
}

function FootnoteProcessing($body)
{
	$start = strpos($body, "<div class=\"footnotes\">"); 
	if ($start!==false)
	{		
		$footnote = substr($body, $start);
		$footnote = substr($footnote, 0, strpos($footnote, "</div>")+6);

		$originalFootnote = $footnote;
		
		$footnote = str_replace("rel=\"external\"", "rel=\"external\" target=\"_blank\"", $footnote);
		$body = str_replace($originalFootnote, $footnote, $body);
		
		if (strpos($footnote, "<p>")!=true)
		{
			$originalFootnote = $footnote;
			$footnote = str_replace("<div class=\"footnotes\">", "<div class=\"footnotes\"><p>", $footnote);
			$footnote = str_replace("</div>", "</p></div>", $footnote);
			$body = str_replace($originalFootnote, $footnote, $body);			
			echo "Footnote modified in doc for missing <p> tag\r\n";
		}
		
		if ($footnote!=$originalFootnote)
			echo "Footnote modified in doc for target=_blank\r\n";
	}
	return $body;
}

function ReplaceCMSBugs($body)
{
	$links = array(
		"<h2>Disclosed material and Report</h2>"=>"<h2>Disclosed Material and Report</h2>",
		"| Home Office</title>"=>"| Hillsborough Independent Panel</title>",
		"?v=1\"" => "\"",
		"http://drugsstaging.homeofficeweb.gws.gsi.gov.uk/" => "/",
		"http://drugsstaging.homeoffice.gov.uk/" => "/",
		"http://drugs.homeoffice.gov.uk/" => "/",
		"http://webarchive.nationalarchives.gov.uk/+/http://www.homeoffice.gov.uk/" => "/",
		"http://old.homeoffice.gov.uk/" => "/",
		"http://drugs.homeoffice.gov.uk/" => "/",
		"http://hillsborough.disclosure/" => "/",
		"http://hillsborough-disclosure/" => "/",
		"http://commercialstaging.homeofficeweb.gws.gsi.gov.uk/" => "/",
		"http://hillsborough.independent.gov.uk/" => "/",
		"http://hillsborough.disclosure.il0/" => "/",
		"3860.css\"" => ".css\"",
		"http://webarchive.nationalarchives.gov.uk/+/http://www.homeoffice.gov.uk/static/hip/favicon.ico" => "/favicon.ico",
		"http://webarchive.nationalarchives.gov.uk/+/http://www.homeoffice.gov.uk/" => "/",
		"<a href=\"/site-map\">" => "<a href=\"/site-map.html\">",
		"<input type=\"text\" placeholder=\"Search everything\" autocomplete=\"off\" list=\"search-list\">" =>
			"<input name=\"rows\" type=\"hidden\" value=\"10\" />\r\n" . 
			"<input name=\"fq\" type=\"hidden\" value=\"-hip_outofscope_reason:['' TO *]\" />\r\n" . 
			"<input name=\"q\" type=\"text\" placeholder=\"Search everything\" autocomplete=\"off\" list=\"search-list\" />\r\n",
	
		"<h1><a href=\"/\">Hillsborough Independent Panel<br /><span class=\"slogan\">Disclosure and Report</span></a></h1>" => 
			"<a href=\"/\">\r\n" . 
			"<hgroup class=\"clearfix\">\r\n" .
			"<h1>Hillsborough Independent Panel</h1>\r\n" .
			"<h2>Disclosure and Report</h2>\r\n" .
			"</hgroup>\r\n" .
			"</a>\r\n",
		"<form method=\"get\" action=\"/search/\" role=\"search\">" =>
			"<form method=\"get\" action=\"/search/select\" role=\"search\">",
		"<li>\r\n				\r\n			\r\n			<a href=\"/guides/\">Guides to key topics</a>\r\n			\r\n			<!-- \"Report\" items within HIP will not have child navigation so this section will be ignored, BUT child Chapter pages will be expanded -->\r\n			\r\n				\r\n				\r\n			\r\n			\r\n			</li>\r\n" =>
			"",
		"<a href=\"/the-independent-panel/panel-terms-reference\">Panel terms of reference</a>" =>
			"<a href=\"/the-independent-panel/panel-terms-reference.html\">Panel terms of reference</a>",
		"<P>[ES99_REC]</P>" => "<div class=\"recommendation\">",
		"<P>[/ES99_REC]</P>" => "</div>",
		"[ES99_STR]" => "<span class=\"strike_through\">",
		"[/ES99_STR]" => "</span>",
		"ihatetimvandamme" => "",
		"<script src=\"/static/hip/js/mylibs/jquery.lightbox-0.5.js\"></script>" => "",
		"<a href=\"/repository/report/HIP_report.pdf\" alt=\"Download the Report\">Download the Report" => "<a href=\"/repository/report/HIP_report.pdf\" alt=\"Download the Report\" target=\"_blank\">Download the Report",
		"<a href='/repository/report/HIP_report_main_report.pdf'>" => "<a href=\"/repository/report/HIP_report_main_report.pdf\" target=\"_blank\">",
		"<a href='/repository/report/HIP_report_appendices.pdf'>" => "<a href=\"/repository/report/HIP_report_appendices.pdf\" target=\"_blank\">",
		"<a href='/repository/report/HIP_report_summary.pdf'>" => "<a href=\"/repository/report/HIP_report_summary.pdf\" target=\"_blank\">",
		"<a class=\"gallery\" href=\"#\">\r\n		                      <img class=\"gallery\" alt=\"Bishop James Jones&#039; signature\" src=\"http://drugs.homeoffice.gov.uk/images/bishops-signature\">\r\n		                   </a>" => 
			"<img class=\"gallery\" alt=\"Bishop James Jones&#039; signature\" src=\"http://drugs.homeoffice.gov.uk/images/bishops-signature\">"
		
/*		"\"index.html\"" 					=> "\"/index.html\"",		
		"\"browse/index.html\"" 			=> "\"/browse/index.html\"",		
		"\"guides/guide-one1/index.html\"" 	=> "\"/guides/guide-one1/index.html\"",		
		"\"report/chapter-1.html\"" 		=> "\"/report/chapter-1.html\"",		
		"\"report/chapter-2.html\"" 		=> "\"/report/chapter-2.html\"",		
		"\"report/chapter-3.html\"" 		=> "\"/report/chapter-3.html\"",		
		"\"report/chapter-4.html\"" 		=> "\"/report/chapter-4.html\"",		
		"\"report/chapter-5.html\"" 		=> "\"/report/chapter-5.html\"",		
		"\"catalogue/index.html\"" 			=> "\"/catalogue/index.html\"",		
		"\"about-panel/\"" 					=> "\"/about-panel/index.html\"",		
		"\"about-panel/index.html\"" 		=> "\"/about-panel/index.html\"",		
		"\"catalogue-process/index.html\"" 	=> "\"/catalogue-process/index.html\"",			
		"\"chapter-"=> "\"/report/chapter-",
		"\"guides/\"" => "\"/guides/\"",	
		"/about-panel/member1/13.html" => "/advancedsearch/",
		"/contact-us1/" => "/contact-us/",
		"/glossary1/" => "/glossary/",
		"/site-map1" => "/site-map",
		"/help1/" => "/help/",
		"/website-accessibility1/" => "/website-accessibility/",
		"/terms-conditions1/" => "/terms-conditions/",
		"/help/#pdf" => "/help/index.html#pdf",
		"//webarchive.nationalarchives.gov.uk/+/http://www.homeoffice.gov.uk/" => "/",
		"\\/catalogue/" => "/catalogue/",
		"\"guides/index.html" => "\"/guides/index.html",
*/	

		// Fix CMS content
		//////////////////
/*		"\"guide-one1/index.html\"" => "\"/guides/guide-one1/index.html\"",
		"\"guide-two1/index.html\"" => "\"/guides/guide-two1/index.html\"",
		"\"guide-three/index.html\"" => "\"/guides/guide-three/index.html\"",
		"\"guide-four/index.html\"" => "\"/guides/guide-four/index.html\"",
		"\"guide-five/index.html\"" => "\"/guides/guide-five/index.html\"",
		"\"guide-six/index.html\"" => "\"/guides/guide-six/index.html\"",
		"\"guide-seven/index.html\"" => "\"/guides/guide-seven/index.html\"",
		"\"guide-eight/index.html\"" => "\"/guides/guide-eight/index.html\"",
		"\"955359/index.html\"" => "\"/guides/955359/index.html\"",
		"\"955361/index.html\"" => "\"/guides/955361/index.html\"",
		"\"page-two/index.html\"" => "\"/report/chapter-4/page-two/index.html\"",
		"\"chapter-1/index.html\"" => "\"/report/chapter-1/index.html\"",
		"\"chapter-two2/index.html\"" => "\"/report/chapter-two2/index.html\"",
		"\"chapter-31/index.html\"" => "\"/report/chapter-31/index.html\"",
		"\"chapter-4/index.html\"" => "\"/report/chapter-4/index.html\"",
		"\"chapter-51/index.html\"" => "\"/report/chapter-51/index.html\"",
		"\"chapter-6/index.html\"" => "\"/report/chapter-6/index.html\"",
		"\"chapter-7/index.html\"" => "\"/report/chapter-7/index.html\"",
		"\"chapter-8/index.html\"" => "\"/report/chapter-8/index.html\"",
		"\"chapter-9/index.html\"" => "\"/report/chapter-9/index.html\"",
		"\"chapter-10/index.html\"" => "\"/report/chapter-10/index.html\"",
		"\"chapter-111/index.html\"" => "\"/report/chapter-111/index.html\"",
		"\"chapter-12/index.html\"" => "\"/report/chapter-12/index.html\"",
*/
	);

	foreach($links as $original => $replacement)
	{
		$body = str_replace($original, $replacement, $body);
	}
	
	$temp = preg_replace("/(?<=\\/images\\/)(.[^.]*?)(?=\\\")/i","$0.jpg", $body);
	
	if (empty($temp))
	{
		echo "Something def BAD!!!";
		var_dump($body, $temp);
		exit();
	}
	
	if ($temp!=$body)
	{
		echo "Change made resulting in \r\n\r\n";
//		echo $temp;
	}
	
	$body = $temp;
	
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

function TidyHTMLTitle($content)
{
	if (strpos($content, "<title>")!==false)
	{
		$start = strpos($content, "<title>");
		$end = strpos($content, "</title>")+8;
		$title = substr($content, $start, $end-$start);
		$newtitle = str_replace("\r", "", $title);
		$newtitle = str_replace("\n", "", $newtitle);
		$newtitle = str_replace("  ", " ", $newtitle);
		$content = str_replace($title,$newtitle, $content);
	}
	return $content;
}

function AddGoogleAnalyticsCode($content)
{
	if (strpos($content, "Google analytics")===false)
	{
		$gaCode = "<!-- Google analytics tracking code -->\r\n".
			"<script type=\"text/javascript\">\r\n".
			"var _gaq = _gaq || [];\r\n".
			"_gaq.push(['_setAccount', ga_tc]);\r\n".
			"_gaq.push(['_trackPageview']);\r\n".
			"(function() {\r\n".
			"var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;\r\n".
			"ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.' + ga_domain + '/ga.js';\r\n".
			"var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);})();\r\n".
			"</script>\r\n".
			"</head>";
			
		$content = str_replace("</head>",$gaCode, $content);		
	}
	
	return $content;

}

function RenameCSS($filename)
{
	global $BASE_PATH;
	if (file_exists($BASE_PATH . "static/hip/css/" . $filename . "3860.css"))
		rename($BASE_PATH . "static/hip/css/" . $filename . "3860.css", $BASE_PATH . "static/hip/css/" . $filename . ".css");	
}


$logfile = fopen(LOG_PATH.date("Ymd-His", time()). ".log", "w");
logit("App and CMS content processing", $logfile);
logit("==============================", $logfile);

RenameCSS("2x");
RenameCSS("480"); 
RenameCSS("768"); 
RenameCSS("992"); 
RenameCSS("1382"); 
RenameCSS("print"); 
RenameCSS("style"); 


$BASE_PATH = $argv[1];
$filenames = ListFiles($BASE_PATH);
if (count($filenames)!=0)
{
	$processingCount=0;
	$ignoreCount=0;
	$ignoreCount=0;

	$expectedURLs = ProcessFileList($filenames);
	$brokenLinks = array();

	foreach($filenames as $filename)
	{
		if ((substr($filename, strlen($filename)-4, 4)=="html")||(substr($filename, strlen($filename)-3, 3)=="css"))
		{
			logit("processing: " . $filename, $logfile);
			$processingCount++;
			
			$original = file_get_contents($filename);
			
			$content = $original;
			
			// Tidy and fix HTML
			$content = ReplaceCMSBugs($content);
			$content = TidyHTMLTitle($content);
			$content = AddGoogleAnalyticsCode($content);
			$content = FootnoteProcessing($content);
				
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
	
	logit("=>    Summary processed: " . $processingCount, $logfile);
	logit("=>      Summary ignored: " . $ignoreCount, $logfile);
	logit("=>    Summary processed: " . $processingCount, $logfile);
	logit("=> Summary broken links: " . count($brokenLinks), $logfile);
	
	foreach($brokenLinks as $link)
	{
		logit("detected broken link: " . $link, $logfile);
	}
}
else
{
	logit("No files found to process", $logfile);
}

fclose($logfile);
?>
<?php



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

function FootnoteProcessing($body)
{
	$start = strpos($body, "<div class=\"footnotes\">"); 
	if ($start!==false)
	{		
		$footnote = substr($body, $start);
		$footnote = substr($footnote, 0, strpos($footnote, "</div>")+6);

		$regex = "/([a-z][a-z][a-z][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]0001.pdf)/i";

		if ($c=preg_match_all ($regex, $footnote, $matches))
		{
			// regex returned a doc id
			return $matches[0];
		}
		else
		{
			return null;
		}
	}
	return null;
}

function FootnoteProcessingLinkErrors($chapter, $page, $body)
{
	$start = strpos($body, "<div class=\"footnotes\">"); 
	if ($start!==false)
	{		
		$footnote = substr($body, $start);
		$footnote = substr($footnote, 0, strpos($footnote, "</div>")+6);

		//$regex = "/([a-z][a-z][a-z][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]0001)/i";
		$regex = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>"; 
				
		if ($c=preg_match_all("/$regex/siU",$footnote, $matches, PREG_SET_ORDER))
		{
			//var_dump($matches);
			//exit;

			foreach($matches as $match)
			//for($i = 0; $i<count($matches[0]); $i++)
			{
				
				echo "\"" . $chapter . "\",\"" . $page . "\",\"" . $match[2] . "\",\"" . $match[3] . "\",";
				
				$regex = "/([a-z][a-z][a-z][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]0001)/i";

				$valid = true;
				
				if ($c=preg_match_all ($regex, $match[2], $url))
				{
					if (isset($url[0][0]))
						echo "\"" . $url[0][0] . "\",";
					else 
						$valid = false;
						
				}
				else
				{
					echo "\"\",";
					$valid = false;
				}
					

				if ($c=preg_match_all ($regex, $match[3], $display))
				{
					if (isset($display[0][0]))
						echo "\"" . $display[0][0] . "\",";
					else 
						$valid = false;
				}
				else
				{
					echo "\"\",";
					$valid = false;
				}
								
				
				if ((isset($display[0][0]))&&(isset($url[0][0])))
				{
					if ($display[0][0]!=$url[0][0])
					{
						echo "\"ERROR\"";
					}
					elseif (!$valid)
					{
						echo "\"Visual check recommended\"";
					}
					else
					{
						echo "\"Ok\"";
					}
				}				
				else
					echo "\"Contains no doc IDs\"";
				
				
				echo "\r\n";
					
			}
		}
		else
		{
			return null;
		}
	}
	return null;
}


function ProcessFileList($basepath, $filenames)
{
	$urls = array();
	foreach($filenames as $filename)
	{
		$urls[] = str_replace($basepath, "", $filename);
	}
	return $urls;
}


$filenames = ListFiles($argv[1]);
if (count($filenames)!=0)
{
	$processingCount=0;
	$ignoreCount=0;
	$ignoreCount=0;

	$expectedURLs = ProcessFileList($argv[1], $filenames);
	$brokenLinks = array();


	//echo "\"chapter\",\"page\",\"url\",\"link text\",\"docid in link\",\"docid in display\",\"match\"\r\n";
	foreach($filenames as $filename)
	{
		if ((substr($filename, strlen($filename)-4, 4)=="html")||(substr($filename, strlen($filename)-3, 3)=="css"))
		{
			//echo "processing: " . $filename . "\r\n";
			$processingCount++;
			
			$original = file_get_contents($filename);
			
			$tf = str_replace($argv[1], "", $filename);
			$tf = str_replace("/index.html", "", $tf);
			$tf = substr($tf, strpos($tf, "chapter"));
			
			if (strpos($tf, "/")==true)
				$tf = substr($tf, 0, strpos($tf, "/"));

			$tp = str_replace($argv[1], "", $filename);
			$tp = str_replace("/index.html", "", $tp);
			$tp = substr($tp, strpos($tp, "page"));
			
			if (strpos($tp, "/")==true)
				$tp = substr($tp, 0, strpos($tp, "/"));
			
				
			
			$links = FootnoteProcessing($original);
			//FootnoteProcessingLinkErrors($tf, $tp, $original);
			
			if ($links!=null)
			{
				$tf = str_replace($argv[1], "", $filename);
				$tf = str_replace("/index.html", "", $tf);
				$tf = substr($tf, strpos($tf, "chapter"));
				
				if (strpos($tf, "/")==true)
					$tf = substr($tf, 0, strpos($tf, "/"));
				
				foreach($links as $link)
				{
					$tl = str_replace(".pdf", "", $link);
					echo $tl . "," . $tf . "\r\n";
				} 
			}
						
		}
		else
		{
			$ignoreCount++;
		}
		
	}
	
	
}
else
{
	logit("No files found to process", $logfile);
}

?>
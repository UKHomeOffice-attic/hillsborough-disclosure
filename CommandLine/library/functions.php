<?php


function dateformat($input)
{

	$date = explode("/", $input);
	$returntime = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
														   
	return $returntime;
}



function foreach_loop($array) {  
global $buff;  

	foreach ($array as $key => $value ) {  
     if (!is_array($value)) { // if it isn't an array show $key and $value  
             $buff .= '   ' . $key; 
                $buff .= '  ->  ' . $value; 
       } else {  // if it is an array -> show $key -> then process $value again will same function. 
                $buff .= '<strong>' . $key.'</strong>';  
               foreach_loop($value);  
       }  
    }  
 }  
 
 
function writelog($logdata, $logfile = "builder", $logtype = 'info')
{
	$writeline = "[" . date("Y-m-d H:m:s", time()) . "] [$logtype] $logdata\r\n";
		
	$fsave = fopen("log/" . $logfile . ".log", 'a');
	fwrite($fsave, $writeline);
	fclose($fsave);
}
 
 
function nameConvert($name)
{
	$no = array (" ", ":", "'", "&", ".", ",", "/", ";", "(", ")", "[NEWLINE]", "[", "]", "’");
	$yes = array("-", "",  "",  "",  "",  "",  "-", "",  "",  "",  "",          "",  "",  "");
	$name = str_replace($no, $yes, trim(strtolower($name)));
	
	if (strlen($name) > 100)
	{
		$name = substr($name, 0, 100);
	}
	
	return $name;
}

function phaseConvert($phase)
{
	$no = array(":", "'", "&");
	$yes = array(" ", "", "and");
	
	$phase = str_replace($no, $yes, $phase);
	
	return $phase;
}
 
function pageBuild($pagedata, $pagedir, $dironly = FALSE, $filename = "index.html", $cat = FALSE, $breadcrumb)
{
	global $dir;	
	
	$head = ($cat == TRUE) ? file_get_contents("templates/head_cat.php") : file_get_contents("templates/head.php");
	$foot = file_get_contents("templates/foot.php");
	

	foreach ($breadcrumb as $b)
	{
		$bcrumb .= " &#62; ";
		if ($b[1] != "")
		{
			$bcrumb .= "<a href=\"" . $b[1] . "\">" . $b[0] . "</a>";
		}
		else
		{
			$bcrumb .= $b[0];
		}
		
	} 
	

	
	
	$head = str_replace("[breadcrumb]", $bcrumb, $head);

	
	if (!file_exists($dir . $pagedir))
	{
		
		if (!mkdir($dir . $pagedir, 0, TRUE))
		{
			writelog("Failed to create directory: $dir$pagedir", 'builder', 'error');
		}
		else
		{
			writelog("Created directory: $dir$pagedir");
		}
	}
	
	if (!$dironly)
	{	
		$pagedata = $head . $pagedata . $foot;
		
		
		
		$fsave = fopen($dir . $pagedir . "/" . $filename, 'w');
		if (!fwrite($fsave, $pagedata))
		{
			writelog("Failed to create page: $dir$pagedir/$filename", 'builder', 'error');
		}
		else
		{
			writelog("Created page: $dir$pagedir/$filename");
		}
		fclose($fsave);
	}
}

function rmdir_r ( $dir, $DeleteMe = TRUE )
{
	if ( ! $dh = @opendir ( $dir ) ) return;
	while ( false !== ( $obj = readdir ( $dh ) ) )
	{
		if ( $obj == '.' || $obj == '..') continue;
		if ( ! @unlink ( $dir . '/' . $obj ) ) rmdir_r ( $dir . '/' . $obj, true );
	}
	
	closedir ( $dh );
	if ( $DeleteMe )
	{
		@rmdir ( $dir );
	}
}

function convert_ascii($string)
{
	$string = str_replace("\r\n", " ", $string);
		 
	// Replace Single Curly Quotes
	$search[] = chr(226).chr(128).chr(152);
	$replace[] = "'";
	$search[] = chr(226).chr(128).chr(153);
	$replace[] = "'";
	
	// Replace Smart Double Curly Quotes
	$search[] = chr(226).chr(128).chr(156);
	$replace[] = '"';
	$search[] = chr(226).chr(128).chr(157);
	$replace[] = '"';
	
	// Replace En Dash
	$search[] = chr(226).chr(128).chr(147);
	$replace[] = '--';
	
	// Replace Em Dash
	$search[] = chr(226).chr(128).chr(148);
	$replace[] = '---';
	
	// Replace Bullet
	$search[] = chr(226).chr(128).chr(162);
	$replace[] = '*';
	
	// Replace Middle Dot
	$search[] = chr(194).chr(183);
	$replace[] = '*';
	
	// Replace Ellipsis with three consecutive dots
	$search[] = chr(226).chr(128).chr(166);
	$replace[] = '...';
	
	    
	// Apply Replacements
	$string = str_replace($search, $replace, $string);
	
	// Remove any non-ASCII Characters
	$string = preg_replace("/[^\x01-\x7F]/","", $string);

  	$string = str_replace(
 		array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
 		array("'", "'", '"', '"', '-', '--', '...'), 
	 	$string);

	// Next, replace their Windows-1252 equivalents.
 	$string = str_replace(
 	array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
 	array("'", "'", '"', '"', '-', '--', '...'),
 	$string);
 	
	return $string;
}

// find a doc id within some other text (3 alpha chars followed by 12 numeric of which last 4 = "0001")
function getAllDocumentIds( $txt )
{
	//original: $regex = "/([a-z])([a-z])([a-z])(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)/is";
	$regex = "/([a-z][a-z][a-z][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]0001)/i";
	
	if ($c=preg_match_all ($regex, $txt, $matches))
	{
		// regex returned a doc id
		return $matches[0];
	}
	else
	{
		return null;
	}
}

function convert_smartquotes($string)
{
  	$string = str_replace(
 		array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
 		array("'", "'", '"', '"', '-', '--', '...'), 
	 	$string);

	// Next, replace their Windows-1252 equivalents.
 	$string = str_replace(
 		array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
 		array("'", "'", '"', '"', '-', '--', '...'),
 		$string);
 	
	return $string;
}



// return true if $str ends with $sub
function endsWith( $str, $sub ) 
{
	return ( substr( $str, strlen( $str ) - strlen( $sub ) ) == $sub );
}

?>
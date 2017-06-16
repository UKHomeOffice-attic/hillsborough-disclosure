<?php

class Hillsborough_Functions
{
	public function __construct()
	{
		
	}
	
	// date format conversion from unix timestamp
	static public function convertDate( $date, $datescope = 0 )
	{	
		return date("d F Y", $date);
	}
	
	//KH: Modified version to allow for wildcard dates being supplied
	static public function convertDateWithWildcard( $date, $accuracy )
	{	
		if ($accuracy == "month") // just month/year
		{
			return date("F Y", $date);
		}
		else if ($accuracy == "year") // just year
		{
			return date("Y", $date);
		}
		else if ($accuracy == "none") // no associated date
		{
			return "No associated date";
		}
		else
		{
			return date("d F Y", $date);
		}
	}
	
	// directory name conversion
	static public function nameConvert($name)
	{
		$no = array(" ", ":", "'", "&", ".", ",", "/", ";", "(", ")", "=");
		$yes = array("-", "", "", "", "", "", "", "", "", "", "");
		$name = str_replace($no, $yes, trim(strtolower($name)));
		
		if (strlen($name) > 100)
		{
			$name = substr($name, 0, 100);
		}
		
		return $name;
	}
	
	// date query conversion
	static public function convertDateQuery( $date )
	{
		$parts = explode("-", $date);
		return mktime( 0, 0, 0, $parts[1], $parts[0], $parts[2]);
	}
	
	// find a doc id within some other text (3 alpha chars followed by 12 numeric)
	static public function findDocumentId( $txt )
	{
		//original: $regex = "/([a-z])([a-z])([a-z])(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)(\\d)/is";
		$regex = "/([a-z][a-z][a-z][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]0001)/i";
		
		if ($c=preg_match_all ($regex, $txt, $matches))
		{
			// regex returned a doc id
			return $matches[0][0];
		}
		else
		{
			return null;
		}

		//echo "\r\n\r\n\r\nKHDEBUG<br>\r\n";
		//var_dump($txt, $matches);
		//exit(-1);
	}
	
	// find a doc id within some other text (3 alpha chars followed by 12 numeric of which last 4 = "0001")
	static public function getAllDocumentIds( $txt )
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
	
	// sort array of objects used by series
	static public function sortSeriesArray( $array )
	{
		foreach ( $array as $key => $value )
		{
			$b[$key] = strtolower( $value->getOrdering() );
		}
		
		asort( $b );
		
		foreach ( $b as $key => $value )
		{
			if ($array[$key]->getOrdering() == "All other documents")
			{
				$other = $array[$key];
			}
			else
			{
				$c[] = $array[$key];
			}
		}
		
		if (isset($other)) // if there was an 'all other documents', this should be at the end of the list so we'll tack it on here
		{
			$c[] = $other;
		}
		
		return $c;
	}
	
	// sort array of objects used by series
	static public function sortArray( $array )
	{
		foreach ( $array as $key => $value )
		{
			$b[$key] = strtolower( $value->getTitle() );
		}
		
		asort( $b );
		
		foreach ( $b as $key => $value )
		{
			if ($array[$key]->getTitle() == "All other documents")
			{
				$other = $array[$key];
			}
			else
			{
				$c[] = $array[$key];
			}
		}
		
		if (isset($other)) // if there was an 'all other documents', this should be at the end of the list so we'll tack it on here
		{
			$c[] = $other;
		}
		
		return $c;
	}
}

?>
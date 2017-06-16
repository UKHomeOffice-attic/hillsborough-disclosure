<?php

class Hillsborough_Directory
{
	public function __construct()
	{
		
	}
	
	// creates valid dirname
	static public function rewriteDirectory( $dir )
	{
		$badchar = array(" ", ".", "?");
		$goodchar = array("-", "", "");
		
		return strtolower(str_replace($badchar, $goodchar, $dir));
	}
}

?>
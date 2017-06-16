<?php

require_once("library/database.php");
require_once("library/hillsboroughlog.php");
require_once("library/schema.php");
require_once("library/config.php");
require_once("library/functions.php");

function Commit()
{
	$server = SEARCH_SERVER;
	$response = shell_exec( "curl " . $server . "/solr/update --data-binary \"<commit/>\" -H \"Content-type:application/xml\"" );
	//echo "[". $server ."]". "[". $response . "]";
	$xml = new SimpleXMLElement($response);
	$return = $xml->xpath("/response/lst/int");
	return "Committed solr index with return code " . trim($return[0]) . " and quey took " . trim($return[1]). "ms.";
}

function Optimise()
{
	$server = SEARCH_SERVER;
	$response = shell_exec("curl " . $server . "/solr/update?optimize=true");
	$xml = new SimpleXMLElement($response);
	$return = $xml->xpath("/response/lst/int");
	$msg = "Optimised solr index with return code " . trim($return[0]) . " and quey took " . trim($return[1]). "ms.";	
}


Optimise();
Commit();

?>
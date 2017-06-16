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
$db = new Database( DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS );

echo "Truncating search index\r\n";

$server = SEARCH_SERVER;
$response = shell_exec("curl " . $server . "/solr/update --data-binary \"<delete><query>*:*</query></delete>\" -H \"Content-type:application/xml\"");
$xml = new SimpleXMLElement($response);
$return = $xml->xpath("/response/lst/int");
echo "Deleted solr index with return code " . trim($return[0]) . " and query took " . trim($return[1]). "ms.\r\n";

Commit();
Optimise();
Commit();

echo "Truncating search index completed\r\n";

?>
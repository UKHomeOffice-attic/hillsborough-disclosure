<?php

require_once("library/database.php");
require_once("library/hillsboroughlog.php");
require_once("library/schema.php");
require_once("library/config.php");
require_once("library/functions.php");

$checkscope = $db->dbFetch("SELECT out_of_scope_lookup FROM outofscopelookup");

$db = new Database( DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS );

foreach($checkscope as $row)
{
	var_dump($row);
	exit();
//	$oldUrl = $rowp
	
}

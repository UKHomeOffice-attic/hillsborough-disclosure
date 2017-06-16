<?phprequire_once("library/database.php");
require_once("library/hillsboroughlog.php");
require_once("library/schema.php");
require_once("library/config.php");
require_once("library/functions.php");

if (!isset($argv[1]))
{
	echo "No params supplied (expecting filename) so exiting.\r\n";
	exit();
}$seriesOrderFile = $argv[1];
$db = new Database( DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS );echo "Importing \"Series ordering\" from ".$seriesOrderFile." from " . $seriesOrderFile . "\r\n";
$importtype = "series-ordering";$importarray = array('owning_organisation','series_title', 'sub_series_title', 'archive_ref_id', 'order');
$start_time = microtime(true);$row = 0;
$buildrow = 0;
$build = array();


// start csv import
if (($handle = fopen($seriesOrderFile, "r")) !== FALSE) 
{
	while (($data = fgetcsv($handle, 0, ",")) !== FALSE) 
	{		
		if ($row++ > 0 && $data[0] != '')
		{				foreach ($data as $key => $value)
			{
				$build[$buildrow][$importarray[$key]] = trim($value);
			}
			$buildrow++;
		}
	}
}
else
{
	die("File not found");
}$counter = array();$lastFolder = "";foreach ($build as $row)
{		if ($lastFolder!=$row['series_title'])	{		if (isset($counter[$row['owning_organisation']]))			$counter[$row['owning_organisation']]++;		else 			$counter[$row['owning_organisation']]=1;							//we need to insert just the folder ref with no sub-folder due to the naff design for folders/sub-folders				$sql = "UPDATE serieslookup SET archive_ref_id = '".$row['archive_ref_id']."', " .				"archiveorder = " . $counter[$row['owning_organisation']] . " " . 				"WHERE owning_organisation = '".addslashes($row['owning_organisation'])."'" .				"AND   series_title = '".addslashes($row['series_title'])."'" . 				"AND series_sub_title = ''";				if ($db->dbUpdate($sql))		{			echo "Added top level folder " . $row['series_title'] . "\r\n";		}				$lastFolder = $row['series_title'];			}		$sql = "SELECT count(*) as recordsfound FROM serieslookup " . 			"WHERE owning_organisation = '".addslashes($row['owning_organisation'])."'" .			"AND   series_title = '".addslashes($row['series_title'])."'" . 			"AND series_sub_title = '".addslashes($row['sub_series_title'])."'";		$check = $db->dbFetch($sql, FALSE);		if ($check['recordsfound']=="0")	{		echo "No match found for folder [" . $row['series_title'] . "/" . $row['sub_series_title'] . "] of " . $row['owning_organisation'] . "\r\n";	}	else 	{				if (isset($counter[$row['owning_organisation']]))			$counter[$row['owning_organisation']]++;		else 			$counter[$row['owning_organisation']]=1;								$sql = "UPDATE serieslookup SET archive_ref_id = '".$row['archive_ref_id']."', " .				"archiveorder = " . $counter[$row['owning_organisation']] . " " . 				"WHERE owning_organisation = '".addslashes($row['owning_organisation'])."'" .				"AND   series_title = '".addslashes($row['series_title'])."'" . 				"AND series_sub_title = '".addslashes($row['sub_series_title'])."'";		
		if ($db->dbUpdate($sql))
		{
			echo "Added sub folder " . $row['sub_series_title'] . "\r\n";		}	}}
echo "Import completed in " . (microtime(true) - $start_time) . " : " . $db->getQueryCount() . " queries executed\r\n";
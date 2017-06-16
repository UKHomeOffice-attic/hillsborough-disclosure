<?php 
require_once("library/database.php");

$db = new Database( "localhost", "hillsborough", "root", "" );
	

function DoConfidenceCheck($title, $type, $dbName, $solrName)
{
	$items = GetLookupValueForType($type);
	foreach($items as $item)
	{
	
		$bDataSet = GetBrowseCount($item['id'], $dbName);
		$bMethod = sizeof($bDataSet);
		$solrResponse = GetSolrResult($item['id'], $solrName);
		$sMethod = $solrResponse['response']['numFound'];
	
		$result ="";
		if (intval($bMethod)!=intval($sMethod))
			$result = "ERROR records";
	
		echo $title."|";
		echo $item['presentation_format'] . "|";
		echo $bMethod . "|";
		echo $sMethod . "|";
		echo $result;
	
		if ($result!="")
		{
//			echo "[In SOLR but not DB: ";
			echo "|";
			
			foreach($bDataSet as $dbRow)
			{
				$found = false;
				foreach($solrResponse['response']['docs'] as $solrRecord)
				{
					if (trim($solrRecord['hip_uid'])==trim($dbRow['begin_doc_id']))
					{
						$found = true;
						break;
					}
				}
				if (!$found)
					echo $dbRow['begin_doc_id'] . ",";
			}
//			echo "]  In DB but not SOLR: [";
			echo "| ";
			foreach($solrResponse['response']['docs'] as $solrRecord)
			{
				$found = false;
				foreach($bDataSet as $dbRow)
				{
					if (trim($solrRecord['hip_uid'])==trim($dbRow['begin_doc_id']))
					{
						$found = true;
						break;
					}
				}
				if (!$found)
					echo $solrRecord['hip_uid'] . ",";
			}
			echo "]";
		}
		
		echo "\"\r\n";
	}
}

function GetSolrResult($id, $field)
{
	$url="http://hip.localhost/search/select?q=*:*&fq=" . $field . ":".$id."&fl=hip_uid&fq=-hip_outofscope_reason:[%22%22%20TO%20*]&rows=50000&indent=on&wt=json";
	$ch = curl_init($url);
	
	curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $res = curl_exec($ch);
	
    $solrRes = json_decode($res, true);
	curl_close($ch);

	return $solrRes;
}

function GetLookupValueForType($type)
{
	global $db;
	$sql = "SELECT id, presentation_format from autopopulatelookup where type = " . $type . " order by presentation_format asc";
	$data = $db->dbFetch($sql);
	return $data;	
}

function GetBrowseCount($id, $fieldName)
{
	$resultSet = array();
	global $db;
	$sql = "SELECT begin_doc_id from disclosed_material where " . $fieldName . " LIKE '%;".$id."(%' AND out_of_scope_reason = ''";
	$data = $db->dbFetch($sql);
	
	
	return $data;
}


/* START CHECKS */

echo "Type|Name|DB Count|SOLR Count|Result|In SOLR but not DB|In DB but not SOLR\r\n";
DoConfidenceCheck("Victim", 1, "ap_victim_name", "hip_victim");
DoConfidenceCheck("Organisation", 2, "ap_corporate_body", "hip_corporate");
DoConfidenceCheck("Person", 3, "ap_person", "hip_person");


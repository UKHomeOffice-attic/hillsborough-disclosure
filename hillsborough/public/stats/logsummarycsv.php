<?php 
require_once("database.php");


header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=file.csv");
header("Pragma: no-cache");
header("Expires: 0");


function outputCSV($data) {
    $outstream = fopen("php://output", "w");
    function __outputCSV(&$vals, $key, $filehandler) {
        fputcsv($filehandler, $vals); // add parameters if you want
    }
    array_walk($data, "__outputCSV", $outstream);
    fclose($outstream);
}

$db = new Database( "localhost", "hillsborough", "root", "" );
	
function GetErrorOutput()
{
	global $db;
	$sql = "SELECT eventtype, eventmessage, barcodeid FROM logging where eventtype in ('Error', 'Warning') order by eventtype, eventmessage, barcodeid";
	$data = $db->dbFetch($sql);
	return $data;	
}

?>



<?php 

$data =array();
$data[] = array("barcodeid", "error type","error message");
foreach(GetErrorOutput() as $code)
	$data[] = array($code["barcodeid"], $code["eventtype"],$code["eventmessage"]);

outputCSV($data);

exit();

?>


<?php 
require_once("database.php");

$db = new Database( "localhost", "hillsborough", "root", "" );
	

function GetErrorCodes()
{
	global $db;
	$sql = "SELECT eventtype, eventmessage FROM logging where eventtype in ('Error', 'Warning') group by eventmessage order by eventtype, eventmessage";
	$data = $db->dbFetch($sql);
	return $data;	
}

function getSQLResult($sql)
{
	global $db;
	$data = $db->dbFetch($sql);
	
	$html = "<table >\r\n";
	foreach($data as $datarow)
	{
		$html .= "<tr>\r\n";
		foreach($datarow as $field)
		{
			$html .= "<td>".$field."</td>\r\n";
		}
		$html .= "</tr>\r\n";		
	}
	$html .= "</table>\r\n";
	
	return $html;
}

function getSQLResultCSV($sql)
{
	global $db;
	$data = $db->dbFetch($sql);
	
	$html = "<table >\r\n";
	$html .= "<tr><td>\r\n";
	foreach($data as $datarow)
	{
		foreach($datarow as $field)
		{
			$html .= "".$field.", ";
		}
	}
	$html .= "</td></tr>\r\n";		
	$html .= "</table>\r\n";
	
	return $html;
}

?>

<!DOCTYPE html>
<head>
<title>Statistics</title>
<style>
table 
{

	border-style: solid;
	border-width: 1px;
	border-color: #000000;
	border-spacing: 0px;
}
td 
{
	border-right-style: solid;
	border-right-width: 1px;
	border-right-color: #000000;
	border-bottom-style: solid;
	border-bottom-width: 1px;
	border-bottom-color: #000000;
}
body
{
	font-family: Arial;
	font-size: 10px;
}

h1
{
	font-size: 18px;
}
h2
{
	font-size: 14px;
}
h3
{
	font-size: 12px;
}
	
</style>
</head>
<body>

<h1>Web build: Summary of log entries</h1>
<small>Created: <?php echo  date("d-m-Y  Gi", time());?></small>

<ul>
<li><a href="#1">Summary warning/error count</a></li>
<li><a href="#2">Warning/error by message count</a></li>
<?php 
$count=3;
$errorCodes = GetErrorCodes();
foreach($errorCodes as $code)
{
	echo "<li><a href=\"#".$count."\">".$code["eventtype"] . ": " . $code["eventmessage"]."</a></li>";
	$count++;
}

?>
</ul>

<h2>Import stats</h2>
<a name="1"></a>
<h3>Import Summary</h3>
<?php echo getSQLResult("SELECT eventtype, count(*) FROM logging where eventtype in ('Error', 'Warning') group by eventtype"); ?>
<a name="2"></a>
<h3>Error/Warning Summary</h3>
<?php echo getSQLResult("SELECT eventtype, eventmessage, count(*) FROM logging where eventtype in ('Error', 'Warning') group by eventtype, eventmessage order by eventtype, eventmessage"); ?>

<?php 
$count=3;
$errorCodes = GetErrorCodes();
foreach($errorCodes as $code)
{
	echo "<a name=\"".$count."\"></a>";
	echo "<h3>".$code["eventtype"] . ": ".$code["eventmessage"]."</h3>";
	echo getSQLResultCSV("SELECT barcodeid FROM logging where eventmessage = '".addslashes($code["eventmessage"]) . "' order by barcodeid ");
	$count++;
}
?>

</body>
</html>
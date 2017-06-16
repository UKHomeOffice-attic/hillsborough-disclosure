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

<h1>Web build statistics</h1>
<small>Created: <?php echo  date("d-m-Y  Gi", time());?></small>

<ul>
<li><a href="#1">Summary warning/error count</a></li>
<li><a href="#2">Warning/error by message count</a></li>
<li><a href="#3">Organisation material count</a></li>
<li><a href="#4">In/out of scope count</a></li>
<li><a href="#5">In/out of scope by organisation count</a></li>
<li><a href="#6">Redacted reasons</a></li>
<li><h2>Document errors</h2></li>

<?php 
$count=7;
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
<h2>Site stats</h2>
<a name="3"></a>
<h3>Organisations material counts</h3>
<?php echo getSQLResult("SELECT owning_organisation, count(*) FROM disclosed_material group by owning_organisation order by owning_organisation"); ?>
<a name="4"></a>
<h3>Material in/out of scope counts (blank is inscope)</h3>
<?php echo getSQLResult("SELECT formatted_outofscope, count(*) FROM disclosed_material group by formatted_outofscope order by formatted_outofscope"); ?>
<a name="5"></a>
<h3>Material in/out of scope counts by organisation (blank is inscope)</h3>
<?php echo getSQLResult("SELECT owning_organisation, formatted_outofscope, count(*) FROM disclosed_material group by owning_organisation, formatted_outofscope order by owning_organisation, formatted_outofscope"); ?>
<a name="6"></a>
<h3>Redacted summary</h3>
<?php echo getSQLResult("SELECT dm.owning_organisation, rg.redacted_group_name, count(*) FROM disclosed_material dm inner join redactedgroups rg on dm.redactedreason = rg.redacted_id group by dm.owning_organisation, dm.redactedreason order by dm.owning_organisation, dm.redactedreason"); ?>
<a name="6"></a>
<h3>Folder/Subfolder summary</h3>
<?php echo getSQLResult("SELECT owning_organisation, series_title, series_sub_title, count(*) FROM disclosed_material group by owning_organisation, series_title, series_sub_title order by owning_organisation, series_title, series_sub_title"); ?>

<?php 
$count=7;
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
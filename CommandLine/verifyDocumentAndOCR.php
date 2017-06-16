<?php

// Verify that the OCR (.txt) and PDF (.pdf) files corresponding to the disclosed_material.begin_doc_id entries exist
// Output (for entries with missing files only):
//				begin_doc_id,[no OCR],[no PDF]\n
//
// Assumptions: All begin_doc_ids should have both OCR and PDF files
// 				The file system is not case sensitive

require_once("library/database.php");
require_once("library/hillsboroughlog.php");
require_once("library/schema.php");
require_once("library/config.php");
require_once("library/functions.php");

$db = new Database( DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS );

$sql = "SELECT begin_doc_id FROM " . DISCLOSED_MATERIAL_TABLE . " WHERE out_of_scope_reason = \"\"" ;
$lookup = $db->dbFetch($sql);

$filecount = 0;
$missingcount = 0;

foreach($lookup as $row)
{
	$filecount++;
	$bdi = $row['begin_doc_id'];
	$ocr = false;
	$pdf = false;
	
	// Look for an OCR file
	if (file_exists(OCR_DATA_DIR . $bdi . ".txt"))
		$ocr = true;
	
	// Look for a PDF file
	if (file_exists(PDF_DIR . $bdi . ".pdf"))
		$pdf = true;

	// Report missing files
	if (!$ocr)
	{
		$missingcount++;
		if (!$pdf)
		{
			echo $bdi, ",no OCR,no PDF\n";
		}
		else
		{
			echo $bdi, ",no OCR,\n";
		}
	}
	elseif (!$pdf)
	{
		$missingcount++;
		echo $bdi, ",,no PDF\n";
	}
}

echo "\n", number_format($missingcount), " of ", number_format($filecount), " entries have missing OCR and/or PDF files.\n";
?>

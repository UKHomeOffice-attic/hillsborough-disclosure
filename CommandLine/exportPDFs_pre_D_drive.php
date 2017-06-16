<?php

//
// Copy the PDFs with Doc IDs that start with the specified prefixes to the PDF_OUTPUT directory.
// This script validates the PDFs against the contents of the disclosed_material database table.
//
// USAGE:
//		Pass space separated prefixes as parameters
//		e.g. php exportPDFs.php HOM SCC SYC
//		or call without any paramters to copy PDfs for all Doc IDs
//
// n.b. THIS SCRIPT DELETES ANY PRE-EXISTING FILES IN THE PDF_OUTPUT DIRECTORY!!!
//      *************************************************************************

define("PDF_OUTPUT_DIR1", "c:\\builds\\temp\\pdf1\\repository\\docs\\");			// Destination directory for expert-specific PDFs
define("PDF_OUTPUT_DIR2", "c:\\builds\\temp\\pdf2\\repository\\docs\\");			// Destination directory for expert-specific PDFs
define("PDF_OUTPUT_DIR3", "c:\\builds\\temp\\pdf3\\repository\\docs\\");			// Destination directory for expert-specific PDFs

// The first three-letter prefix to be copied to DIR2
define("PDF_CUTOFF1", "SYP000000010001");
define("PDF_CUTOFF2", "SYP000110990001");

require_once("library/config.php");
require_once("library/database.php");


$prefixes = null;

if ($argc > 1)
{
	$prefixes = array();
	
	for($i = 1; $i < $argc; $i++)
	{
		$prefixes[] = $argv[$i];
	}
}

export();


function export()
{
	global $prefixes;
	
	prepareOutputDirectory();

	$noPDFs = 0;
	$noMissingPDFs = 0;
	$noOutOfScopePDFs = 0;
	$noUnreferencedPDFs = 0;

	$db = new Database(DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS);

	if (isset($prefixes))
	{
		$sql = "SELECT `begin_doc_id`, `out_of_scope_reason` FROM `".DISCLOSED_MATERIAL_TABLE."` WHERE `begin_doc_id` LIKE '".implode($prefixes, "%' OR `begin_doc_id` LIKE '")."%' ORDER BY `begin_doc_id`";
	}
	else
	{
		$sql = "SELECT `begin_doc_id`, `out_of_scope_reason` FROM `".DISCLOSED_MATERIAL_TABLE."` ORDER BY `begin_doc_id`";
	}
	$fetchResults = $db->dbFetch($sql);
	$docs = array();
	foreach($fetchResults as $row) {
		$docs[$row["begin_doc_id"]] = trim($row["out_of_scope_reason"]);
	}

	$pdfs = fetchPDFs(PDF_DIR);

	// The following code relies on the two arrays being in alphabetical order (and $pdfs being contiguous)
	
	$pdf = reset($pdfs);
	while (list($doc, $OutOfScope) = each($docs))
	{
		while ($pdf && (strcasecmp($pdf, $doc) < 1))
		{
			// Report PDF without database entry
			echo "PDF without database entry: ", $pdf, "\r\n";
			$noUnreferencedPDFs++;
			
			$pdf = next($pdfs);
		}
		
		if ($pdf && (strcasecmp(substr($pdf, 0, -4), $doc) == 0))
		{
			if ($OutOfScope != "")
			{
				// Report PDF for out of scope doc
				echo "PDF for out of scope document: ", $pdf, "\r\n";
				$noOutOfScopePDFs++;
			}
			else
			{
				// Copy PDF to destination folder
				//echo "Copying: ", $pdf, "\r\n";
				if (strcasecmp(substr($pdf, 0, 15), PDF_CUTOFF1) < 0)
				{
					copy(PDF_DIR.$pdf, PDF_OUTPUT_DIR1.$pdf);
				}
				elseif (strcasecmp(substr($pdf, 0, 15), PDF_CUTOFF2) < 0)
				{
					copy(PDF_DIR.$pdf, PDF_OUTPUT_DIR2.$pdf);
				}
				else				
				{
					copy(PDF_DIR.$pdf, PDF_OUTPUT_DIR3.$pdf);
				}

				$noPDFs++;
			}
			
			$pdf = next($pdfs);
		}
		elseif ($OutOfScope == "")
		{
			// Report in scope database entry without PDF
			echo "Database entry without a PDF: ", $doc, "\r\n";
			$noMissingPDFs++;
		}
	}
	
	echo "\r\n";
	echo "Number of PDF files copied: $noPDFs\r\n";
	echo "Number of database entries missing PDF files: $noMissingPDFs\r\n";
	echo "Number of PDF files for out of scope database entries (not copied): $noOutOfScopePDFs\r\n";
	echo "Number of PDF files without database entries (not copied): $noUnreferencedPDFs\r\n";

}

function fetchPDFs($dir)
{
	global $prefixes;

	$filter = "typePDF";
	
	if (isset($prefixes))
	{
		$filter = "typeExpertPDF";
	}
	
	return (array_filter(scandir($dir), $filter));

}

function typePDF($value)
{
	// Return true if the file name ends with '.pdf'
	return (strcasecmp(substr($value, -4), ".pdf") == 0);
}

function typeExpertPDF($value)
{
	global $prefixes;

	// If it doesn't end with '.pdf' then we're not interested
	if (strcasecmp(substr($value, -4), ".pdf") != 0)
		return false;
		
	foreach($prefixes as $prefix)
	{
		if (strcasecmp(substr($value, 0, strlen($prefix)), $prefix) == 0)
			return true;
	}
	
	return false;
}

function prepareOutputDirectory()
{
	clearDirectory(PDF_OUTPUT_DIR1);
	clearDirectory(PDF_OUTPUT_DIR2);
	clearDirectory(PDF_OUTPUT_DIR3);
}

function clearDirectory($dir)
{
	if (!is_dir($dir))
	{
		mkdir($dir, 0777, true);
	}
	else
	{
		$dirContents = scandir($dir);
		foreach($dirContents as $file)
		{
			if (is_file($dir.$file))
			{
				unlink($dir.$file);
			}
		}
	}
}

?>

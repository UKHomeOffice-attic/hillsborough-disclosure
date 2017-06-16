<?php

// hillsborough disclosure build script
// config file

// db settings
define("DB_HOSTNAME", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "hillsborough");

// database tables
define("DISCLOSED_MATERIAL_TABLE", "disclosed_material"); // disclosed data
define("SERIES_TABLE", "series"); // series
define("ORGANISATIONS_TABLE", "organisations"); // organisations
define("SERIES_LOOKUP_TABLE", "serieslookup"); // series lookups - can't remember why this wasn't part of series now
define("OUTOFSCOPE_LOOKUP_TABLE", "outofscopelookup"); // out of scope lookups
define("AUTOPOPULATE_LOOKUP_TABLE", "autopopulatelookup"); // autopopulation lookups
define("OCR_TEXT_TABLE", "ocr_text"); // ocr text


// directories
define("EXTRACT_DATA_DIR", 'c:\\hillsborough_extracts\\csv\\'); // extracts from lextranet/br
define("AUTOPOPULATE_DATA_DIR", 'c:\\hillsborough_extracts\\autopopulate\\'); // autopopulation csv templates
define("OCR_DATA_DIR", 'c:\\hillsborough_extracts\\ocr\\'); // ocr text files
define("LOG_DIR", "C:\\IL2Delivery\\BuildHistory\\logs\\"); // log files
define("PDF_DIR", 'c:\\hillsborough_extracts\\pdf\\');
define("SEARCH_DATA_DIR", 'c:\\hillsborough_extracts\\searchdata\\'); // ocr text files

// search index
define("RECORDS_PER_FILE", 500); // how many rows per xml file

// servers
define("SEARCH_SERVER", "http://localhost:84");			// where SOLR is located 
define("REPO_SERVER", "/repository/"); 			// where the docs are located

?>
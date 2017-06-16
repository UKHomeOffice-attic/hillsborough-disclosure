<?php


require_once("library/database.php");
require_once("library/hillsboroughlog.php");
require_once("library/schema.php");
require_once("library/config.php");
require_once("library/functions.php");

define ("HIGH_CONF", "strong");
define ("MEDIUM_CONF", "average");
define ("LOW_CONF", "weak");
define ("NO_CONF", "no");
    
define ("DEBUG", FALSE);

$validConfs = array(HIGH_CONF, MEDIUM_CONF, LOW_CONF, NO_CONF);

$noTerms = 0;
    
if ($argc != 2)
{
	echo "Usage: importPeople.php Loookup_filename\r\n";
	exit();
}

echo "Importing Person data from ".$file."\r\n";

$db = new Database( DB_HOSTNAME, DB_NAME, DB_USER, DB_PASS );

processFile($argv[1]);

echo "Person data import complete\r\n";
echo "Number of look-up terms produced: ", $noTerms, "\r\n";
    
function processFile($file)
{
    global $db;
    global $noTerms;
    
    $type = 3;  // person
    
// For the 'match_' fields:
//      sn = surname, fn = first name, mn = middle name, fi = first initial, mi = middle initial, ti = title
    
    $importarray = array('id','full_title','presentation_format','url','surname','first_name','middle_name','title',
    'first_initial','middle_initial','role','blank','match_sn','match_sn_fn', 'match_sn_fn_mn','match_sn_fi',
    'match_sn_fi_mi','match_sn_ti','match_sn_fn_ti','match_sn_fi_ti','match_sn_fi_mi_ti','match_role'
    );
	

    $deleteSql = "DELETE FROM " . AUTOPOPULATE_LOOKUP_TABLE . " WHERE type = " . $type;
    if ($db->dbUpdate($deleteSql))
    {
        echo "Deleted existing records.\r\n";
    }


    $row = 0;
    $buildrow = 0;
    $build = array();
    
    $arrayKeys = array_flip($importarray);

    // start csv import
    if (($handle = fopen($file, "r")) !== FALSE)
    {
        while (($data = fgetcsv($handle, 0, ",")) !== FALSE)
        {
            // Skip the first two rows as they contain headings
            if ($row++ < 2)
            {
                continue;
            }
	
            if ($data[0] != '')
            {
                $vars[HIGH_CONF] = "";
                $vars[MEDIUM_CONF] = "";
                $vars[LOW_CONF] = "";				

                // Load data
				$surnames = explode(";", $data[$arrayKeys['surname']]);
				$forenames = explode(";", $data[$arrayKeys['first_name']]);
				$middlenames = explode(";", $data[$arrayKeys['middle_name']]);
				$firstinitials = explode(";", $data[$arrayKeys['first_initial']]);
				$middleinitials = explode(";", $data[$arrayKeys['middle_initial']]);
                $titles = explode(";", $data[$arrayKeys['title']]);
                $roles = explode(";", $data[$arrayKeys['role']]);

                // We need the surname for the old variants field (strips space from within input so not completely useless)
                $surnameOutput = "";
                iterateCols("addSurname", $surnameOutput, $surnames);
                
                // Process each match combination
				processFields("addSurname", $vars, $data[$arrayKeys['match_sn']], $surnames);
				processFields("addSurnameForename", $vars, $data[$arrayKeys['match_sn_fn']], $surnames, $forenames);
				processFields("addSurnameForenameMiddlename", $vars, $data[$arrayKeys['match_sn_fn_mn']], $surnames, $forenames, $middlenames);
				processFields("addSurnameFirstInitial", $vars, $data[$arrayKeys['match_sn_fi']], $surnames, $firstinitials);
				processFields("addSurnameFirstInitialMiddleInitial", $vars, $data[$arrayKeys['match_sn_fi_mi']], $surnames, $firstinitials, $middleinitials);
				processFields("addTitleSurname", $vars, $data[$arrayKeys['match_sn_ti']], $titles, $surnames);
                processFields("addTitleForenameSurname", $vars, $data[$arrayKeys['match_sn_fn_ti']], $titles, $forenames, $surnames);
				processFields("addTitleInitialSurname", $vars, $data[$arrayKeys['match_sn_fi_ti']], $titles, $firstinitials, $surnames);
				processFields("addTitleBothInitialsSurname", $vars, $data[$arrayKeys['match_sn_fi_mi_ti']], $titles, $firstinitials, $middleinitials, $surnames);
                processFields("addRoleToVariant", $vars, $data[$arrayKeys['match_role']], $roles, explode(";",$vars[MEDIUM_CONF]));
                processFields("addRoleToVariant", $vars, $data[$arrayKeys['match_role']], $roles, explode(";",$vars[LOW_CONF]));

                debugOut($data[$arrayKeys['surname']].":\r\n");
//				var_dump($vars);
				                
                
                $sql = "SELECT id FROM " . AUTOPOPULATE_LOOKUP_TABLE . " WHERE type = '$type' AND full_title = '" . addslashes($data[$arrayKeys['full_title']]) . "' LIMIT 0,1";
                
                try {
                    $check = $db->dbFetch($sql, FALSE);
                }
                catch(Exception $ex)
                {
                    var_dump($sql);
                    exit();
                }
                
                if (!empty($check)) // Either we have a duplicate entry in the input file or the table wasn't cleared
                {
                    var_dump($check);
                    die("Error! Row already exists in the ". AUTOPOPULATE_LOOKUP_TABLE ." table\r\n");
                }

                $sql = "INSERT INTO " . AUTOPOPULATE_LOOKUP_TABLE . " (id, type, full_title, presentation_format, lookup_variants, url_name, description, high_variants, medium_variants, low_variants) VALUES ( NULL, '$type', '" . addslashes(convert_ascii($data[$arrayKeys['full_title']])) . "', '" . addslashes(convert_ascii($data[$arrayKeys['presentation_format']])) . "', '" . addslashes(convert_ascii($surnameOutput)) . "', '" . nameConvert(convert_ascii($data[$arrayKeys['full_title']])) . "', NULL,'" .  addslashes(convert_ascii($vars[HIGH_CONF])) . "','" . addslashes(convert_ascii($vars[MEDIUM_CONF])) . "','" . addslashes(convert_ascii($vars[LOW_CONF])) . "')";

                if ($db->dbUpdate($sql))
                {
                    echo "Added lookup data for ", $data[$arrayKeys['full_title']], "\r\n";
                }
                
                // Record the number of look-up terms
                $temp = explode(";", $vars[HIGH_CONF]);
                $noTerms += count($temp);
                $temp = explode(";", $vars[MEDIUM_CONF]);
                $noTerms += count($temp);
                $temp = explode(";", $vars[LOW_CONF]);
                $noTerms += count($temp);
            }
        }
    }
    else
    {
        die("Error! File '" . $file . "' not found.\r\n");
    }

}

//
// Establish whether the variants are for high, medium or low match confidence
//
function processFields($func, &$vars, $match, $fields1, $fields2 = null, $fields3 = null, $fields4 = null)
{
	global $validConfs;
	
	$matchConf = strtolower(trim($match));
	if (in_array($matchConf, $validConfs))
	{
        debugOut("Match confidence: ".$matchConf."\r\n");
		if ($matchConf != NO_CONF)
		{
			iterateCols($func, $vars[$matchConf], $fields1, $fields2, $fields3, $fields4);
		}
	}
	else
	{
		echo "Error! Unrecognised match confidence '", $matchConf, "'\r\n";
	}
}

//
// Loop through each entry for each relevant field
//
function iterateCols($func, &$var, $fields1, $fields2 = null, $fields3 = null, $fields4 = null)
{
	debugOut($func.",");
	
	foreach($fields1 as $field1)
	{
		if (!isset($fields2))
		{
			$func($var, trim($field1));
		}
		else
		{
			foreach($fields2 as $field2)
			{
				if (!isset($fields3))
				{
					$func($var, trim($field1), trim($field2));
				}
				else
				{
					foreach($fields3 as $field3)
					{
						if (!isset($fields4))
						{
							$func($var, trim($field1), trim($field2), trim($field3));
						}
						else
						{
							foreach($fields4 as $field4)
							{
								$func($var, trim($field1), trim($field2), trim($field3), trim($field4));
							}
						}
					}
				}
			}
		}
	}
	
    debugOut($var."\r\n");
}


// Grey
function addSurname(&$var, $sn)
{
	if (!empty($sn))
	{
		if (!empty($var))
		{
			$var .= ";";
		}
		$var .= $sn;
	}
}


// Charles Grey
// Grey Charles
// Grey, Charles
// Grey,Charles
function addSurnameForename(&$var, $sn, $fn)
{
	if (!empty($sn) && !empty($fn))
	{
		if (!empty($var))
		{
			$var .= ";";
		}
		$var .= $fn." ".$sn . ";";
		$var .= $sn." ".$fn . ";";
		$var .= $sn.", ".$fn . ";";
		$var .= $sn.",".$fn;
	}
}

// Charles Henry Grey
// Grey Charles Henry
// Grey, Charles Henry
// Grey,Charles Henry
function addSurnameForenameMiddleName(&$var, $sn, $fn, $mn)
{
	if (!empty($sn) && !empty($fn) && !empty($mn))
	{
		if (!empty($var))
		{
			$var .= ";";
		}
		$var .= $fn." ".$mn." ".$sn . ";";
		$var .= $sn." ".$fn." ".$mn . ";";
		$var .= $sn.", ".$fn." ".$mn . ";";
		$var .= $sn.",".$fn." ".$mn;
	}
}

// C Grey
// C.Grey
// C. Grey
// Grey C
// Grey, C
// Grey,C
function addSurnameFirstInitial(&$var, $sn, $fi)
{
    if (!empty($sn) && !empty($fi))
    {
        if (!empty($var))
        {
            $var .= ";";
        }
        $var .= $fi." ".$sn . ";";
        $var .= $fi.".".$sn . ";";
        $var .= $fi.". ".$sn . ";";
        $var .= $sn." ".$fi . ";";
        $var .= $sn.", ".$fi . ";";
        $var .= $sn.",".$fi;
    }
}

// C H Grey
// Grey C H
// Grey, C H
// Grey,C H
// C.H.Grey
// Grey C.H
// Grey, C.H
// Grey,C.H
// C.H. Grey
// C.H Grey
// CH Grey
// Grey CH
// Grey, CH
// Grey,CH
function addSurnameFirstInitialMiddleInitial(&$var, $sn, $fi, $mi)
{
    if (!empty($sn) && !empty($fi) && !empty($mi))
    {
        if (!empty($var))
        {
            $var .= ";";
        }
        $var .= $fi." ".$mi." ".$sn.";";
		$var .= $sn." ".$fi." ".$mi.";";
		$var .= $sn.", ".$fi." ".$mi.";";
		$var .= $sn.",".$fi." ".$mi.";";
		
        $var .= $fi.".".$mi.".".$sn.";";
		$var .= $sn." ".$fi.".".$mi.";";
		$var .= $sn.", ".$fi.".".$mi.";";
		$var .= $sn.",".$fi.".".$mi.";";
		
        $var .= $fi.".".$mi.". ".$sn.";";
        $var .= $fi.".".$mi." ".$sn.";";
        
        $var .= $fi.$mi." ".$sn.";";
		$var .= $sn." ".$fi.$mi.";";
		$var .= $sn.", ".$fi.$mi.";";
		$var .= $sn.",".$fi.$mi;
    }
}

// Early Grey
// Grey Earl
// Grey, Earl
// Grey,Earl
function addTitleSurname(&$var, $ti, $sn)
{
    if (!empty($sn) && !empty($ti))
    {
        if (!empty($var))
        {
            $var .= ";";
        }
        $var .= $ti." ".$sn . ";";
		$var .= $sn." ".$ti . ";";
		$var .= $sn.", ".$ti . ";";
		$var .= $sn.",".$ti;
    }
}

// Earl Charles Grey
// Charles Grey,Earl
// Charles Grey, Earl
function addTitleForenameSurname(&$var, $ti, $fn, $sn)
{
    if (!empty($sn) && !empty($fn) && !empty($ti))
    {
        if (!empty($var))
        {
            $var .= ";";
        }
        $var .= $ti." ".$fn." ".$sn . ";";
        $var .= $fn." ".$sn.",".$ti . ";";
        $var .= $fn." ".$sn.", ".$ti;
    }
}

// Earl C Grey
// C Grey,Earl
// C Grey, Earl
// Earl C. Grey
// C. Grey,Earl
// C. Grey, Earl
// Earl C.Grey
// C.Grey,Earl
// C.Grey, Earl
function addTitleInitialSurname(&$var, $ti, $fi, $sn)
{
    if (!empty($sn) && !empty($fi) && !empty($ti))
    {
        if (!empty($var))
        {
            $var .= ";";
        }
        $var .= $ti." ".$fi." ".$sn . ";";
        $var .= $fi." ".$sn.",".$ti . ";";
        $var .= $fi." ".$sn.", ".$ti . ";";
        $var .= $ti." ".$fi.". ".$sn . ";";
        $var .= $fi.". ".$sn.",".$ti . ";";
        $var .= $fi.". ".$sn.", ".$ti . ";";
        $var .= $ti." ".$fi.".".$sn . ";";
        $var .= $fi.".".$sn.",".$ti . ";";
        $var .= $fi.".".$sn.", ".$ti;
    }
}

// Earl C H Grey
// C H Grey,Earl
// C H Grey, Earl
// Earl C. H. Grey
// C. H. Grey,Earl
// C. H. Grey, Earl
// Earl C.H.Grey
// C.H.Grey,Earl
// C.H.Grey, Earl
// Earl C.H. Grey
// C.H. Grey,Earl
// C.H. Grey, Earl
// Earl C.H Grey
// C.H Grey,Earl
// C.H Grey, Earl
// Earl CH Grey
// CH Grey,Earl
// CH Grey, Earl
function addTitleBothInitialsSurname(&$var, $ti, $fi, $mi, $sn)
{
    if (!empty($sn) && !empty($fi) && !empty($mi) && !empty($ti))
    {
        if (!empty($var))
        {
            $var .= ";";
        }
        $var .= $ti." ".$fi." ".$mi." ".$sn . ";";
        $var .= $fi." ".$mi." ".$sn.",".$ti . ";";
        $var .= $fi." ".$mi." ".$sn.", ".$ti . ";";
        $var .= $ti." ".$fi.". ".$mi.". ".$sn . ";";
        $var .= $fi.". ".$mi.". ".$sn.",".$ti . ";";
        $var .= $fi.". ".$mi.". ".$sn.", ".$ti . ";";
        $var .= $ti." ".$fi.".".$mi.".".$sn . ";";
        $var .= $fi.".".$mi.".".$sn.",".$ti . ";";
        $var .= $fi.".".$mi.".".$sn.", ".$ti . ";";
        $var .= $ti." ".$fi.".".$mi.". ".$sn . ";";
        $var .= $fi.".".$mi.". ".$sn.",".$ti . ";";
        $var .= $fi.".".$mi.". ".$sn.", ".$ti . ";";
        $var .= $ti." ".$fi.".".$mi." ".$sn . ";";
        $var .= $fi.".".$mi." ".$sn.",".$ti . ";";
        $var .= $fi.".".$mi." ".$sn.", ".$ti . ";";
        $var .= $ti." ".$fi.$mi." ".$sn . ";";
        $var .= $fi.$mi." ".$sn.",".$ti . ";";
        $var .= $fi.$mi." ".$sn.", ".$ti;
    }
}

// Role Name_Variants
// Name_Variants Role
// Name_Variants, Role
// Name_Variants,Role
function addRoleToVariant(&$var, $role, $variant)
{
    if (!empty($role) && !empty($variant))
    {
        if (!empty($var))
        {
            $var .= ";";
        }
		$var .= $role." ".$variant . ";";
		$var .= $variant." ".$role . ";";
		$var .= $variant.", ".$role . ";";
		$var .= $variant.",".$role;
    }
}

function debugOut($string)
{
    if (DEBUG)
    {
        echo $string;
    }
}
    
?>

<?php

    
if ($argc != 3)
{
	echo "Usage: php updateMasterlist.php masterlist_file.csv updates_file.csv\r\n";
	exit();
}


processFiles($argv[1], $argv[2]);

    
function processFiles($masterlist, $updates)
{
    echo "Applying changes in ", $updates, " to ", $masterlist, "\r\n\r\n";

    $noUpdates = 0;
    $outputFile;
    if (strcasecmp(substr($masterlist, -4), ".csv") == 0)
    {
        $outputFile = substr($masterlist,0 , -4) . "_new.csv";
        
    }
    else
    {
        $outputFile = $masterlist . "_new.csv";
    }
 
    // Read updates
    $ups = array();
    if (($uHandle = fopen($updates, "r")) !== FALSE)
    {
        while (($uRow = fgetcsv($uHandle, 0, ",")) !== FALSE)
        {
            $ups[] = $uRow;
        }
    }
    else
    {
        die("Error! Updates file '" . $updates . "' not found.\r\n");
    }
    fclose($uHandle);
        
    if (($oHandle = fopen($outputFile, "w")) !== FALSE)
    {
        if (($mHandle = fopen($masterlist, "r")) !== FALSE)
        {
            if (($fieldNames = fgetcsv($mHandle, 0, ",")) === FALSE)
            {
                die("Error! Masterlist file '" . $masterlist . "' is empty.\r\n");                
            }
            
            // Convert update field names to indices
            foreach($ups as $uInd => $uRow)
            {
                $match = false;
                foreach($fieldNames as $fInd => $field)
                {
                    if ($field == $uRow[1])
                    {
                        $ups[$uInd][1] = $fInd;
                        $match = true;
                        break;
                    }
                }
                if (!$match)
                {
                    die("Error! Unable to match field '".$uRow[1]."'\r\n");
                }
            }

            $oRow = array();
            foreach($fieldNames as $mField)
            {
                $oRow[] = "\"".str_replace("\"", "\"\"", $mField)."\"";
            }
            fwrite($oHandle, implode($oRow, ",")."\r\n");

            while (($mRow = fgetcsv($mHandle, 0, ",")) !== FALSE)
            {
                $oRow = array();
                unset($index);
                unset($value);

                foreach($mRow as $mInd => $mField)
                {
                    if ($mInd == 0)
                    {
                        foreach($ups as $uRow)
                        {
                            if ($uRow[0] == $mField)
                            {
                                $index = $uRow[1];
                                $value = $uRow[2];
                                break;
                            }
                        }
                    }

                    if ((isset($index) && ($index == $mInd)))
                    {
                        if (isset($value))
                        {
                            $oRow[] = "\"".str_replace("\"", "\"\"", $value)."\"";
                            echo "Updating ", $mRow[0], " - replacing '",$mField,"' with '",$value,"'\r\n";
                            $noUpdates++;
                        }
                        else
                        {
                            die("Error! No value for '".$mRow[0]."'\r\n");
                        }
                    }
                    else
                    {
                        $oRow[] = "\"".str_replace("\"", "\"\"", $mField)."\"";
                    }
                }
                fwrite($oHandle, implode($oRow, ",")."\r\n");
//                fputcsv($oHandle, $oRow, ",");    // Didn't use this as I want the output to match the input (i.e. lots of quotes)
            }
//            fwrite($oHandle, "");   // Add a blank line

        }
        else
        {
            fclose($oHandle);
            die("Error! Masterlist file '" . $masterlist . "' not found.\r\n");
        }
    }
    else
    {
        die("Error! Unable to open temp file '" . $outputFile . "' for output.\r\n");
    }

    fclose($mHandle);
    fclose($oHandle);
    
    echo "\r\nSuccess! ", $noUpdates, " updates available in the new file: ", $outputFile, "\r\n\r\n";
}
    
?>

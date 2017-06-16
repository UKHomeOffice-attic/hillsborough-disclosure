<?php

define("CSV_DELIMITER", ",");
define("CSV_FIELD_WRAPPER", "\"");

class masterlist
{
	protected $columns = array("Begin Doc ID","Out of Scope Reason");
	protected $colNums = array();
	protected $rows = array();
	
	public function __construct($file, $prefixes = null)
	{
		$handle = fopen($file, "rb");

		if (!$handle)
		{
			die ("Error: Unable to open $file\r\n");
		}

		// Read the first line of the masterlist file
		if (($buffer = fgetcsv($handle, 32768, ",", "\"")) === false)
		{
			die ("Error: Unable to read 1st line of data from $file\r\n");
		}
		
		// Look for the named columns
		for($i = 0; $i < count($this->columns); $i++)
		{
			$this->colNums[$i] = $this->getNumberOfColumn($buffer, $this->columns[$i]);
		}

		// Load the data corresponding to those columns
		while (($buffer = fgetcsv($handle, 32768, ",", "\"")) !== false)
		{
			$key = $buffer[$this->colNums[0]];

			$matched = false;
			if (isset($prefixes))
			{
				// Check to see if the key matches any of the specified prefixes
				foreach($prefixes as $prefix)
				{
					if (strcmp(substr($key, 0, strlen($prefix)), $prefix) == 0)
					{
						// We've got one
						$matched = true;
						break;
					}
				}
			}
			else
			{
				$matched = true;
			}
			
			if ($matched)
			{
				$row = array();
				$row[0] = $key;
				for($i = 1; $i < count($this->colNums); $i++)
				{
					$row[$i] = $buffer[$this->colNums[$i]];
				}
				$this->rows[] = $row;
			}
		}
		
		if (!feof($handle))
		{
			echo "Error: Unexpected fgets() fail for $file\r\n";
			fclose($handle);
			return false;
		}
	
		fclose($handle);
	}

	public function inScopeDocs()
	{
		function isInScope($value) {
			return (strlen(trim($value[1])) < 1);
		}
		
		return array_filter($this->rows, "isInScope");
	}

	public function outOfScopeDocs()
	{
		function isOutOfScope($value) {
			return (strlen(trim($value[1])) > 0);
		}
		
		return array_filter($this->rows, "isOutOfScope");
	}
	
	public function inMasterlist($docID)
	{
		foreach($this->rows as $row)
		{
			if (strcmp($row[0], $docID) == 0)
				return true;
		}
		
		return false;
	}
	
	protected function getNumberOfColumn($row, $colName)
	{
		for ($i = 0; $i < count($row); $i++)
		{
			if ($row[$i] == $colName)
				return $i;
		}

		return FALSE;
	}	
}

?>
<?php

// database class
class Database 
{	
	private $connection = false;
	protected $query_count;
	
	function __construct($db_hostname, $db_name, $db_user, $db_pass)
	{
		$connection = mysql_connect($db_hostname, $db_user, $db_pass);
		
		if ($connection === false) 
		{
			die("There was a problem connecting to mysql: " . mysql_error());
		}
		
		$db_connect = mysql_select_db($db_name);
		
		if ($db_connect === false) 
		{
			die("There was a problem connecting to database $db_name: " . mysql_error());
		}
		
		$this->connection = $connection;
		$this->query_count = 0;
	}
	
	function __destroy() 
	{
		mysql_close($this->connection);
	}

	// db query function. assumes any data from the user has been cleaned
	public function dbFetch($sql, $return_array = TRUE) 
	{
		if (!$db_query = mysql_query($sql))
		{
			throw new Exception("Query failed: " . mysql_error());
		}
		
		$this->query_count++;
		
		if ($return_array === FALSE) 
		{
			$result = mysql_fetch_assoc($db_query);
		} 
		else 
		{
			$result = array();
			while ($line = mysql_fetch_array($db_query, MYSQL_ASSOC))	
			{
				$result[] = $line;
			}
		}
		
		mysql_free_result($db_query);
		
		return ($result);			
	}
	
	// xss/sql injection protection
	public function clean($input) 
	{
		if (!is_numeric($input))
		{
			$return = mysql_real_escape_string(strip_tags($input));
		}
		
		return $return;
	}
	
	// for updating db/queries which don't return a result set
	public function dbUpdate($sql, $return_id = FALSE) 
	{
		if ($this->connection === false) 
		{
			$this->db_connect();
		}
	
		if (!$db_query = mysql_query($sql))
		{
			throw new Exception("Query failed: " . mysql_error());
		}
		
		$this->query_count++;
		
		if ($return_id)
		{
			return mysql_insert_id();
		}
		else
		{
			return $db_query;
		}
	}
	
	public function getQueryCount()
	{
		return $this->query_count;
	}
}


?>
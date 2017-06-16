<?php

// Hillsborough log class, for import and error logging
class HillsboroughLog
{
	protected $log_type;
	protected $log_location;
	protected $logged;
	
	public function __construct( $log_type, $log_location )
	{
		$this->log_type = $log_type;
		$this->log_location = $log_location;
		$this->log_file = $log_type . date("YmdHms", time());
		$this->logged = 0;
		
		// write log initialised
		$this->write("Log initialised");
	}
	
	public function write( $data ) 
	{
		$writeline = "[" . date("Y-m-d H:M:s", time()) . "] " . $data . "\r\n";
		
		$fsave = fopen($this->log_location . $this->log_file . ".log", 'a');
		fwrite($fsave, $writeline);
		fclose($fsave);
		
		$this->logged++;
	}
	
	public function getLoggedItems()
	{
		return $this->logged;
	}
}

?>
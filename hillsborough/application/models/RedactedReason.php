<?php

// document series
class Application_Model_RedactedReason extends Zend_Db_Table_Abstract 
{
	public function getByID($id)
	{
		$query = "SELECT redacted_group_name FROM redactedgroups WHERE redacted_id = " . $id;
		$redactedreason = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);	
		return $redactedreason[0]->redacted_group_name;
	}		
}


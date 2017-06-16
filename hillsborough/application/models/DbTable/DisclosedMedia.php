<?php

// disclosed material table holds all of the material data, including out of scope reasons.
class Application_Model_DbTable_DisclosedMedia extends Zend_Db_Table_Abstract
{
    protected $_name = 'MediaFiles';
	protected $return;
	protected $order;
	protected $documentid;
	protected $query;
	protected $startdate;
	protected $enddate;
	protected $rowreturn;
	

	public function get($id)
	{
		
		$dbselect = new Zend_Db_Select( $this->getAdapter() );

		//$documents = $dbselect->from("MediaFiles")
		//					  ->where("document_id = '" . $id . "'");
							  
		$documents = $this->fetchRow("document_id = '" . $id . "'"); 
							  
		if (!$documents) 
		{
			throw new Exception("Fail to get media document"); 
		}
		
		return $documents;
	}
	
}
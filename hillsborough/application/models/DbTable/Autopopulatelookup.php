<?php

class Application_Model_DbTable_Autopopulatelookup extends Zend_Db_Table_Abstract
{
    protected $_name = 'autopopulatelookup';
	protected $type;
	protected $query;
	protected $lookups = array();
	protected $order = "";
	
	protected function buildQuery()
	{
		$this->query = "type = '" . $this->type . "'";
	}
	
	public function setOrder( $order )
	{
		$this->order = $order;
	}
	
	public function setType( $type )
	{
		$this->type = $type;
	}
	
	public function setLookupIds( $ids )
	{
		$lookups = explode(";", $ids);
		
		foreach ($lookups as $l)
		{
			if (!empty($l))
			{
				$pos = strpos($l, "(");
				if ($pos === FALSE) {
					$this->lookups[] = $l;
				} else {
					$this->lookups[] = substr($l, 0, $pos);
				}
			}
		}
	}

	public function queryFullTitles()
	{
		
		/*$dbselect = new Zend_Db_Select( $this->getAdapter() );
		$documents = $dbselect->from("autopopulatelookup")
							  ->where($this->query)
							  ->order($this->order);
		*/
		//$documents = $this->fetchAll($this->query);
		
		$query = "SELECT presentation_format, url_name FROM autopopulatelookup WHERE type = '" . $this->type . "' ORDER BY presentation_format ASC";
		$titles = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);
		
		return $titles;
	}
	
	public function getLookupData( $lookup )
	{
		$query = "SELECT * FROM autopopulatelookup WHERE type = '" . $this->type . "' AND url_name = '" . addslashes($lookup) . "' LIMIT 0,1";
		$lookup = $this->getAdapter()->fetchRow($query, array(), Zend_Db::FETCH_OBJ);

		return $lookup;
	}
	
	public function getNames()
	{
		$query = "SELECT * FROM autopopulatelookup WHERE type = '" . $this->type . "' AND id IN (" . implode(',', $this->lookups) . ")";
		$return = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);
		
		//echo $query;
		
		return $return;
	}

}


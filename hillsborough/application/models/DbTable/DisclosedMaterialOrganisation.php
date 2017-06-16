<?php

class Application_Model_DbTable_DisclosedMaterialOrganisation extends Zend_Db_Table_Abstract
{

    protected $_name = 'disclosed_material';
	protected $organisation;
	protected $series;
	
	public function __construct( $organisation )
	{
		$this->organisation = $organisation;
		parent::__construct();
	}
	
	public function getSeries()
	{
		$query = "SELECT DISTINCT(series_title) FROM disclosed_material WHERE owning_organisation = '" . $this->organisation . "' ORDER BY series_title";
		$this->series = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);
	}
}


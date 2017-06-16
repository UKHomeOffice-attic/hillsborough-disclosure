<?php

// organisations lookup table
class Application_Model_DbTable_Organisations extends Zend_Db_Table_Abstract
{
    protected $_name = 'organisations';

	public function getOrganisation($orgname)
	{
		$row = $this->fetchRow("dirname = '" . $orgname . "'");
		if (!$row) {
			throw new Exception("Could not find organisation $orgname");
		}
		
		return $row->toArray();
	}

	public function getOrganisationByDisplayName($orgname)
	{
		$row = $this->fetchRow("owning_organisation = '" . addslashes($orgname) . "'");
		if (!$row) {
			throw new Exception("Could not find organisation $orgname");
		}
		
		return $row->toArray();
	}
	
	public function getAllOrganisations()
	{
		//$documents = $this->fetchAll();
		$query = "SELECT distinct o.owning_organisation as owning_organisation, o.description, o.dir_name, o.non_contributing " .
			"FROM organisations o ORDER BY o.owning_organisation ASC";
		$documents = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);
		
		return $documents;
	}

	public function getAllOrganisationsWithDisclosedMaterial()
	{
		//NB: Only include non_contrib 2's, not 1.  2's are displayed but with no link as a "see also" reference.
		$query = 
			"SELECT distinct o.owning_organisation as owning_organisation, o.description, o.dir_name, o.non_contributing " . 
			"FROM organisations o " . 
			"inner join disclosed_material d on o.owning_organisation = d.owning_organisation and d.out_of_scope_reason = '' " . 
			"where o.non_contributing = 0 " . 
			"union " .
			"SELECT distinct concat(t.owning_organisation, ' (', t.short_title, ')') as owning_organisation, t.description, t.dir_name, t.non_contributing " . 
			"FROM organisations t " . 
			"where t.non_contributing = 2 " .  
			"order by owning_organisation asc";
			
		$documents = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);
		
		return $documents;
	}

	public function getAllOrganisationsWithDisclosedMaterialForExpertLaptop()
	{
		// As per the getAllOrganisationsWithDisclosedMaterial method but without the non contributing Contributors
		$query = 
			"SELECT distinct o.owning_organisation as owning_organisation, o.description, o.dir_name, o.non_contributing " . 
			"FROM organisations o " . 
			"inner join disclosed_material d on o.owning_organisation = d.owning_organisation and d.out_of_scope_reason = '' " . 
			"where o.non_contributing = 0 " . 
			"order by owning_organisation asc";
			
		$documents = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);		
		return $documents;
	}
	
    /**
     * get the "out of scope" organisations to be displayed on the out of scope org landing page
     */	
	public function getOutOfScopeOrganisation($id)
	{
		$query = "SELECT unique_id, owning_organisation, description, non_disclosed_summary FROM organisations WHERE unique_id = '" . $id . "'";		
		$documents = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);	
		return $documents;
	}
	
}


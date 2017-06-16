<?php

// document series
class Application_Model_ReportStructure extends Zend_Db_Table_Abstract 
{
	public function getReportStructure()
	{

 		$query = 
 				"SELECT Title, LextranetRef, LevelID, path, ParentID, GroupID " . 
			   	"FROM reportstructure " . 
			   	"order by ordering asc ";
		$structure = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);	
		return $structure;
	}		
	
	public function getTopLevel()
	{		
 		$query = 
 				"SELECT idreportstructure, Title, LextranetRef, LevelID, path, ParentID, GroupID " . 
			   	"FROM reportstructure " . 
 			   	"WHERE parentID=-1 " . 
			   	"order by ordering asc ";
		$structure = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);	
		return $structure;
	}

	public function getChildren($id)
	{		
 		$query = 
 				"SELECT idreportstructure, Title, LextranetRef, LevelID, path, ParentID, GroupID, style " . 
			   	"FROM reportstructure " . 
 			   	"WHERE parentID=" . $id . " " . 
			   	"order by ordering asc ";
		$structure = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);	
		return $structure;
	}

	public function getItemDetails($path)
	{		
 		$query = 
 				"SELECT idreportstructure, Title, LextranetRef, LevelID, path, ParentID, GroupID " . 
			   	"FROM reportstructure " . 
 			   	"WHERE path='" . addslashes($path) . "' " . 
			   	"order by ordering asc ";
		$structure = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);	
		return $structure;
	}
	
}


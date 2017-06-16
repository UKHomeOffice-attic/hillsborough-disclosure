<?php

// disclosed material table holds all of the material data, including out of scope reasons.
class Application_Model_DbTable_Folders extends Zend_Db_Table_Abstract
{
	public function getOutOfScopeFolder($docID)
	{
		$sql = "SELECT begin_doc_id, archive_ref_id, short_title, description, series_title, sub_series_title, formated_outofscope FROM out_of_scope_folders where begin_doc_id = '" . $docID . "'";
		$record = $this->getAdapter()->fetchAll($sql, array(), Zend_Db::FETCH_OBJ);
		return $record[0];
	}
}
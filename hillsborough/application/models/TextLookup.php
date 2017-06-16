<?php

class TextLookup extends Zend_Db_Table_Abstract
{
	public function getText($fieldName)
	{
		$query = "SELECT field_value FROM Lookup_Text WHERE field_name = '" . addslashes($fieldName) . "'";
		$displayText = $this->getAdapter()->fetchRow($query, array(), Zend_Db::FETCH_OBJ);
		return $displayText;
	}
	
}
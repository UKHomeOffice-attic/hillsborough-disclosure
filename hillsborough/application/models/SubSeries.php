<?php

// document sub-series - extends all methods from series
class Application_Model_SubSeries extends Application_Model_Series
{
	protected $_name = 'disclosed_material';

	public function __construct( $organisation, $title, $url_title, $date_start, $date_end, $series_reference, $series_description, $archive_ref_id = NULL, $ordering = NULL, $out_of_scope = FALSE )
	{
/*
var_dump($organisation);
echo "title\r\n";
var_dump($title);
echo "url title\r\n";
var_dump($url_title);
echo "start\r\n";
var_dump($date_start);
echo "end\r\n";
var_dump($date_end);
echo "series ref\r\n";
var_dump($series_reference);
echo "series desc\r\n";
var_dump($series_description);
echo "archive ref\r\n";
var_dump($archive_ref_id);
echo "ordering\r\n";
var_dump($ordering);
echo "out of scope\r\n";
var_dump($out_of_scope);		
die("The End");
*/

// The parent constructor doesn't accept $date_start and $date_end parameters!
//		parent::__construct($organisation, $title, $url_title, $date_start, $date_end, $series_reference, $series_description, $archive_ref_id, $ordering, $out_of_scope, TRUE);
		parent::__construct($organisation, $title, $url_title, $series_reference, $series_description, $archive_ref_id, $ordering, $out_of_scope, TRUE);
		
		$this->archiveid = $archive_ref_id;
		$this->archiveorder = $ordering;
		$this->title = $title;
		$this->url = $url_title;
		$this->date_start = $date_start;
		$this->date_end = $date_end;
		$this->series_reference = $series_reference;
		$this->description = $series_description;
		$this->out_of_scope = $out_of_scope;
	}
	
	public function getDocumentCount($org, $seriesTitle)
	{
		// get any sub series
		$query = 	"SELECT count(*) as count FROM disclosed_material d " . 
					"LEFT JOIN serieslookup s ON d.sub_series_lookup = s.id " . 
					"WHERE d.owning_organisation = '" . addslashes($org) . "' " . 
					"AND d.series_title = '" . addslashes($seriesTitle) . "' " . 
					"AND d.series_sub_title = '" . addslashes($this->title) . "' " . 
					"AND out_of_scope_reason = ''";

		$results = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);
		return $results[0]->count;		
	}

/*	public function getOrdering()
	{
		if (!isset($this->archiveorder))
			return $this->title;
		return $this->archiveorder;
	}
*/
}


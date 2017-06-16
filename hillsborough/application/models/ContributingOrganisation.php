<?php

// organisations lookup table
class Application_Model_ContributingOrganisation extends Zend_Db_Table_Abstract implements IteratorAggregate 
{
	protected $_name = 'organisations';
	protected $title;
	protected $short_title;
	protected $description;
	protected $series = array();
	protected $lextranet_title;
	protected $non_contributing;
	protected $orgID;
	
	public function __construct( $organisation_id )
	{
		
		parent::__construct();
		
		$row = $this->fetchRow("dir_name = '" . $organisation_id . "'");
		if (!$row) 
		{
			throw new Exception("Could not find organisation $orgname");
		}
		
		$this->orgID = $organisation_id;
		
		// set up a few things here
		$this->title = $row->owning_organisation;
		$this->short_title = $row->short_title;
		$this->description = $row->description;
		$this->lextranet_title = $row->lextranet_title;		
		$this->non_contributing = $row->non_contributing;
	}
	
	public function querySeries()
	{
		//Check to see what series_titles exist for an org which contain disclosed material
		$query = "SELECT s.series_title, s.url_title, s.archiveorder, s.archive_ref_id  FROM serieslookup s " . 
				 "inner join disclosed_material d on s.owning_organisation = d.owning_organisation " . 
				 	"and s.series_title = d.series_title and d.out_of_scope_reason='' " . 
				 "WHERE s.owning_organisation = '".addslashes($this->title)."' " . 
				 "and s.url_title!='' group by s.series_title ORDER BY s.archiveorder, s.series_title";
		$series_results = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);	
		
		//KH: Depreciated as no longer handle OOS series from Lextranet so not in disclosed or series table
		//get another list of series from the series lookup table, as this will include any which were out of scope and had no disclosed material
		//$query = "SELECT DISTINCT(series_title), description FROM series WHERE type = 1 AND owning_organisation = '" . addslashes($this->title) . "' AND series_title NOT IN (SELECT DISTINCT(series_title) FROM disclosed_material WHERE owning_organisation = '" . addslashes($this->title) . "') ORDER BY series_title";
		//$series_lookup_results = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);	
		
		foreach ($series_results as $result)
		{
			$query = "SELECT description, series_sub_title FROM series WHERE series_title = '" . addslashes($result->series_title) . "' AND type = 1 LIMIT 0,1";
			$series_detail = $this->getAdapter()->fetchRow($query, array(), Zend_Db::FETCH_OBJ);
			
			if (!empty($series_detail))
			{
				$series_reference = $series_detail->series_sub_title;
				$series_description = $series_detail->description;
			}
			else
			{
				$series_reference = "";
				$series_description = "";
			}

			// create an array of series objects, which will be passed with the contributing org object
			$this->series[] = new Application_Model_Series ( $this, $result->series_title, $result->url_title, $series_reference, $series_description, $result->archive_ref_id, $result->archiveorder );			
		}

		if (!empty($this->series)) 
		{
			// sort the series array by the title
//			var_dump($this->title);
//			exit;
			
			//KH: CHIP005 Reorder series based on rule yet to be determined
			$this->series = Hillsborough_Functions::sortSeriesArray( $this->series );			
//			$this->series = Hillsborough_Functions::sortArray( $this->series );			

		}

		
	}
	
	public function getTitle()
	{
		return $this->title;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	public function getUrl()
	{
		return $this->orgID;
	}
	
	public function getShortTitle()
	{
		return $this->short_title;
	}
	
	public function getContributing()
	{
		if ($this->non_contributing == 1)
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	public function getLextranetTitle()
	{
		return $this->lextranet_title;
	}
	
	public function getIterator() 
	{  
        return new ArrayIterator( $this->documents );  
    } 
	
	public function getSeries()
	{
		return $this->series;
	}
	
	public function addSeries( Series $series )
	{
		$this->series[] = $series;
	}

}


<?php

// document series
class Application_Model_Series extends Zend_Db_Table_Abstract implements IteratorAggregate
{
	protected $title;
	protected $_name = 'disclosed_material';
	protected $sub_series = array();
	
	protected $url; 
	protected $archiveorder;
	protected $archiveid;
	protected $date_start;
	protected $date_end;
	protected $series_reference;
	protected $description;
	protected $out_of_scope;
	

	public function __construct( Application_Model_ContributingOrganisation $organisation, $title, $url, $series_reference, $description, $archive_ref_id = NULL, $ordering = NULL, $out_of_scope = FALSE, $isSubSeries = FALSE)
	{
		parent::__construct();
//echo "Constructing a series: $title <br>\r\n";		
		$this->archiveid = $archive_ref_id;
		$this->archiveorder = $ordering;
		
		$this->title = $title;
		$this->url = $url;
		$this->series_reference = $series_reference;
		$this->description = $description;
		$this->out_of_scope = $out_of_scope;

		// Had to add this bodge as it was looping if a contributor had a subseries with the same name as a series (e.g. Not Applicable for the Football Foundation)
		if (!$isSubSeries)
		{
			// get any sub series		
			$query = 
			"SELECT s.series_sub_title, s.url_title, s.archiveorder, s.archive_ref_id " . 
			"FROM serieslookup s " .
			"INNER JOIN disclosed_material d on s.owning_organisation = d.owning_organisation and s.series_title = d.series_title and s.series_sub_title = d.series_sub_title and d.out_of_scope_reason='' " .  
			"WHERE s.owning_organisation = '" . addslashes($organisation->getTitle()) . "' " . 
			"AND s.series_title = '" . addslashes($this->title) . "' " . 
			"AND s.series_sub_title != '' " . 
			"group by s.series_sub_title " . 
			"ORDER BY s.archiveorder, s.series_sub_title";
		
			$subseries_results = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);
		}
		else
		{
			$subseries_results = "";
		}
		
		//OUT OF SCOPE NOT HANDLED THIS WAY ANYMORE
		/*
		// get another list of sub-series from the series lookup table, as this will include any which were out of scope and had no disclosed material
		$query = "SELECT DISTINCT(series_sub_title), description FROM series WHERE type = 2 AND series_sub_title != '' " . 
			"AND owning_organisation = '" . addslashes($organisation->getTitle()) . "' " . 
			"AND series_title = '" . addslashes($this->title) . "' AND series_sub_title NOT IN " . 
			"(SELECT DISTINCT(series_sub_title) FROM disclosed_material WHERE owning_organisation = " . 
			"'" . addslashes($organisation->getTitle()) . "' AND series_title = '" . addslashes($this->title) . "') ORDER BY series_sub_title";

		$subseries_lookup_results = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);
		*/
		if (!empty($subseries_results))
		{
			foreach ($subseries_results as $result)
			{
				$query = "SELECT MIN(date_start) AS start_date, MAX(date_end) AS end_date FROM disclosed_material WHERE owning_organisation = '" . addslashes($organisation->getTitle()) . "' AND series_title = '" . addslashes($this->title) . "' AND series_sub_title = '" . addslashes($result->series_sub_title) . "' LIMIT 0,1";
				$subseries_daterange = $this->getAdapter()->fetchRow($query, array(), Zend_Db::FETCH_OBJ);
				
				$query = "SELECT description, series_sub_title FROM series WHERE series_title = '" . addslashes($result->series_sub_title) . "' AND type = 2 LIMIT 0,1";
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
					

				
				// as with the series objects being passed with the contributing orgs, sub-series are passed as an array with the series objects	
				$this->sub_series[] = new Application_Model_SubSeries( $organisation, $result->series_sub_title, 
					$result->url_title, $subseries_daterange->start_date, $subseries_daterange->end_date, 
					$series_reference, $series_description,  $result->archive_ref_id, $result->archiveorder);
			}
			
			/* NOT USED ANYMORE
			if (!empty($subseries_lookup_results))
			{
				// now add the out of scope ones
				foreach ($subseries_lookup_results as $lookup_result)
				{
					$this->sub_series[] = new Application_Model_SubSeries( $organisation, $lookup_result->series_sub_title, '', '', '', '', $lookup_result->description, TRUE );
				}
			
				
			}
			*/
			
			// sort the series array by the title
			$this->sub_series = Hillsborough_Functions::sortSeriesArray( $this->sub_series );
		}
		$query = "SELECT MIN(date_start) AS start_date, MAX(date_end) AS end_date FROM disclosed_material WHERE owning_organisation = '" . addslashes($organisation->getTitle()) . "' AND series_title = '" . addslashes($this->title) . "' LIMIT 0,1";
		$series_daterange = $this->getAdapter()->fetchRow($query, array(), Zend_Db::FETCH_OBJ);
		
		$this->date_start = $series_daterange->start_date;
		$this->date_end = $series_daterange->end_date;		
	}	
	
	public function getOrdering()
	{
		if (!isset($this->archiveorder))
			return $this->title;
		return $this->archiveorder;
	}
	
	public function getTitle()
	{
		return $this->title;
	}
	
	public function getStartDate()
	{
		if ($this->date_start > 0)
		{
			return Hillsborough_Functions::convertDate($this->date_start);
		}
	}
	
	public function getEndDate()
	{
		if ($this->date_end > 0)
		{
			if ($this->date_start > 0)
			{
				$returnchar = "&#8211; ";
			}
			else
			{
				$returnchar = "";
			}
			return $returnchar . Hillsborough_Functions::convertDate($this->date_end);
		}
	}
	
	public function getOutOfScope()
	{
		return $this->out_of_scope;
	}
	
	public function getSubSeries()
	{
		return $this->sub_series;
	}
	
	public function hasSeriesReference()
	{
		if (!empty($this->series_reference))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	public function getSeriesReference()
	{
		return $this->series_reference;
	}
	
	public function hasDescription()
	{
		if (!empty($this->description))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	public function getSeriesDescription()
	{
		return $this->description;
	}
	
	public function hasSubSeries()
	{
		if (count($this->sub_series) > 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	public function getIterator() 
	{  
        return new ArrayIterator( $this->documents );  
    } 
	
	public function getHref()
	{
		if (substr(strrev($_SERVER['REQUEST_URI']), 0, 1) == '/')
		{
			$domain = $_SERVER['REQUEST_URI'];
		}
		else
		{
			$domain = $_SERVER['REQUEST_URI'] . '/';
		}
		
		return $domain . $this->url;
	}
}


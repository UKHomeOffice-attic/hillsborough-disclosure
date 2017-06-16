<?php

// series lookup table
class Application_Model_DbTable_SeriesLookup extends Zend_Db_Table_Abstract
{
    protected $_name = 'serieslookup';
	protected $lookup;
	protected $organisation;
	protected $series;
	protected $subseries;
	protected $archive_ref_id;
	
	public function __construct( $lookup, Application_Model_ContributingOrganisation $organisation )
	{
		parent::__construct();
		
		$this->lookup = $lookup;
		$this->organisation = $organisation;
	}

	public function doLookup()
	{
		if (empty($this->lookup))
		{
			throw new Exception("No lookup set");
		}
		/*
		$row = $this->fetchRow("url_title = '" . $lookup . "'");
		if (!$row) {
			throw new Exception("Could not find series");
		}

		$this->lookup = $row;	
		*/
		
		$row = $this->fetchRow('url_title = "' . $this->lookup . '" AND owning_organisation = "' . $this->organisation->getTitle() . '"');
		if (!$row) 
		{
			throw new Exception("Could not find a matching series for the organisation");
		}
		
		$this->series = $row['series_title'];
		$this->archive_ref_id = $row['archive_ref_id'];
		if (!empty($row['series_sub_title']))
		{
			$this->subseries = $row['series_sub_title'];
		}
	}
	
	public function getArchiveRefId()
	{
		return $this->archive_ref_id;
	}
	
	public function getSeries()
	{
		return $this->series;
	}
	
	public function getSubSeries()
	{
		return $this->subseries;
	}

	public function getSeriesTitle()
	{
		if (!empty($this->subseries))
		{
			return $this->subseries;
		}
		else
		{
			return $this->series;
		}
	}
}


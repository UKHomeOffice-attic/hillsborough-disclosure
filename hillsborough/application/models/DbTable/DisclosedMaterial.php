<?php

// disclosed material table holds all of the material data, including out of scope reasons.
class Application_Model_DbTable_DisclosedMaterial extends Zend_Db_Table_Abstract
{
    protected $_name = 'disclosed_material';
	protected $organisation;
	protected $series;
	protected $subseries;
	protected $inscope = FALSE;
	protected $return;
	protected $order;
	protected $documentid;
	protected $query;
	protected $outofscope;	
	protected $outofscopeformatted;	
	protected $ap_victimname;
	protected $ap_corporatebody;
	protected $ap_person;
	protected $startdate;
	protected $format = array(); 
	protected $originalformat;
	protected $formatted_outofscope;
	protected $redactedreason;
	protected $enddate;
	protected $rowreturn;
	protected $reportRef;
	protected $includeOrgs = FALSE;
	protected $columns = array();
	
	public function doesDocumentExist($beginDocID)
	{
		$query = "SELECT count(*) as RecordCount FROM disclosed_material WHERE begin_doc_id = '".$beginDocID."' and out_of_scope_reason = ''";
		$result = $this->getAdapter()->fetchRow($query, array(), Zend_Db::FETCH_OBJ);
		
		// To be safe if 1 item exists then return true 
		// otherwise it either doesn't exist or something unexpected is wrong
		if ($result->RecordCount=="1")	
			return true;
			
		return false;	
	}
	
	public function doesDocumentExistRegardlessOfScope($beginDocID)
	{
		$query = "SELECT count(*) as RecordCount FROM disclosed_material WHERE begin_doc_id = '".$beginDocID."'";
		$result = $this->getAdapter()->fetchRow($query, array(), Zend_Db::FETCH_OBJ);
		
		// To be safe if 1 item exists then return true 
		// otherwise it either doesn't exist or something unexpected is wrong
		if ($result->RecordCount=="1")	
			return true;
			
		return false;	
	}
	
	public function addColumns($columnDef)
	{
		$this->columns = $columnDef;
	}
	
	public function setIncludeOrgs( $includeThem )
	{
		$this->includeOrgs = $includeThem;
	}
	
	public function setOrganisation( Application_Model_ContributingOrganisation $organisation )
	{
		$this->organisation = $organisation;
	}
	
	public function setApVictimName( $name )
	{
		$this->ap_victimname = $name;
	}
	
	public function setApCorporateBody( $name )
	{
		$this->ap_corporatebody = $name;
	}
	
	public function setApPerson( $name )
	{
		$this->ap_person = $name;
	}
	
	public function setFormat( $format )
	{
		$this->format[] = "'" . $format . "'";
	}
	
	public function setOrder( $order )
	{
		$this->order = $order;
	}
	
	public function setSeries( $series )
	{
		$this->series = $series;
	}
	
	public function setReportRef ( $reportRef )
	{
		$this->reportRef = $reportRef;	
	}
	
	public function setOutOfScopeReason( $outofscope )
	{
		
		/*
		 * Change made to pull query for grouped out of scope reasons to apply on the
		 * disclosed material 
		 * 
		 */
		
		$query = "SELECT out_of_scope_query FROM outofscopegroups where out_of_scope_url = '".$outofscope."'";
		$result = $this->getAdapter()->fetchRow($query, array(), Zend_Db::FETCH_OBJ);
		$this->outofscope = "";
		if (!empty($result))
		{
			$this->outofscope = $result->out_of_scope_query;
		}
		
		
		
//		$query = "SELECT out_of_scope_reason FROM outofscopelookup WHERE out_of_scope_lookup = '$outofscope' LIMIT 0,1";
//		$result = $this->getAdapter()->fetchRow($query, array(), Zend_Db::FETCH_OBJ);
//		
//		if (!empty($result))
//		{
//			$this->outofscope = $result->out_of_scope_reason;
//
//		}
//		else
//		{
//			$this->outofscope = '';
//		} 
		
		//$this->outofscope = "yes";
	}
	
	public function setSubSeries( $subseries )
	{
		$this->subseries = $subseries;
	}
	
	public function setReturn( $return )
	{
	}
	
	public function setStartDate( $startdate )
	{
		$this->startdate = $startdate;
	}
	
	public function setInScope()
	{
		$this->inscope = TRUE;
	}
	
	public function setEndDate( $enddate )
	{
		$this->enddate = $enddate;
	}
	
	public function setRowReturn( $rowReturn )
	{
		$this->rowreturn = $rowReturn;
	}
	
	public function setDocumentid( $docid )
	{
		$this->documentid = $docid;
	}

	
	private function GetLastDayofMonth($year, $month) {
	    for ($day=31; $day>=28; $day--) {
	        if (checkdate($month, $day, $year)) {
	            return $day;
	        }
	    }   
	}

	protected function buildQuery()
	{
		// build query string
		$query = array();
		$buildquery = "";
		
		// doc id will only return one result
		if (!empty($this->documentid))
		{
			$buildquery = "begin_doc_id = '" . $this->documentid . "'";
		}
		else
		{
			try {
				if ($this->startdate!="")
				{
					if ($this->startdate=='0')
					{
						$tq = "(date_start = '0') ";
					}
					else 
					{
						$tq = "((date_start_from < '".$this->enddate."') AND (((date_end = '0') AND (date_start_to >= '" . $this->startdate . "')) OR (date_end_to >= '" . $this->startdate . "'))) ";						
					}
					$query[] = $tq;
					//var_dump($query);
					//exit;
				}
			}
			catch(Exception $ex)
			{
				var_dump($ex);
				exit();
			}
			// multiple formats supported
			if (!empty($this->format))
			{
				$query[] = "format IN (" . implode(",", $this->format) . ")";
			}
			// autopopulation victim name - uses like as it's a multiple value field
			if (!empty($this->ap_victimname))
			{
				$query[] = "ap_victim_name LIKE '%;" . $this->ap_victimname . "(%'";
			}
			
			// autopopulation corporate body - uses like as it's a multiple value field
			if (!empty($this->ap_corporatebody))
			{
				$query[] = "ap_corporate_body LIKE '%;" . $this->ap_corporatebody . ";%'";
			}
			
			// autopopulation persons - uses like as it's a multiple value field
			if (!empty($this->ap_person))
			{
				$query[] = "(ap_person LIKE '%;" . $this->ap_person . "(%' OR ap_person LIKE '%;" . $this->ap_person . ";%')";
			}
			
			// org
			if (!empty($this->organisation))
			{
				$query[] = "owning_organisation = '" . addslashes($this->organisation->getTitle()) . "'";
			}
			
			// series
			if (!empty($this->series))
			{
				$query[] = "series_title = '" . addslashes($this->series) . "'";
				
				if (!empty($this->subseries))
				{
					$query[] = "series_sub_title = '" . addslashes($this->subseries) . "'";
				}
			}
			
			// doc is not out of scope
			if ($this->inscope)
			{
				// only records which are in scope
				$query[] = "out_of_scope_reason = ''";
			}
			else if (!empty($this->outofscope)) // or is it?
			{
				$query[] = $this->outofscope;	
			}

			/* KH add report referenced clause */
			if ($this->reportRef)
			{
				$query[] = "research_significance like '%".$this->reportRef .";%'";
			}
			
			if (count($query) > 0)
			{
				for ($q = 0; $q < (count($query) - 1); $q++)
				{
					$buildquery .= $query[$q] . " AND ";
				}
				
				$buildquery .= $query[count($query) - 1];
			}
		}
	
		// do build
		$this->query = $buildquery;
	}

	
	
	/***************************************
	 * KH: 
	 * I have created a view called cataloguepage to work as and interim solution for including
	 * out of scope organisations in the catalogue pages.  It's not quite right though...  It 
	 * works but the mySQL view is veeeery slow and means that this will slow the build down.  
	 * I think we either need to optimise this, try a UNION sql join and see if that improves
	 * (which I doubt as we do paging against the table normally whereas this will need to 
	 * build the whole recordset then extract subset) or as this is just to reached the end 
	 * point, clone the view as a table at the end of the newImport process.
	 * 
	 * Hmm, pick up nearer end of R3 changes
	 * 
	 * ************ UPDATE **************************
	 * Too slow...  now have a merged table "cataloguemerge" which holds orgs, disclosed_material and 
	 * out of scope folders.  Not the cleanest route but with current constraints the fastest way to 
	 * complete this functionality.
	 * 
	 */
	public function get()
	{
		if (empty($this->query))
		{
			$this->buildQuery();
		}
						
		if (!empty($this->documentid) || $this->rowreturn == TRUE)
		{
			$documents = $this->fetchRow($this->query); 			
		}
		else if (!empty($this->query))
		{
			$dbselect = new Zend_Db_Select( $this->getAdapter() );
			
			if (empty($this->order))
				$this->order = "begin_doc_id ASC";

				
			if (!$this->includeOrgs)
			{
				//KH Am i ordering browse by victim?
				if ($this->order[0]=="ordering desc")
				{
					
					$documents = $dbselect->from("disclosed_material", $this->columns)
									  ->where($this->query)
									  ->order($this->order);
				}
				else
				{
					
					$documents = $dbselect->from("disclosed_material")
									  ->where($this->query)
									  ->order($this->order);
				}				  
								  
			}
			else 
			{
				$documents = $dbselect->from("cataloguemerge")
								  ->where($this->query)
								  ->order($this->order);
								  
			}					  								  								  		
		}
		else
		{
			if (!$this->includeOrgs)
			{	
				$dbselect = new Zend_Db_Select( $this->getAdapter() );
				$documents = $dbselect->from("disclosed_material")
									  ->order("begin_doc_id ASC");
			}
			else
			{
				$dbselect = new Zend_Db_Select( $this->getAdapter() );
				$documents = $dbselect->from("cataloguemerge")
									  ->order("begin_doc_id ASC");
			}										  
		}

		if (!$documents) 
		{
			throw new Exception("Documents object not set so failing to force the correction of fault (DisclosedMaterial.php)");
			exit();
		}
		
		return $documents;
	}
	
	/*
	 * New function to give us the summary out of scope reasons rather than actual OOS values
	 */
	public function getSummaryOutOfScopeReasons()
	{
		// get list of out of scope reasons
		$query = "SELECT out_of_scope_url, out_of_scope_group_name FROM outofscopegroups ORDER BY group_order";
		$scopes = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);
		return $scopes;
	}
	
	public function getOutOfScopeReasons($filtered_org = null)
	{
		// get list of out of scope reasons
		$query = "SELECT DISTINCT(out_of_scope_reason), out_of_scope_lookup, owning_organisation FROM outofscopelookup";
		if (!empty($filtered_org))
		{
			$query .= " WHERE owning_organisation = '" . addslashes($filtered_org) . "'";
		}
		$query .= " ORDER BY out_of_scope_reason";
		$scopes = $this->getAdapter()->fetchAll($query, array(), Zend_Db::FETCH_OBJ);
		return $scopes;
	}
}
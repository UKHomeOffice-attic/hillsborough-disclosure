<?php

class BrowseController extends Zend_Controller_Action
{



//KH: Added to implement breadcrumb for missing attributes
    protected $contributingBody = null;
    protected $victimName = null;
    protected $personName = null;
//KH: End	    
	
	protected $orgid = null;

    protected $organisation_id = null;

    protected $series = null;

    protected $alpha = null;

    protected $layout_view = null;

    protected $organisation = null;

    public function init()
    {

		$request = $this->getRequest();

		//KH: Added for missing elements
		$this->contributingBody = $request->getParam('corporatebody');
		$this->personName = $request->getParam('person');
		$this->victimName = $request->getParam('victim');
		//KH: End
		
		$this->organisation_id = $request->getParam('organisation');
		$this->series = $request->getParam('series');
		
		// set up organisations list
		$organisations = new Application_Model_DbTable_Organisations();
		$allOrganisations = 	$organisations->getAllOrganisations();
		
		$bootstrap = $this->getInvokeArg('bootstrap');
		$config = $bootstrap->getOptions();
		
		// get layout view
		$layout = Zend_Layout::getMvcInstance();
      	$this->layout_view = $layout->getView();
		$this->layout_view->allOrganisations = $allOrganisations;
		$this->layout_view->reporturl = $config['hillsborough']['reporturl'];
//AS: Propagate the Suppress Navigation config setting
		if ((isset($config['hillsborough']['suppress_navigation']))&&($config['hillsborough']['suppress_navigation']))
		{
			$this->layout_view->suppressnav = TRUE;
			$this->view->suppressnav = TRUE;
		}
//AS: End
    }

    public function indexAction()
    {
        // action body
		$this->layout_view->showReportDownload = false;
		$this->layout_view->pagetitle = "Browse the disclosed material";
		$this->layout_view->inBrowse = TRUE;
		$this->layout_view->showBrowseRHB = TRUE;
		//$this->layout_view->nobreadcrumb = TRUE;
		$this->layout_view->rightHandBlockText = 
			"<h3>Advice</h3>\r\n" .
            "<p>For hints and tips on using browse, see the individual browse methods or go to <a href=\"/help/\">Help</a>.</p>\r\n".
			"<h3>Material considered for disclosure</h3>\r\n" .
			"<p>Not all the material that was considered for disclosure by the Panel has eventually been made available on this website. Go to <a href=\"/disclosure-process/\">the disclosure process</a> to understand how the Panel decided whether or not to make something available here.</p>\r\n" .
			"<h3><a href=\"/catalogue/index/organisation/all/outofscope/all/perpage/20/page/1\">Catalogue</a></h3>\r\n" . 
			"<p>The Catalogue lists all of the material considered for disclosure whether or not it was eventually made available on this website.</p>\r\n";
    }

    public function selectorganisationAction()
    {
		$organisations = new Application_Model_DbTable_Organisations();
		if ($this->layout_view->suppressnav == TRUE)
		{
			$allOrganisations = $organisations->getAllOrganisationsWithDisclosedMaterialForExpertLaptop();
		}
		else
		{
			$allOrganisations = $organisations->getAllOrganisationsWithDisclosedMaterial();
		}
		
		$this->view->allOrganisations = $allOrganisations;	
		$this->view->alpha = Application_Model_Schema::getAlpha();
		//$this->layout_view->showOrganisationDropdown = TRUE;
		$this->layout_view->inBrowse = TRUE;
		$this->layout_view->showReportDownload = FALSE;
		$this->layout_view->pagetitle = "Browse by contributor";

  		$this->layout_view->showBrowseRHB = TRUE;
		$this->layout_view->rightHandBlockText = 
			"<h3><a href=\"/contributors/\">85 Contributors</a></h3>" .
			"<p>Click <a href=\"/contributors/\">here</a> for a list of those organisations and individuals whose material has been provided and reviewed through the Panel's process.</p>\r\n" .
			"<h3><a href=\"/catalogue/index/organisation/all/outofscope/all/perpage/20/page/1\">Catalogue</a></h3>" .
			"<p>For a complete list of material considered, including material not disclosed on this website, go to the Catalogue.</p>\r\n" .
			"<h3><a href=\"/browse/by-corporate-body/\">Browse by organisation involved</a></h3>" .
			"<p>To view material that refers to a particular organisation (regardless of who contributed it), use this browse method.</p>\r\n" .
   			"<h3><a href=\"/advancedsearch/\">Advanced search of disclosed material</a></h3>" .
			"<p>If you want to search within the material provided by a contributor, use Advanced search. Select the contributor's name from the appropriate pulldown list and apply other criteria to narrow down your search.</p>\r\n";
		    }

    public function vieworganisationAction()
    {
		// get query
		$organisation = new Application_Model_ContributingOrganisation( $this->organisation_id );

		// check if this organisation did not contribute to the disclosure
		
		if (!$organisation->getContributing())
		{
			//KH: This is depreciated as should never get to this situation.  Orgs that have not 
			//    disclosed would not apprear in the browse sections.  Leveing however, just incase it
			//    changes.			
			$disclosedMaterial = new Application_Model_DbTable_DisclosedMaterial();
			$disclosedMaterial->setOrganisation( $organisation );
			$disclosedMaterial->setRowReturn( TRUE );
			try
			{
				$nonDisclosedDocument = $disclosedMaterial->get();
			}
			catch(Exception $ex)
			{
			}
		}
		else
		{			
			$organisation->querySeries();
			$nonDisclosedDocument = null;
		}
		
				
		$this->view->nonDisclosedDocument = $nonDisclosedDocument;
		$this->view->organisation = $organisation;
		$this->layout_view->organisation = $organisation;

		$this->layout_view->showReportDownload = FALSE;
		$this->layout_view->inBrowse = TRUE;
		$this->layout_view->pagetitle = $organisation->getTitle();
    }

    public function listdocumentsAction()
    {
        // get documents based on series and organisation
		$organisation = new Application_Model_ContributingOrganisation( $this->organisation_id );
		
		$seriesLookup = new Application_Model_DbTable_SeriesLookup( $this->series, $organisation );
		$seriesLookup->doLookup();
		
		$series_view = ($seriesLookup->getSubSeries() != '') ? $seriesLookup->getSubSeries() : $seriesLookup->getSeries();
		
		$disclosedMaterial = new Application_Model_DbTable_DisclosedMaterial();
		$disclosedMaterial->setOrganisation( $organisation );
		$disclosedMaterial->setInScope();
		$disclosedMaterial->setSeries( $seriesLookup->getSeries() );
		$disclosedMaterial->setSubSeries( $seriesLookup->getSubSeries() );

		
		if ($seriesLookup->getArchiveRefId()!="")
		{
			$l = strlen($seriesLookup->getArchiveRefId())+1;
			$disclosedMaterial->setOrder(
				array(
					"convert(substr(archive_ref_id, " . $l .", 5),UNSIGNED INTEGER) ASC"
					)
				);
			//$disclosedMaterial->setOrder(array("substr(archive_ref_id, " . $l .", 1) ASC","LPAD(substr(archive_ref_id, " . $l ."),10,'0') ASC"));
		}
		else 
			$disclosedMaterial->setOrder(array("short_title ASC", "begin_doc_id asc"));		
		
		$documents = $disclosedMaterial->get();
		
		$page = $this->_getParam('page', 1);
		$perpage = $this->_getParam('perpage', 20);
		$paginator = Zend_Paginator::factory($documents);
		$paginator->setItemCountPerPage($perpage);
		$paginator->setCurrentPageNumber($page);
		
		
		$this->view->pagetitle = $seriesLookup->getSeriesTitle();
		$this->view->paginator = $paginator;
		$this->layout_view->organisation = $organisation;
		$this->layout_view->showDocKey = TRUE;
		$this->layout_view->series = $series_view;
		$this->view->series = $this->series;
		
		$this->view->organisation = $organisation;
		$this->view->organisation_id = $this->organisation_id;
		
		$this->layout_view->showReportDownload = FALSE;
		$this->layout_view->inBrowse = TRUE;
		$this->layout_view->pagetitle = "Documents from " . $organisation->getTitle();
    }

    public function viewdocumentAction()
    {
		$request = $this->getRequest();
		$this->document_id = $request->getParam('document');
		
		$organisation = new Application_Model_ContributingOrganisation( $this->organisation_id );

		$seriesLookup = new Application_Model_DbTable_SeriesLookup( $this->series, $organisation );
		$seriesLookup->doLookup();

		$series_view = ($seriesLookup->getSubSeries() != '') ? $seriesLookup->getSubSeries() : $seriesLookup->getSeries();
		
        $disclosedMaterial = new Application_Model_DbTable_DisclosedMaterial();
		$disclosedMaterial->setDocumentid( $this->document_id );
		//$disclosedMaterial->buildQuery();
		
		$document = $disclosedMaterial->get();
		
		$this->layout_view->document_title = $document[0]->short_title;
		$this->layout_view->organisation = $organisation;
		$this->layout_view->series = $series_view;
		$this->view->document = $document;
		$this->layout_view->inBrowse = TRUE;
		//$this->view->organisation_id = $this->organisation_id;
		$this->view->series = $this->series;
    
    }

    public function chapterAction()
    {
    	
		$this->layout_view->pagetitle = "Browse material referenced in the Report";
		$this->layout_view->inBrowse = TRUE;
		$this->_helper->viewRenderer('viewchapters');

 		$report = new Application_Model_ReportStructure();
 		
		$this->view->reportStructure = $report->getReportStructure();
		
  		$this->layout_view->showBrowseRHB = TRUE;
		$this->layout_view->rightHandBlockText = 
			"<h3>Advice</h3>" .
			"<p>The footnotes in the Report contain the references to the disclosed material. If you wish to find more precisely where and  how a document is referenced, navigate to the Report home page, select 'Search report only' (in the banner at the top of the page) and type the Unique ID into the search everything box.</p>\r\n" .
   			"<h3><a href=\"/advancedsearch/\">Advanced search of disclosed material</a></h3>" .
			"<p>To search the material referenced in the Report in other ways, go to Advanced search.  Select 'Search only disclosed material referenced in the Panel's Report' and apply other criteria to narrow down your search.</p>\r\n" .
   			"<h3><a href=\"/catalogue/index/organisation/all/outofscope/all/perpage/20/page/1\">Catalogue</a></h3>" .
			"<p>The Catalogue lists all of the material considered for disclosure whether or not it was eventually made available on this website.</p>\r\n" .
			"<p>Go to <a href=\"/help/\">Help</a> for more information.</p>\r\n";
	}

    public function chapterlookupAction()
    {
		// get lookup query
		$request = $this->getRequest();
		$query = $request->getParam('reportref');
		
		$report = new Application_Model_ReportStructure();
		$details = $report->getItemDetails($query);
		
		$disclosedMaterial = new Application_Model_DbTable_DisclosedMaterial();

		//KH: No longer only "disclosed" items on the browse by material referenced...! (CHIP002)
		//$disclosedMaterial->setInScope();	
		$disclosedMaterial->setReportRef( $query );		
		
		$documents = $disclosedMaterial->get();
		$pageCount = $this->_getParam('perpage', 20);		
		$page = $this->_getParam('page', 1);
		$paginator = Zend_Paginator::factory($documents);
		$paginator->setItemCountPerPage($pageCount);
		$paginator->setCurrentPageNumber($page);

		
		$this->view->paginator = $paginator;
		$this->_helper->viewRenderer('listdocuments');
		$this->layout_view->showDocKey = TRUE;
		$this->layout_view->showReportDownload = FALSE;
		$this->view->showOrganisation = TRUE;
		//$this->view->pagetitle = "Browse by material referenced in the report under &quot;" . $details[0]->Title . "&quot;";
		$title=$details[0]->Title;
		if (!isset($title))
			$title = "Unknown report section";
		$this->layout_view->chaptername = $title;
		$this->view->pagetitle = $title;
		$this->layout_view->inBrowse = TRUE;

  		$this->layout_view->showBrowseRHB = FALSE;
		$this->layout_view->rightHandBlockText = 
			"<h3>title</h3>\r\n" .
			"<p>text</p>\r\n" . 
			"<h4><a href=\"/Someurl/\">link title</a></h4>\r\n" . 
			"<p>text</p>\r\n";
    }
    
    public function placeholderAction()
    {

		$this->layout_view->document_title = "Placeholder";
		$this->_helper->viewRenderer('placeholder');
    }

    public function opendataplaceholderAction()
    {

		$this->layout_view->document_title = "Open Data Placeholder";
		$this->_helper->viewRenderer('opendataplaceholder');

		$bootstrap = $this->getInvokeArg('bootstrap');
		$config = $bootstrap->getOptions();
		
	// get filesizes
		$path = $config['hillsborough']['opendatafiles'] . ".csv";
		if (file_exists($path))
		{
			$this->view->csvfilesize = round((filesize($path)), 0);
		}
		else
		{
			$this->view->csvfilesize = "";
		}

		$path = $config['hillsborough']['opendatafiles'] . ".xml";
		if (file_exists($path))
		{
			$this->view->xmlfilesize = round((filesize($path)), 0);
		}
		else
		{
			$this->view->xmlfilesize = "";
		}


    }
    
    public function viewnotreadyAction()
    {

		$this->layout_view->document_title = "Not available yet";
		$this->_helper->viewRenderer('notimplementedyet');
    }
    
    public function dateAction()
    {
        // action body
		//$this->layout_view->nobreadcrumb = TRUE;
		
		$this->view->dateRanges = Application_Model_Schema::getDateRangeTitles();
		$this->layout_view->showReportDownload = FALSE;
		$this->layout_view->pagetitle = "Browse by date or date range";
		$this->layout_view->inBrowse = TRUE;
		
  		$this->layout_view->showBrowseRHB = TRUE;
		$this->layout_view->rightHandBlockText = 
			"<h3>Advice</h3>" .
            "<p>Some items only have a partial date eg June 1989 or 1989. In this event, they will appear in more than one of the date ranges. For example, if an item has a partial date of June 1989, it will appear in the results set for each of the individual days in that month. If an item has a partial date of 1989 it will appear under any of the date ranges which include 1989.</p>".
			"<p>Most items will just have a start date (ie the date they were created - such as the date a letter was written).   However, some documents have been grouped and scanned together as one item. Where this occurs the item will have a start date (the date of the first document) and an end date (the date of the last document created in the group). Such an item will appear in each of the date ranges here that its own dates overlap. For example, if it has dates of 1 April 1989 - 30 April 1989 it will appear in each of the top three date ranges offered on this page.</p>\r\n" .
			"<h3><a href=\"/advancedsearch/\">Advanced search of disclosed material</a></h3>" .
			"<p>To search for a specific date or custom date range, go to Advanced search.</p>\r\n" .
			"<h3><a href=\"/catalogue/index/organisation/all/outofscope/all/perpage/20/page/1\">Catalogue</a></h3>" . 
			"<p>For a complete list of material including material not disclosed on this website, go to the catalogue of all material considered for disclosure.</p>\r\n" .
			"<p>Go to <a href=\"/help/\">Help</a> for more information.</p>\r\n";
    }

    public function daterangeAction()
    {
    	try 
    	{	
	        $request = $this->getRequest();
			$start = $request->getParam('start');
			$end = $request->getParam('end');
			
			$page = $this->_getParam('page', 1);
			$pageCount = $this->_getParam('perpage', 20);
			
			// get disclosed materials by date range
			$disclosedMaterial = new Application_Model_DbTable_DisclosedMaterial();
			
			
			if (($start=="none")&&($end=="none"))
			{
				$disclosedMaterial->setStartDate( '0' );
				$disclosedMaterial->setInScope();
				$disclosedMaterial->setEndDate( '0' );
				
			}
			else
			{
				$disclosedMaterial->setStartDate( Hillsborough_Functions::convertDateQuery( $start ) );
				$disclosedMaterial->setInScope();
				$disclosedMaterial->setEndDate( Hillsborough_Functions::convertDateQuery( $end ) );
			}
//			$disclosedMaterial->setOrder("date_start ASC");
			$disclosedMaterial->setOrder(array("date_end ASC", "date_start DESC"));
			
			$documents = $disclosedMaterial->get();
			// set up pagination
					
			$paginator = Zend_Paginator::factory($documents);
			$paginator->setItemCountPerPage($pageCount);
			$paginator->setCurrentPageNumber($page);
		
			$this->view->pagetitle = $this->layout_view->daterange = Application_Model_Schema::getSingleDateRangeTitle( $start, $end );
			$this->view->additionDescription = 
			
			"<p>Disclosed items that have a specific date are returned first in descending order (most recent first). " .
"So  a document dated 26 March 1994 will be higher in the list than a document dated 30 January 1994.</p>" .
"<p>Items that span a range of dates (ie that have an end date) are returned after those that have a specific date (no end date). " .
"Items that span a range of dates are returned in ascending order of end date.</p>";
			$this->view->paginator = $paginator;
			$this->_helper->viewRenderer('listdocuments');
			$this->layout_view->showDocKey = TRUE;
			$this->layout_view->showReportDownload = FALSE;
			$this->view->showOrganisation = TRUE;
			$this->layout_view->pagetitle = "Browse by date range";
			$this->layout_view->inBrowse = TRUE;

		  	$this->layout_view->showBrowseRHB = TRUE;
			$this->layout_view->rightHandBlockText = "<h3>Advice</h3><p>To decide where in the list to put documents with partial dates, we have treated 'March 1994' as equivalent to '01 March 1994' and '1994' as equivalent to '01 January 1994'.</p>";
	
//			$this->layout_view->rightHandBlockText = 
//				"<h3>title</h3>\r\n" .
//				"<p>text</p>\r\n" . 
//				"<h4><a href=\"/catalogue/index/organisation/all/outofscope/all/perpage/20/page/1\">link title</a></h4>\r\n" . 
//				"<p>text</p>\r\n";
	    }
		catch(Exception $ex)
		{
			var_dump($ex, $request,$start,$end,$page,$pageCount);
			exit();
		}
    }

    public function populatedcontentAction($type)
    {
     	$lookup_data = new Application_Model_DbTable_Autopopulatelookup();
		$lookup_data->setType( $type );
		
		$full_titles = $lookup_data->queryFullTitles();
		
		$this->view->alpha = Application_Model_Schema::getAlpha();
		$this->view->full_titles = $full_titles;
		$this->layout_view->inBrowse = TRUE;
    }

    public function populatedcontentlookupAction($type, $query)
    {

		$autopopulatelookup = new Application_Model_DbTable_Autopopulatelookup();
		$autopopulatelookup->setType( $type );
		$autopopulate_lookup = $autopopulatelookup->getLookupData($query);		

		$this->view->vid = $autopopulate_lookup->id;
		
        $disclosedMaterial = new Application_Model_DbTable_DisclosedMaterial();
		$disclosedMaterial->setInScope();
  		$this->layout_view->showBrowseRHB = FALSE;

		
  		$searchTitle = "";
  		$searchCriteria = "";
  		
		switch ( $type )
		{
			case 1:
  				$searchTitle = "person who died";
  				$searchCriteria = "fq=hip_victim:".$autopopulate_lookup->id."";
  				
  				$disclosedMaterial->setApVictimName( $autopopulate_lookup->id );

				$columns = array();
				$columns[] = "*";
				$columns[] = "substr(ap_victim_name, position('" . $autopopulate_lookup->id . "' in ap_victim_name)+".(strlen($autopopulate_lookup->id)+1) . ", 1) as ordering";
				
				$disclosedMaterial->addColumns($columns);
				
				$sortOrder=array();
				$sortOrder[] = "ordering desc"; 
				$disclosedMaterial->setOrder($sortOrder);
				break;

			case 2:
  				$searchTitle = "organisation";
  				$searchCriteria = "fq=hip_corporate:".$autopopulate_lookup->id."";
  				
  				$this->view->additionDescription = $autopopulate_lookup->description;		
				$disclosedMaterial->setApCorporateBody( $autopopulate_lookup->id );		
				$disclosedMaterial->setOrder(array("short_title ASC", "begin_doc_id asc"));
				break;
			
			case 3:
  				$searchTitle = "person";
  				$searchCriteria = "fq=hip_person:".$autopopulate_lookup->id."";
  				
  				$disclosedMaterial->setApPerson( $autopopulate_lookup->id );

				$columns = array();
				$columns[] = "*";
				$columns[] = "substr(ap_person, position('" . $autopopulate_lookup->id . "' in ap_person)+".(strlen($autopopulate_lookup->id)+1) . ", 1) as ordering";
				
				$disclosedMaterial->addColumns($columns);
				
				$sortOrder=array();
				$sortOrder[] = "ordering desc"; 
				$disclosedMaterial->setOrder($sortOrder);
				break;				
		}

		
		$documents = $disclosedMaterial->get();
		
//KH: (18/10/2012) Adding new feature of linking to advanced search form		
  		$this->layout_view->showSearchRHB = TRUE;
		$this->layout_view->rightHandBlockSearch = "<p>Narrow down your results by using <a href=\"/advancedsearch/?".$searchCriteria."\">advanced search</a>.</p>"; 
//KH: End		
		
		$page = $this->_getParam('page', 1);
		$pageCount = $this->_getParam('perpage', 20);
		
		$paginator = Zend_Paginator::factory($documents);
		$paginator->setItemCountPerPage($pageCount);
		$paginator->setCurrentPageNumber($page);
		
		$this->view->paginator = $paginator;
		$this->layout_view->pagetitle = $this->view->pagetitle = $autopopulate_lookup->presentation_format;
		$this->layout_view->showDocKey = TRUE;
		$this->layout_view->showReportDownload = FALSE;
		$this->layout_view->inBrowse = TRUE;
		
		$this->_helper->viewRenderer('listdocuments'); // list docs view
		//$this->view->documents = $documents;
		
		return $autopopulate_lookup->presentation_format;
    }

    public function victimnameAction()
    {
        $this->populatedcontentAction( 1 );
		$this->layout_view->pagetitle = $this->view->pagetitle = "Browse by name of those who died";		


		$this->layout_view->description = "Definition and explanatory notes for term \"victim name\" and how it differs from \"person\".";
		$this->layout_view->inBrowse = TRUE;
		$this->layout_view->showReportDownload = FALSE;
		$this->view->linkref = "by-name-of-deceased";
		$this->_helper->viewRenderer('populatedcontent');

   		$this->layout_view->showBrowseRHB = TRUE;
		$this->layout_view->rightHandBlockText = 
			"<h3>Advice</h3>" .
			"<p>To help find documents that refer to an individual, we have used Optical Character Recognition (OCR) software to 'read' words on the scanned images of pages.  Documents may not appear here if the OCR software was unable to recognise the relevant word eg where they are hand written, or the paper original was in a poor condition.</p>\r\n" .
			"<p>Please note that where names have been found by OCR, there is a possibility that the document text may refer to a different person with the same or similar name. This is most likely to happen where the text only contains the surname and one initial. It is least likely to happen where there is more than one initial or forenames are given in full.</p>\r\n" .
   			"<h3><a href=\"/catalogue/index/organisation/all/outofscope/all/perpage/20/page/1\">Complete catalogue</a></h3>" .
			"<p>For a complete list of material including material not disclosed on this website, go to the catalogue of all material considered for disclosure.</p>\r\n" .
   			"<h3><a href=\"/advancedsearch/\">Advanced search of disclosed material</a></h3>" .
			"<p>To search within the documents that refer to one of those who died, use Advanced search. Select their name from the relevant pulldown list and apply other criteria to narrow down your search.</p>\r\n" .
			"<h3><a href=\"/browse/by-person/\">Browse by person involved</a></h3>" .
			"<p>To browse documents referring to other individuals use Browse by person involved.</p>\r\n" .
			"<p>Go to <a href=\"/help/\">Help</a> for more information.</p>\r\n";
    }


	public function victimnamelookupAction()
	{
		// get lookup query
		$request = $this->getRequest();
		$query = $request->getParam('victim');
		
		/*
		 * 
		$autopopulatelookup = new Application_Model_DbTable_Autopopulatelookup();
		$autopopulatelookup->setType( 1 );
		$autopopulate_lookup = $autopopulatelookup->getLookupData($query);		
		
		$disclosedMaterial = new Application_Model_DbTable_DisclosedMaterial();
		$disclosedMaterial->setInScope();
		
		//
		
		
		$documents = $disclosedMaterial->get();
		$pageCount = $this->_getParam('perpage', 20);		
		$page = $this->_getParam('page', 1);
		$paginator = Zend_Paginator::factory($documents);
		$paginator->setItemCountPerPage($pageCount);
		$paginator->setCurrentPageNumber($page);
		
		*/
		
		$title = $this->populatedcontentlookupAction( 1, $query );
		$this->layout_view->victimname = $title;
		$this->_helper->viewRenderer('listdocuments');
		
    }

    public function corporatebodyAction()
    {
        $this->populatedcontentAction( 2 );
		$this->layout_view->pagetitle = $this->view->pagetitle = "Browse by organisation involved";
		$this->layout_view->description = "Definition and explanatory notes for term \"corporate body\" and how it differs from \"contributing organisation\".";
		

		$this->layout_view->inBrowse = TRUE;
		$this->layout_view->showReportDownload = FALSE;
		$this->view->linkref = "by-corporate-body";
		$this->_helper->viewRenderer('populatedcontent');	

		$this->layout_view->showBrowseRHB = TRUE;
		$this->layout_view->rightHandBlockText = 
			"<h3>Advice</h3>" .
			"<p>To help find documents that refer to an organisation, we have used Optical Character Recognition (OCR) software to 'read' words on the scanned images of pages.  Documents may not appear here if the OCR software was unable to recognise the relevant word eg where they are hand written, or the paper original was in a poor condition.</p>\r\n" .
   			"<h3><a href=\"/advancedsearch/\">Advanced search of disclosed material</a></h3>" . 
			"<p>To search within the material that refers to one of the organisations involved, use Advanced search. Select the organisation's name from the relevant pulldown list and apply other criteria to narrow down your search.</p>\r\n" .
   			"<h3><a href=\"/catalogue/index/organisation/all/outofscope/all/perpage/20/page/1\">Catalogue</a></h3>" .
			"<p>For a complete list of material including material not disclosed on this website, go to the Catalogue.</p>\r\n" .
			"<h3><a href=\"/browse/by-contributor/\">Browse by contributor</a></h3>" .
			"<p>You can also view material by the names of the organisations who contributed it.</p>\r\n" .
			"<p>Go to <a href=\"/help/\">Help</a> for more information.</p>\r\n";
		    }

    public function corporatebodylookupAction()
    {

		$request = $this->getRequest();
		$cbody = $this->_getParam('corporatebody');

		$corpBody = $this->populatedcontentlookupAction( 2, $cbody );
	
//		$paginator = Zend_Paginator::factory($corpBody);
//		$paginator->setItemCountPerPage($pageCount);
//		$paginator->setCurrentPageNumber($page);
		$page  = $this->_getParam('page', 1);
		$pageCount = $this->_getParam('perpage', 20);
		$this->view->paginator->setItemCountPerPage($pageCount);
		$this->view->paginator->setCurrentPageNumber($page);
				
		$this->layout_view->corporateBody = $corpBody;
		$this->_helper->viewRenderer('listdocuments');
    }
    

    public function personAction()
    {
		$this->populatedcontentAction( 3 );
		$this->layout_view->pagetitle = $this->view->pagetitle = "Browse by person involved";
	
		$this->layout_view->description = "Definition and explanatory notes for term \"person\" and how it differs from \"victim name\".";
		$this->layout_view->inBrowse = TRUE;
		$this->layout_view->showReportDownload = FALSE;
		$this->view->linkref = "by-person";
		$this->_helper->viewRenderer('populatedcontent');

		$this->layout_view->showBrowseRHB = TRUE;
		$this->layout_view->rightHandBlockText = 
			"<h3>Advice</h3>" .
			"<p>To help find documents that refer to an individual, we have used Optical Character Recognition (OCR) software to 'read' words on the scanned images of pages.  Documents may not appear here if the OCR software was unable to recognise the relevant word eg where they are hand written, or the paper original was in a poor condition.</p>\r\n" .
			"<p>Where names have been found by OCR, there is a possibility that the document text may refer to a different person with the same or similar name. This is most likely to happen where the text only contains the surname and one initial. It is least likely to happen where there is more than one initial or forenames are given in full.</p>\r\n" .
   			"<h3><a href=\"/catalogue/index/organisation/all/outofscope/all/perpage/20/page/1\">Catalogue</a></h3>" . 
			"<p>For a complete list of material including material not disclosed on this website, go to the Catalogue.</p>\r\n" .
   			"<h3><a href=\"/advancedsearch/\">Advanced search of disclosed material</a></h3>" .
			"<p>To search within the material that refers to one of the people involved, use Advanced search. Select the person's name from the relevant pulldown list and apply other criteria to narrow down your search.</p>\r\n" .
			"<h3><a href=\"/browse/by-name-of-deceased/\">Browse by name of those who died</a></h3>" .
			"<p>Use this link to view material referring to those who died.</p>\r\n" .
			"<p>Go to <a href=\"/help/\">Help</a> for more information.</p>\r\n";
    }

	public function personlookupAction()
    {
		// get lookup query
		$request = $this->getRequest();
		$query = $request->getParam('person');
		
		$title = $this->populatedcontentlookupAction( 3, $query );
		$this->layout_view->personname = $title;
		$this->_helper->viewRenderer('listdocuments');
    }
	
	/*
    public function personlookupAction()
    {
		// get lookup query
		$request = $this->getRequest();
		$query = $request->getParam('person');
		
		$autopopulatelookup = new Application_Model_DbTable_Autopopulatelookup();
		$autopopulatelookup->setType( 3 );
		$autopopulate_lookup = $autopopulatelookup->getLookupData($query);		
		
        $disclosedMaterial = new Application_Model_DbTable_DisclosedMaterial();
		$disclosedMaterial->setInScope();
		
		$disclosedMaterial->setApPerson( $autopopulate_lookup->id );		
		
		$documents = $disclosedMaterial->get();
		
		$page = $this->_getParam('page', 1);
		$pageCount = $this->_getParam('perpage', 10);
		$paginator = Zend_Paginator::factory($documents);
		$paginator->setItemCountPerPage($pageCount);
		$paginator->setCurrentPageNumber($page);
		
		
		$title = $this->populatedcontentlookupAction( 3, $query );
		$this->layout_view->personname = $title;
		$this->_helper->viewRenderer('listdocuments');
    }
	*/

}



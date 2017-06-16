<?php

class CatalogueController extends Zend_Controller_Action
{
	protected $layout_view;

    public function init()
    {
        /* Initialize action controller here */
		$layout = Zend_Layout::getMvcInstance();
      	$this->layout_view = $layout->getView();
//AS: Propagate the Suppress Navigation config setting
		$bootstrap = $this->getInvokeArg('bootstrap');
		$config = $bootstrap->getOptions();

		if ((isset($config['hillsborough']['suppress_navigation']))&&($config['hillsborough']['suppress_navigation']))
		{
			$this->layout_view->suppressnav = TRUE;
		}
//AS: End
    }

    public function indexAction()
    {
		// far too long a method really, sorry.		
		// $start_time = microtime(true);
		
		// get orgs
		$allOrganisations = "";
		if (!isset($this->layout_view->suppressnav))
		{
			$organisations = new Application_Model_DbTable_Organisations();
			$allOrganisations = $organisations->getAllOrganisations();
		}
		
		// get params		
		$filterorg = $this->_getParam('organisation', '');		
		$outofscope = $this->_getParam('outofscope', '');
		$page = $this->_getParam('page', 1);
		$perpage = $this->_getParam('perpage', 20);
		
		$this->view->perpage = $perpage;
		
		switch ($perpage) 
		{
			case 20:
			case 100:
			case 500:
				break;
			default:
				$perpage = 20;
		}
		
		// get disclosed material object
        $disclosedMaterial = new Application_Model_DbTable_DisclosedMaterial();
		
		// set any filters
		if (!empty($filterorg) && $filterorg != 'all' && !isset($this->layout_view->suppressnav))
		{
			$organisation = new Application_Model_ContributingOrganisation( $filterorg );
			$disclosedMaterial->setOrganisation( $organisation );
			$filterOwningOrganisation = $organisation->getTitle();

		}
		else
		{
			$filterOwningOrganisation = "";
		}
		
		// out of scoped the out of scope reasons
		
		//KH: Removed the following line as now showing summary reasons rather than specific OOS 
		//$outofscopereasons = $disclosedMaterial->getOutOfScopeReasons( $filterOwningOrganisation );
		$outofscopereasons = $disclosedMaterial->getSummaryOutOfScopeReasons();
		
		if (!empty($outofscope) && $outofscope != 'all')
		{
			$disclosedMaterial->setOutOfScopeReason( $outofscope );
		}		
		
		/* KH: new bit of functionality to include out-of-scope orgs in the catalogue view */
		$disclosedMaterial->setIncludeOrgs(TRUE);
		
		// get documents
		$documents = $disclosedMaterial->get();
				
		// set up paginator
		$paginator = Zend_Paginator::factory($documents);
		$paginator->setItemCountPerPage($perpage);
		$paginator->setCurrentPageNumber($page);

		// testing response time of db
		// echo (microtime(true) - $start_time);

		// output to view
		$this->view->paginator = $paginator;
		$this->view->totalReturn = count($documents);
		$this->layout_view->perpage = $perpage;
		$this->layout_view->nobreadcrumb = TRUE;
		$this->view->outofscopereasons = $outofscopereasons; // outwardly scoped
		$this->view->outofscopelookup = $outofscope;
		$this->view->allOrganisations = $allOrganisations;	
		$this->view->selectedOrg = $filterorg;		
		$this->layout_view->pagetitle = "Catalogue of all material considered for disclosure";
		if (isset($this->layout_view->suppressnav))
		{
			$this->view->suppressnav = TRUE;
		}		
    }


}


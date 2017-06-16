<?php

class IndexController extends Zend_Controller_Action
{
	protected $suppressnav = FALSE;

    public function init()
    {
        /* Initialize action controller here */
		$bootstrap = $this->getInvokeArg('bootstrap');
		$config = $bootstrap->getOptions();
		if ((isset($config['hillsborough']['suppress_navigation']))&&($config['hillsborough']['suppress_navigation']))
		{
			$this->suppressnav = TRUE;
		}
		else
		{
			$this->suppressnav = FALSE;
		}
    }

    public function indexAction()
    {
        // httrack needs to spider the functionality which uses javascript to move location
		
		// get organisations
		$organisations = new Application_Model_DbTable_Organisations();
		if ($this->suppressnav == TRUE)
		{
			$allOrganisations = $organisations->getAllOrganisationsWithDisclosedMaterialForExpertLaptop();
		}
		else
		{		
			$allOrganisations = 	$organisations->getAllOrganisations();
		}
		// get out of scope reasons - may yet change
		$disclosedMaterial = new Application_Model_DbTable_DisclosedMaterial();
		
		//KH Old OOS reasons $outofscopereasons = $disclosedMaterial->getOutOfScopeReasons();
		
		$outofscopereasons = $disclosedMaterial->getSummaryOutOfScopeReasons();
		
		$this->view->organisations = $allOrganisations;
		$this->view->outofscopes = $outofscopereasons;
		
		echo "<p>";
		echo "<a href=\"/catalogue/index/organisation/all/outofscope/all/perpage/20/page/1\">all</a><br/>";

		// Each organisation with no out of scope filter
		foreach($allOrganisations as $org)
		{
			echo "<a href=\"/catalogue/index/organisation/" . $org->dir_name . "/outofscope/all/perpage/20/page/1\">".$org->dir_name." - all scope</a><br/>\r\n";
		}
		
		// Each out of scope with no org
		foreach($outofscopereasons as $scope)
		{
			echo "<a href=\"/catalogue/index/organisation/all/outofscope/" . $scope->out_of_scope_url . "/perpage/20/page/1\">All org - ".$scope->out_of_scope_url."</a><br/>\r\n";
		}
		
		
		// Each organisation with each out of scope filter
		foreach($allOrganisations as $org)
		{
			
			//foreach($disclosedMaterial->getOutOfScopeReasons($org->lextranet_title) as $scope)
			foreach($outofscopereasons as $scope)
			{
			
				echo "<a href=\"/catalogue/index/organisation/" . $org->dir_name . "/outofscope/".$scope->out_of_scope_url."/perpage/20/page/1\">".$org->dir_name." - ".$scope->out_of_scope_url."</a><br/>\r\n";
			}
		}
		
		// Misc pages:
		
		echo "<a href=\"/stats/\">Stats</a><br/>\r\n";
		echo "<a href=\"/error/400\">error pages</a><br/>\r\n";
		echo "<a href=\"/error/403\">error pages</a><br/>\r\n";
		echo "<a href=\"/error/404\">error pages</a><br/>\r\n";
		echo "<a href=\"/error/405\">error pages</a><br/>\r\n";
		echo "<a href=\"/error/4xxg\">error pages</a><br/>\r\n";
		echo "<a href=\"/error/500\">error pages</a><br/>\r\n";
		echo "<a href=\"/error/502\">error pages</a><br/>\r\n";
		echo "<a href=\"/error/503\">error pages</a><br/>\r\n";
		echo "<a href=\"/error/504\">error pages</a><br/>\r\n";
		echo "<a href=\"/error/Unavailable/SiteUnavailable.html\">Maintenance page</a><br/>\r\n";						
		echo "<a href=\"/error/Unavailable/JettyUnavailable.html\">Maintenance page</a><br/>\r\n";						
		
//		echo "<a href=\"/css/480.css\">asset pages</a><br/>\r\n";
//		echo "<a href=\"/css/768.css\">asset pages</a><br/>\r\n";
//		echo "<a href=\"/css/992.css\">asset pages</a><br/>\r\n";
//		echo "<a href=\"/js/libs/jquery-extended-selectors.js\">asset pages</a><br/>\r\n";
//		echo "<a href=\"/js/libs/selectivizr-min.js\">asset pages</a><br/>\r\n";
//		echo "<a href=\"/js/libs/imgsizer.js\">asset pages</a><br/>\r\n";
//		echo "<a href=\"/js/utils.js\">asset pages</a><br/>\r\n";

		if ($this->suppressnav == FALSE)
		{
			echo "<a href=\"/html/memorial.html\">victim page</a><br/>\r\n";
			echo "<a href=\"/html/homepage.html\">homepage</a><br/>\r\n";


			echo "<a href=\"repository/media/VID0001\">Vid1</a><br/>\r\n";
			echo "<a href=\"repository/media/VID0002\">Vid2</a><br/>\r\n";
		}
		
		echo "</p>";
		
		exit();
		
    }


}


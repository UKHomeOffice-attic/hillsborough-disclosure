<?php

class RepositoryController extends Zend_Controller_Action
{
	protected $document_id;
	protected $config;
	protected $layout_view;

    public function init()
    {
        $request = $this->getRequest();
		$this->document_id = $request->getParam('document');

		//check if has .html on end in which case we are froma search page so strip it
		$this->document_id = str_replace(".html", "", $this->document_id);
		
//AS: Propagate the Suppress Navigation config setting
		$layout = Zend_Layout::getMvcInstance();
      	$this->layout_view = $layout->getView();
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
        // action body
    }

    
    public function vieworganisationAction()
    {
		$layout = Zend_Layout::getMvcInstance();
      	$layout_view = $layout->getView();		
		$layout_view->backbutton = TRUE;
		$layout_view->pagetitle = "Out of scope organisation";

		$oosOrg = new Application_Model_DbTable_Organisations();		
		$document = $oosOrg->getOutOfScopeOrganisation($this->document_id);
		
		$bootstrap = $this->getInvokeArg('bootstrap');
		$config = $bootstrap->getOptions();		
		$this->view->inBrowse = FALSE;
		$this->view->document = $document;    	
    }

    public function viewfolderAction()
    {
		$layout = Zend_Layout::getMvcInstance();
      	$layout_view = $layout->getView();		
		$layout_view->backbutton = TRUE;
		$layout_view->pagetitle = "Out of scope folder";

		$oosFolder = new Application_Model_DbTable_OOSFolders();
		$document = $oosFolder->getOutOfScopeFolder($this->document_id);
		
		$bootstrap = $this->getInvokeArg('bootstrap');
		$config = $bootstrap->getOptions();		
		$this->view->inBrowse = FALSE;
		$this->view->document = $document;    	
    }
    
	protected function rearrangeDisplayNames(&$names)
	{
		foreach($names as $name)
		{
			$oldDN = $name->presentation_format;
			$commaPos = strpos($oldDN, ",");
			if ($commaPos !== false)
			{
				$surname = substr($oldDN, 0, $commaPos);
				$forenames = "";
				$role = "";
				
				$dashPos = strpos($oldDN, " -", $commaPos);
				if ($dashPos !== false)
				{
					$forenames = substr($oldDN, $commaPos + 1, ($dashPos - $commaPos) - 1);
					$role = substr($oldDN, $dashPos);
				}
				else
				{
					$forenames = substr($oldDN, $commaPos + 1);
				}

				$name->presentation_format = $forenames . " ". $surname . $role;
			}
		}
	}
	
    public function viewAction()
    {
        $disclosedMaterial = new Application_Model_DbTable_DisclosedMaterial();
		$disclosedMaterial->setDocumentid( $this->document_id );
		//$disclosedMaterial->buildQuery();
		
		
		$document = $disclosedMaterial->get();
		
		
		if (($document->ap_victim_name != ';')&&($document->ap_victim_name != '')) // an entry in victim name, you say?
		{
			$victimNameLookup = new Application_Model_DbTable_Autopopulatelookup();
			$victimNameLookup->setType( 1 );
			$victimNameLookup->setLookupIds( $document->ap_victim_name );
			
			$victim_names = $victimNameLookup->getNames();
			$this->rearrangeDisplayNames($victim_names);
		}
		else
		{
			$victim_names = null;
		}
		

		
		if (($document->ap_corporate_body != ';')&&($document->ap_corporate_body != '')) // an entry in victim name, you say?
		{
			$corporateBodyLookup = new Application_Model_DbTable_Autopopulatelookup();
			$corporateBodyLookup->setType( 2 );
			$corporateBodyLookup->setLookupIds( $document->ap_corporate_body );
			
			$corporate_bodies = $corporateBodyLookup->getNames();
		}
		else
		{
			$corporate_bodies = null;
		}
		
		if (($document->ap_person != ';')&&($document->ap_person != '')) // an entry in victim name, you say?
		{
			$corporateBodyLookup = new Application_Model_DbTable_Autopopulatelookup();
			$corporateBodyLookup->setType( 3 );
			$corporateBodyLookup->setLookupIds( $document->ap_person );
			
			$persons = $corporateBodyLookup->getNames();
			$this->rearrangeDisplayNames($persons);
		}
		else
		{
			$persons = null;
		}
		

		
		
		$layout = Zend_Layout::getMvcInstance();
      	$layout_view = $layout->getView();
		
		$layout_view->backbutton = TRUE;
		$layout_view->pagetitle = $document['short_title'];
		
		$bootstrap = $this->getInvokeArg('bootstrap');
		$config = $bootstrap->getOptions();
		
		// get filesize
		$path = $config['hillsborough']['pdflookup'] . '/' . $this->document_id . ".pdf";
		if (file_exists($path))
		{
			$this->view->pdffilesize = round(filesize($path), 0);
		}
		else
		{
			$this->view->pdffilesize = "";
		}
		
		// check if this is an av item
		if (in_array(strtolower($document->format), Application_Model_Schema::$av_formats))
		{
			$this->view->is_av = TRUE;
		}
		else
		{
			$this->view->is_av = FALSE;
		}
		
		$this->view->relatedItems = "";
		if ($document['related_material']!="")
		{
			$origText = htmlspecialchars($document['related_material']);
			$potentialLinks = Hillsborough_Functions::getAllDocumentIds($origText);
			$potentialLinks = array_unique($potentialLinks);
			
			foreach($potentialLinks as $link)
			{
				//check if link exists as disclosed (or nondisclosed item).  If so convert to hyperlink
				if ($disclosedMaterial->doesDocumentExistRegardlessOfScope($link))
					$origText = str_replace($link, "<a href=\"/repository/" . $link . "\">" . $link . "</a>", $origText);
			}
			//Check for crln to break into <LI> items
			$listItems = explode("\r\n", $origText);
	
			foreach($listItems as $newListitems)
				$this->view->relatedItems .= "<li>" . $newListitems . "</li>";
		}
		
 		$this->view->inBrowse = TRUE;
		$this->view->mediapath = $config['hillsborough']['mediapath'];
		$this->view->downloadpath = $config['hillsborough']['downloadpath'];
		$this->view->transcriptpath = $config['hillsborough']['transcriptpath'];
		$this->view->victim_names = $victim_names;
		$this->view->corporate_bodies = $corporate_bodies;
		$this->view->persons = $persons;
		$this->view->document = $document;
    }

    public function viewmediaAction()
    {

        $disclosedMedia = new Application_Model_DbTable_DisclosedMedia();
		$document = $disclosedMedia->get($this->document_id);
				
		
		$layout = Zend_Layout::getMvcInstance();
      	$layout_view = $layout->getView();
		
		$layout_view->backbutton = TRUE;
		$layout_view->pagetitle = $document['title'];
		
		$bootstrap = $this->getInvokeArg('bootstrap');
		$config = $bootstrap->getOptions();
				
		
		$this->view->inBrowse = TRUE;
		$this->view->mediapath = $config['hillsborough']['mediapath'];
		$this->view->downloadpath = $config['hillsborough']['downloadpath'];
		$this->view->transcriptpath = $config['hillsborough']['transcriptpath'];
		$this->view->document = $document;
    }
    
}




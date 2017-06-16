<?php

class ErrorController extends Zend_Controller_Action
{
	protected $layout_view;

//AS: Propagate the Suppress Navigation config setting
    public function init()
    {
        /* Initialize action controller here */
		$layout = Zend_Layout::getMvcInstance();
		

		if (strpos(strtolower($_SERVER["REQUEST_URI"]), "/error")!==FALSE)
		{
			$layout->setLayout("errorlayout");
		}
			
		$this->layout_view = $layout->getView();
      	
      	
		$bootstrap = $this->getInvokeArg('bootstrap');
		$config = $bootstrap->getOptions();

		if ((isset($config['hillsborough']['suppress_navigation']))&&($config['hillsborough']['suppress_navigation']))
				{
			$this->layout_view->suppressnav = TRUE;
		}
    }
//AS: End

    public function formattederrorAction()
    {
   		// get error code
		$request = $this->getRequest();
		$errNo = $request->getParam('error');

      	
		
		
		$this->layout_view->pagetitle = $this->view->pagetitle = "Error ";
		$this->layout_view->nobreadcrumb = TRUE;		
		
		switch($errNo)
		{
			case '400':
				$this->view->message = 
					"<p><strong>We're sorry but the website can't find what you're looking for, try changing the way you searched.</strong> </p><p>If you used a Boolean operator (AND, OR, NOT), please ensure that it is placed between search terms and not at the beginning or end ie 'Taylor AND report' rather than 'AND Taylor report'.</p>";
				break;
			case '403':
			case '404':
				$this->view->message = 
						"<p><strong>We're sorry but the page you're looking for couldn't be found.</strong></p>" . 
						"<p>The problem has been reported.</p>";
				break;
			case '405':
				$this->view->message = 
						"<p><strong>We're sorry but this website hasn't been designed to be accessed using the method attempted.</strong></p>" . 
						"<p>Please try again using a standard browser such as Internet Explorer, Firefox, Chrome or Safari.</p>"; 
				break;
			case '4xxg':
				$this->view->message = 
					"<p><strong>We're sorry but an unexpected error has occurred.</strong></p>" . 
					"<p>The problem has been reported.</p>";
				break;
			case '500':
				$this->view->message = 
						"<p><strong>We're sorry but an unexpected error has occurred.</strong></p>" . 
						"<p>The problem has been reported.</p>";
				break;				
			case '502':
			case '503':
			case '504':
				$this->view->message = 
						"<p><strong>We're sorry but your search is currently busy.</strong></p>" . 
						"<p>This affects all search functions (search everything; search within the Report; advanced search and advanced catalogue search).</p><p>Try using browse or the catalogue to find disclosed materials. The problem has been reported.</p>";
				break;
				
		}
		$this->view->errorcode = $errNo;
		

		$this->_helper->viewRenderer('formattederror');
    }
    
    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        
        if (!$errors || !$errors instanceof ArrayObject) {
            $this->view->message = 'You have reached the error page';
            return;
        }
        
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $priority = Zend_Log::NOTICE;
                $this->view->message = 'Page not found';
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $priority = Zend_Log::CRIT;
                $this->view->message = 'Application error';
                break;
        }
        
        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->log($this->view->message, $priority, $errors->exception);
            $log->log('Request Parameters', $priority, $errors->request->getParams());
        }
        
        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }
        
        $this->view->request   = $errors->request;
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }


}


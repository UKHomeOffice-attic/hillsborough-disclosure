<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initRequest()
	{
		$front = Zend_Controller_Front::getInstance();
		    $front->setControllerDirectory(array(
				'default' => APPLICATION_PATH . 'application/controllers',
			));
			
		$autoloader = Zend_Loader_Autoloader::getInstance();
		$autoloader->registerNamespace('Hillsborough_');
		
		// get hillsborough config
		//$config = new Zend_Config_Ini('../application/configs/hillsborough.ini');
		//Zend_Registry::set('hillsboroughonfig', $config);
		
		
	}
	
	/**
     * Initialise site navigation
     */
    protected function _initNavigation()
    {
        $this->bootstrap('layout');
        //$this->bootstrap('auth');

        $layout     = $this->getResource('layout');
        $view       = $layout->getView();

        $config     = new Zend_Config_Xml(APPLICATION_PATH . '/configs/navigation.xml', 'nav');
        $navigation = new Zend_Navigation($config);

	        Zend_Registry::set('navigation', $navigation);

        $view->navigation($navigation);

    }
	

	protected function _initConfig()
	{
		$config = new Zend_Config($this->getOptions(), true);
		Zend_Registry::set('config', $config);
		return $config;
	}

	
}


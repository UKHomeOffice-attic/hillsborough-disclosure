<?php

/**
 * Set the CSS class on the navigation menu depending on the current page
 */
class App_Plugin_PrepareNavigation extends Zend_Controller_Plugin_Abstract
{
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $viewRenderer = Zend_Controller_Action_HelperBroker::getExistingHelper('ViewRenderer');
        $viewRenderer->initView();
        $view = $viewRenderer->view;
       
        $navigation = Zend_Registry::get('navigation');
        $currentUri = $request->getRequestUri();        
        foreach ($navigation->getPages() as $page) {
            $uri = $page->getHref();
            $sectionActive = false;                       
            if ($uri == $currentUri) {
                if ($page->getPages()) {
                    $page->setClass('active active-parent');
                } else {
                    $page->setClass('active');
                }
                $sectionActive = true;
            }

            foreach ($page->getPages() as $subPage) {                
                $subUri = $subPage->getHref(); 
                if ($subUri == $currentUri || substr($subUri, 0, -2) == substr($currentUri, 0, -2)) {
                    $page->setClass('active active-parent');
                    $subPage->setClass('active-sub');
                    $sectionActive = true;
                }                
            }

            if (! $sectionActive) {
                foreach ($page->getPages() as $subPage) {
                    $subPage->setVisible(false);
                }
            }
        }
    }
}
<?php

class Pd_PwgDemos extends Pwg_Application {
    
    protected $defaultControllerId = 'Pd_Index';

    protected $defaultAssetsPlaceholder = '{PD}';
 
    function doOnInitialize() {
        parent::doOnInitialize();
        $this->controllers['Pd_Index'] = array(
            'class' => 'Pd_Index',
        );
    }
    
    function getAppClassFile() {
        return __FILE__;
    }
    
    static function getInstance($id = null) {
        return Ac_Application::getInstance('Pd_PwgDemos', $id);
    }
    
}
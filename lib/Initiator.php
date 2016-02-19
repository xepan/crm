<?php

namespace xepan\crm;

class Initiator extends \Controller_Addon {
    
    public $addon_name = 'xepan_crm';

    function init(){
        parent::init();
        
        $this->routePages('xepan_crm');

        $m = $this->app->top_menu->addMenu('Crm');
        $m->addItem('SupportTicket','xepan_crm_supportticket');
       
        
        $this->addLocation(array('template'=>'templates'));
    }
}

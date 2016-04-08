<?php

namespace xepan\crm;

class Initiator extends \Controller_Addon {
    
    public $addon_name = 'xepan_crm';

    function init(){
        parent::init();
        
        $this->routePages('xepan_crm');
        $this->addLocation(array('template'=>'templates'));

        if($this->app->is_admin){
	        $m = $this->app->top_menu->addMenu('Crm');
            $m->addItem('SupportTicket','xepan_crm_supportticket');
	        $m->addItem('Configuration','xepan_crm_config');
            $cont = $this->add('xepan\crm\Controller_FilterEmails');

            $this->app->addHook('emails_fetched',[$cont,'emails_fetched']);
	    }
	              
    }
}

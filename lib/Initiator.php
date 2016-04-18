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

    function generateInstaller(){
        $this->app->epan=$this->app->old_epan;
        $truncate_model = ['Ticket_Comments','Ticket_Attachment','SupportTicket'];
        foreach ($truncate_tables as $t) {
            $m=$this->add('xepan\crm\Model_'.$t);
            foreach ($m as $mt) {
                $mt->delete();
            }
        }
        
        $this->app->epan=$this->app->new_epan;
    }
}

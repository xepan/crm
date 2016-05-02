<?php

namespace xepan\crm;

class Initiator extends \Controller_Addon {
    
    public $addon_name = 'xepan_crm';

    function setup_admin(){
        
        $this->routePages('xepan_crm');
        $this->addLocation(array('template'=>'templates'));

        if($this->app->is_admin){
	        $m = $this->app->top_menu->addMenu('Crm');
            $m->addItem(['SupportTicket','icon'=>'fa fa-file-text-o'],$this->app->url('xepan_crm_supportticket',['status'=>'Pending,Assigned']));
	        $m->addItem(['Configuration','icon'=>'fa fa-cog fa-spin'],'xepan_crm_config');
            $cont = $this->add('xepan\crm\Controller_FilterEmails');

            $this->app->addHook('emails_fetched',[$cont,'emails_fetched']);
        }
                
        return $this;  
    }

    function setup_frontend(){
        $this->routePages('xepan_crm');
        $this->addLocation(array('template'=>'templates'));

        $cont = $this->add('xepan\crm\Controller_FilterEmails');
        $this->app->addHook('emails_fetched',[$cont,'emails_fetched']);
        
        return $this;
    }

    function resetDB(){
        if(!isset($this->app->old_epan)) $this->app->old_epan = $this->app->epan;
        if(!isset($this->app->new_epan)) $this->app->new_epan = $this->app->epan;
        
        $this->app->epan=$this->app->old_epan;
        $truncate_model = ['Ticket_Comments','Ticket_Attachment','SupportTicket'];
        foreach ($truncate_model as $t) {
            $m=$this->add('xepan\crm\Model_'.$t);
            foreach ($m as $mt) {
                $mt->delete();
            }
        }
        
        $this->app->epan=$this->app->new_epan;
    }
}

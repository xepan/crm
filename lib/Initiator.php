<?php

namespace xepan\crm;

class Initiator extends \Controller_Addon {
    
    public $addon_name = 'xepan_crm';

    function setup_admin(){
        
        $this->routePages('xepan_crm');
        $this->addLocation(array('template'=>'templates'));

        if($this->app->is_admin && !$this->app->isAjaxOutput() && !$this->app->getConfig('hidden_xepan_crm',false)){
	        $m = $this->app->top_menu->addMenu('Crm');
            // $m->addItem(['Dashboard','icon'=>'fa fa-dashboard'],$this->app->url('xepan_crm_dashboard'));
            $m->addItem(['Customers','icon'=>'fa fa-male'],'xepan_commerce_customer');
            $m->addItem(['SupportTicket','icon'=>'fa fa-file-text-o'],$this->app->url('xepan_crm_supportticket',['status'=>'Pending,Assigned']));
            $m->addItem(['Configuration','icon'=>'fa fa-cog fa-spin'],'xepan_crm_config');
            $cont = $this->add('xepan\crm\Controller_FilterEmails');

            $this->app->addHook('emails_fetched',[$cont,'emails_fetched']);

            $emp_emails = $this->app->employee->getAllowSupportEmail();
            if($emp_emails){
                $email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
                $email_setting->addCondition('is_active',true);
                $email_setting->addCondition('id',$emp_emails);

                $allow_email=[];
                foreach ($emp_emails as  $email) {
                    $allow_email[] = $email;
                }
                $st = $this->add('xepan\crm\Model_SupportTicket');
                $st->addCondition('status',['Pending','Assigned']);

                $st->addCondition(
                    $st->dsql()->orExpr()
                        ->where('to_id',$allow_email)
                        ->where($st->dsql()->expr('[0] = [1]',[$st->getElement('assign_to_id'),$this->app->employee->id]))
                );    

                $st_count= $st->count()->getOne();

                $this->app->js(true)->append("<span style='width:auto;top:7px;border-radius:0.5em;padding:1px 2px' class='count'>". $st_count ."</span>")->_selector('a:contains(Crm)');
            }            

            $this->app->status_icon["xepan\crm\Model_SupportTicket"] = ['All'=>'fa fa-globe','Pending'=>"fa fa-clock-o xepan-effect-warinig",'Assigned'=>'fa fa-male text-primary','Closed'=>'fa fa-times-circle-o text-success','Rejected'=>'fa fa-times text-danger'];

            // $this->app->user_menu->addItem(['My Issue','icon'=>'fa fa-edit'],'xepan_crm_solution&status=Draft');            
            $this->app->user_menu->addItem(['My Issue','icon'=>'fa fa-file-text-o'],$this->app->url('xepan_crm_solution',['status'=>'Draft,Pending']));
        }

        $search_supportticket = $this->add('xepan\crm\Model_SupportTicket');
        $this->app->addHook('quick_searched',[$search_supportticket,'quickSearch']);
        $this->app->addHook('activity_report',[$search_supportticket,'activityReport']);
        $this->app->addHook('widget_collection',[$this,'exportWidgets']);
        $this->app->addHook('entity_collection',[$this,'exportEntities']);

        return $this;  
    }

    function setup_frontend(){
        $this->routePages('xepan_crm');
        $this->addLocation(array('template'=>'templates'));

        $cont = $this->add('xepan\crm\Controller_FilterEmails');
        $this->app->addHook('emails_fetched',[$cont,'emails_fetched']);
        
        return $this;
    }

    function exportWidgets($app,&$array){
        $array[] = ['xepan\crm\Widget_PendingTickets','level'=>'Individual','title'=>'Pending Tickets'];
    }

    function exportEntities($app,&$array){
        $array['SupportTicket'] = ['caption'=>'SupportTicket','type'=>'DropDown','model'=>'xepan\crm\Model_SupportTicket'];
        $array['SUPPORT_SYSTEM_CONFIG'] = ['caption'=>'SUPPORT_SYSTEM_CONFIG','type'=>'DropDown','model'=>'xepan\crm\Model_SUPPORT_SYSTEM_CONFIG'];
    }

    function resetDB(){
        // if(!isset($this->app->old_epan)) $this->app->old_epan = $this->app->epan;
        // if(!isset($this->app->new_epan)) $this->app->new_epan = $this->app->epan;
        
        // $this->app->epan=$this->app->old_epan;
        // $truncate_model = ['Ticket_Comments','Ticket_Attachment','SupportTicket'];
        // foreach ($truncate_model as $t) {
        //     $m=$this->add('xepan\crm\Model_'.$t);
        //     foreach ($m as $mt) {
        //         $mt->delete();
        //     }
        // }
        
        // $this->app->epan=$this->app->new_epan;
    }
}

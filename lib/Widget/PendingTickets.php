<?php

namespace xepan\crm;

class Widget_PendingTickets extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->view = $this->add('View',null,null,['view\pending-widget']);
	}

	function recursiveRender(){
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
        	$this->view->template->trySet('st_count',$st_count);
        }else{
        	$this->view->template->trySet('st_count',0);
        }

        $this->view->template->trySet('url',$this->app->url('xepan_crm_supportticket'));
		
        return parent::recursiveRender();
	}
}
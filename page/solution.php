<?php

namespace xepan\crm;

/**
* 
*/
class page_solution extends \xepan\base\Page{
	public $title="My Solutions";
	function init(){
		parent::init();

		$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
		$email_setting->addCondition('is_support_email',true);

		$form = $this->add('Form');
		$complain_field = $form->addField('xepan\base\DropDown','complain_to')->validate('required');
		$complain_field->setEmptyText("Please Select");
		$complain_field->setModel($email_setting,['name']);

		$form->addField('line','subject')->validate('required');
		$form->addField('text','message');

		$form->addSubmit('Submit')->addClass('btn btn-primary');

		if($form->isSubmitted()){
			$est_model = $this->add('xepan\communication\Model_Communication_EmailSetting')->load($form['complain_to']);
			
			$ticket = $this->add('xepan\crm\Model_SupportTicket');
			$ticket['to_id'] = $form['complain_to'];	
			$ticket['to_raw'] = json_encode(['name'=>$est_model['name'],'email'=>$est_model['email_username']]);	
			$ticket['contact_id'] = $this->app->employee->id;	
			$ticket['from_id'] = $this->app->employee->id;	
			$ticket['from_email'] = $this->app->employee['first_email'];	
			$ticket['subject'] = $form['subject'];	
			$ticket['message'] = $form['message'];	
			$ticket['status'] = 'Draft';
			$ticket->save();

			$form->js(null, $form->js()->univ()->successMessage('Ticket Created SuccessFully'))->reload()->execute();	
		}

		$my_ticket = $this->add('xepan\crm\Model_SupportTicket');
		$crud = $this->add('xepan\hr\CRUD',['allow_add'=>false],null,['view/supportticket/mysolution']);

		$crud->setModel($my_ticket,['contact_id','subject','message','priority'],['id','contact','created_at','subject','last_comment','from_email','ticket_attachment','task_status','task_id']);

		$f = $crud->grid->addQuickSearch(['title','contact','id']);

		$s_f =$f->addField('xepan\base\DropDown','status')->setEmptyText("All Status");
		$s_f->setValueList(['Draft'=>'Draft','Pending'=>'Pending','Assigned'=>'Assigned','Closed'=>'Closed','Rejected'=>"Rejected"]);
		$s_f->js('change',$f->js()->submit());

		$f->addHook('appyFilter',function($f,$m){
			
			switch ($f['status']) {
					case 'Draft':
						$m->addCondition('status','Draft');
						break;
					case 'Pending':
						$m->addCondition('status','Pending');
						break;
					case 'Assigned':
						$m->addCondition('status','Assigned');
						break;		
					case 'Closed':
						$m->addCondition('status','Closed');
						break;
					case 'Rejected':
						$m->addCondition('status','Rejected');
						break;
					default:
						# code...
						break;
				}
		});
	}
}
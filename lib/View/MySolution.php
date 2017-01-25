<?php

namespace xepan\crm;

/**
* 
*/
class View_MySolution extends \View{
	public $title="";
	function init(){
		parent::init();
		$status = $this->app->stickyGET('status');

		$crud = $this->add('xepan\hr\CRUD',['allow_add'=>false],null,['view/supportticket/mysolution']);
		$crud->grid->add('Button',null,'grid_buttons')->set('Submit A Issue')->addClass('btn btn-primary solution-add-form');		
		$crud->grid->addClass('trigger-reload');
		$crud->grid->js('reload')->reload();
		$vp = $this->add('VirtualPage')->set(function($page)use($crud){
			$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
			$email_setting->addCondition('is_support_email',true);
			$email_setting->addCondition('is_active',true);
			// if($this->app->employee->getAllowSupportEmail()){
			// 	$email_setting->addCondition('id',$this->app->employee->getAllowSupportEmail());
			// }else{
			// 	$email_setting->addCondition('id',-1);
			// }
			$form = $page->add('Form');
			$complain_field = $form->addField('xepan\base\DropDown','complain_to')->validate('required');
			$complain_field->setEmptyText("Please Select");
			$complain_field->setModel($email_setting,['name']);
			$form->addField('line','subject')->validate('required');
			$form->addField('xepan\base\RichText','message');

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

				$my_emails = $this->add('xepan\hr\Model_Post_Email_MyEmails');
				$my_emails->addCondition('id',$ticket['to_id']);
				$my_emails->tryLoadAny();
				
				$emp = $this->add('xepan\hr\Model_Employee');
				$emp->addCondition('post_id',$my_emails['post_id']);
				
				$post_employee=[];
				foreach ($emp as  $employee) {
					$post_employee[] = $employee->id;
				}
				$this->app->employee
					->addActivity("Create New Support Ticket : From '".$ticket['contact_name']. " ticket no ' ", $ticket->id, $ticket['from_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$ticket->id."")
					->notifyto($post_employee,'Create New Ticket From : ' .$ticket['contact_name']. ', Ticket No:  ' .$ticket->id. ",  to :  ".$my_emails['name']. ",  " .  "  Related Message :: " .$ticket['subject']);
				
				$js = [
						$form->js()->closest('.dialog')->dialog('close')->univ()->successMessage('Ticket Created SuccessFully'),
						$crud->grid->js()->trigger('reload')
					];
				$form->js(null,$js)->reload()->execute();	
			}
		});

		// $grid->js('click',$this->js()->univ()->frameURL('Create Issue',[$this->app->url()]))->_selector('.solution-add-form');
		$crud->grid->js('click')
			->_selector('.solution-add-form')
			->univ()->frameURL('Create Issue',[$vp->getURL()]);
		
		$my_ticket = $this->add('xepan\crm\Model_SupportTicket');
		$my_ticket->addCondition('contact_id',$this->app->employee->id);
		
		if($status){
			$my_ticket->addCondition('status',explode(",",$status));
		}

		$my_ticket->setOrder('id','desc');
		$crud->setModel($my_ticket,['contact_id','subject','message','priority'],['id','contact','created_at','subject','last_comment','from_email','task_status','task_id']);
		$crud->add('xepan\base\Controller_Avatar',['options'=>['size'=>45,'border'=>['width'=>0]],'name_field'=>'contact','default_value'=>'','image_field','image_avtar']);
		$f = $crud->grid->addQuickSearch(['contact','id']);

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

		$td_view=$this->add('xepan/crm/View_TicketDetail'/*,['reply_view'=>false]*/);//->addClass('xepan-crm-ticket-detail-view');
			$this->js(true,$td_view->js()->hide());
			// $crud->grid->js('click')->_selector('.do-view-ticket-customer')->univ()->frameURL('Customer Details',[$this->api->url('xepan_commerce_customerdetail'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-contact-id]')->data('contact-id')]);
			$crud->grid->js('click',
				[
					$td_view->js()
						->html('<div style="width:100%"><img style="width:20%;display:block;margin:auto;" src="vendor/xepan/communication/templates/images/email-loader.gif"/></div>')
						->reload(['ticket_id'=>$this->js()->_selectorThis()->data('id')]),
					$td_view->js()->show(),
					$crud->js()->hide()])
			->_selector('.support-ticket-view');

			$td_view->js('click',[$td_view->js()->hide(),$crud->js()->show()])
				->_selector('.back-to-support-ticket');



			if($this->app->stickyGET('ticket_id')){
				$ticket_model=$this->add('xepan\crm\Model_SupportTicket')->load($_GET['ticket_id']);
				$td_view->setModel($ticket_model);
				// $td_view->add('xepan\base\Controller_Avatar',['name_field'=>'contact','image_field'=>'contact_image','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$ticket_model]);
			}


	}
	
}
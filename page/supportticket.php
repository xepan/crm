<?php
namespace xepan\crm;

// class page_supportticket extends \xepan\crm\page_sidebarmystauts{
class page_supportticket extends \xepan\base\Page{
	public $title="Support Ticket";

	function init(){
		parent::init();

		$status = $this->app->stickyGET('status');
		$duration = $this->app->stickyGET('duration');

		$filter_ticket_id = $this->app->stickyGET('filter_ticket_id');
		$filter_from_date = $this->app->stickyGET('filter_from_date');
		$filter_to_date = $this->app->stickyGET('filter_to_date');
		$filter_customer_id = $this->app->stickyGET('filter_customer_id');
		// apply from date and to date condition on field like closed_at, 
		$apply_date_filter_on_field = $this->app->stickyGET('apply_date_filter_on_field')?:'created_at';

		$filter_form = $this->add('Form');
		$filter_form->add('xepan\base\Controller_FLC')
			->showLables(true)
			->addContentSpot()
			->makePanelsCoppalsible(true)
			->layout([
					'ticket'=>'Filter Form~c1~3',
					'customer'=>'c2~3',
					'from_date'=>'c3~2',
					'to_date'=>'c4~2',
					'FormButtons~'=>'c5~2'
				]);

		$st_m  = $this->add('xepan\crm\Model_SupportTicket');
		$st_m->add('xepan\base\Controller_TopBarStatusFilter');
		// $st_m->addExpression('title_field')->set($st_m->dsql()->expr('CONCAT([0],"::",[1])',[$st_m->getElement('name'),$st_m->getElement('contact_name')]));
		$st_m->addCondition('status',explode(",",$status));

		$ticket_field = $filter_form->addField('DropDown','ticket');
		$model = $ticket_field->setModel($st_m);
		$ticket_field->setEmptyText('Please Select Ticket');
		

		$customer_field = $filter_form->addField('DropDown','customer');
		$cm = $this->add('xepan\commerce\Model_Customer',['title_field'=>'unique_name'])
					->addCondition('status','Active');
		$customer_field->setModel($cm);
		$customer_field->setEmptyText('Please Select Customer');

		$from_date = $filter_form->addField('DateTimePicker','from_date');
		if(strtotime($filter_from_date) > 0){
			$from_date->set($filter_from_date);
		}else
			$from_date->set($this->app->today);

		$to_date = $filter_form->addField('DateTimePicker','to_date');
		if(strtotime($filter_to_date) > 0){
			$to_date->set($filter_to_date);
		}else{
			$to_date->set($this->app->now);
		}
		$filter_form->addSubmit('Filter')->addClass('btn btn-primary');
			

		// ----------
		$this->js(true)->_selector('.xepan-crm-reply-tool')->hide();

		$st = $this->add('xepan\crm\Model_SupportTicketData');
		// used for support ticket tat status
		if($duration){
			$temp = explode("-", $duration);
	        $min_hour = $temp[0]?:0;
	        $max_hour = $temp[1]?:0;

	        $min_minute = $min_hour * 60;
	        $max_minute = $max_hour * 60;

	        $duration_variable = strtolower($status)."_duration";
	        if($min_minute >= 0){
				$st->addCondition($duration_variable,'>=',$min_minute);
	        }
	        if($max_minute){
				$st->addCondition($duration_variable,'<',$max_minute);
	        }
	        $st->addCondition($duration_variable,'<>',null);

		}else{
			$st->getElement('pending_duration')->destroy();
			$st->getElement('assigned_duration')->destroy();
			$st->getElement('closed_duration')->destroy();
			$st->getElement('rejected_duration')->destroy();
		}
		
		$st->addCondition('status','<>','Draft');
		if($status)
			$st->addCondition('status',explode(",",$status));
		
		$st->addCondition(
					$st->dsql()->orExpr()
						->where('to_id',array_merge([0],$this->app->employee->getAllowSupportEmail()))
						->where('to_id',null)
						->where($st->dsql()->expr('[0] = [1]',[$st->getElement('assign_to_id'),$this->app->employee->id]))
				);
		unset($st->status[0]);
		// $st->add('xepan\crm\Controller_SideBarStatusFilter');
		
		// unset($st->actions['Assigned'][5]);
		// unset($st->actions['Pending'][6]);
		$crud = $this->add('xepan\hr\CRUD',['grid_class'=>'xepan\base\Grid','grid_options'=>['fixed_header'=>false]],null,['view/supportticket/grid']);
		$form = $crud->form;
		// form layout
		$form->add('xepan\base\Controller_FLC')
			->showLables(true)
			->addContentSpot()
			->makePanelsCoppalsible(true)
			->layout([
					'complain_to~Complain to Support Department'=>'Support Ticket Management~c1~6',
					'priority~Ticket Priority'=>'c2~6',
					'contact~Customer Name'=>'Customer Detail~c3~12',
					'email_to~Customer Email Ids'=>'c4~12',
					'subject~Ticket Subject'=>'Ticket/ Complain Detail~c5~12',
					'message~Ticket Message'=>'c6~12'
				]);

		$c = $this->add('xepan\base\Model_Contact');
		if($crud->isEditing()){
			$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
			$email_setting->addCondition('is_support_email',true);
			$email_setting->addCondition('is_active',true);
			
			$complain_field = $form->addField('xepan\base\DropDown','complain_to')->validate('required');
			$complain_field->setEmptyText("Please Select");
			$complain_field->setModel($email_setting,['name']);
			
			if($this->app->stickyGET('contact_id')){
				$c->load($_GET['contact_id']);
			}
			$email_to = $form->addField('email_to')->set(implode(",",$c->getEmails())?:"")->validate('required');
		}


		if($_GET['filter_ticket_id']){
			$st->addCondition('id',$_GET['filter_ticket_id']);
		}else{
			if($filter_from_date){
				$st->addCondition($apply_date_filter_on_field,'>=',$filter_from_date);
			}

			if($filter_to_date){
				$st->addCondition($apply_date_filter_on_field,'<',$this->app->nextDate($filter_to_date));
			}
			if($cid = $_GET['filter_customer_id']){
				$st->addCondition('contact_id',$cid);
			}
		}

		$st->setOrder(['last_comment desc','created_at desc']);
	
		$crud->setModel($st,['contact_id','subject','message','priority','image_avtar'],['id','contact','created_at','subject','last_comment','from_email','ticket_attachment','task_status','task_id','image_avtar','assign_to_employee','assigned_at','closed_at','rejected_at','pending_at','branch_id']);
		if($crud->isEditing()){
			$contact_field = $form->getElement('contact_id');

			$contact_field->other_field->js('change',$email_to->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$email_to->name]),'contact_id'=>$contact_field->js()->val()]));
			// $contact_field->other_field->js('change',$form->js()->atk4_form(
			// 			'reloadField','email_to',
			// 			[
			// 				$this->app->url(),
			// 				'contact_id'=>$contact_field->js()->val(),
			// 			]
			// 		));

			$form->getElement('subject');//->set($sub_view->getHTML());
			$form->getElement('message');//->set($body_view->getHTML());
		}
		


		// $crud->add('xepan\hr\Controller_ACL',['action_allowed'=>[],'permissive_acl'=>true]);
		$crud->add('xepan\base\Controller_Avatar',['options'=>['size'=>45,'border'=>['width'=>0]],'name_field'=>'contact','default_value'=>'','image_field'=>'image_avtar']);
		if($crud->isEditing()){
			if($form->isSubmitted()){
				$new_ticket = $this->add('xepan\crm\Model_SupportTicket');
				$new_ticket->addCondition('id',$st->id);
				$new_ticket->tryLoadAny();
				if($new_ticket->loaded()){

					$contact = $this->add('xepan\base\Model_Contact')->tryLoad($form['contact_id']?:0);

					if($form['email_to']){
						$to_email_ids = explode(",",$form['email_to']);
						foreach ($to_email_ids as $key => $value) {
							if(!filter_var(trim($value), FILTER_VALIDATE_EMAIL)){
								$form->error('email_to','email must be valid emai address');
							}
						}
					}else
						$to_email_ids = $contact->getEmails();

					$new_ticket['from_id'] = $form['contact_id'];
					$new_ticket['from_raw'] = ['name'=>$contact['name'],'email'=>$to_email_ids[0]];
					$new_ticket['from_name'] = $contact['name'];
					$new_ticket['from_email'] = $to_email_ids[0];
					$new_ticket['to_id'] = $form['complain_to'];
					$email_to_setting = $this->add('xepan\communication\Model_Communication_EmailSetting')->load($form['complain_to']);
					$new_ticket['to_raw'] = ['name'=>$email_to_setting['name'],'email'=>$email_to_setting['email_username']];
					$new_ticket->save();
					$config_m = $this->add('xepan\base\Model_ConfigJsonModel',
									[
										'fields'=>[
													'auto_reply_subject'=>'Line',
													'auto_reply_body'=>'xepan\base\RichText',
													'denied_email_subject'=>'Line',
													'denied_email_body'=>'xepan\base\RichText',
													'closed_email_subject'=>'Line',
													'closed_email_body'=>'xepan\base\RichText',
													],
											'config_key'=>'SUPPORT_SYSTEM_CONFIG',
											'application'=>'crm'
									]);
					$config_m->tryLoadAny();
					$email_subject=$config_m['auto_reply_subject'];
					$email_body=$config_m['auto_reply_body'];
					$subject=$this->add('GiTemplate')->loadTemplateFromString($email_subject);
					$subject->setHTML('token',$new_ticket->getToken());
					$subject->setHTML('title', $new_ticket['title']);

					$message=$this->add('GiTemplate')->loadTemplateFromString($email_body);
					$message->setHTML('contact_name',$new_ticket['contact']);
					$message->setHTML('sender_email_id',$new_ticket['from_email']);
					$message->setHTML('token',$new_ticket->getToken());

					$sub_view = $this->add('View',null,null,$subject);
					$body_view = $this->add('View',null,null,$message);
					$s = $sub_view->getHTML(). "". $form['subject'] ;
					$body = $form['message']."<br/>".$body_view->getHTML() ;
					
					$to_array=[];
					
					foreach (explode(",",$form['email_to']) as $email) {
						$to_array[]= ['name'=>null,'email'=>$email];
					}
					
					$new_ticket->createComment(
								$s,
								$body_view->getHTML(),
								"Email",
								$to_array,
								null,
								null,
								$send_setting=$email_to_setting,
								$send_also=true);
				}
				
				$my_emails = $this->add('xepan\hr\Model_Post_Email_MyEmails');
				$my_emails->addCondition('id',$new_ticket['to_id']);
				$my_emails->tryLoadAny();
				$emp = $this->add('xepan\hr\Model_Employee');
				$emp->addCondition('post_id',$my_emails['post_id']);
				$post_employee=[];
				foreach ($emp as  $employee) {
					$post_employee[] = $employee->id;
				}
				$this->app->employee
					->addActivity("Create New Support Ticket : From '".$new_ticket['contact_name']. " ticket no ' ", $new_ticket->id, $new_ticket['from_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$new_ticket->id."")
					->notifyto($post_employee,'Create New Ticket From : ' .$new_ticket['contact_name']. ', Ticket No:  ' .$new_ticket->id. ",  to :  ".$my_emails['name']. ",  " .  "  Related Message :: " .$new_ticket['subject']);
			}
		}
		if($crud->isEditing()){
			$complain_field->set($crud->model['to_id']);

		}
		
		if(!$crud->isEditing())
			$crud->grid->controller->importField('created_at');
		
		$g=$crud->grid;
		$g->addMethod('format_ticket_attachment',function($g,$f){
			if(!$g->model['ticket_attachment']){
				$g->current_row[$f]='';
			}else{
				$g->current_row_html[$f]='<a href="#" class="attachment"><i class="fa fa-paperclip"></i></a>';
			}
		});	

		$g->addFormatter('ticket_attachment','ticket_attachment');

		$crud->grid->addPaginator(10);
		$crud->grid->addQuickSearch(['document_id','contact_id','contact']);
		
		if(!$crud->isEditing()){
			$this->app->stickyGET('ticket_id');
			$this->vp = $this->add('VirtualPage')->set(function($p){
				$tst=$this->add('xepan\crm\Model_SupportTicket')->load($_GET['ticket_id']);	
				$task = $this->add('xepan\projects\Model_Task');
				$task->load($tst['task_id']);
				$detail = $p->add('xepan\projects\View_Detail',['task_id'=>$tst['task_id']]);//,null,null,['view\task_form']);
				$detail->setModel($task);
			});

			$crud->grid->js('click')->_selector('.view-related-task')->univ()->frameURL('Related Task',[$this->api->url($this->vp->getURL()),'ticket_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
			
			$td_view=$this->add('xepan/crm/View_TicketDetail');//->addClass('xepan-crm-ticket-detail-view');
			$this->js(true,$td_view->js()->hide());
			$crud->grid->js('click')->_selector('.do-view-ticket-customer')->univ()->frameURL('Customer Details',[$this->api->url('xepan_commerce_customerdetail'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-contact-id]')->data('contact-id')]);
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
				$td_view->add('xepan\base\Controller_Avatar',['name_field'=>'contact','image_field'=>'contact_image','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$ticket_model]);
			}

		}

		if($filter_form->isSubmitted()){
			$crud->js()->reload([
					'filter_ticket_id'=>$filter_form['ticket'],
					'filter_customer_id'=>$filter_form['customer'],
					'filter_from_date'=>$filter_form['from_date'],
					'filter_to_date'=>$filter_form['to_date'],
				])->execute();
		}		
		
	}

}

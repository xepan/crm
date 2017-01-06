<?php
namespace xepan\crm;

// class page_supportticket extends \xepan\base\Page{
class page_supportticket extends \xepan\crm\page_sidebarmystauts{
	public $title="Support Ticket";
	function init(){
		parent::init();
		$this->js(true)->_selector('.xepan-crm-reply-tool')->hide();
		
		$status = $this->app->stickyGET('status');
		$st=$this->add('xepan\crm\Model_SupportTicket');
		$st->addCondition('status','<>','Draft');
		// $st->app->muteACL= true;
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
		$st->setOrder(['last_comment desc','created_at desc']);
		
		// unset($st->actions['Assigned'][5]);
		// unset($st->actions['Pending'][6]);

		$crud=$this->add('xepan\base\CRUD',['grid_class'=>'xepan\base\Grid'],null,['view/supportticket/grid']);
		$form = $crud->form;

		if($crud->isEditing()){

			$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
			$email_setting->addCondition('is_support_email',true);
			$email_setting->addCondition('is_active',true);
			
			$complain_field = $form->addField('xepan\base\DropDown','complain_to')->validate('required');
			$complain_field->setEmptyText("Please Select");
			$complain_field->setModel($email_setting,['name']);
		}


		$crud->setModel($st,['contact_id','subject','message','priority','image_avtar'],['id','contact','created_at','subject','last_comment','from_email','ticket_attachment','task_status','task_id','image_avtar']);
		$form->getElement('subject');//->set($sub_view->getHTML());
		$form->getElement('message');//->set($body_view->getHTML());

		$crud->add('xepan\hr\Controller_ACL',['action_allowed'=>[],'permissive_acl'=>true]);
		$crud->add('xepan\base\Controller_Avatar',['options'=>['size'=>45,'border'=>['width'=>0]],'name_field'=>'contact','default_value'=>'','image_field','image_avtar']);
		if($crud->isEditing()){
			if($form->isSubmitted()){
				$new_ticket = $this->add('xepan\crm\Model_SupportTicket');
				$new_ticket->addCondition('id',$st->id);
				$new_ticket->tryLoadAny();
				if($new_ticket->loaded()){
					$contact = $this->add('xepan\base\Model_Contact')->load($form['contact_id']);
					$new_ticket['from_id'] = $form['contact_id'];
					$new_ticket['from_raw'] = ['name'=>$contact['name'],'email'=>$contact->getEmails()[0]];
					$new_ticket['from_name'] = $contact['name'];
					$new_ticket['from_email'] = $contact->getEmails()[0];
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
					
					$new_ticket->createComment(
								$s,
								$body,
								"Email",
								[$new_ticket['from_raw']],
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
		$frm=$crud->grid->addQuickSearch(['document_id']);

		
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
		
	}

}

<?php

namespace xepan\crm;

class Model_SupportTicket extends \xepan\hr\Model_Document{
	public $status=[
		'Draft',
		'Pending',
		'Assigned',
		'Closed',
		'Rejected'
	];
	// 'draft','submitted','solved','canceled','assigned','junk'
	public $actions=[
		'Draft'=>['view','edit','delete','submit'],
		'Pending'=>['view','edit','delete','reject','assign','closed','comment'],
		'Assigned'=>['view','edit','delete','closed','reject','comment'],
		'Closed'=>['view','edit','delete','open'],
		'Rejected'=>['view','edit','delete']
	];

	public $document_type = "SupportTicket";

	function init(){
		parent::init();
		$this->addCondition('type','SupportTicket');
		$this->getElement('created_at')->type('datetime');
		$st_j=$this->join('support_ticket.document_id');

		$st_j->hasOne('xepan\base\Contact','contact_id','unique_name')->display(['form'=>'xepan\base\Basic']);
		$st_j->hasOne('xepan\communication\Communication','communication_id');
		
		$st_j->addField('name')->defaultValue(rand(999,999999))->sortable(true);
		$st_j->addField('uid');
		

		$st_j->addField('from_email');
		$st_j->addField('from_name');

		$st_j->addField('from_raw');
		$st_j->addField('from_id');

		$st_j->addField('to_id');
		$st_j->addField('to_raw');

		$st_j->addField('cc_raw');//->type('text');
		$st_j->addField('bcc_raw');//->type('text');

		$st_j->addField('subject');
		$st_j->addField('message')->type('text')->display(array('form'=>'xepan\base\RichText'));

		$st_j->addField('task_id');
		$st_j->addField('priority')->enum(array('Low','Medium','High','Urgent'))->defaultValue('Medium')->mandatory(true);

		$st_j->addField('assigned_at')->type('datetime');
		$st_j->addField('closed_at')->type('datetime');
		$st_j->addField('rejected_at')->type('datetime');
		$st_j->addField('pending_at')->type('datetime');

		$st_j->hasMany('xepan\crm\Ticket_Comments','ticket_id',null,'Comments');
		$st_j->hasMany('xepan\crm\Ticket_Attachment','ticket_id',null,'TicketAttachments');

		$this->getElement('status')->defaultValue('Pending');
		// $this->getElement('created_at')->defaultValue($this->app->now)->sortable(true);

		$this->add('misc/Field_Callback','callback_date')->set(function($m){
			if(date('Y-m-d',strtotime($m['created_at']))==date('Y-m-d',strtotime($this->app->now))){
				return date('h:i a',strtotime($m['created_at']));	
			}
			return date('M d',strtotime($m['created_at']));
		});

		$this->addExpression('last_comment')->set(function($m,$q){
			return $m->refSQL('Comments')->setLimit(1)->setOrder('created_at','desc')->fieldQuery('created_at');
		})->sortable(true);

		$this->addExpression('assign_to_id')->set(function($m,$q){
			return $task = $m->add('xepan\projects\Model_Task',['table_alias'=>'sttiass'])
				->addCondition('id',$m->getElement('task_id'))
				->fieldQuery('assign_to_id'); 
		});

		$this->addExpression('assign_to_employee')->set(function($m,$q){
			return $task = $m->add('xepan\projects\Model_Task',['table_alias'=>'sttiass'])
				->addCondition('id',$m->getElement('task_id'))
				->fieldQuery('assign_to'); 
		});

		$this->addExpression('ticket_attachment')->set($this->refSQL('communication_id')->fieldQuery('attachment_count'));
		
		$this->addExpression('task_status')->set(function($m,$q){
			return $task = $m->add('xepan\projects\Model_Task')
					->addCondition('id',$m->getField('task_id'))
					->fieldQuery('status');

		});

		$this->addExpression('image_avtar')->set(function($m,$q){
				return $q->expr('[0]',[$m->refSQL('contact_id')->fieldQuery('image')]);
			});

		$this->addHook('afterLoad',function($m){
			$this['from_raw'] = json_decode($m['from_raw'],true);

			$this['to_raw'] = json_decode($m['to_raw'],true);
			// var_dump($this['to_raw']);
			// exit;
			$this['cc_raw'] = json_decode($m['cc_raw'],true);
			$this['bcc_raw'] = json_decode($m['bcc_raw'],true);
			$this['subject'] = $m['subject']?:'(no subject)';
		});
		
		$this->addHook('beforeSave',function($m){
			if(is_array($m['to_raw']))
				$m['to_raw'] = json_encode($m['to_raw']);
			if(is_array($m['from_raw']))
				$m['from_raw'] = json_encode($m['from_raw']);
			if(is_array($m['cc_raw']))
				$m['cc_raw'] = json_encode($m['cc_raw']);
			if(is_array($m['bcc_raw']))
				$m['bcc_raw'] = json_encode($m['bcc_raw']);
			$m['subject'] = $m['subject']?:("(no subject)");
		});

		$this->addExpression('contact_name')->set($this->refSQL('contact_id')->fieldQuery('name'));

		$this->addHook('beforeDelete',[$this,'deleteComments']);
		$this->addHook('beforeSave',[$this,'updateSearchString']);
		$this->addHook('beforeSave',[$this,'updateDates']);

	}

	function updateDates(){
		$this[strtolower($this['status'])."_at"] = $this->app->now;
	}

	function deleteComments(){
		$this->ref('Comments')->each(function($o){
			$o->delete();
		});		
	}

	function getToken(){
		return "# [ ".$this->id." ]";
	}

	function submit(){
		$this['status'] = "Pending";
		$this->app->employee
			->addActivity(" Support Ticket No :  '[#".$this->id."]'  has Submitted", $this->id, $this['to_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$this->id."")
			->notifyWhoCan('reject,assign,closed,comment','Pending');
		$this->save();
	}

	function page_comment($page){
		$comment = $page->add('xepan\crm\Model_Ticket_Comments');
		// $comment->addCondition('created_by_id',$this->app->employee->id);
		$comment->addCondition('ticket_id',$this->id);
		$comment->setOrder('created_at','desc');
		$comment->getElement('description')->display(['form'=>'xepan\base\RichText']);

		$comment_view = $page->add('xepan\hr\CRUD');
		$comment_view->setModel($comment,['title','description'],['title','description','created_by','created_at']);
		$comment_view->grid->addPaginator(10);

		$comment_view->grid->addHook('formatRow',function($g){
			$g->current_row_html['description'] = $g->model['description'];
			$g->current_row_html['created_by'] = $g->model['created_by']."<br/>".$g->model['created_at'];
		});

		$comment_view->grid->removeAttachment();
		$comment_view->grid->removeColumn('action');
		$comment_view->grid->removeColumn('created_at');
	}

	function page_assign($page){
		$task = $this->add('xepan\projects\Model_Task');
		$form  = $page->add('Form');
		$assign_field = $form->addField('xepan\base\DropDown','assign_to')->setEmptyText('Please Select Employee')->validate('required');
		$assign_field->setModel('xepan\hr\Employee');
		$form->addField('DateTimePicker','starting_date');
		$form->addField('DateTimePicker','deadline');
		$form->addField('xepan\base\DropDown','priority')
			->setValueList(['25'=>'Low','50'=>'Medium','75'=>'High','90'=>'Critical'])->setEmptyText('Select Priority');
		$form->addField('xepan\base\RichText','narration')->set($this['message']);

		$form->addSubmit('Assign')->addClass('btn btn-primary');

		if($form->isSubmitted()){
			$task['task_name'] = "Ticket: ".$this['subject'];
			$task['assign_to_id'] = $form['assign_to'];
			$task['starting_date'] = $form['starting_date'];
			$task['deadline'] = $form['deadline'];
			$task['status'] = "Assigned";
			$task['type'] = "Task";
			$task['priority'] = $form['priority'];
			$task['description'] = $form['narration'];
			$task->save();

			$this['task_id']=$task->id;
			$this['status']='Assigned';
			
			$my_emails = $this->add('xepan\hr\Model_Post_Email_MyEmails');
			$my_emails->addCondition('id',$this['to_id']);
			$my_emails->tryLoadAny();

			$emp = $this->add('xepan\hr\Model_Employee');
			$emp->addCondition('id',$form['assign_to']);
			$emp->tryLoadAny();

			$this->app->employee
			->addActivity("Support Ticket [#".$this->id."] From : '".$this['contact_name']." is Assign to You" , $this->id, $this['from_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$this->id."")
			->notifyto([$emp->id],"Support Ticket [#".$this->id."] From : '".$this['contact_name']." is Assign to You" );

			$this->saveAndUnload();

			$this->app->page_action_result = $form->js(null,$form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Ticket Assigned SuccessFully');
		}

	}

	function page_reject($p){
		if(!$this->loaded()){
			return false;	
		}
		$communication=$this->ref('communication_id');
		$mailbox=explode('#', $communication['mailbox']);
		$support_email = $this->supportEmail($mailbox[0]);

		$mail = $this->add('xepan\communication\Model_Communication_Email');
		$communication = $this->ref('communication_id');

		$email_subject = "Support Ticket Rejected [#".$this->id."]";
		
		$subject=$this->add('GiTemplate');
		$subject->loadTemplateFromString($email_subject);

		$emails_cc =[];		
		if($this['cc_raw']){
			foreach ($this['cc_raw'] as $flipped) {
				$emails_cc [] = $flipped['email'];
			}
		}

		$emails_bcc =[];		
		if($this['bcc_raw']){
			foreach ($this['bcc_raw'] as $flipped) {
				$emails_bcc [] = $flipped['email'];
			}
		}

		$form=$p->add('Form');
		$send_email_field = $form->addField('Checkbox','send_email');
		if($this['from_email']){
			$send_email_field->set(true);
		}else{
			$send_email_field->set(false);
		}
		if(!$support_email['from_email']){
			$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
			$email_setting->addCondition('is_support_email',true);
			$email_setting->addCondition('is_active',true);
			if($this->app->employee->getAllowSupportEmail()){
				$email_setting->addCondition('id',$this->app->employee->getAllowSupportEmail());
			}else{
				$email_setting->addCondition('id',-1);
			}
			$form->addField('DropDown','from_email')->setModel($email_setting);
		}
		$form->addField('line','to')->set($this['from_email']?:str_replace("<br/>", ", ", $this->ref('contact_id')->get('emails_str')));
		$form->addField('line','cc')->set(implode(", ", $emails_cc));
		$form->addField('line','bcc')->set(implode(", ", $emails_bcc));
		$form->addField('line','subject')->set($subject->render());
		$form->addField('xepan\base\RichText','email_body_or_comment')->set(' ');

		$form->addSubmit('Send')->addClass('btn btn-primary');
		if($form->isSubmitted()){
			if($form['from_email']){
				$support_email = $this->add('xepan\communication\Model_Communication_EmailSetting')->load($form['from_email']);
				$mail->setfrom($support_email['from_email'],$support_email['from_name']);
			}else{
				$mail->setfrom($support_email['from_email'],$support_email['from_name']);
			}	
			$to_emails=explode(',', trim($form['to']));
			foreach ($to_emails as $to_mail) {
				$mail->addTo($to_mail);
			}
			if($form['cc']){
				$cc_emails=explode(',', trim($form['cc']));
				foreach ($cc_emails as $cc_mail) {
						$mail->addCc($cc_mail);
				}
			}
			if($form['bcc']){
				$bcc_emails=explode(',', trim($form['bcc']));
				foreach ($bcc_emails as $bcc_mail) {
						$mail->addBcc($bcc_mail);
				}
			}

			$mail->setSubject($form['subject']);
			$mail->setBody($form['email_body_or_comment']);
			$mail['to_id']=$this['contact_id'];

			if($form['send_email']){
				$mail->send($support_email);
				$comment_msg = "Email Send SuccessFully";
			}else{
				$comment_msg = "Commented SuccessFully";
			}
	
			$this->createCommentOnlyOnReject($mail);

			$this['status']='Rejected';
			$this->save();
			$my_emails = $this->add('xepan\hr\Model_Post_Email_MyEmails');
			$my_emails->addCondition('id',$this['to_id']);
			$my_emails->tryLoadAny();

			$emp = $this->add('xepan\hr\Model_Employee');
			$emp->addCondition('post_id',$my_emails['post_id']);
			$post_employee=[];
			foreach ($emp as  $employee) {
				$post_employee[] = $employee->id;
			}

			$this->app->employee
			->addActivity(" Support Ticket No : '[#".$this->id."]' rejected", $this->id, $this['contact_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$this->id."")
			->notifyTo($post_employee,"Support Ticket [#".$this->id."] Rejected  by: '".$this->app->employee['name']);

			return $form->js(null,$form->js()->univ()->closeDialog())->univ()->successMessage($comment_msg);
		}
	}

	function createCommentOnlyOnReject($related_mail){
		$comment = $this->add('xepan\crm\Model_Ticket_Comments');
		$comment['ticket_id'] = $this->id;
		$comment['communication_id'] = $related_mail->id;
		$comment['title'] = $related_mail['title'];
		$comment['description'] = $related_mail['description'];
		$comment['type'] = $related_mail['communication_type'];
		$comment->save();
		return $comment;
	}

	function reject(){
		$this['status']='Rejected';
		$this->app->employee
			->addActivity(" Support Ticket No : '[#".$this->id."]' rejected", $this->id, $this['contact_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$this->id."")
			->notifyWhoCan(' ','Rejected');
		$this->save();
	}

	function open(){
		$this['status']='Pending';
		$this->app->employee
			->addActivity(" Support Ticket No : '[#".$this->id."]' reopened", $this->id, $this['contact_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$this->id."")
			->notifyWhoCan('reject,assign,closed,comment','Pending');
		$this->save();
	}

	function page_closed($p){
		if(!$this->loaded()){
			return false;	
		}
		$communication=$this->ref('communication_id');
		$mailbox=explode('#', $communication['mailbox']);
		$support_email = $this->supportEmail($mailbox[0]);

		$mail = $this->add('xepan\communication\Model_Communication_Email');
		$communication = $this->ref('communication_id');

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

		$email_subject = $config_m['closed_email_subject'];
		$email_body = $config_m['closed_email_body'];
		// $config_model=$this->add('xepan\base\Model_Epan_Configuration');
		// $config_model->addCondition('application','crm');
		// $email_subject=$config_model->getConfig('SUPPORT_EMAIL_CLOSED_TICKET_SUBJECT');
		// $email_body=$config_model->getConfig('SUPPORT_EMAIL_CLOSED_TICKET_BODY');
		
		$subject=$this->add('GiTemplate');
		$subject->loadTemplateFromString($email_subject);
		$subject->trySetHTML('token',$this->getToken());
		$subject->trySetHTML('title', $communication['title']);
		$subject->trySetHTML('related_email_subject', $communication['title']);

		$temp=$this->add('GiTemplate');
		$temp->loadTemplateFromString($email_body);
		$temp->trySetHTML('contact_name',$this['contact']);
		$temp->trySetHTML('sender_email_id',$this['from_email']);
		$temp->trySetHTML('token',$this->getToken());
		$temp->trySetHTML('title', $communication['title']);

		// $emails_to =[];		
		// foreach ($this->getReplyEmailFromTo()['to'] as $flipped) {
		// 	$emails_to [] = $flipped['email'];
		// }
		$emails_cc =[];		
		if($this['cc_raw']){
			foreach ($this['cc_raw'] as $flipped) {
				$emails_cc [] = $flipped['email'];
			}
		}

		$emails_bcc =[];		
		if($this['bcc_raw']){
			foreach ($this['bcc_raw'] as $flipped) {
				$emails_bcc [] = $flipped['email'];
			}
		}

		$form=$p->add('Form');
		$send_email_field = $form->addField('Checkbox','send_email');
		if($this['from_email']){
			$send_email_field->set(true);
		}else{
			$send_email_field->set(false);
		}
		if(!$support_email['from_email']){
			$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
			$email_setting->addCondition('is_support_email',true);
			$email_setting->addCondition('is_active',true);
			if($this->app->employee->getAllowSupportEmail()){
				$email_setting->addCondition('id',$this->app->employee->getAllowSupportEmail());
			}else{
				$email_setting->addCondition('id',-1);
			}
			$form->addField('DropDown','from_email')->setModel($email_setting);
		}
		$form->addField('line','to')->set($this['from_email']?:str_replace("<br/>", ", ", $this->ref('contact_id')->get('emails_str')));
		// $form->addField('line','to')->set(implode(", ", $emails_to));
		$form->addField('line','cc')->set(implode(", ", $emails_cc));
		$form->addField('line','bcc')->set(implode(", ", $emails_bcc));
		$form->addField('line','subject')->set($subject->render());
		$form->addField('xepan\base\RichText','email_body')->set($temp->render());

		$form->addSubmit('Send')->addClass('btn btn-primary');
		// $form->addSubmit('close');
		if($form->isSubmitted()){
			if($form['from_email']){
				$support_email = $this->add('xepan\communication\Model_Communication_EmailSetting')->load($form['from_email']);
				$mail->setfrom($support_email['from_email'],$support_email['from_name']);
			}else{
				$mail->setfrom($support_email['from_email'],$support_email['from_name']);
			}	
			$to_emails=explode(',', trim($form['to']));
			foreach ($to_emails as $to_mail) {
				$mail->addTo($to_mail);
			}
			if($form['cc']){
				$cc_emails=explode(',', trim($form['cc']));
				foreach ($cc_emails as $cc_mail) {
						$mail->addCc($cc_mail);
				}
			}
			if($form['bcc']){
				$bcc_emails=explode(',', trim($form['bcc']));
				foreach ($bcc_emails as $bcc_mail) {
						$mail->addBcc($bcc_mail);
				}
			}

			$mail->setSubject($form['subject']);
			$mail->setBody($form['email_body']);
			$mail['to_id']=$this['contact_id'];

			if($form['send_email']){
				$mail->send($support_email);
			}
	
			$this->createCommentOnly($mail);

			$this['status']='Closed';
			$this->save();
			$my_emails = $this->add('xepan\hr\Model_Post_Email_MyEmails');
			$my_emails->addCondition('id',$this['to_id']);
			$my_emails->tryLoadAny();

			$emp = $this->add('xepan\hr\Model_Employee');
			$emp->addCondition('post_id',$my_emails['post_id']);
			$post_employee=[];
			foreach ($emp as  $employee) {
				$post_employee[] = $employee->id;
			}

			$this->app->employee
			->addActivity("Support Ticket [#".$this->id."] Closed  by: '".$this->app->employee['name'], $this->id, $this['from_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$this->id."")
			->notifyto($post_employee,"Support Ticket [#".$this->id."] Closed  by: '".$this->app->employee['name']);

			return $form->js(null,$form->js()->univ()->closeDialog())->univ()->successMessage("Email Send SuccessFully");
		}
	}

	function createCommentOnly($related_mail){
		if($this['status']=='Closed')
			$this->open();
		
		$comment = $this->add('xepan\crm\Model_Ticket_Comments');
		$comment['ticket_id'] = $this->id;
		$comment['communication_id'] = $related_mail->id;
		$comment['title'] = $related_mail['title'];
		$comment['description'] = $related_mail['description'];
		$comment['type'] = $related_mail['communication_type'];
		$comment->save();

		return $comment;
	}


	function createComment($subject,$message,$type,$to,$cc=[],$bcc=[],$send_setting=null,$send_also=true){
		if(!in_array($type,['Phone','Email','Comment','SMS']))
			throw $this->exception('Comment type is wrong')
						->addMoreInfo('Porvided Type',$type)
						->addMoreInfo('Accepted Type','Phone,Email.Comment,SMS');

		$ticket_comm = $this->ref('communication_id');

		if($type=='Email' && !$send_setting) $send_setting = $this->supportEmail();
		
		$comm = $this->add('xepan\communication\Model_Communication_'.$type);
		$comm->setSubject($subject);
		$comm->setBody($message);
		
		$comm->setfrom($send_setting['from_email'],$send_setting['from_name']);
		// throw new \Exception(var_dump($to), 1);
		foreach ($to as $_to) {
			// var_dump($_to);
			$comm->addTo($_to['email'],$_to['name']);
		}
		if($cc){
			foreach ($cc as $_cc) {
				$comm->addCc($_cc['email'],$_cc['name']);
			}
		}

		if($bcc){
			foreach ($bcc as $_bcc) {
				$comm->addBcc($_bcc['email'],$_bcc['name']);
			}
		}
		$comm['direction']='Out';
		$comm->save();

		if($send_also)
			$comm->send($send_setting);

		return $this->createCommentOnly($comm);
	}

	function getReplyEmailFromTo(){
		$ticket_comm = $this->add('xepan\communication\Model_Communication_Abstract_Email');
		$ticket_comm->addCondition('id',$this['communication_id']);
		$ticket_comm->tryLoadAny();
		return $ticket_comm->getReplyEmailFromTo(false);
	}

	function fetchTicketNumberFromSubject($subject){
		preg_match_all('/# \[( [0-9]+ )\]/',$subject,$preg_match_array);
		return count($preg_match_array[1])?$preg_match_array[1][0]:false;
	}

	function getTicket($subject=null){
		if(!$subject)
			return $this;

		if($relatedticket = $this->fetchTicketNumberFromSubject($subject)){
			$this->tryLoad($relatedticket);
			if($this->loaded()){
				return $this ;
			}
		}

		return $this;
	}

	function createTicket($communication){
		$this['communication_id'] = $communication->id;
		$this['uid'] = $communication['uid'];
		$this['from_id'] = $communication['from_id'];
		$this['from_raw'] = $communication['from_raw'];
		$this['from_email'] = $communication['from_raw']['email'];
		$this['from_name'] = $communication['from_raw']['name'];
		$this['to_id'] = $this->supportEmail()->id;
		$this['to_raw'] = $communication['to_raw'];
		$this['cc_raw'] = $communication['cc_raw'];
		$this['bcc_raw'] = $communication['bcc_raw'];
		$this['subject'] = $communication['title'];
		$this['message'] = $communication['description'];
		$this['contact_id'] = $communication['from_id'];
		$this['created_at'] = $communication['created_at'];
		$this['status'] = "Pending";
		$this->save();

		$my_emails = $this->add('xepan\hr\Model_Post_Email_MyEmails');
		$my_emails->addCondition('id',$this['to_id']);
		$my_emails->tryLoadAny();

		$emp = $this->add('xepan\hr\Model_Employee');
		$emp->addCondition('post_id',$my_emails['post_id']);
		$post_employee=[];
		foreach ($emp as  $employee) {
			$post_employee[] = $employee->id;
		}
		$this->app->employee
			->addActivity("New Support Ticket Created: From '".$this['contact_name']. " ticket no ' ", $this->id, $this['from_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$this->id."")
			->notifyto($post_employee,'Create New Ticket From : ' .$this['contact_name']. ', Ticket No:  ' .$this->id. ",  to :  ".$my_emails['name']. ",  " .  "  Related Message :: " .$this['subject']);
	}

	function supportEmail(){
		$original_comm = $this->ref('communication_id');
		$mail_box = explode('#',$original_comm['mailbox']);
		$mail_box = $mail_box[0];

		$support_email = $this->add('xepan\communication\Model_Communication_EmailSetting');
		if($mail_box)
			$support_email->addCondition('imap_email_username',$mail_box);
		$support_email->addCondition('is_support_email',true);

		return $support_email->tryLoadAny();
	}

	function autoReply(){

		$communication = $this->ref('communication_id');
		if(!$this->loaded()){
			return false;	
		}
		// $config_model=$this->add('xepan\base\Model_Epan_Configuration');
		// $config_model->addCondition('application','crm');

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
		$subject->setHTML('token',$this->getToken());
		$subject->setHTML('title', $communication['title']);

		$message=$this->add('GiTemplate')->loadTemplateFromString($email_body);
		$message->setHTML('contact_name',$this['contact']);
		$message->setHTML('sender_email_id',$this['from_email']);
		$message->setHTML('token',$this->getToken());

		$reverted_to_from = $this->getReplyEmailFromTo();

		$this->createComment(
				$subject->render(),
				$message->render(),
				'Email',
				$reverted_to_from['to'],
				$reverted_to_from['cc'],
				$reverted_to_from['bcc'],
				$send_setting=null,
				$send_also=true
				);
	}
	
	function replyRejection(){
		$communication = $this->ref('communication_id');
		if(!$this->loaded()){
			return false;	
		}

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

		$email_subject = $config_m['denied_email_subject'];
		$email_body = $config_m['denied_email_body'];

		$subject=$this->add('GiTemplate')->loadTemplateFromString($email_subject);
		$message=$this->add('GiTemplate')->loadTemplateFromString($email_body);
		$message->setHTML('sender_email_id',$this['from_email']);

		$reverted_to_from = $this->getReplyEmailFromTo();

		$this->createComment(
				$subject->render(),
				$message->render(),
				'Email',
				$reverted_to_from['to'],
				$reverted_to_from['cc'],
				$reverted_to_from['bcc'],
				$send_setting=null,
				$send_also=true
				);
	}

	function activityReport($app,$report_view,$emp,$start_date,$end_date){		
		$employee = $this->add('xepan\hr\Model_Employee')->load($emp);
							  					  
		$st = $this->add('xepan\crm\Model_SupportTicket');
		$st->addCondition('created_at','>=',$start_date);
		$st->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$st->addCondition('assign_to_id',$emp);
		$st_count = $st->count()->getOne();
		
		$result_array[] = [
 					'assign_to'=>$employee['name'],
 					'from_date'=>$start_date,
 					'to_date'=>$end_date,
 					'type'=> 'Support Ticket',
 					'count'=>$st_count,
 				];

		$cl = $report_view->add('CompleteLister',null,null,['view\crmactivityreport']);
		$cl->setSource($result_array);		
	}

	function updateSearchString($m){

		$search_string = ' ';
		$search_string .=" ". $this['name'];
		$search_string .=" ". $this['uid'];
		$search_string .=" ". $this['from_email'];
		$search_string .=" ". $this['from_name'];
		$search_string .=" ". $this['from_raw'];
		$search_string .=" ". $this['from_id'];
		$search_string .=" ". $this['to_id'];
		$search_string .=" ". $this['to_raw'];
		$search_string .=" ". $this['cc_raw'];
		$search_string .=" ". $this['bcc_raw'];
		$search_string .=" ". $this['subject'];
		$search_string .=" ". $this['message'];
		$search_string .=" ". $this['priority'];

		$this['search_string'] = $search_string;
		
		
	}

	function quickSearch($app,$search_string,&$result_array,$relevency_mode){
		$this->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" '.$relevency_mode.')');
		$this->addCondition('Relevance','>',0);
 		$this->setOrder('Relevance','Desc');
 		
 		
 		if($this->count()->getOne()){
 			foreach ($this->getRows() as $data) {	 				 				
 				$result_array[] = [
 					'image'=>null,
 					'title'=>$data['name'],
 					'relevency'=>$data['Relevance'],
 					'url'=>$this->app->url('xepan_crm_ticketdetails',['ticket_id'=>$data['id']])->getURL(),
 					'type_status'=>$data['type'].' '.'['.$data['status'].']',
 				];
 			}
		}
	}	
}
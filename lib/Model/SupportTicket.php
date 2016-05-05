<?php

namespace xepan\crm;

class Model_SupportTicket extends \xepan\hr\Model_Document{
	public $status=[
		'Pending',
		'Assigned',
		'Closed',
		'Rejected'
	];
	// 'draft','submitted','solved','canceled','assigned','junk'
	public $actions=[
		'Pending'=>['view','edit','delete','reject','assign','closed'],
		'Assigned'=>['view','edit','delete','closed','reject'],
		'Closed'=>['view','edit','delete'],
		'Rejected'=>['view','edit','delete']


	];
	function init(){
		parent::init();
		$this->addCondition('type','SupportTicket');
		
		$st_j=$this->join('support_ticket.document_id');

		$st_j->hasOne('xepan\base\Contact','contact_id');
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
		$st_j->addField('message')->type('text');

		$st_j->addField('priority')->enum(array('Low','Medium','High','Urgent'))->defaultValue('Medium')->mandatory(true);

		$st_j->hasMany('xepan\crm\Ticket_Comments','ticket_id',null,'Comments');
		$st_j->hasMany('xepan\crm\Ticket_Attachment','ticket_id',null,'TicketAttachments');

		$this->getElement('status')->defaultValue('Pending');
		$this->getElement('created_at')->defaultValue($this->app->now)->sortable(true);

		$this->add('misc/Field_Callback','callback_date')->set(function($m){
			if(date('Y-m-d',strtotime($m['created_at']))==date('Y-m-d',strtotime($this->app->now))){
				return date('h:i a',strtotime($m['created_at']));	
			}
			return date('M d',strtotime($m['created_at']));
		});

		$this->addExpression('last_comment')->set(function($m,$q){
			return $m->refSQL('Comments')->setLimit(1)->setOrder('created_at','desc')->fieldQuery('created_at');
		})->sortable(true);

		$this->addExpression('ticket_attachment')->set($this->refSQL('communication_id')->fieldQuery('attachment_count'));

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

	}

	function getToken(){
		return "# [ ".$this->id." ]";
	}

	function assign(){
		$this['status']='Assigned';

		$this->app->employee
			->addActivity("Converted Supportticket", $this->id, $this['ticket_id'])
			->notifyWhoCan('assign,convert,open','Converted');

		$this->saveAndUnload();
	}

	function reject(){
		$this['status']='Rejected';
		$this->app->employee
			->addActivity("Rejected Supportticket", $this->id, $this['ticket_id'])
			->notifyWhoCan('reject,convert,open','Converted');
		$this->saveAndUnload();
	}


	function page_closed($p){
		if(!$this->loaded()){
			return false;	
		}
		$communication=$this->ref('communication_id');
		$mailbox=explode('#', $communication['mailbox']);
		$support_email = $this->supportEmail($mailbox[0]);

		if(!$this['from_email']){
			return false;
		}

		$mail = $this->add('xepan\communication\Model_Communication_Email');

		$config_model=$this->add('xepan\base\Model_Epan_Configuration');
		$config_model->addCondition('application','crm');
		$email_subject=$config_model->getConfig('SUPPORT_EMAIL_CLOSED_TICKET_SUBJECT');
		$email_body=$config_model->getConfig('SUPPORT_EMAIL_CLOSED_TICKET_BODY');
		
		$subject=$this->add('GiTemplate');
		$subject->loadTemplateFromString($email_subject);
		$subject->trySetHTML('token',$this->getToken());
		$subject->trySetHTML('title', $communication['title']);

		$temp=$this->add('GiTemplate');
		$temp->loadTemplateFromString($email_body);
		$temp->trySetHTML('contact_name',$this['contact']);
		$temp->trySetHTML('sender_email_id',$this['from_email']);
		$temp->trySetHTML('token',$this->getToken());
		$temp->trySetHTML('title', $communication['title']);


		$form=$p->add('Form');
		$form->addField('Checkbox','send_email');
		$form->addField('line','to')->set($this['from_email']);
		$form->addField('line','cc');
		$form->addField('line','bcc');
		$form->addField('line','subject')->set($subject->render());
		$form->addField('xepan\base\RichText','email_body')->set($temp->render());

		$form->addSubmit('Send')->addClass('btn btn-primary');
		// $form->addSubmit('close');
		if($form->isSubmitted()){
				
			$mail->setfrom($support_email['from_email'],$support_email['from_name']);
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

			$this->createCommentOnly($mail);
			if($form['send_email']){
				$mail->send($support_email);
			}
	

			$this['status']='Closed';
			$this->save();
			$this->app->employee
				->addActivity("Closed Supportticket", $this->id, $this['ticket_id'])
				->notifyWhoCan('reject,convert,open','Converted');
			return $form->js(null,$form->js()->univ()->closeDialog())->univ()->successMessage("Email Send SuccessFully");
		}
	}

	function createCommentOnly($related_mail){
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
		foreach ($to as $_to) {
			$comm->addTo($_to['email'],$_to['name']);
		}

		foreach ($cc as $_cc) {
			$comm->addCc($_cc['email'],$_cc['name']);
		}

		foreach ($bcc as $_bcc) {
			$comm->addBcc($_bcc['email'],$_bcc['name']);
		}

		$comm->save();

		if($send_also)
			$comm->send($send_setting);

		return $this->createCommentOnly($comm);
	}

	function getReplyEmailFromTo(){
		$ticket_comm = $this->ref('communication_id');
		$support_email = explode('#',$ticket_comm['mailbox']);
		$support_email = $support_email[0];		

		$return =[
			'to'=>[['email'=>$this['from_raw']['email']]],
			'cc'=>[],
			'bcc'=>[],
			'from'=>[$support_email]
		];
		foreach ($this['to_raw'] as  $to_mail) {
			if(trim($to_mail['email']) != $support_email){
				$return['to'][] = ['email'=>trim($to_mail['email'])];
			}
		}

		if($this['cc_raw']){
			foreach ($this['cc_raw'] as  $cc_mail) {
				if(trim($cc_mail['email']) != $support_email){
					$return['cc'][]  = ['email'=>trim($cc_mail['email'])];
				}
			}	
		}

		if($this['bcc_raw']){
			foreach ($this['bcc_raw'] as  $bcc_mail) {
				if(trim($bcc_mail['email']) != $support_email){
					$return['bcc'][] = ['email'=>trim($bcc_mail['email'])];
				}
			}
		}

		return $return;
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
		$this['to_id'] = $communication['to_id'];
		$this['to_raw'] = $communication['to_raw'];
		$this['cc_raw'] = $communication['cc_raw'];
		$this['bcc_raw'] = $communication['bcc_raw'];
		$this['subject'] = $communication['title'];
		$this['message'] = $communication['description'];
		$this['contact_id'] = $communication['from_id'];
		$this['status'] = "Pending";
		$this->save();
		// foreach ($this->attachment() as $attach) {
		// 	$ticket->addAttachment($attach['attachment_url_id'],$attach['file_id']);	
		// }
	}

	function supportEmail(){
		$original_comm = $this->ref('communication_id');
		$mail_box = explode('#',$original_comm['mailbox']);
		$mail_box = $mail_box[0];

		$support_email = $this->add('xepan\communication\Model_Communication_EmailSetting');
		$support_email->addCondition(
					$support_email->dsql()->orExpr()
						->where('imap_email_username',$mail_box)
						->where('is_support_email',true)
				);			

		return $support_email->tryLoadAny();
	}

	function autoReply(){

		$communication = $this->ref('communication_id');
		if(!$this->loaded()){
			return false;	
		}
		
		$config_model=$this->add('xepan\base\Model_Epan_Configuration');
		$config_model->addCondition('application','crm');

		$email_subject=$config_model->getConfig('TICKET_GENERATED_EMAIL_SUBJECT');
		$email_body=$config_model->getConfig('TICKET_GENERATED_EMAIL_BODY');
		
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

		$config_model=$this->add('xepan\base\Model_Epan_Configuration');
		$config_model->addCondition('application','crm');
		$email_subject=$config_model->getConfig('SUPPORT_EMAIL_DENIED_SUBJECT');
		$email_body=$config_model->getConfig('SUPPORT_EMAIL_DENIED_BODY');

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
}
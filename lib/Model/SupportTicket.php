<?php

namespace xepan\crm;

class Model_SupportTicket extends \xepan\hr\Model_Document{
	public $status=[
		'Pending',
		'Activated',
		'Assigned',
		'Completed',
		'Rejected'
	];
	// 'draft','submitted','solved','canceled','assigned','junk'
	public $actions=[
		'Pending'=>['view','edit','delete','convert','reject','assign','complete'],
		'Activated'=>['view','edit','delete','open','reject','assign','complete'],
		'Assigned'=>['view','edit','delete','open','complete','reject'],
		'Completed'=>['view','edit','delete','open','reject','assign'],
		'Rejected'=>['view','edit','delete','open','complete','assign']


	];
	function init(){
		parent::init();
		$this->addCondition('type','SupportTicket');
		
		$st_j=$this->join('support_ticket.document_id');

		$st_j->hasOne('xepan\base\Contact','contact_id');
		$st_j->hasOne('xepan\communication\Communication','communication_email_id');
		
		$st_j->addField('name')->defaultValue(rand(999,999999));
		$st_j->addField('uid');
		$st_j->addField('from_id');
		$st_j->addField('from_email');
		$st_j->addField('from_name');

		$st_j->addField('to_id');
		$st_j->addField('to_email');

		$st_j->addField('cc')->type('text');
		// $st_j->addField('bcc')->type('text');

		$st_j->addField('subject');
		$st_j->addField('message')->type('text');

		$st_j->addField('priority')->enum(array('Low','Medium','High','Urgent'))->defaultValue('Medium')->mandatory(true);

		$st_j->hasMany('xepan\crm\Ticket_Comments','ticket_id',null,'Comments');
		$st_j->hasMany('xepan\crm\Ticket_Attachment','ticket_id',null,'TicketAttachments');

		$this->getElement('status')->defaultValue('Pending');
		$this->getElement('created_at')->defaultValue($this->app->now);


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

	function complete(){
		$this['status']='Completed';
		$this->app->employee
			->addActivity("Completed Supportticket", $this->id, $this['ticket_id'])
			->notifyWhoCan('reject,convert,open','Converted');
		$this->saveAndUnload();
	}

	function createComment($communication){
		$comment = $this->add('xepan\crm\Model_Ticket_Comments');
		$comment['ticket_id'] = $this->id;
		$comment['communication_email_id'] = $communication->id;
		$comment->save();


	}

	function fetchTicketNumberFromSubject($subject){
		preg_match_all('/\[#([0-9]+)\]/',$subject,$preg_match_array);
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
		$this['communication_email_id'] = $communication->id;
		$this['uid'] = $communication['uid'];
		$this['from_id'] = $communication['from_id'];
		$this['from_email'] = $communication['from_raw']['email'];
		$this['from_name'] = $communication['from_raw']['name'];
		$this['to_id'] = $communication['to_id'];
		$this['to_email'] = $communication['to_raw'];
		$this['cc'] = $communication['cc_raw']['email'];
		$this['subject'] = $communication['title'];
		$this['message'] = $communication['description'];
		$this['contact_id'] = $communication['from_id'];
		$this['status'] = "Pending";
		$this->save();
		// foreach ($this->attachment() as $attach) {
		// 	$ticket->addAttachment($attach['attachment_url_id'],$attach['file_id']);	
		// }
	}

	function supportEmail($to_email=null){
		$support_email = $this->add('xepan\base\Model_Epan_EmailSetting');
		$support_email->addCondition(
					$support_email->dsql()->orExpr()
						->where('imap_email_username',$to_email)
						->where('is_support_email',true)
				);			

		return $support_email->tryLoadAny();
	}

	function autoReply($communication){
		
		if(!$this->loaded()){
			return false;	
		}
		$mailbox=explode('#', $communication['mailbox']);
		$support_email = $this->supportEmail($mailbox[0]);

		if(!$this['from_email']){
			return false;
		}

		$mail = $this->add('xepan\communication\Model_Communication_Email');

		$config_model=$this->add('xepan\base\Model_Epan_Configuration');
		$config_model->addCondition('application','crm');
		// $email_subject=$communication['title'] ." ".$config_model->getConfig('TICKET_GENERATED_EMAIL_SUBJECT')." [ ".$this->id. " ]  " .;

		$email_subject=$config_model->getConfig('TICKET_GENERATED_EMAIL_SUBJECT');
		$email_body=$config_model->getConfig('TICKET_GENERATED_EMAIL_BODY');
		
		$subject=$this->add('GiTemplate')->loadTemplateFromString($email_subject);
		$subject->setHTML('ticket_id',"[ ".$this->id." ]");
		$subject->setHTML('title',"[ ".$communication['title']);

		$temp=$this->add('GiTemplate')->loadTemplateFromString($email_body);
		$temp->setHTML('contact_name',$this['contact']);
		$temp->setHTML('sender_email_id',$this['from_email']);
		$temp->setHTML('ticket_id',$this['name']);
		// echo $temp->render();
		// exit;		
		$mail->setfrom($support_email['from_email'],$support_email['from_name']);
		$mail->addTo($this['from_email']);
		$mail->setSubject($subject->render());
		$mail->setBody($temp->render());
		$mail->send($support_email);
	}
	
	function replyRejection($communication){
		// throw new \Exception("Your Email Id Not Registerd on Support", 1);
		if(!$this->loaded()){
			return false;	
		}
		$mailbox=explode('#', $communication['mailbox']);
		$support_email = $this->supportEmail($mailbox[0]);

		// $from_email="vijay.mali552@gmail.com";
		if(!$this['from_email']){
			return false;
		}
		
		$mail = $this->add('xepan\communication\Model_Communication_Email');

		$config_model=$this->add('xepan\base\Model_Epan_Configuration');
		$config_model->addCondition('application','crm');
		$email_subject=$config_model->getConfig('SUPPORT_EMAIL_DENIED_SUBJECT');
		$email_body=$config_model->getConfig('SUPPORT_EMAIL_DENIED_BODY');

		$temp=$this->add('GiTemplate')->loadTemplateFromString($email_body);
		$temp->setHTML('sender_email_id',$this['from_email']);
		// echo $temp->render();
		// exit;		
		$mail->setfrom($support_email['from_email'],$support_email['from_name']);
		$mail->addTo($this['from_email']);
		$mail->setSubject($email_subject);
		$mail->setBody($temp->render());
		$mail->send($support_email);

	}
}
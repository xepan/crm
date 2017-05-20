<?php

namespace xepan\crm;

class Controller_FilterEmails extends \AbstractController {

	function emails_fetched($app,$data){
		if(!isset($data['fetched_emails_from'])) return;
		// Emails fetched and now you can track if anything
		// 		for new ticket, 

		// 		to reply rejection
		// 		or to create a new comment for existing ticket
		
		$email_setting=$this->add('xepan\communication\Model_Communication_EmailSetting');
		$email_setting->addCondition('is_support_email',true);


		$emails=$this->add('xepan\communication\Model_Communication_Email_Received');
		$or = $emails->dsql()->orExpr();
		
		$or->where('mailbox','like','---dummy-hash----%');
		foreach ($email_setting as $es) {
			$or->where('mailbox','like','%'.$es['email_username'].'#%');
		}
		
		$emails->addCondition('id','>=',$data['fetched_emails_from']);
		$emails->addCondition($or);

		/*Config Model */
		$config_m = $this->add('xepan\base\Model_ConfigJsonModel',
			[
				'fields'=>[
							'new_customer'=>'DropDown'
							],
					'config_key'=>'CRM_Allow_New_Customer_Ticket',
					'application'=>'crm'
			]);
		$config_m->tryLoadAny();

		foreach ($emails as $email) {			
			$ticket=$this->add('xepan\crm\Model_SupportTicket');
			// check if this contains any past support number [SUP 1] like this
			$ticket->getTicket($email['title']);
			// if yes
			if($ticket->loaded()){
				// create a new comment to that support .. add $email->id as its communication_id
				$ticket->createCommentOnly($email);
			// if no
			}else{
				// create a new ticket with communication_id = this email id
				/*Check Config Value Allow New Customer Ticket Email Or Not*/
				$ticket->createTicket($email);
				if($config_m['new_customer'] === "Allowed" OR $email['from_id']){
				// if(!$email['from_id']){
					// this is junk, reply with : you are not supported
					$ticket->autoReply();
					
				// }
				}else{
					$ticket->replyRejection();
					$ticket['status']="Rejected";
					$ticket->save();
				}
			}
		}
	}
}
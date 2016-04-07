<?php

namespace xepan\crm;

class Controller_FilterEmails extends \AbstractController {

	function emails_fetched($app,$data){
		// Emails fetched and now you can track if anything
		// 		for new ticket, 

		// 		to reply rejection
		// 		or to create a new comment for existing ticket
		
		$email_setting=$this->add('xepan\base\Model_Epan_EmailSetting');
		$email_setting->addCondition('is_support_email',true);


		$emails=$this->add('xepan\communication\Model_Communication_Email_Received');
		$or = $emails->dsql()->orExpr();
		
		foreach ($email_setting as $es) {
			$or->where('to_raw','like','%'.$es['email_username'].'%');
		}
		
		$emails->addCondition('id','>',11);
		$emails->addCondition($or);

		foreach ($emails as $email) {
			if(!$email['from_id']){
				// this is junk, reply with : you are not supported
				//continue;
			}
			// check if this contains any past support number [SUP 1] like this
			// if yes
				// create a new comment to that support .. add $email->id as its communication_id
			// if no
				// create a new ticket with communication_id = this email id

		}
	}
}
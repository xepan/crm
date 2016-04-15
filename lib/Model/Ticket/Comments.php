<?php

namespace xepan\crm;

class Model_Ticket_Comments extends \xepan\base\Model_Table{
	public $table="comments";
	public $acl = false;
	function init(){
		parent::init();

		$this->hasOne('xepan\communication\Communication_Abstract_Email','communication_email_id');
		$this->hasOne('xepan\crm\SupportTicket','ticket_id');

	
	}
}
<?php

namespace xepan\crm;

class Model_Ticket_Attachment extends \xepan\base\Model_Table{
	public $table="ticket_attachment";

	function init(){
		parent::init();
		$this->hasOne('xepan\crm\SupportTicket','ticket_id');
		$this->add('filestore\Field_File','attachment_id');
	}
}
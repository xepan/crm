<?php

namespace xepan\crm;

class Model_SupportTicket extends \xepan\base\Model_Contact{
	function init(){
		parent::init();
		$st_j=$this->join('supportticket.document_id');
		// $st_j->addField('customer');
		$st_j->addField('title');

	}
}
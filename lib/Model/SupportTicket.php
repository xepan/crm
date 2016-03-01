<?php

namespace xepan\crm;

class Model_SupportTicket extends \xepan\hr\Model_Document{
	function init(){
		parent::init();
		$st_j=$this->join('supportticket.document_id');
		$st_j->hasOne('xepan\commerce\Customer','customer_id');

		$st_j->addField('title');
		$st_j->addField('description')->type('text');
		$this->getElement('status')->defaultValue('Pending');
		$this->getElement('created_at')->defaultValue($this->app->now);
		$this->addCondition('type','SupportTicket');


	}
}
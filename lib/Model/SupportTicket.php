<?php

namespace xepan\crm;

class Model_SupportTicket extends \xepan\hr\Model_Document{
	public $status=[
		'Pending',
		'Activated',
		'Assigned',
		'Completed'
	];
	public $actions=[
		'Pending'=>['view','edit','delete','convert','reject','assign','complete'],
		'Activated'=>['view','edit','delete','open','reject','assign','complete'],
		'Assigned'=>['view','edit','delete','open','convert','complete'],
		'Completed'=>['view','edit','delete','open','convert','reject']

	];
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

		function assign(){
		$this['status']='Pending';

		$this->app->employee
			->addActivity("Converted Opportunity", $this->id, $this['ticket_id'])
			->notifyWhoCan('assign,convert,open','Converted');

		$this->saveAndUnload();
	}

	function reject(){
		$this['status']='Pending';
		$this->app->employee
			->addActivity("Rejected Supportticket", $this->id, $this['ticket_id'])
			->notifyWhoCan('reject,convert,open','Converted');
		$this->saveAndUnload();
	}

	function complete(){
		$this['status']='Pending';
		$this->app->employee
			->addActivity("Completed Opportunity", $this->id, $this['ticket_id'])
			->notifyWhoCan('reject,convert,open','Converted');
		$this->saveAndUnload();
	}
}
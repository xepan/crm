<?php

namespace xepan\crm;

class Model_SupportTicket extends \xepan\hr\Model_Document{
	public $status=[
		'Pending',
		'Active',
		'Assign',
		'Completed'
	];
	public $actions=[
		'Pending'=>['view','edit','delete','convert','reject'],
		'Active'=>['view','edit','delete','open','reject'],
		'Assign'=>['view','edit','delete','open','convert'],
		'Completed'=>['view','edit','delete','open','convert']

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

	function pending(){
		$this['status']='Pending';

		$this->app->employee
			->addActivity("Converted supportticket", $this->id, $this['ticket_id'])
			->notifyWhoCan('pending,convert,open','Converted');

		$this->saveAndUnload();
	}

	function active(){
		$this['status']='Active';
		$this->app->employee
			->addActivity("Active supportticket", $this->id, $this['ticket_id'])
			->notifyWhoCan('active,convert,open','Converted');
		$this->saveAndUnload();
	}

	function assign(){
		$this['status']='Assign';
		$this->app->employee
			->addActivity("Assign supportticket", $this->id, $this['ticket_id'])
			->notifyWhoCan('assign,convert,open','Converted');
		$this->saveAndUnload();
	}
	function completed(){
		$this['status']='Completed';
		$this->app->employee
			->addActivity("Completed supportticket", $this->id, $this['ticket_id'])
			->notifyWhoCan('completed,convert,open','Converted');
		$this->saveAndUnload();
	}
}
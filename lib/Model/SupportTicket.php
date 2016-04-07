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
		$st_j->hasOne('xepan\communication\Communication','communication_id');
		
		$st_j->addField('name');
		$st_j->addField('uid');
		$st_j->addField('from_id');
		$st_j->addField('from_email');
		$st_j->addField('from_name');

		$st_j->addField('to');
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
}
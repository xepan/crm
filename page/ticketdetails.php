<?php

namespace xepan\crm;

class page_ticketdetails extends \Page{
	public $title='Ticket-Detail';

	function init(){
		parent::init();
		$ticket_id=$this->app->stickyGET('ticket_id');
		$m_ticket=$this->add('xepan/crm/Model_SupportTicket');
		$m_ticket->addCondition('id',$ticket_id);

		$crud=$this->add('xepan/hr/CRUD',null,null,['grid/ticketdetail-grid']);
		$crud->setModel($m_ticket);
	}

	// function defaultTemplate(){
	// 	return['view/ticketdetails/ticketdetails'];
	// }
}
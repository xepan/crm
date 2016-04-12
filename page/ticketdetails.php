<?php

namespace xepan\crm;

class page_ticketdetails extends \xepan\base\Page{
	public $title='Ticket-Detail';
	public $breadcrumb=['Home'=>'index','Support Ticket'=>'xepan_crm_supportticket','Details'=>'#'];

	function init(){
		parent::init();
		$ticket_id=$this->app->stickyGET('ticket_id');
		$m_ticket=$this->add('xepan/crm/Model_SupportTicket');
		$m_ticket->addCondition('id',$ticket_id);

		$crud=$this->add('xepan/hr/CRUD',null,null,['view/grid/ticketdetail-grid']);
		$crud->setModel($m_ticket);
	}

	// function defaultTemplate(){
	// 	return['view/ticketdetails/ticketdetails'];
	// }
}
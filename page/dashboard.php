<?php

namespace xepan\crm;

class page_dashboard extends \xepan\base\Page{
	public $title = 'Dashboard';
	
	function init(){
		parent::init();
		
		$ticket = $this->add('xepan\crm\Model_SupportTicket');
		$ticket->addCondition('status','!=','Rejected');
		$ticket->_dsql()->group('contact_id');
		$ticket->addExpression('count','count(*)');
		$ticket->setOrder('count','desc');

		$grid = $this->add('xepan\base\Grid',null,null,['view\grid\crmdashboard']);
		$grid->setModel($ticket);
		$grid->addPaginator(5);
	}
}
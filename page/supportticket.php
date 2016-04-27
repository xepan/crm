<?php
namespace xepan\crm;

class page_supportticket extends \Page{
	public $title="Support Ticket";
	function init(){
		parent::init();
		
		$st=$this->add('xepan\crm\Model_SupportTicket');
		$st->add('xepan\crm\Controller_SideBarStatusFilter');
		$st->setOrder(['last_comment desc','created_at desc']);
		// $st->debug();

		$crud=$this->add('xepan\hr\CRUD',null,null,['view/supportticket/grid']);
		$crud->setModel($st);
		$crud->grid->controller->importField('created_at');
		$crud->add('xepan\base\Controller_Avatar',['options'=>['size'=>45,'border'=>['width'=>0]],'name_field'=>'contact','default_value'=>'']);
		$crud->grid->addPaginator(10);
		$frm=$crud->grid->addQuickSearch(['name']);
		
	}
}

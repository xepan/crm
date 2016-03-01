<?php
namespace xepan\crm;

class page_supportticket extends \Page{
	function init(){
		parent::init();
		
		$st=$this->add('xepan\crm\Model_SupportTicket');
		$crud=$this->add('xepan\base\CRUD',null,null,['view/supportticket/grid']);
		$crud->setModel($st);
		$crud->grid->addQuickSearch(['name']);
		
	}
	// function setModel($model){
	// 	$model->getField('name')->caption('SupportTicket');
	// 	$m=parent::setModel($model);
	// 	$this->removeColumn('epan');
	// 	return $m;
		
	// }
	// function defaultTemplate(){
	// 	return[$this->grid_template];
	// }
}
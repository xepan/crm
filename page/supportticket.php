<?php
namespace xepan\crm;

class page_supportticket extends \Page{
	public $title="Support Ticket";
	function init(){
		parent::init();
		
		$st=$this->add('xepan\crm\Model_SupportTicket');
		$crud=$this->add('xepan\hr\CRUD',null,null,['view/supportticket/grid']);
		$crud->setModel($st);
		$crud->grid->addPaginator(10);
		$frm=$crud->grid->addQuickSearch(['name']);

		$frm_drop=$frm->addField('DropDown','status')->setValueList(['Pending'=>'Pending','Activated'=>'Activated','Assigned'=>'Assigned','Completed'=>'Complete','Rejected'=>'Rejected'])->setEmptyText('Status');
		$frm_drop->js('change',$frm->js()->submit());

		$frm->addHook('appyFilter',function($frm,$m){
			if($frm['document_id'])
				$m->addCondition('supportticket_id',$frm['supportticket_id']);
			
			if($frm['status']='Active'){
				$m->addCondition('status','Active');
			}else{
				$m->addCondition('status','Inactive');

			}

		});
	}

	
}


	// function setModel($model){
	// 	$model->getField('name')->caption('SupportTicket');
	// 	$m=parent::setModel($model);
	// 	$this->removeColumn('epan');
	// 	return $m;
		
	// }
	// function defaultTemplate(){
	// 	return[$this->grid_template];
	//
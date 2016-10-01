<?php

namespace xepan\crm;


/**
* 
*/
class View_Lister_TicketComments extends \CompleteLister{
	
	function init(){
		parent::init();
	}

	function setModel($model){
		$m = parent::setModel($model);
		return $m;
	}

	function formatRow(){
		$this->current_row_html['collapse_header']= strip_tags($this->model['description']);
		$this->current_row_html['body']= $this->model['description'];
		
		$attach=$this->add('xepan\communication\View_Lister_Attachment',null,'attachments');
		$attach->setModel('xepan\communication\Communication_Attachment')
				->addCondition('communication_id',$this->model['communication_id']);
		$this->current_row_html['attachments']=$attach->getHtml();

		parent::formatRow();
	}

	function defaultTemplate(){
		return ['view/ticket-comments'];
	}
}
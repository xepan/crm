<?php
namespace xepan\crm;
class View_TicketDetail extends \View{
	
	function setModel($model){
		$m=parent::setModel($model);

		$to_lister=$this->add('CompleteLister',null,'to_lister',['view/grid/ticketdetail-grid','to_lister']);
		$to_lister->setSource($m['to_raw']);
		
		if($m['cc_raw']){			
			$cc_lister=$this->add('CompleteLister',null,'cc_lister',['view/grid/ticketdetail-grid','cc_lister']);
			$cc_lister->setSource($m['cc_raw']);
		}

		$this->template->setHTML('collapse_header',strip_tags(substr($model['message'], 0,100)));
		$this->template->setHTML('email_body',$model['message']);

		$attach=$this->add('CompleteLister',null,'Attachments',['view/emails/email-detail','Attachments']);
		$attach->setModel('xepan\communication\Communication_Attachment')
				->addCondition('communication_id',$m['communication_id']);
		
		return $m;

	}
	function defaultTemplate(){
		return ['view/grid/ticketdetail-grid'];
	}
}
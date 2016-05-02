<?php
namespace xepan\crm;
class View_TicketDetail extends \View{
	function init(){
		parent::init();
	}
	function setModel($model){
		$m=parent::setModel($model);
		$to_mail=json_decode($m['to_email'],true);
		
		$to_lister=$this->add('CompleteLister',null,'to_lister',['view/grid/ticketdetail-grid','to_lister']);
		// var_dump($to_mail);
		// exit;
		$to_lister->setSource($to_mail);
		
		if($model['cc']){
			$cc_raw=json_decode($m['cc'],true);
			$cc_lister=$this->add('CompleteLister',null,'cc_lister',['view/grid/ticketdetail-grid','cc_lister']);
			$cc_lister->setSource($cc_raw);
		}

		$this->template->setHTML('collapse_header',strip_tags($model['message']));
		$this->template->setHTML('email_body',$model['message']);

		$attach=$this->add('CompleteLister',null,'Attachments',['view/emails/email-detail','Attachments']);
		$attach->setModel('xepan\communication\Communication_Attachment')->addCondition('communication_email_id',$model['communication_email_id']);
		
		return $m;

	}
	function defaultTemplate(){
		return ['view/grid/ticketdetail-grid'];
	}
}
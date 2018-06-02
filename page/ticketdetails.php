<?php

namespace xepan\crm;

class page_ticketdetails extends \xepan\base\Page{
	public $title='';
	public $breadcrumb=['Home'=>'index','Support Ticket'=>'xepan_crm_supportticket&status=Pending,Assigned','Details'=>'#'];

	function init(){
		parent::init();

		$ticket_id=$this->app->stickyGET('ticket_id');
		$this->title="Ticket-Detail [" .$ticket_id. "]";

		$ticket_model=$this->add('xepan\crm\Model_SupportTicket')->load($ticket_id);

		$td_view = $this->add('xepan/crm/View_TicketDetail');
		$td_view->template->tryDel('back_to_support_ticket_btn_wrapper');
		$td_view->setModel($ticket_model);
		// $td_view->add('xepan\base\Controller_Avatar',['options'=>['size'=>40,'border'=>['width'=>0]],'name_field'=>'contact','default_value'=>'']);
		// $td_view->add('xepan\hr\Controller_ACL');
		/*$m_comment=$this->add('xepan\crm\Model_Ticket_Comments');			
		$m_comment->addCondition('ticket_id',$ticket_id);
		
		$ticket_j = $m_comment->join('support_ticket.document_id','ticket_id');

		$comment_join = $m_comment->leftJoin('communication.id','communication_id');
		$comment_join->addField('status');

		$m_comment->setOrder('created_at','desc');
		$comment_view = $this->add('xepan\crm\View_Lister_TicketComments');
		$comment_view->setModel($m_comment);
		$comment_view->add('xepan\base\Controller_Avatar',['options'=>['size'=>50,'margin'=>0,'border'=>['width'=>0]],'name_field'=>'from','default_value'=>'','image_field','contact_image']);
*/

		// $contact = $ticket_model->ref('contact_id');
		
		// $member_phones = array_reverse($contact->getPhones());
		
		// $form = $this->add('xepan\communication\Form_Communication');
		// // $form->setLayout(['form/comment-reply']);
		// $form->setContact($contact);
		
		// // $form->getElement('email_to')->set(implode(", ", $ticket_model->ref('contact_id')->getEmails()));
		// $emails_to =[];		
		// foreach ($ticket_model->getReplyEmailFromTo()['to'] as $flipped) {
		// 	$emails_to [] = $flipped['email'];
		// }

		// $emails_cc =[];		
		// foreach ($ticket_model->getReplyEmailFromTo()['cc'] as $flipped) {
		// 	$emails_cc [] = $flipped['email'];
		// }

		// $emails_bcc =[];		
		// foreach ($ticket_model->getReplyEmailFromTo()['bcc'] as $flipped) {
		// 	$emails_bcc [] = $flipped['email'];
		// }

		// $form->getElement('email_to')->set(implode(", ", $emails_to));
		// $form->getElement('cc_mails')->set(implode(", ", $emails_cc));
		// $form->getElement('bcc_mails')->set(implode(", ", $emails_bcc));
		// $form->getElement('called_to')->set(array_pop($member_phones));
		// $form->getElement('title')->set("Re: Ticket ".$ticket_model->getToken()." ".$ticket_model['subject']);
		
		// $from_email = $form->getElement('from_email');
		// $from_email->getModel()->addcondition('is_support_email',true);
		// $from_email->set($ticket_model->supportEmail()->id);

		// $form->addSubmit('Save')->addClass('pull-right btn btn-primary');

		// if($form->isSubmitted()){
		// 	$comm = $form->process();
		// 	$ticket_model->createCommentOnly($comm);
		// 	$js=[];
		// 	$js[]=$form->js()->reload();
		// 	$js[]=$comment_lister->js()->reload();
		// 	$form->js(true,$js)->univ()->successMessage('Done')->execute();
		// }
	}
}
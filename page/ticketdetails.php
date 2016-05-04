<?php

namespace xepan\crm;

class page_ticketdetails extends \xepan\base\Page{
	public $title='Ticket-Detail';
	public $breadcrumb=['Home'=>'index','Support Ticket'=>'xepan_crm_supportticket','Details'=>'#'];

	function init(){
		parent::init();

		$ticket_id=$this->app->stickyGET('ticket_id');

		$ticket_model=$this->add('xepan\crm\Model_SupportTicket')->load($ticket_id);

		$td_view=$this->add('xepan/crm/View_TicketDetail');
		$td_view->setModel($ticket_model);
		
		$m_comment=$this->add('xepan\crm\Model_Ticket_Comments');			
		$m_comment->addCondition('ticket_id',$ticket_id);
		
		$ticket_j = $m_comment->join('support_ticket.document_id','ticket_id');
		$ticket_j->addField('from_id');
		$ticket_j->addField('to_id');

		$comment_join = $m_comment->leftJoin('communication.id','communication_id');
		$comment_join->addField('status');
		// $comment_join->addField('communication_type');


		$m_comment->addExpression('title_expression')->set(function ($m,$q){
			return $q->expr("IF([0] is null or [0]='',[1],[2])",[
					$m->getElement('title'),
					$m->refSQL('communication_id')->fieldQuery('title'),
					$m->getElement('title')
				]);
		});

		$m_comment->addExpression('message_expression')->set(function ($m,$q){
			return $q->expr("IF([0] is null or [0]='',[1],[2])",[
					$m->getElement('description'),
					$m->refSQL('communication_id')->fieldQuery('description'),
					$m->getElement('description')
				]);
		});

		$comment_lister=$this->add('xepan/hr/Grid',null,null,['view/grid/ticketdetail-comment-grid']);
		$comment_lister->setModel($m_comment)->setOrder('created_at','desc');

		$comment_lister->addMethod('format_message_expression',function($g,$f){
			$g->current_row_html[$f]= strip_tags($g->model['message_expression']);
		});

		$comment_lister->addFormatter('message_expression','message_expression');
		

		$comment_lister->removeColumn('description');

		$comment_lister->add('xepan\base\Controller_Avatar',['options'=>['size'=>45,'border'=>['width'=>0]],'name_field'=>'contact','default_value'=>'']);

		$contact = $ticket_model->ref('contact_id');
		
		$member_phones = array_reverse($contact->getPhones());
		
		$form = $this->add('xepan\communication\Form_Communication');
		$form->setContact($contact);
		
		// $form->getElement('email_to')->set(implode(", ", $ticket_model->ref('contact_id')->getEmails()));
		$emails_to =[];		
		foreach ($ticket_model->getReplyEmailFromTo()['to'] as $flipped) {
			$emails_to [] = $flipped['email'];
		}

		$emails_cc =[];		
		foreach ($ticket_model->getReplyEmailFromTo()['cc'] as $flipped) {
			$emails_cc [] = $flipped['email'];
		}

		$emails_bcc =[];		
		foreach ($ticket_model->getReplyEmailFromTo()['bcc'] as $flipped) {
			$emails_bcc [] = $flipped['email'];
		}

		$form->getElement('email_to')->set(implode(", ", $emails_to));
		$form->getElement('cc_mails')->set(implode(", ", $emails_cc));
		$form->getElement('bcc_mails')->set(implode(", ", $emails_bcc));
		
		$form->getElement('called_to')->set(array_pop($member_phones));

		if($form->isSubmitted()){
			$comm = $form->process();
			$ticket_model->createCommentOnly($comm);
			$form->js(null,$form->js()->reload())->univ()->successMessage('Done')->execute();
		}
	}
}
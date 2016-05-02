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

		$comment_join = $m_comment->leftJoin('communication.id','communication_email_id');
		$comment_join->addField('status');


		$m_comment->addExpression('title_expression')->set(function ($m,$q){
			return $q->expr("IF([0] is null or [0]='',[1],[2])",[
					$m->getElement('title'),
					$m->refSQL('communication_email_id')->fieldQuery('title'),
					$m->getElement('title')
				]);
		});

		$m_comment->addExpression('message_expression')->set(function ($m,$q){
			return $q->expr("IF([0] is null or [0]='',[1],[2])",[
					$m->getElement('description'),
					$m->refSQL('communication_email_id')->fieldQuery('description'),
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

		$form = $comment_lister->add('Form',null,'form');
		$form->addField('xepan\base\RichText','body','');

		$form->addSubmit('Send Mail');
		
		$form->onSubmit(function($form)use($ticket_id){
			$ticket = $this->add('xepan\crm\Model_SupportTicket');
			$ticket->load($ticket_id);
			$comment=$ticket->ref('Comments');

			$communication=$ticket->ref('communication_email_id');
			$mailbox=explode('#', $communication['mailbox']);
			$support = $ticket->supportEmail($mailbox[0]);

			$mail = $this->add('xepan\communication\Model_Communication_Email');
			$mail->setfrom($support['from_email'],$support['from_name']);
			$mail->addTo($ticket['from_email'],$ticket['from_name']);
			
			$to_mails=json_decode($ticket['to_email'],true);
			foreach ($to_mails as $to_mail) {
				if($to_mail['email'] != $support['from_email']){
					$mail->addTo($to_mail['email'],$to_mail['name']);
				}
			}
			if(isset($ticket['cc']) and $ticket['cc']){
				$cc_mails=json_decode($ticket['cc'],true);
				foreach ($cc_mails as $cc_mail) {
					if($cc_mail['email'] != $support['from_email']){
						$mail->addCc($cc_mail['email'],$cc_mail['name']);
					}
				}
			}
			if(isset($ticket['bcc']) and $ticket['bcc']){
				$bcc_mails=json_decode($ticket['bcc'],true);
				foreach ($bcc_mails as $bcc_mail) {
					if($bcc_mail['email'] != $support['from_email']){
						$mail->addBcc($bcc_mail['email'],$bcc_mail['name']);
					}
				}
			}

			// echo "<pre>";
			// var_dump($mail->data);

			$mail->setSubject("Re: Ticket # [".$ticket->id."] -".$ticket['subject']);
			$mail->setBody($form['body']);
			$mail->send($support);
			$mail->save();

			$comment['communication_email_id']=$mail->id;
			$comment->addCondition('created_by',$this->app->employee->id);
			$comment->addCondition('created_at',$this->app->today);

			$comment->save();

			return $this->js()->univ()->successMessage('E-Mail Send');			
		});
	}
}
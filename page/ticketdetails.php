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
		
		
		$comment_join = $m_comment->join('communication.id','communication_email_id');
		
		$comment_join->addField('title');
		$comment_join->addField('description');
		$comment_join->addField('from_id');
		$comment_join->addField('created_at');
		$comment_join->addField('status');

		$comment_lister=$this->add('xepan/hr/Grid',null,null,['view/grid/ticketdetail-comment-grid']);
		$comment_lister->addColumn('message');
		$comment_lister->setModel($m_comment);

		$comment_lister->addMethod('format_message',function($g,$f){
			$g->current_row_html[$f]= strip_tags($g->model['description']);
			// $this->current_row['body'] = strip_tags($this->current_row['body']);
		});

		$comment_lister->addFormatter('message','message');
		

		$comment_lister->removeColumn('description');

		$comment_lister->add('xepan\base\Controller_Avatar',['options'=>['size'=>45,'border'=>['width'=>0]],'name_field'=>'contact','default_value'=>'']);

		$form = $comment_lister->add('Form',null,'form');
		$form->addField('xepan\base\RichText','body','');

		$form->addSubmit('Send Mail');
		
		$form->onSubmit(function($form)use($ticket_id){
			$email_settings = $this->add('xepan\base\Model_Epan_EmailSetting');
			$email_settings->addCondition('is_support_email',true);
			$email_settings->tryLoadAny();

			$ticket = $this->add('xepan\crm\Model_SupportTicket');
			$ticket->load($ticket_id);
			$comment=$ticket->ref('Comments');
			

			$mail = $this->add('xepan\communication\Model_Communication_Email');
			$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);
			$mail->addTo($ticket['from_email']);
			$mail->setSubject($ticket['subject']);
			$mail->setBody($form['body']);
			$mail->send($email_settings);
			$mail->save();

			$comment['communication_email_id']=$mail->id;
			$comment->addCondition('created_by',$this->app->employee->id);

			$comment->save();

			return $this->js()->univ()->successMessage('E-Mail Send');			
		});
	}
}
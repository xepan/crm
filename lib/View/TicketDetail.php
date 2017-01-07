<?php
namespace xepan\crm;
class View_TicketDetail extends \View{
	public $ticket_id;
	public $reply_view = true;

	function setModel($model){
		$m=parent::setModel($model);
		// throw new \Exception($this->reply_view, 1);
		if($m['to_raw']){
		$to_lister=$this->add('CompleteLister',null,'to_lister',['view/grid/ticketdetail-grid','to_lister']);
		$to_lister->setSource($m['to_raw']);
		}
		if($m['cc_raw']){
			$cc_lister=$this->add('CompleteLister',null,'cc_lister',['view/grid/ticketdetail-grid','cc_lister']);
			$cc_lister->setSource($m['cc_raw']);
		}else{
			$this->template->trySet('cc_lister'," ");
		}

		$this->template->setHTML('collapse_header',strip_tags(substr($model['message'], 0,100))?:"No Subject");
		$this->template->setHTML('email_body',$model['message']);

		$attach=$this->add('xepan\communication\View_Lister_Attachment',null,'Attachments');
		$attach->setModel('xepan\communication\Communication_Attachment')
				->addCondition('communication_id',$m['communication_id']);
		
		$m_comment=$this->add('xepan\crm\Model_Ticket_Comments');			
		$m_comment->addCondition('ticket_id',$this->model->id);
		
		$ticket_j = $m_comment->join('support_ticket.document_id','ticket_id');

		$comment_join = $m_comment->leftJoin('communication.id','communication_id');
		$comment_join->addField('status');

		$m_comment->setOrder('created_at','desc');
		$comment_view = $this->add('xepan\crm\View_Lister_TicketComments',null,'comment_view');
		$comment_view->setModel($m_comment);
		// $comment_view->add('xepan\base\Controller_Avatar',['name_field'=>'contact','image_field'=>'contact_image','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$m_comment]);
		// $comment_view->add('xepan\base\Controller_Avatar',['options'=>['size'=>50,'margin'=>0,'border'=>['width'=>0]],'name_field'=>'from','default_value'=>'','image_field','contact_image']);
		
		$contact = $this->model->ref('contact_id');
		
		$member_phones = array_reverse($contact->getPhones());
		
		if($this->reply_view And $this->model['status'] != "Draft"){
			$form = $this->add('xepan\communication\Form_Communication',null,'reply_form');
			// $form->setLayout(['form/comment-reply']);
			$form->setContact($contact);
			
			// $form->getElement('email_to')->set(implode(", ", $ticket_model->ref('contact_id')->getEmails()));
			$emails_to =[];		
			foreach ($this->model->getReplyEmailFromTo()['to'] as $flipped) {
				$emails_to [] = $flipped['email'];
			}

			$emails_cc =[];		
			foreach ($this->model->getReplyEmailFromTo()['cc'] as $flipped) {
				$emails_cc [] = $flipped['email'];
			}

			$emails_bcc =[];		
			foreach ($this->model->getReplyEmailFromTo()['bcc'] as $flipped) {
				$emails_bcc [] = $flipped['email'];
			}

			$form->getElement('email_to')->set(implode(", ", $emails_to));
			$form->getElement('cc_mails')->set(implode(", ", $emails_cc));
			$form->getElement('bcc_mails')->set(implode(", ", $emails_bcc));
			$form->getElement('called_to')->set(array_pop($member_phones));
			$form->getElement('title')->set("Re: Ticket ".$this->model->getToken()." ".$this->model['subject']);
			$body_field = $form->getElement('body');
			$body_field->options = ['toolbar1'=>"styleselect | bold italic fontselect fontsizeselect | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | forecolor backcolor",'menubar'=>false];
			$from_email = $form->getElement('from_email');
			$from_email->getModel()->addcondition('is_support_email',true);
			$from_email->set($this->model->supportEmail()->id);

			$form->addSubmit('Save')->addClass('pull-right btn btn-primary');

			if($form->isSubmitted()){
				$comm = $form->process();
				$this->model->createCommentOnly($comm);
				// $js=[];
				// $js[]=$form->js()->reload();
				// $js[]=$comment_lister->js()->reload();
				$js = [
					$form->js()->reload(),
					$this->js()->find('.xepan-crm-reply-tool')->toggleClass('xepan-crm-reply-tool-closed'),
					$comment_view->js()->reload()
					];
				$form->js(true,$js)->univ()->successMessage('Done')->execute();
			}

			$this->js('click',$this->js()->toggleClass('xepan-crm-reply-tool-closed')->_selector('.xepan-crm-reply-tool'))->_selector('.close-reply-comment-popup');
			// $this->js('click',$this->js()->slideToggle()->_selector('.xepan-crm-reply-tool'))->_selector('.minimize-comment-popup');
		}else{
			$this->template->tryDel('reply_wrapper');
		}		

		return $m;

	}
	function defaultTemplate(){
		return ['view/grid/ticketdetail-grid'];
	}
}
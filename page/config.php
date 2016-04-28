<?php
namespace xepan\crm;

class page_config extends \Page{
	public $title="CRM Config";
	function init(){
		parent::init();
		/*Auto Reply Email Content*/
		$auto_config = $this->app->epan->config;
		$auto_subject = $auto_config->getConfig('TICKET_GENERATED_EMAIL_SUBJECT');
		$auto_body = $auto_config->getConfig('TICKET_GENERATED_EMAIL_BODY');
		$form=$this->add('Form',null,'auto-reply');
		$form->addField('line','subject')->set($auto_subject)->setFieldHint('{$ticket_id}, {$title}');
		$form->addField('xepan\base\RichText','body')->set($auto_body)->setFieldHint('{$contact_name}, {$ticket_id}, {$sender_email_id}');
		$form->addSubmit('Update');

		if($form->isSubmitted()){
			$auto_config->setConfig('TICKET_GENERATED_EMAIL_SUBJECT',$form['subject'],'crm');

			$auto_config->setConfig('TICKET_GENERATED_EMAIL_BODY',$form['body'],'crm');
			$form->js(null,$form->js()->reload())->univ()->successMessage('Update Information')->execute();
		}

		/*Reject Mail Content*/

		$reject_config = $this->app->epan->config;
		$reject_subject = $reject_config->getConfig('SUPPORT_EMAIL_DENIED_SUBJECT');
		$reject_body = $reject_config->getConfig('SUPPORT_EMAIL_DENIED_BODY');
		$form=$this->add('Form',null,'reject-reply');
		$form->addField('line','subject')->set($reject_subject);
		$form->addField('xepan\base\RichText','body')->set($reject_body)->setFieldHint('{{sender_email_id}}');
		$form->addSubmit('Update');

		if($form->isSubmitted()){
			$reject_config->setConfig('SUPPORT_EMAIL_DENIED_SUBJECT',$form['subject'],'crm');

			$reject_config->setConfig('SUPPORT_EMAIL_DENIED_BODY',$form['body'],'crm');
			$form->js(null,$form->js()->reload())->univ()->successMessage('Update Information')->execute();
		}
		/*Closed / Complete  Ticket Mail Content*/

		$close_config = $this->app->epan->config;
		$close_subject = $close_config->getConfig('SUPPORT_EMAIL_CLOSED_TICKET_SUBJECT');
		$close_body = $close_config->getConfig('SUPPORT_EMAIL_CLOSED_TICKET_BODY');
		$form=$this->add('Form',null,'close-reply');
		$form->addField('line','subject')->set($close_subject);
		$form->addField('xepan\base\RichText','body')->set($close_body)->setFieldHint('{{sender_email_id}}');
		$form->addSubmit('Update');

		if($form->isSubmitted()){
			$close_config->setConfig('SUPPORT_EMAIL_CLOSED_TICKET_SUBJECT',$form['subject'],'crm');

			$close_config->setConfig('SUPPORT_EMAIL_CLOSED_TICKET_BODY',$form['body'],'crm');
			
			$form->js(null,$form->js()->reload())->univ()->successMessage('Update Information')->execute();
		}
	}
	
	function defaultTemplate(){
		return ['page/config'];
	}
}
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
		$form->addField('line','subject')->set($auto_subject);
		$form->addField('xepan\base\RichText','body')->set($auto_body);
		$form->addSubmit('Update');

		if($form->isSubmitted()){
			$auto_config->setConfig('TICKET_GENERATED_EMAIL_SUBJECT',$form['subject'],'crm');

			$auto_config->setConfig('TICKET_GENERATED_EMAIL_BODY',$form['body'],'crm');
			$form->js(null,$form->js()->reload())->univ()->successMessage('Update Information')->execute();
		}

		/*Reject Mail Content*/

		$reject_config = $this->app->epan->config;
		$reject_subject = $reject_config->getConfig('SUPPORT_EMAIL_REGISTERED_SUBJECT');
		$reject_body = $reject_config->getConfig('SUPPORT_EMAIL_REGISTERED_BODY');
		$form=$this->add('Form',null,'reject-reply');
		$form->addField('line','subject')->set($reject_subject);
		$form->addField('xepan\base\RichText','body')->set($reject_body);
		$form->addSubmit('Update');

		if($form->isSubmitted()){
			$reject_config->setConfig('SUPPORT_EMAIL_REGISTERED_SUBJECT',$form['subject'],'crm');

			$reject_config->setConfig('SUPPORT_EMAIL_REGISTERED_BODY',$form['body'],'crm');
			$form->js(null,$form->js()->reload())->univ()->successMessage('Update Information')->execute();
		}
	}
	
	function defaultTemplate(){
		return ['page/config'];
	}
}
<?php
namespace xepan\crm;

class page_config extends \xepan\base\Page{
	public $title="CRM Config";

	public $config_model = null;

	function init(){
		parent::init();

		// $crud=$this->add('xepan\base\CRUD',null,'support_escalation');
		// $model=$this->add('xepan\crm\Model_SupportEscalation');
		// $crud->setModel($model);
		// $form= $crud->form;
		// $form->addField('post_id');
		// $form->getElement('post_id')->setModel('xepan\hr\Model_Post');	
		
		/*Auto Reply Email Content*/

		$this->config_model = $config_m = $this->add('xepan\base\Model_ConfigJsonModel',
		[
			'fields'=>[
						'auto_reply_subject'=>'Line',
						'auto_reply_body'=>'xepan\base\RichText',
						'denied_email_subject'=>'Line',
						'denied_email_body'=>'xepan\base\RichText',
						'closed_email_subject'=>'Line',
						'closed_email_body'=>'xepan\base\RichText',
						],
				'config_key'=>'SUPPORT_SYSTEM_CONFIG',
				'application'=>'crm'
		]);
		$config_m->add('xepan\hr\Controller_ACL');
		$config_m->tryLoadAny();
	}
	
	function page_autoReplyTicketCreate(){
		/*Auto Reply Email Content*/

		$config_m= $this->config_model;

		// $auto_config = $this->app->epan->config;
		// $auto_subject = $auto_config->getConfig('TICKET_GENERATED_EMAIL_SUBJECT');
		// $auto_body = $auto_config->getConfig('TICKET_GENERATED_EMAIL_BODY');
		$form=$this->add('Form');
		$form->setModel($config_m,['auto_reply_subject','auto_reply_body']);
		$form->getElement('auto_reply_subject')->set($config_m['auto_reply_subject'])->setFieldHint('{$token}, {$title}')->setCaption('Subject');
		$form->getElement('auto_reply_body')->set($config_m['auto_reply_body'])->setFieldHint('{$contact_name}, {$token}, {$sender_email_id}')->setCaption('Body');
		$form->addSubmit('Update')->addClass('btn btn-primary');

		if($form->isSubmitted()){
			$form->save();
			// $auto_config->setConfig('TICKET_GENERATED_EMAIL_SUBJECT',$form['subject'],'crm');

			// $auto_config->setConfig('TICKET_GENERATED_EMAIL_BODY',$form['body'],'crm');
			$form->js(null,$form->js()->reload())->univ()->successMessage('Update Information')->execute();
		}
	}

	function page_rejectTicketEmail(){
		/*Reject Mail Content*/

		$config_m= $this->config_model;

		// $reject_config = $this->app->epan->config;
		// $reject_subject = $reject_config->getConfig('SUPPORT_EMAIL_DENIED_SUBJECT');
		// $reject_body = $reject_config->getConfig('SUPPORT_EMAIL_DENIED_BODY');
		$form=$this->add('Form');
		$form->setModel($config_m,['denied_email_subject','denied_email_body']);
		$form->getElement('denied_email_subject')->set($config_m['denied_email_subject'])->setCaption('Subject');
		$form->getElement('denied_email_body')->set($config_m['denied_email_body'])->setFieldHint('{$sender_email_id}')->setCaption('Body');
		$form->addSubmit('Update')->addClass('btn btn-primary');

		if($form->isSubmitted()){
			$form->save();
			// $reject_config->setConfig('SUPPORT_EMAIL_DENIED_SUBJECT',$form['subject'],'crm');

			// $reject_config->setConfig('SUPPORT_EMAIL_DENIED_BODY',$form['body'],'crm');
			$form->js(null,$form->js()->reload())->univ()->successMessage('Update Information')->execute();
		}
	}

	function page_ticketCloseContent(){
		/*Closed / Complete  Ticket Mail Content*/

		$config_m= $this->config_model;

		// $close_config = $this->app->epan->config;
		// $close_subject = $close_config->getConfig('SUPPORT_EMAIL_CLOSED_TICKET_SUBJECT');
		// $close_body = $close_config->getConfig('SUPPORT_EMAIL_CLOSED_TICKET_BODY');
		$form=$this->add('Form');
		$form->setModel($config_m,['closed_email_subject','closed_email_body']);
		$form->getElement('closed_email_subject')->set($config_m['closed_email_subject'])->setFieldHint('{$token}, {$title}')->setCaption('Subject');
		$form->getElement('closed_email_body')->set($config_m['closed_email_body'])->setFieldHint('{$sender_email_id} {$token} {$title}')->setCaption('Body');
		$form->addSubmit('Update')->addClass('btn btn-primary');

		if($form->isSubmitted()){
			// $close_config->setConfig('SUPPORT_EMAIL_CLOSED_TICKET_SUBJECT',$form['subject'],'crm');

			// $close_config->setConfig('SUPPORT_EMAIL_CLOSED_TICKET_BODY',$form['body'],'crm');
			$form->save();
			$form->js(null,$form->js()->reload())->univ()->successMessage('Update Information')->execute();
		}

	}

	function page_allowNonCustomerTicket(){
		/****New Customer Create Ticket Or Not***/

		// $a_n_c_t = $tab->addTab('Allow New Customer Ticket');

		$config_m = $this->add('xepan\base\Model_ConfigJsonModel',
			[
				'fields'=>[
							'new_customer'=>'DropDown'
							],
					'config_key'=>'CRM_Allow_New_Customer_Ticket',
					'application'=>'crm'
			]);
		$config_m->add('xepan\hr\Controller_ACL');
		$config_m->tryLoadAny();
		
		$f = $this->add('Form');
		$f->setModel($config_m);
		$f->getElement('new_customer')
										->setValueList(
											[
												'Allowed'=>'Allowed New Customer Ticket',
												'Denied' => 'Denied New Customer Ticket'
											]
										)->validate('required');
		$f->addSubmit('save')->addClass('btn btn btn-primary');

		if($f->isSubmitted()){
			// $inv_config['invoice_schema'] = $f['invoice_schema'];
			$f->save(); 

			$f->js()->reload()->execute();
		}
	}
	
	// function defaultTemplate(){
	// 	return ['page/crm-config'];
	// }
}
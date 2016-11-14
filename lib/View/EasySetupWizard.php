<?php


namespace xepan\crm;

class View_EasySetupWizard extends \View{
	function init(){
		parent::init();
		
		if($_GET[$this->name.'_support_mail_content']){
			
			$support_mail_config = $this->add('xepan\base\Model_ConfigJsonModel',
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
			$support_mail_config->tryLoadAny();

			$auto_reply_subject_template = file_get_contents(realpath(getcwd().'/vendor/xepan/crm/templates/default/auto_reply_subject.html'));
			$auto_reply_body_template = file_get_contents(realpath(getcwd().'/vendor/xepan/crm/templates/default/auto_reply_body.html'));
			
			$denied_email_subject_template = file_get_contents(realpath(getcwd().'/vendor/xepan/crm/templates/default/denied_email_subject.html'));
			$denied_email_body_template = file_get_contents(realpath(getcwd().'/vendor/xepan/crm/templates/default/denied_email_body.html'));
			
			$closed_email_subject_template = file_get_contents(realpath(getcwd().'/vendor/xepan/crm/templates/default/closed_email_subject.html'));
			$closed_email_body_template = file_get_contents(realpath(getcwd().'/vendor/xepan/crm/templates/default/closed_email_body.html'));

			if(!$support_mail_config['auto_reply_subject']){
				$support_mail_config['auto_reply_subject'] = $auto_reply_subject_template;
			}

			if(!$support_mail_config['auto_reply_body']){
				$support_mail_config['auto_reply_body'] = $auto_reply_body_template;
			}

			if(!$support_mail_config['denied_email_subject']){
				$support_mail_config['denied_email_subject'] = $denied_email_subject_template;
			}

			if(!$support_mail_config['denied_email_body']){
				$support_mail_config['denied_email_body'] = $denied_email_body_template;
			}

			if(!$support_mail_config['closed_email_subject']){
				$support_mail_config['closed_email_subject'] = $closed_email_subject_template;
			}

			if(!$support_mail_config['closed_email_body']){
				$support_mail_config['closed_email_body'] = $closed_email_body_template;
			}

			$support_mail_config->save();

			$this->js(true)->univ()->frameURL("Customer Support Layout",$this->app->url('xepan_crm_config'));
		}

		$isDone = false;
		
		$action = $this->js()->reload([$this->name.'_support_mail_content'=>1]);

		$support_cnfg_mdl = $this->add('xepan\base\Model_ConfigJsonModel',
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
		$support_cnfg_mdl->tryLoadAny();

		if(!$support_cnfg_mdl['auto_reply_subject'] || !$support_cnfg_mdl['auto_reply_body'] || !$support_cnfg_mdl['denied_email_subject'] || !$support_cnfg_mdl['denied_email_body'] || !$support_cnfg_mdl['closed_email_subject'] || !$support_cnfg_mdl['closed_email_body']){
			$isDone =false;
		}else{
			$isDone = true;
			$action = $this->js()->univ()->dialogOK("Already have Data",' You already have emailsetting, visit page ? <a href="'. $this->app->url('xepan_communication_general_email')->getURL().'"> click here to go </a>');
		}


		$task_reminder_view = $this->add('xepan\base\View_Wizard_Step');

		$task_reminder_view->setAddOn('Application - CRM')
			->setTitle('Set Layout For Send Mail For Support Customer')
			->setMessage('Please set layout for sending mail for customer support mail.')
			->setHelpMessage('Need help ! click on the help icon')
			->setHelpURL('#')
			->setAction('Click Here',$action,$isDone);

	}
}
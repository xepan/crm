<?php
	namespace xepan\crm;

	class Tool_Support extends \xepan\cms\View_Tool{
		public $options = [
				'success_message'=>'Ticket Created Successfully',
				'complain_to_id'=>null
			];

		function init(){
			parent::init();
			
			if(!$this->validate()) return;

			
			$model = $this->add('xepan\crm\Model_SupportTicket');
			$form = $this->add('Form');
			
			if(!$this->options['complain_to_id']){
				$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
				$email_setting->addCondition('is_support_email',true);
				$email_setting->addCondition('is_active',true);
				$complain_field = $form->addField('xepan\base\DropDown','to_id','Complain To ')->validate('required');
				$complain_field->setEmptyText("Please Select");
				$complain_field->setModel($email_setting,['name']);
			}
			 $form->add('xepan\base\Controller_FLC')
			 	->showLables(true)
			 	->addContentSpot()
			 	->makePanelsCoppalsible(true)
				->layout([
					
					'priority~Priority'=>'c2~12',
					'subject~Subject'=>'c3~12',
			 		'message~Message'=>'c4~12',
			
			 		'FormButtons~&nbsp;'=>'c5~12'
         			 ]);
			$form->setModel($model,['priority','subject','message']);
			$form->addSubmit('Submit')->addClass('btn btn-danger');
			
			if($form->isSubmitted()){
				$form->save();
				$form->js(null,$form->js()->reload())->univ()->successMessage($this->options['success_message'])->execute();
			}

		}

		function validate(){
			if($this->owner instanceof \AbstractController){
				$this->add('View')->set('I am support tool')->addClass('alert alert-info');
				return false; 
			}

			$cust_model = $this->add('xepan\commerce\Model_Customer');
			if(!$cust_model->loadLoggedin()){
				$this->add('View')->set('you are not a customer');
				return false;
			}

			return true;
		}
	}

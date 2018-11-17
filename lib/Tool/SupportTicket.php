<?php
namespace xepan\crm;

class Tool_SupportTicket extends \xepan\cms\View_Tool{

	public $options = [
			'success_message'=>'Ticket Created Successfully',
			'complain_to_id'=>null,
			'show_ticket_form'=>true,
			'show_ticket_history'=>true,
			'paginator'=>5,
			'default_priority'=>null
		];
	public $form;
	public $pending_grid;
	public $customer_model;
	function init(){
		parent::init();
		
		if(!$this->validate()) return;
		
		if($this->options['show_ticket_history']){
			$this->addTicketHistory();
		}

		if($this->options['show_ticket_form']){
			$this->addTicketForm();
		}

	}

	function addTicketHistory(){

		$this->add('H3',null,'ticket_history')->set('Support Ticket History')->addClass('xepan-support-heading');

		$status_array = [
					'Pending'=>['id','complain_to','subject','message','priority','created_at'],
					'Assigned'=>['id','complain_to','subject','message','priority','created_at','assigned_at'],
					'Closed'=>['id','complain_to','subject','message','priority','created_at','assigned_at','closed_at'],
					'Rejected'=>['id','complain_to','subject','message','priority','created_at','rejected_at']
				];

		$tabs = $this->add('Tabs',null,'ticket_history');
		foreach ($status_array as $status => $field_to_show) {
			$tab = $tabs->addTab($status);

			$model = $tab->add('xepan\crm\Model_SupportTicket');
			$model->addExpression('complain_to')->set(function($m,$q){
				$email_setting = $m->add('xepan\communication\Model_Communication_EmailSetting');
				$email_setting->addCondition('id',$m->getElement('to_id'));

				return $q->expr('IFNULL([0],"-")',[$email_setting->fieldQuery('name')]);
			});
			$model->getElement('id')->caption('Ticket No');
			$model->addCondition('contact_id',$this->customer_model->id);
			$model->addCondition('status',$status);
			$model->setOrder('created_at','desc');
			
			$grid = $tab->add('xepan\base\Grid');
			$grid->addHook('formatRow',function($g){
				$g->current_row_html['message'] = $g->model['message'];
			});
			if($status == "Pending")
				$this->pending_grid = $grid;

			$grid->fixed_header = false;
			$grid->setModel($model,$field_to_show);
			$grid->addQuickSearch(['id','subject','message']);
			$grid->addPaginator($this->options['paginator']);
			$grid->addFormatter('subject','Wrap');
			$grid->addFormatter('message','Wrap');
			}		
		}
	function defaultTemplate(){
		return ['view/tool/crm/supportticket'];
	}

	function addTicketForm(){
		
		$model = $this->add('xepan\crm\Model_SupportTicket');
		$model->addCondition('contact_id',$this->customer_model->id);

		$model->getElement('message')->display(array('form'=>'text'));

		$form = $this->add('Form',null,'ticket_add_form');
		$flc = [];
		if(!$this->options['complain_to_id']){
			$flc['complain_to'] = 'Create Support Ticket~c1~12';
		}


		$form_fields = ['priority','subject','message'];
		if(in_array($this->options['default_priority'] , ['Low','Medium','High','Urgent'])){
			$model->addCondition('priority',$this->options['default_priority']);
			$form_fields = ['subject','message'];
		}else{
			$flc['priority'] = 'c2~12';
		}
		$flc['subject'] = 'c3~12';
		$flc['message'] = 'c4~12';
		$flc['FormButtons~&nbsp;'] = 'c5~12';
		

		$form->add('xepan\base\Controller_FLC')
			->showLables(true)
			->makePanelsCoppalsible(true)
			->layout($flc);

		if(!$this->options['complain_to_id']){
			$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
			$email_setting->addCondition('is_support_email',true);
			$email_setting->addCondition('is_active',true);
			$complain_field = $form->addField('xepan\base\DropDown','complain_to')->validate('required');
			$complain_field->setEmptyText("Please Select");
			$complain_field->setModel($email_setting,['name']);
		}

		$form->setModel($model,$form_fields);
		$form->addSubmit('Submit')->addClass('btn btn-primary');
		
		if($form->isSubmitted()){
			if(!$this->options['complain_to_id']){
				$form->model['to_id'] = $form['to_id'];
			}else
				$form->model['to_id'] = $this->options['complain_to_id'];

			$form->save();
			$js = [$form->js()->reload()];
			if($this->pending_grid)
				$js[] = $this->pending_grid->js()->reload();

			$form->js(null,$js)->univ()->successMessage($this->options['success_message'])->execute();
		}
	}
	function validate(){
		if($this->owner instanceof \AbstractController){
			$this->add('View')->set('I am support tool')->addClass('alert alert-info');
			return false; 
		}

		$this->customer_model = $cust_model = $this->add('xepan\commerce\Model_Customer');
		if(!$cust_model->loadLoggedin()){
			$this->add('View')->set('you are not a customer');
			return false;
		}

		return true;
	}
}

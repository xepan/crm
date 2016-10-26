<?php
namespace xepan\crm;

// class page_supportticket extends \xepan\base\Page{
class page_supportticket extends \xepan\crm\page_sidebarmystauts{
	public $title="Support Ticket";
	function init(){
		parent::init();
		$status = $this->app->stickyGET('status');
		$st=$this->add('xepan\crm\Model_SupportTicket');
		$st->addCondition('status','<>','Draft');
		// $st->app->muteACL= true;
		if($status)
			$st->addCondition('status',$status);
		$st->addCondition(
					$st->dsql()->orExpr()
						->where('to_id',array_merge([0],$this->app->employee->getAllowSupportEmail()))
						->where('to_id',null)
				);
		unset($st->status[0]);
		// $st->add('xepan\crm\Controller_SideBarStatusFilter');
		$st->setOrder(['last_comment desc','created_at desc']);
		
		unset($st->actions['Assigned'][5]);
		unset($st->actions['Pending'][6]);

		$crud=$this->add('xepan\base\CRUD',['grid_class'=>'xepan\base\Grid'],null,['view/supportticket/grid']);
		$crud->setModel($st,['contact_id','subject','message','priority','image_avtar'],['id','contact','created_at','subject','last_comment','from_email','ticket_attachment','task_status','task_id','image_avtar']);
		$crud->add('xepan\hr\Controller_ACL',['action_allowed'=>[],'permissive_acl'=>true]);
		$crud->add('xepan\base\Controller_Avatar',['options'=>['size'=>45,'border'=>['width'=>0]],'name_field'=>'contact','default_value'=>'','image_field','image_avtar']);
		
		if(!$crud->isEditing())
			$crud->grid->controller->importField('created_at');
		
		$g=$crud->grid;
		$g->addMethod('format_ticket_attachment',function($g,$f){
			if(!$g->model['ticket_attachment']){
				$g->current_row[$f]='';
			}else{
				$g->current_row_html[$f]='<a href="#" class="attachment"><i class="fa fa-paperclip"></i></a>';
			}
		});	

		$g->addFormatter('ticket_attachment','ticket_attachment');

		$crud->grid->addPaginator(10);
		$frm=$crud->grid->addQuickSearch(['document_id']);

		
		if(!$crud->isEditing()){
			$this->app->stickyGET('ticket_id');
			$this->vp = $this->add('VirtualPage')->set(function($p){
				$tst=$this->add('xepan\crm\Model_SupportTicket')->load($_GET['ticket_id']);	
				$task = $this->add('xepan\projects\Model_Task');
				$task->load($tst['task_id']);
				$detail = $p->add('xepan\projects\View_Detail',['task_id'=>$tst['task_id']]);//,null,null,['view\task_form']);
				$detail->setModel($task);
			});

			$crud->grid->js('click')->_selector('.view-related-task')->univ()->frameURL('Related Task',[$this->api->url($this->vp->getURL()),'ticket_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
			
			$td_view=$this->add('xepan/crm/View_TicketDetail');//->addClass('xepan-crm-ticket-detail-view');
			$this->js(true,$td_view->js()->hide());
			$crud->grid->js('click')->_selector('.do-view-ticket-customer')->univ()->frameURL('Customer Details',[$this->api->url('xepan_commerce_customerdetail'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-contact-id]')->data('contact-id')]);
			$crud->grid->js('click',
				[
					$td_view->js()
						->html('<div style="width:100%"><img style="width:20%;display:block;margin:auto;" src="vendor/xepan/communication/templates/images/email-loader.gif"/></div>')
						->reload(['ticket_id'=>$this->js()->_selectorThis()->data('id')]),
					$td_view->js()->show(),
					$crud->js()->hide()])
			->_selector('.support-ticket-view');

			$td_view->js('click',[$td_view->js()->hide(),$crud->js()->show()])
				->_selector('.back-to-support-ticket');



			if($this->app->stickyGET('ticket_id')){
				$ticket_model=$this->add('xepan\crm\Model_SupportTicket')->load($_GET['ticket_id']);
				$td_view->setModel($ticket_model);
				// $td_view->add('xepan\base\Controller_Avatar',['name_field'=>'contact','image_field'=>'contact_image','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$ticket_model]);
			}

		}		
		
	}

}

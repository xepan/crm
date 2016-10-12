<?php
namespace xepan\crm;

class page_supportticket extends \xepan\base\Page{
	public $title="Support Ticket";
	function init(){
		parent::init();
		$st=$this->add('xepan\crm\Model_SupportTicket');
		$st->add('xepan\crm\Controller_SideBarStatusFilter');
		$st->setOrder(['last_comment desc','created_at desc']);

		$crud=$this->add('xepan\hr\CRUD',null,null,['view/supportticket/grid']);
		$crud->setModel($st);
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


		$crud->add('xepan\base\Controller_Avatar',['options'=>['size'=>45,'border'=>['width'=>0]],'name_field'=>'contact','default_value'=>'']);
		$crud->grid->addPaginator(10);
		$frm=$crud->grid->addQuickSearch(['document_id']);

		
		if(!$crud->isEditing()){
			
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
			}

		}		
		
	}

}

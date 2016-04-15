<?php

namespace xepan\crm;

class page_ticketdetails extends \xepan\base\Page{
	public $title='Ticket-Detail';
	public $breadcrumb=['Home'=>'index','Support Ticket'=>'xepan_crm_supportticket','Details'=>'#'];

	function init(){
		parent::init();
		
		$ticket_id=$this->app->stickyGET('ticket_id');
		$m_comment=$this->add('xepan\crm\Model_Ticket_Comments');			
		$m_comment->addCondition('ticket_id',$ticket_id);
		
		$comment_join = $m_comment->join('communication.id','communication_email_id');
		
		$comment_join->addField('title');
		$comment_join->addField('description');
		$comment_join->addField('from_id');
		$comment_join->addField('created_at');

		$comment_lister=$this->add('xepan/hr/Grid',null,null,['view/grid/ticketdetail-grid']);
		$comment_lister->addColumn('message');
		$comment_lister->setModel($m_comment);

		$comment_lister->addMethod('format_message',function($g,$f){
			$g->current_row_html[$f]=$g->model['description'];
		});

		$comment_lister->addFormatter('message','message');
		

		$comment_lister->removeColumn('description');

		$comment_lister->add('xepan\base\Controller_Avatar',['options'=>['size'=>45,'border'=>['width'=>0]],'name_field'=>'contact','default_value'=>'']);

		$comment_lister->on('click','.send',function($js){
				return $js->univ()->alert('hi');
		});
	}
}
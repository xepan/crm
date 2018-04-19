<?php

namespace xepan\crm;

class Model_Ticket_Comments extends \xepan\base\Model_Table{
	public $table="comments";
	public $acl = false;
	function init(){
		parent::init();

		$this->hasOne('xepan\base\Contact','created_by_id')
				->defaultValue($this->app->employee->id);
		$this->hasOne('xepan\communication\Communication_Abstract_Email','communication_id');
		$this->hasOne('xepan\crm\SupportTicket','ticket_id');

		$this->addField('title');
		$this->addField('description')->type('text');
		$this->addField('type');

		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
		
		$this->add('misc/Field_Callback','callback_date')->set(function($m){
			if(date('Y-m-d',strtotime($m['created_at']))==date('Y-m-d',strtotime($this->app->now))){
				return date('h:i a',strtotime($m['created_at']));	
			}
			return date('d M Y',strtotime($m['created_at']));
		});

		$this->addExpression('collapse_header')->set(function ($m,$q){
			return $q->expr("IF([0] is null or [0]='',[1],[2])",[$m->getElement('description'),$m->refSQL('communication_id')->fieldQuery('description'),$m->getElement('description')]);
		});	

		$this->addExpression('from')->set(function($m,$q){
			return $m->refSQL('communication_id')->fieldQuery('from');
		});
		$this->addExpression('image')->set(function($m,$q){
			return $m->add('xepan\base\Model_Contact')
				->addCondition('id',$m->refSQL('communication_id')->fieldQuery('from_id'))
				->fieldQuery('image');
		});

		$this->addExpression('attach_count')->set(function($m,$q){
			return $m->refSQL('communication_id')->fieldQuery('attachment_count');
		});	
	}
}
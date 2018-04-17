<?php

namespace xepan\crm;

class Model_SupportTicketData extends \xepan\crm\Model_SupportTicket{

	function init(){
		parent::init();

        $this->addExpression('pending_duration',function($m,$q){
          return $q->expr('TIMESTAMPDIFF(Minute,[0],[1])',[$m->getElement('created_at'),$m->getElement('pending_at')]);
        });

        $this->addExpression('assigned_duration',function($m,$q){
          return $q->expr('TIMESTAMPDIFF(Minute,[0],[1])',[$m->getElement('created_at'),$m->getElement('assigned_at')]);
        });

        $this->addExpression('closed_duration',function($m,$q){
          return $q->expr('TIMESTAMPDIFF(Minute,[0],[1])',[$m->getElement('created_at'),$m->getElement('closed_at')]);
        });

        $this->addExpression('rejected_duration',function($m,$q){
          return $q->expr('TIMESTAMPDIFF(Minute,[0],[1])',[$m->getElement('created_at'),$m->getElement('rejected_at')]);
        });
	}
}
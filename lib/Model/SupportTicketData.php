<?php

namespace xepan\crm;

class Model_SupportTicketData extends \xepan\crm\Model_SupportTicket{

	function init(){
		parent::init();

        $this->addExpression('pending_duration',function($m,$q){
          return $q->expr('TIMESTAMPDIFF(Minute,[0],"[1]")',[$m->getElement('pending_at'),$m->getElement('created_at')]);
        });

        $this->addExpression('assigned_duration',function($m,$q){
          return $q->expr('TIMESTAMPDIFF(Minute,[0],"[1]")',[$m->getElement('assigned_at'),$m->getElement('created_at')]);
        });

        $this->addExpression('closed_duration',function($m,$q){
          return $q->expr('TIMESTAMPDIFF(Minute,[0],"[1]")',[$m->getElement('closed_at'),$m->getElement('created_at')]);
        });

        $this->addExpression('rejected_duration',function($m,$q){
          return $q->expr('TIMESTAMPDIFF(Minute,[0],"[1]")',[$m->getElement('rejected_at'),$m->getElement('created_at')]);
        });

	}
}
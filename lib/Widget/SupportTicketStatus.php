<?php

namespace xepan\crm;

class Widget_SupportTicketStatus extends \xepan\base\Widget{
	function init(){
		parent::init();
		
		$this->report->enableFilterEntity('date_range');
	}

	function recursiveRender(){
    	$this->add('xepan\crm\View_SupportTicketStatus',['from_date'=>$this->report->start_date,'to_date'=>$this->report->end_date]);
		return Parent::recursiveRender();
	}
}
<?php

namespace xepan\crm;

class Widget_SupportTicketTAT extends \xepan\base\Widget{

	function init(){
		parent::init();
    
    $this->add('xepan\crm\View_SupportTicketDayWise');

	}
}
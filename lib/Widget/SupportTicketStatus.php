<?php

namespace xepan\crm;

class Widget_SupportTicketStatus extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->add('xepan\crm\View_SupportTicketStatus');
	}
}
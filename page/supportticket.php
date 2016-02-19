<?php

namespace xepan\crm;

class page_supportticket extends \Page{
	public $title='Support-Ticket';

	function init(){
		parent::init();

		//$this->add('View_Info')->set('Hello');

	}

	function defaultTemplate(){
		return['page/supportticket'];
	}
}
<?php

namespace xepan\crm;

class page_ticketdetails extends \Page{
	public $title='Ticket-Detail';

	function init(){
		parent::init();

		//$this->add('View_Info')->set('Hello');

	}

	function defaultTemplate(){
		return['page/ticketdetails'];
	}
}
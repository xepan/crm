<?php

namespace xepan\crm;

/**
* 
*/
class page_solution extends \xepan\base\Page{
	public $title="My Solutions";
	function init(){
		parent::init();

		$this->add('xepan\crm\View_MySoluction'/*,null,'solution_view'*/);
		
	}

	// function defaultTemplate(){
	// 	return ['view/mysolution'];
	// }
}
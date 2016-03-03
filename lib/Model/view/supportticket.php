// <?php
// namespace xepan\crm;
// class View_supportticket extends \View{

// 	function init(){
// 		parent::init();
// 	}

// 	function defaultTemplate(){

// 		return ['view/supportticket'];
// 	}

// }

<?php
namespace xepan\supportticket;

class page_supportticket extends \Page{

	public $title="Opportunity";

	function init(){
		parent::init();

		$opportunity=$this->add('xepan\crm\Model_supportticket');

		$crud=$this->add('xepan\hr\CRUD',null,null,['grid/supportticket-grid']);

		$crud->setModel($supportticket);
		
		$crud->grid->addQuickSearch(['ticketdetails']);

	}

}
<?php
namespace xepan\crm;

class page_opportunity extends \Page{

  public $title="Supportticket";

  function init(){
    parent::init();

    $opportunity=$this->add('xepan\crm\Model_Supportticket');

    $crud=$this->add('xepan\hr\CRUD',null,null,['grid/supportticket-grid']);

    $crud->setModel($supportticket);
    
    $crud->grid->addQuickSearch(['ticketdetails']);

  }

}
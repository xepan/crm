<?php
namespace xepan\crm;
class page_oursupport extends \xepan\base\page{
	public $title ="Our-support";
	function init(){
		parent::init();
		$model =$this->add('xepan\crm\Model_OurSupport');
		$crud =$this->add('xepan\hr\CRUD');
		$crud ->setModel($model);
	}
} 
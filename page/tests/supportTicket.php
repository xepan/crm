<?php
namespace xepan\crm;

class page_tests_supportTicket extends \xepan\base\Page_Tester {
    public $title = 'Communication Tests';

     public $proper_responses=[
        "Test_CheckForTicket"=>'default'
        ];

     function test_CheckForTicket(){
     	return $this->add('xepan\base\Model_Epan')->setOrder('id')->tryLoadANy()->get('name');
     }
}
?>
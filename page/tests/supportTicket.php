<?php
namespace xepan\crm;

class page_tests_supportTicket extends \xepan\base\Page_Tester {
    public $title = 'Communication Tests';

    public $proper_responses=[
     	"Test_CheckGetTicket"=> true,
        "Test_CheckForTicket"=>[false,false,false,123,false]
        ];

    function test_CheckGetTicket(){
    	$st = $this->add('xepan\crm\Model_SupportTicket');
    	return $st->getTicket('') instanceof \xepan\crm\Model_SupportTicket;
    }

    function test_CheckForTicket(){
    	$st = $this->add('xepan\crm\Model_SupportTicket');
     	$tickets = [];
     	$tickets[] = $st->fetchTicketNumberFromSubject("HELLO TERE");
     	$tickets[] = $st->fetchTicketNumberFromSubject("Re: HELLO TERE [This looks good]");
     	$tickets[] = $st->fetchTicketNumberFromSubject("Re: HELLO TERE [123]");
     	$tickets[] = $st->fetchTicketNumberFromSubject("Re: HELLO TERE [#123]");
     	$tickets[] = $st->fetchTicketNumberFromSubject("HELLO TERE #[456][456]");

     	return $tickets;
    }

}
?>
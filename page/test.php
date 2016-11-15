<?php


namespace xepan\crm;

class page_test extends \xepan\base\Page {
	function init(){
		parent::init();

		foreach ($this->add('xepan\crm\Model_SupportTicket') as $t) {
			$email=['email'=>$t['from_email'],'name'=>$t['from_name']];
			$email=json_encode($email);
			$t['from_raw'] = $email;
			$t->save();
		}

	}
}
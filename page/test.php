<?php


namespace xepan\crm;

class page_test extends \xepan\base\Page {
	function init(){
		parent::init();

		// foreach ($this->add('xepan\crm\Model_SupportTicket') as $t) {
		// 	$email=['email'=>$t['from_email'],'name'=>$t['from_name']];
		// 	$email=json_encode($email);
		// 	$t['from_raw'] = $email;
		// 	$t->save();
		// }

		foreach ($this->add('xepan\crm\Model_SupportTicket') as $t) {
			// $t['closed_at'] = $t['created_at'];
			$t['closed_at'] = date("Y-m-d H:i:s", strtotime('+2 hours',strtotime($t['created_at'])));
			$t->save();
		}

	}
}
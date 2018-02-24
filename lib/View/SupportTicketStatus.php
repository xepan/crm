<?php

namespace xepan\crm;

class View_SupportTicketStatus extends \View{
	function init(){
		parent::init();

		$this->view = $this->add('View')->addClass('main-box main-box-body padding-10');
		$this->view->add('View')->setElement('h1')->set('Support Ticket Status')->addClass('text-center');
	}

	function recursiveRender(){
		$m = $this->add('xepan\crm\Model_SupportTicket');
        $counts = $m->_dsql()->del('fields')->field('status')->field('count(*) counts')->group('Status')->get();
        $data_array = [];

        $total_ticket = 0;
		$data_array = [
				'Assigned'=>['css_class'=>'purple-bg','counts'=>"0"],
				'Closed'=>['css_class'=>'green-bg','counts'=>0],
				'Pending'=>['css_class'=>'red-bg','counts'=>0],
				'Rejected'=>['css_class'=>'emerald-bg','counts'=>0],
			];
		foreach ($counts as $key => $value) {
			$data_array[$value['status']]['counts'] = $value['counts'];
		}

		$col = $this->view->add('Columns');
        foreach ($data_array as $status => $data) {
        	$total_ticket += $data['counts'];

        	$v = $col->addColumn(3)->addClass('xepan-hover-hand');
        	$view = $v->add('xepan\base\View_Widget_SingleInfo');
        	$view->setHeading($status);
        	$view->setValue($data['counts']);
        	$view->setIcon("");
        	$view->setClass($data['css_class']);
			// digging
			$view->js('click')->univ()->frameURL('All Tickets',$this->api->url('xepan/crm/supportticket',['status'=>$status]));
        }

		$v = $this->view->add('xepan\base\View_Widget_SingleInfo')
			->setIcon("")
			->setValue('Total Tickets: '.$total_ticket)
			->setHeading("")
			->setClass('text-center gray-bg xepan-hover-hand');
		
		$v->js('click')->univ()->frameURL('All Tickets',$this->api->url($this->app->url('xepan/crm/supportticket')));

		parent::recursiveRender();
	}
}
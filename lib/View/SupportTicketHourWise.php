<?php

namespace xepan\crm;

class View_SupportTicketHourWise extends \View{

	function init(){
		parent::init();

		$this->view = $this->add('View')->addClass('main-box main-box-body padding-10');
		$this->view->add('View')->setElement('h1')->set('Support Ticket TAT')->addClass('text-center');	
	}

	function recursiveRender(){
		$tat_array = [
				'0-4'=>['css_class'=>'purple-bg','heading'=>'0-4 Hours'],
				'4-8'=>['css_class'=>'green-bg','heading'=>'4-8 Hours'],
				'8-12'=>['css_class'=>'emerald-bg','heading'=>'8-12 Hours'],
				'12-'=>['css_class'=>'red-bg','heading'=>' > 12 Hours'],
			];
        $total_ticket = 0;

       	foreach ($tat_array as $duration => $data) {
			$col = $this->view->add('Columns');

       		$temp = explode("-", $duration);
       		$min_hour = $temp[0]?:0;
       		$max_hour = $temp[1]?:0;

       		$model = $this->add('xepan\crm\Model_SupportTicket');
			$model->addExpression('duration',function($m,$q){
				return $q->expr('TIMESTAMPDIFF(HOUR,[0],"[1]")',[$m->getElement('created_at'),$this->app->now]);
			});
			if($min_hour)
				$model->addCondition('duration','>=',$min_hour);
			if($max_hour)
				$model->addCondition('duration','<',$max_hour);

			$count = $model->count()->getOne();
        	$total_ticket += $count;

        	$records = $model->_dsql()->del('fields')->field('status')->field('count(*) counts')->group('Status')->get();
			$status_counts = [];
        	foreach ($records as $key => $junk) {
        		$status_counts[$junk['status']] = $junk['counts'];
        	}

        	$v = $col->addColumn(3)->addClass('xepan-hover-hand');
        	$view = $v->add('xepan\base\View_Widget_SingleInfo');
        	$view->setHeading($data['heading']);
        	$view->setValue($count);
        	$view->setIcon("");
        	$view->setClass($data['css_class']);
			// digging
			$view->js('click')->univ()->frameURL('All Tickets',$this->api->url('xepan/crm/supportticket',['duration'=>$duration]));

        	$v = $col->addColumn(9);
        	$inner_col = $v->add('Columns');
        	$col1 = $inner_col->addColumn(6);
        	$col2 = $inner_col->addColumn(6);
        	$col3 = $inner_col->addColumn(6);
        	$col4 = $inner_col->addColumn(6);

        	// assigned
        	$assign_value = isset($status_counts['Assigned'])?$status_counts['Assigned']:0;
        	$assigned_percentage = 0;
        	if($count)
        		$assigned_percentage = (($assign_value / $count) * 100);

        	$view_ps_assigned = $col1->add('xepan\base\View_Widget_ProgressStatus')->addClass('margin-reset-top xepan-hover-hand');
  			$view_ps_assigned
  					->setHeading('Assigned')
  					->setValue($assign_value)
  					->makeWarning()
  					->setProgressPercentage($assigned_percentage)
  					;
  			// digging
  			$view_ps_assigned->js('click')->univ()->frameURL('Assigned Tickets in Duration '.$data['heading'],$this->api->url('xepan/crm/supportticket',['duration'=>$duration,'status'=>'Assigned']));

			//pendind
  			$pending_value = isset($status_counts['Pending'])?$status_counts['Pending']:0;
  			$pending_percentage = 0;
        	if($count)
        		$pending_percentage = (($pending_value / $count) * 100);

        	$view_ps_pending = $col2->add('xepan\base\View_Widget_ProgressStatus')->addClass('margin-reset-top xepan-hover-hand');
			$view_ps_pending
					->setHeading('Pending')
  					->setValue($pending_value)
  					->makeDanger()
  					->setProgressPercentage($pending_percentage)
  					;
  			// digging
  			$view_ps_pending->js('click')->univ()->frameURL('Pending Tickets in Duration '.$data['heading'],$this->api->url('xepan/crm/supportticket',['duration'=>$duration,'status'=>'Pending']));

        	// closed
  			$closed_value = isset($status_counts['Closed'])?$status_counts['Closed']:0;
  			$closed_percentage = 0;
        	if($count)
        		$closed_percentage = (($closed_value / $count) * 100);
        	$view_ps_closed = $col3->add('xepan\base\View_Widget_ProgressStatus')->addClass('margin-reset-top xepan-hover-hand');
			$view_ps_closed
					->setHeading('Closed')
  					->setValue($closed_value)
  					->makeSuccess()
  					->setProgressPercentage($closed_percentage)
  					;
  			// digging
  			$view_ps_closed->js('click')->univ()->frameURL('Closed Tickets in Duration '.$data['heading'],$this->api->url('xepan/crm/supportticket',['duration'=>$duration,'status'=>'Closed']));

			//rejected
  			$rejected_value = isset($status_counts['Rejected'])?$status_counts['Rejected']:0;
  			$rejected_percentage = 0;
        	if($count)
        		$rejected_percentage = (($rejected_value / $count) * 100);
        		
        	$view_ps_rejected = $col4->add('xepan\base\View_Widget_ProgressStatus')->addClass('margin-reset-top xepan-hover-hand');
			$view_ps_rejected
					->setHeading('Rejected')
  					->setValue($rejected_value)
  					// ->makeWarning()
  					->setProgressPercentage($rejected_percentage)
  					;
  			// digging
  			$view_ps_rejected->js('click')->univ()->frameURL('Closed Tickets in Duration '.$data['heading'],$this->api->url('xepan/crm/supportticket',['duration'=>$duration,'status'=>'Rejected']));
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
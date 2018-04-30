<?php

namespace xepan\crm;

class View_SupportTicketTAT extends \View{
  public $tat_array = [];

  public $tat_of_status = "Closed";
  public $from_date;
  public $to_date;

	function init(){
		parent::init();
                
		$this->view = $this->add('View')->addClass('main-box main-box-body padding-10');
		$this->view->add('View')->setElement('h1')->setHtml('Closed Support Ticket TAT <br/> <small>('.(date('d-M-Y',strtotime($this->from_date))).' to '.(date('d-M-Y',strtotime($this->to_date))).')<br/><small>Closed tickets in given date range and their time taken</small></small>')->addClass('text-center');
	}

	function recursiveRender(){
		$tat_array = [
				'0-4'=>['css_class'=>'purple-bg','heading'=>'0-4 Hours'],
				'4-8'=>['css_class'=>'green-bg','heading'=>'4-8 Hours'],
				'8-12'=>['css_class'=>'emerald-bg','heading'=>'8-12 Hours'],
				'12-'=>['css_class'=>'red-bg','heading'=>' > 12 Hours'],
			];

    if(is_array($this->tat_array) && count($this->tat_array))
      $tat_array = $this->tat_array;

    $total_ticket = 0;
    $col = $this->view->add('Columns');
    foreach ($tat_array as $duration => $data) {

        $temp = explode("-", $duration);
        $min_hour = $temp[0]?:0;
        $max_hour = $temp[1]?:0;

        $min_minute = $min_hour * 60;
        $max_minute = $max_hour * 60;

        $duration_variable = strtolower($this->tat_of_status)."_duration";

        $model = $this->add('xepan\crm\Model_SupportTicketData');
        $model->addCondition('status','Closed');
        $model->addCondition('closed_at','>',$this->from_date);
        $model->addCondition('closed_at','<',$this->app->nextDate($this->to_date));

        if($min_minute >= 0){
            $model->addCondition($duration_variable,'>=',$min_minute);
        }
        if($max_minute){
            $model->addCondition($duration_variable,'<',$max_minute);
        }
        $model->addCondition($duration_variable,'<>',null);
            
        // $grid = $this->view->add('Grid')->setModel($model,[$duration_variable,'created_at','closed_at']);
        
        $count = $model->count()->getOne();
        $total_ticket += $count;

        $v = $col->addColumn(3)->addClass('xepan-hover-hand');
        $view = $v->add('xepan\base\View_Widget_SingleInfo');
        $view->setHeading($data['heading']);
        $view->setValue($count);
        $view->setIcon("");
        $view->setClass($data['css_class']);
        
        // digging
        $view->js('click')->univ()->frameURL($data['heading']." ".strtolower($this->tat_of_status).' Tickets',$this->api->url('xepan/crm/supportticket',['status'=>$this->tat_of_status,'duration'=>$duration,'filter_from_date'=>$this->from_date,'filter_to_date'=>$this->to_date,'apply_date_filter_on_field'=>'closed_at']));
    }

		$v = $this->view->add('xepan\base\View_Widget_SingleInfo')
			->setIcon("")
			->setValue('Total '.$this->tat_of_status.' Tickets: '.$total_ticket)
			->setHeading("")
			->setClass('text-center gray-bg xepan-hover-hand');			
		$v->js('click')->univ()->frameURL('All Tickets',$this->api->url($this->app->url('xepan/crm/supportticket',['status'=>$this->tat_of_status])));

		parent::recursiveRender();
	}
}
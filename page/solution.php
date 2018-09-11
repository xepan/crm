<?php

namespace xepan\crm;

/**
* 
*/
// class page_solution extends \xepan\crm\page_sidebarmystauts{
class page_solution extends \xepan\base\Page{
	public $title="My Solutions";
	public $add_all=true;
	function init(){
		parent::init();
		$ticket_id = $this->app->stickyGET('ticket_id');
		$solution_view = $this->add('xepan\crm\View_MySolution'/*,null,'solution_view'*/);
		
		// $emp_emails = $this->app->employee->getAllowSupportEmail();
		// $email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
		// $email_setting->addCondition('is_active',true);
		// $email_setting->addCondition('id',$emp_emails);
		// $allow_email=[];
		// foreach ($emp_emails as  $email) {
		// 	$allow_email[] = $email;
		// }
		// var_dump($allow_email);
		$st = $this->add('xepan\crm\Model_SupportTicket');
		$st->addCondition('contact_id',$this->app->employee->id);
		$icon_array = $this->app->status_icon;
		$model_class=get_class($st);
		// throw new \Exception($model_class, 1);
		$counts = $st->_dsql()->del('fields')->field('status')->field('count(*) counts')->group('Status')->get();
		$counts_redefined =[];
		$total=0;
		foreach ($counts as $cnt) {
			$counts_redefined[$cnt['status']] = $cnt['counts'];
			$total += $cnt['counts'];
		}
		if($this->add_all){
			$this->app->page_top_right_button_set->addButton(["All ($total)",'icon'=>$icon_array[$model_class]['All']])
				->addClass('btn btn-primary')
				->js('click')->univ()->location($this->api->url(null,['status'=>null]))
				;

			// $this->app->side_menu->addItem(['All','icon'=>$icon_array[$model_class]['All'],'badge'=>[$total,'swatch'=>' label label-primary label-circle pull-right']],$this->api->url(null,['status'=>null]),['status'])->setAttr(['title'=>'All']);
		}
		foreach ($st->status as $s) {
			$this->app->page_top_right_button_set->addButton([$s,'icon'=>$icon_array[$model_class][$s]])
				->addClass('btn btn-primary')
				->js('click')->univ()->location($this->api->url(null,['status'=>$s]))
				;
			// echo $s."</br>";
			// $this->app->side_menu->addItem([$s,'icon'=>$icon_array[$model_class][$s],'badge'=>[$counts_redefined[$s],'swatch'=>' label label-primary label-circle pull-right']],$this->api->url(null,['status'=>$s]),['status'])->setAttr(['title'=>$s]);
		}
		// $this->app->employee->getAllowSupportEmail();
		// $comment_view = $this->add('View',null,'comment_view');
		// if($ticket_id){
		// 	$comment_view->add('H1')->set('Ticket No : ' . $ticket_id)->addClass('text-center alert alert-success xepan-push');
		// 	// throw new \Exception($_GET['ticket_id'], 1);
		// 	$form = $comment_view->add('Form');
		// 	$form->addField('line','title');	
		// 	$form->addField('text','message');
		// 	$form->addSubmit('Create Comment')->addClass('btn btn-primary btn-block');
		// 	$comment=$this->add('xepan\crm\Model_Ticket_Comments');			
		// 	$comment->addCondition('ticket_id',$ticket_id);
		// 	$comment_grid = $comment_view->add('xepan\hr\Grid');
			
		// 	if($form->isSubmitted()){
		// 		$comment['title'] = $form['title'];
		// 		$comment['description'] = $form['message'];
		// 		$comment->save();
		// 		$js = [
		// 			$form->js()->univ()->successMessage('Comment Created'),
		// 			$comment_grid->js()->reload()
		// 		];
		// 		$form->js(null,$js )->reload()->execute();	
			
		// 	}

		// 	$comment_grid->setModel($comment,['title','description']);	
		// 	// $comment_m=$page->add('xepan\crm\Model_Ticket_Comments');			
		// 	// $comment->addCondition('ticket_id',$this->id);
		
		// }else{
		// 	$comment_view->add('H2')->set('Please Select Ticket')->addClass('text-center alert alert-warning');
		// }
		// $solution_view->js('click',
		// 		[
		// 			$comment_view->js()
		// 				->html('<div style="width:100%"><img style="width:20%;display:block;margin:auto;" src="vendor/xepan/communication/templates/images/email-loader.gif"/></div>')
		// 				->reload(['ticket_id'=>$this->js()->_selectorThis()->data('id')])])
		// 	->_selector('.support-ticket-view');
		
	}

	// function defaultTemplate(){
	// 	return ['view/mysolution'];
	// }
}
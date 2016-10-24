<?php

namespace xepan\crm;

/**
* 
*/
class page_solution extends \xepan\base\Page{
	public $title="My Solutions";
	function init(){
		parent::init();
		$ticket_id = $this->app->stickyGET('ticket_id');
		$solution_view = $this->add('xepan\crm\View_MySolution'/*,null,'solution_view'*/);

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
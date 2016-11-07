<?php

namespace xepan\crm;

/**
* 
*/
class page_sidebarmystauts extends \xepan\base\Page{
	// public $title = "";
	public $add_all=true;
	function init(){
		parent::init();
		$emp_emails = $this->app->employee->getAllowSupportEmail();
		$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
		$email_setting->addCondition('is_active',true);
		$email_setting->addCondition('id',$emp_emails);
		$allow_email=[];
		foreach ($emp_emails as  $email) {
			$allow_email[] = $email;
		}
		// var_dump($allow_email);
		$st = $this->add('xepan\crm\Model_SupportTicket');
		$st->addCondition('to_id',array_merge([0],$allow_email));
		$st->addCondition('status','<>','Draft');
		$icon_array = $this->app->status_icon;
		$model_class=get_class($st);
		// throw new \Exception($model_class, 1);
		unset($st->status[0]);
		$counts = $st->_dsql()->del('fields')->field('status')->field('count(*) counts')->group('Status')->get();
		$counts_redefined =[];
		$total=0;
		foreach ($counts as $cnt) {
			$counts_redefined[$cnt['status']] = $cnt['counts'];
			$total += $cnt['counts'];
		}
		if($this->add_all){
			$this->app->side_menu->addItem(['All','icon'=>$icon_array[$model_class]['All'],'badge'=>[$total,'swatch'=>' label label-primary label-circle pull-right']],$this->api->url(null,['status'=>null]),['status'])->setAttr(['title'=>'All']);
		}
		foreach ($st->status as $s) {
			// echo $s."</br>";
			$this->app->side_menu->addItem([$s,'icon'=>$icon_array[$model_class][$s],'badge'=>[$counts_redefined[$s],'swatch'=>' label label-primary label-circle pull-right']],$this->api->url(null,['status'=>$s]),['status'])->setAttr(['title'=>$s]);
		}
	}
}
<?php
namespace xepan\crm;
class page_getdata extends \page{
	function page_getsupportemail(){
		$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting');
			$email_setting->addCondition('is_support_email',true);
			$email_setting->addCondition('is_active',true);
			$option ="<option value='0'>Please select</option>";
			foreach ($email_setting as $email){
				$option .="<option value='".$email['id']."'>".$email['name']."</option>";
			}
			echo $option;
			exit;
	}
}
<?php

class BentoBaseActionLogOut extends ActionBase
{
	static $requiredPermission = 'Read';

	public $AdminSettings = array('linkLabel' => 'Log Out',
									'linkTab' => 'Universal',
									'headerTitle' => 'Log Out');

	public function logic()
	{
		$user = ActiveUser::getInstance();
		$user->loadUserByName('guest');
		$this->ioHandler->setStatusCode(200);
	}


	public function viewAdmin()
	{
		return 'You have been logged out.';
	}


	public function viewHtml()
	{
		return 'You have been logged out.';
	}

}


?>
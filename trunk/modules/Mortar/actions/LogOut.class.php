<?php

class MortarActionLogOut extends ActionBase
{
	static $requiredPermission = 'Read';

	public $adminSettings = array( 'headerTitle' => 'Log Out' );
	public $htmlSettings = array( 'headerTitle' => 'Log Out' );

	public function logic()
	{
		ActiveUser::changeUserByName('Guest');
		$this->ioHandler->setStatusCode(200);
	}


	public function viewAdmin()
	{
		$this->setTitle($this->adminSettings['headerTitle']);
		return 'You have been logged out.';
	}


	public function viewHtml()
	{
		$this->setTitle($this->htmlSettings['headerTitle']);
		return 'You have been logged out.';
	}

}


?>
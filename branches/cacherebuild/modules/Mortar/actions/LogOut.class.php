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


	public function viewAdmin($page)
	{
		return 'You have been logged out.';
	}


	public function viewHtml($page)
	{
		return 'You have been logged out.';
	}

}


?>
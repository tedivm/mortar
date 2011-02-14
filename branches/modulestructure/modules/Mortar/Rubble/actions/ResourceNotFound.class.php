<?php

class MortarRubbleActionResourceNotFound extends MortarRubbleActionAuthenticationError
{
	public static $settings = array( 'Base' => array('headerTitle' => '404 Error') );

	static $requiredPermission = 'Read';

	protected $authenticatedErrorCode = 404;
	protected $unauthenticatedErrorCode = 404;

	protected $errorMessage = 'The page or resource you are looking for cannot be found.';

	public function viewHtml($page)
	{
		return $this->errorMessage;
	}

	public function viewAdmin($page)
	{
		$page->showMenus(false);

		return $this->errorMessage;
	}
}

?>
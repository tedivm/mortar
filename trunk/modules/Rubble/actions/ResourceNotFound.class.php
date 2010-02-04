<?php

class RubbleActionResourceNotFound extends RubbleActionAuthenticationError
{
	public $AdminSettings = array(	'linkTab' => 'Universal',
									'headerTitle' => '404 Error');
	static $requiredPermission = 'Read';

	protected $authenticatedErrorCode = 404;
	protected $unauthenticatedErrorCode = 404;

	protected $errorMessage = 'The page or resource you are looking for cannot be found.';

	public function viewHtml()
	{
		return $this->errorMessage;
	}

	public function viewAdmin()
	{
		$page = ActivePage::getInstance();
		$page->showMenus(false);

		return $this->errorMessage;
	}
}

?>
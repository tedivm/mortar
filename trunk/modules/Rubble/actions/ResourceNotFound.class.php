<?php

class RubbleActionResourceNotFound extends RubbleActionAuthenticationError
{
	public $adminSettings = array('headerTitle' => '404 Error');
	public $htmlSettings = array('headerTitle' => '404 Error');
	static $requiredPermission = 'Read';

	protected $authenticatedErrorCode = 404;
	protected $unauthenticatedErrorCode = 404;

	protected $errorMessage = 'The page or resource you are looking for cannot be found.';

	public function viewHtml($page)
	{
		$page->setTitle($this->htmlSettings['headerTitle']);
		return $this->errorMessage;
	}

	public function viewAdmin($page)
	{
		$page->showMenus(false);
		$page->setTitle($this->adminSettings['headerTitle']);

		return $this->errorMessage;
	}
}

?>
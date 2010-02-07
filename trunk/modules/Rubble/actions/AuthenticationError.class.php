<?php

class RubbleActionAuthenticationError extends ActionBase
{
	public $AdminSettings = array(	'linkTab' => 'Universal',
									'headerTitle' => 'Forbidden',
									'EnginePermissionOverride' => true);

	static $requiredPermission = 'Read';

	protected $authenticatedErrorCode = 403;
	protected $unauthenticatedErrorCode = 401;

	protected $errorMessage = 'You do not have the appropriate permissions to access this resource.';

	public function logic()
	{
		if(isset($this->argument) && is_numeric($this->argument))
		{
			$this->ioHandler->setStatusCode($this->argument);
		}else{
			if(ActiveUser::isLoggedIn())
			{
				$this->ioHandler->setStatusCode($this->authenticatedErrorCode);

			}else{
				$this->ioHandler->setStatusCode($this->unauthenticatedErrorCode);
			}
		}
	}

	public function viewHtml($page)
	{
		if(!ActiveUser::isLoggedIn())
		{
			$this->ioHandler->setStatusCode(307);
			$redirectUrl = $this->redirectUrl('Html');
			$this->ioHandler->addHeader('Location', (string) $redirectUrl);
		}

		return $this->errorMessage;
	}

	public function viewAdmin($page)
	{
		if(!ActiveUser::isLoggedIn())
		{
			$this->ioHandler->setStatusCode(307);
			$redirectUrl = $this->redirectUrl('Admin');
			$this->ioHandler->addHeader('Location', (string) $redirectUrl);
		}
		$page->showMenus(false);

		return $this->errorMessage;
	}

	public function viewJson()
	{
		return array('error' => $this->errorMessage);
	}

	public function viewText()
	{
		return $this->errorMessage;
	}

	protected function redirectUrl()
	{
		$query = Query::getQuery();
		$redirectUrl = new Url();
		$redirectUrl->module = 'Mortar';
		$redirectUrl->action = 'LogIn';
		$redirectUrl->format = $query['format'];
		$currentAction = isset($query['action']) ? strtolower($query['action']) : false;

		if($currentAction)
		{
			if(isset($query['location'])
				&& ($currentAction == 'index' || $currentAction == 'read'))
			{
				$redirectUrl->l = $query['location'];
				$redirectUrl->a = $query['action'];
			}elseif(isset($query['module'])){
				$redirectUrl->m = $query['module'];
				$redirectUrl->a = $query['action'];
			}
		}
		return $redirectUrl;
	}

	public function checkAuth($action = NULL)
	{
		return true;
	}
}

?>
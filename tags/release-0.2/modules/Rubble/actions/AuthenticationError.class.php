<?php

class RubbleActionAuthenticationError extends ActionBase
{
	public $AdminSettings = array(	'linkTab' => 'Universal',
									'headerTitle' => 'Forbidden',
									'EnginePermissionOverride' => true);

	static $requiredPermission = 'Read';

	public function logic()
	{
		if(isset($this->argument) && is_numeric($this->argument))
		{
			$this->ioHandler->setStatusCode($this->argument);
		}else{
			if(ActiveUser::isLoggedIn())
			{
				$this->ioHandler->setStatusCode(403);
			}else{
				$this->ioHandler->setStatusCode(401);
			}
		}
	}

	public function viewHtml()
	{
		if(!ActiveUser::isLoggedIn())
		{
			$this->ioHandler->setStatusCode(307);
			$redirectUrl = $this->redirectUrl('Html');
			$this->ioHandler->addHeader('Location', (string) $redirectUrl);
		}

		$output = 'You do not have the appropriate permissions to access this resource.';
		return $output;
	}

	public function viewAdmin()
	{
		if(!ActiveUser::isLoggedIn())
		{
			$this->ioHandler->setStatusCode(307);
			$redirectUrl = $this->redirectUrl('Admin');
			$this->ioHandler->addHeader('Location', (string) $redirectUrl);
		}
		$output = 'You do not have the appropriate permissions to access this resource.';
		return $output;
	}

	public function viewJson()
	{

		return $output;
	}

	public function viewText()
	{
		return 'You do not have permission to perform that action.';
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
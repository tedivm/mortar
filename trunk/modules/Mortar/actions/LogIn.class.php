<?php

class MortarActionLogIn extends ActionBase
{
	static $requiredPermission = 'Read';

	public $AdminSettings = array('linkLabel' => 'Log In',
									'linkTab' => 'Universal',
									'headerTitle' => 'Log In',
									'EnginePermissionOverride' => true);

	protected $form;
	protected $loginSuccessful = false;

	protected $allowedFailures = 3;
	protected $maxFailures = 20;

	protected function logic()
	{
		if(MortarLoginTracker::getFailureCount($_SERVER['REMOTE_ADDR']) >= $this->maxFailures)
			throw new AuthenticationError('Too many failed logins.');

		$form = new Form('logIn');
		$this->form = $form;

		$form->createInput('username')->
				setLabel('Username: ')->
				addRule('required')->
			getForm()->
			createInput('password')->
				setLabel('Password: ')->
				setType('password')->
				addRule('required');

		if($inputHandler = $form->checkSubmit())
		{
			try{
				$userId = ActiveUser::getIdFromName($inputHandler['username']);

				// We grab the failed login count before checking the form.	This way bots have to wait for the
				// connection to finish to see if they succeeded or not, instead of detecting the delay and dropping
				// the connection.
				$count = MortarLoginTracker::getFailureCount($_SERVER['REMOTE_ADDR'], $userId);

				$this->loginSuccessful =
				(bool) (ActiveUser::changeUserByNameAndPassword($inputHandler['username'], $inputHandler['password']));

				if($this->loginSuccessful)
				{
					MortarLoginTracker::clearFailures($_SERVER['REMOTE_ADDR'], ActiveUser::getUser()->getId());
				}else{
					MortarLoginTracker::addFailure($_SERVER['REMOTE_ADDR'], $userId);
					$count++;
				}

				if($count >= $this->allowedFailures) // requires two logins to enter loop
				{
					$badLogins = $count - ($this->allowedFailures - 1);
					$delayList = array(60);
					$delayList[] = ini_get('max_execution_time') * .85;
					$delayList[] = log($badLogins, 1.07);
					$delayList[] = log($this->maxFailures, 1.07);
					$delay = (int) min($delayList);

					if($delay > 0)
						sleep($delay);
				}
			}catch(Exception $e){}
		}

		// I can't remember why this is important and that makes me sad. Cookies to anyone who can figure it out.
		$this->ioHandler->setStatusCode(200);
	}

	public function viewHtml()
	{
		$output = '';
		if($this->loginSuccessful)
		{
			if($redirectUrl = $this->getUrl())
			{
				$urlString = (string) $redirectUrl;
				$this->ioHandler->addHeader('Location', $urlString);
			}
			return 'You have successfully logged in.';
		}else{
			if($this->form->wasSubmitted())
				$this->AdminSettings['headerSubTitle'] = 'Invalid login';

			$output .= $this->form->getFormAs('Html');
		}

		return $output;
	}

	public function viewAdmin()
	{
		return $this->viewHtml();
	}

	protected function getUrl()
	{
		$query = Query::getQuery();

		$url = new Url();
		$url->format = $query['format'];

		$action = isset($query['a']) ? strtolower($query['a']) : false;
		if(isset($query['l']) && is_numeric($query['l']) && ($action == 'index' || $action == 'read'))
		{
			$location = new Location($query['l']);
			$model = $location->getResource();

			if(!$model->getAction($query['a']))
				return false;

			$url->location = $query['l'];
			$url->action = $query['a'];
		}elseif(isset($query['m']) && $action){
			$packageInfo = new PackageInfo($query['m']);
			if(!$packageInfo->getActions($query['a']))
				return false;

			$url->module = $query['m'];
			$url->action = $query['a'];
		}else{
			return false;
		}

		$user = ActiveUser::getUser();
		if(!$url->checkPermission($user->getId()))
			return false;

		return $url;
	}

	public function checkAuth($action = NULL)
	{
		return true;
	}
}


?>
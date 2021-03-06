<?php

class MortarCoreActionLogIn extends MortarCoreActionLogOut
{
	static $requiredPermission = 'Read';

	public static $settings = array( 'Base' => array( 'headerTitle' => 'Log In', 'EnginePermissionOverride' => true) );

	protected $form;
	protected $loginSuccessful = false;

	protected $allowedFailures = 3;
	protected $maxFailures = 20;

	public function logic()
	{
		if(MortarCoreLoginTracker::getFailureCount($_SERVER['REMOTE_ADDR']) >= $this->maxFailures)
			throw new AuthenticationError('Too many failed logins.');

		$form = new MortarCoreLogInForm('logIn');
		$this->form = $form;

		if($inputHandler = $form->checkSubmit())
		{
			try{
				$userId = ActiveUser::getIdFromName($inputHandler['username']);

				// We grab the failed login count before checking the form. This way bots have to wait for the
				// connection to finish to see if they succeeded or not, instead of detecting the delay and dropping
				// the connection.
				$count = MortarCoreLoginTracker::getFailureCount($_SERVER['REMOTE_ADDR'], $userId);

				$this->loginSuccessful =
				(bool) (ActiveUser::changeUserByNameAndPassword($inputHandler['username'], $inputHandler['password']));

				if($this->loginSuccessful)
				{
					MortarCoreLoginTracker::clearFailures($_SERVER['REMOTE_ADDR'], ActiveUser::getUser()->getId());
				}else{
					MortarCoreLoginTracker::addFailure($_SERVER['REMOTE_ADDR'], $userId);
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

	public function viewHtml($page)
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
				$this->adminSettings['headerSubTitle'] = 'Invalid login';

			$output .= $this->form->getFormAs('Html');
		}

		return $output;
	}

	public function viewAdmin($page)
	{
		$page->showMenus(false);
		$output = $this->viewHtml($page);
		return $output;
	}

	public function checkAuth($action = NULL)
	{
		return true;
	}
}


?>
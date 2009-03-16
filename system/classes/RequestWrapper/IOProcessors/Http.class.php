<?php

class IOProcessorHttp extends IOProcessorCli
{

	protected function setEnvironment()
	{
		$query = Query::getQuery();

		if($this->arguments['inputHandler'] == 'put'){
			Form::$userInput = Put::getInstance();
		}else{
			Form::$userInput = Post::getInstance();
		}

		if(!isset($query['format']))
			$query['format'] = 'Html';

		if(INSTALLMODE === true && $query['format'] == 'Html')
			$query['format'] = 'Admin';

		$query->save();



	}

	protected function start()
	{
		if(INSTALLMODE === true)
			return false;

		if(session_id() == '')
		{
			session_start();
			$sessionObserver = new SessionObserver();
			$user = ActiveUser::getInstance();
			$user->attach($sessionObserver);
		}
	}

	public function close()
	{
		if(session_id() != '')
			session_commit();
	}



	public function finishPath($pathArray, $package, $resource = null)
	{
		$moduleInfo = new PackageInfo($package);

		if(is_array($pathArray) && count($pathArray) > 0)
		{
			$query = Query::getQuery();

			$urlTemplate = new DisplayMaker();

			if( (!is_null($resource) && $url->loadTemplate($resource . 'UrlMapping', $package))
				|| $url->loadTemplate('UrlMapping', $package))
			{
				$tags = $url->tagsUsed();
				if(count($tags) > 0)
				{
					foreach($tags as $name)
						$query[$name] = array_shift($pathArray);
				}

			}elseif(is_string($pathArray[0])){
				$query['action'] = $pathArray[0];
			}

			$query->save();
		}
	}

}


class SessionObserver implements SplObserver
{
	protected $userId;
	protected $obsoleteTime = 15; //seconds


	public function update(SplSubject $object)
	{
		if($object instanceof ActiveUser)
		{
			// If this class hasn't been loaded before and the session is good, load it
			if(!is_numeric($this->userId) && $this->checkSession())
			{
				$this->userId = 0; // prevents looping
				$object->loadUser($_SESSION['user_id']);
			}

			$this->userId = $object->getId();
			$this->regenerateSession();
		}
	}

	protected function regenerateSession()
	{

		// This forces the system to reload certain variables when the user changes.
		$reload = ($_SESSION['user_id'] != $this->userId || ($_SESSION['idExpiration'] < time()));

		// This token is used by forms to prevent cross site forgery attempts
		if(!isset($_SESSION['nonce']) || $reload)
			$_SESSION['nonce'] = md5($this->id . START_TIME);

		if(!isset($_SESSION['IPaddress']) || $reload)
			$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];

		if(!isset($_SESSION['userAgent']) || $reload)
			$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];

		$_SESSION['user_id'] = $this->userId;

		if($reload || !$_SESSION['OBSOLETE'] && mt_rand(1, 100) == 1)
		{
			// Set current session to expire in x seconds
			$_SESSION['OBSOLETE'] = true;
			$_SESSION['EXPIRES'] = time() + $this->obsoleteTime;


			// Create new session without destroying the old one
			session_regenerate_id(false);

			// Grab current session ID and close both sessions to allow other scripts to use them
			$newSession = session_id();
			session_write_close();

			// Set session ID to the new one, and start it back up again
			session_id($newSession);
			session_start();

			$_SESSION['idExpiration'] = time() + (300);
			// Don't want this one to expire
			unset($_SESSION['OBSOLETE']);
			unset($_SESSION['EXPIRES']);
		}
	}

	protected function checkSession()
	{
		try{

			if($_SESSION['OBSOLETE'] && ($_SESSION['EXPIRES'] < time()))
				throw new BentoWarning('Attempt to use expired session.');

			if(!is_numeric($_SESSION['user_id']))
				throw new BentoNotice('No session started.');

			if($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR'])
				throw new BentoNotice('IP Address mixmatch (possible session hijacking attempt).');

			if($_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT'])
				throw new BentoNotice('Useragent mixmatch (possible session hijacking attempt).');

		}catch(Exception $e){
			return false;
		}
		return true;
	}

}




?>
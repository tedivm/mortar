<?php

class IOProcessorHttp extends IOProcessorCli
{

	protected $headers = array();
	protected $cacheExpirationOffset;

	protected function setEnvironment()
	{
		$query = Query::getQuery();

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
			$site = ActiveSite::getSite();
			$siteLocation = $site->getLocation();
			$cookieName = $siteLocation->getName() . 'Session';

			session_name($cookieName);
			session_start();
			$sessionObserver = new SessionObserver();
			$user = ActiveUser::getInstance();
			$user->attach($sessionObserver);
		}
	}

	public function addHeader($name, $value)
	{
		$name = (string) $name;
		$value = (string) $value;

		if(strpos($name, "\n") || strpos($value, "\n"))
			return false;

		$this->headers[$name] = $value;
	}

	public function output($output)
	{
		$sendOutput = true;

		if(!headers_sent())
			$this->sendHeaders($output);

		$method = strtolower($_SERVER['REQUEST_METHOD']);

		if($method == 'get')
		{
			$clientCacheTime = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
											$_SERVER['HTTP_IF_MODIFIED_SINCE'] : '0';

			$serverCacheTime = isset($this->headers['Last-Modified']) ? $this->headers['Last-Modified'] : 1;

			$clientCacheEtag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : '0';

			$serverEtag = isset($this->headers['ETag']) ? $this->headers['ETag'] : 1;

			if((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) &&
				(!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || $serverCacheTime == $clientCacheTime) &&
				(!isset($_SERVER['HTTP_IF_NONE_MATCH']) || $serverEtag == $clientCacheEtag))
			{
				$sendOutput = false;
				header("HTTP/1.1 304 Not Modified");
			}
		}elseif($method == 'head'){
			$sendOutput = false;
		}

		if($sendOutput)
			echo $output;
	}


	protected function sendHeaders($output)
	{
		// this doesn't seem to have any affect, as it gets overridden by apache (the only one i've tested
		// this on so far) or php
		$this->addHeader('Content-Length', strlen($output));
		$this->addHeader('Date',gmdate('D, d M y H:i:s T'));

		$requestMethod = strtolower($_SERVER['REQUEST_METHOD']);

		// if the request is read-only we'll return some caching headers
		if($requestMethod == 'head' || $requestMethod == 'get')
		{
			$contentMd5 = md5($output);
			$this->addHeader('Content-MD5', $contentMd5);
			$etag = $this->headers['Content-Length'] . $contentMd5;

			if(isset($this->headers['Last-Modified']))
			{
				$etag .= strtotime($this->headers['Last-Modified']);
			}

			$time = time();
			$timeBetweenNowAndLastChange = $time - strtotime($this->headers['Last-Modified']);

			$offset = (isset($this->cacheExpirationOffset)) ? $this->cacheExpirationOffset : 21600;

			// at most the cache time should be half the time between access and last modification
			// so quick typoes and such can easily be checked
			if($offset > $timeBetweenNowAndLastChange / 2)
				$offset = floor($timeBetweenNowAndLastChange / 2);

			$this->addHeader('Pragma', 'Asparagus');
			$this->addHeader('ETag', hash('crc32', $etag));
			$this->addHeader('Expires', gmdate('D, d M y H:i:s T', $time + $offset ));
			$this->addHeader('Cache-Control', 'must-revalidate, max-age=' . $offset);
		}



		foreach($this->headers as $name => $value)
		{
			header($name . ':' . $value);
		}

	}


	public function close()
	{
		if(session_id() != '')
			session_commit();
	}



	public function finishPath($pathArray, $package, $resource = null)
	{

	}

}


class SessionObserver implements SplObserver
{
	protected $userId;

	// this is the delay between marking a session as expired and actually killing it
	// this is needed because quick concurrent connections (ajax) doesn't handle immediate
	// session changes well
	protected $obsoleteTime = 15;


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
			$_SESSION['nonce'] = md5($this->userId . START_TIME);

		if(!isset($_SESSION['IPaddress']) || $reload)
			$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];

		if(!isset($_SESSION['userAgent']) || $reload)
			$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];

		$_SESSION['user_id'] = $this->userId;

		// there's a one percent of the session id changing to help prevent session theft
		if($reload || !isset($_SESSION['OBSOLETE']) && mt_rand(1, 100) == 1)
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

			if(isset($_SESSION['OBSOLETE']) && ($_SESSION['EXPIRES'] < time()))
				throw new BentoWarning('Attempt to use expired session.');

			if(!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']))
				throw new BentoNotice('No session started.');

			if(!isset($_SESSION['IPaddress']) || $_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR'])
				throw new BentoNotice('IP Address mixmatch (possible session hijacking attempt).');

			if(!isset($_SESSION['userAgent']) || $_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT'])
				throw new BentoNotice('Useragent mixmatch (possible session hijacking attempt).');

		}catch(Exception $e){
			return false;
		}
		return true;
	}

}




?>
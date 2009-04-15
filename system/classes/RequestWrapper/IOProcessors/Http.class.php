<?php

class IOProcessorHttp extends IOProcessorCli
{

	protected $headers = array();
	protected $cacheExpirationOffset;
	protected $responseCode = 200;
	public $maxClientCache = 21600;

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

		switch(round($this->responseCode, -2)/100)
		{
			case 5:
				break;
			case 4:
				break;
			case 3:
				$sendOutput = false;
				break;
			case 1:
				break;
			default:
			case 2:
				break;
		}


		if($method == 'head')
			$output = false;


		if($this->responseCode != 200)
		{
			if($codeString = ResponseCodeLookup::stringFromCode($this->responseCode))
			{
				header('HTTP/1.1 ' . $this->responseCode . ' ' . $codeString);
			}
		}


		if($sendOutput)
			echo $output;
	}


	protected function sendHeaders($output)
	{
		// basic clickjacking protection
		$this->addHeader('X-FRAME-OPTIONS', 'SAMEORIGIN');

		// this doesn't seem to have any affect, as it gets overridden by apache (the only one i've tested
		// this on so far) or php
		$this->addHeader('Content-Length', strlen($output));
		$this->addHeader('Date',gmdate('D, d M y H:i:s T'));
		$contentMd5 = md5($output);
		$this->addHeader('Content-MD5', $contentMd5);

		$cacheControl = 'must-revalidate';
		// if the request is read-only we'll return some caching headers
		$requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
		if(($requestMethod == 'head' || $requestMethod == 'get') &&
					(!defined('DISABLECLIENTCACHE') || DISABLECLIENTCACHE !== true))
		{
			$etag = $this->headers['Content-Length'] . $contentMd5;

			if(isset($this->headers['Last-Modified']))
			{
				$lastModified = $this->headers['Last-Modified'];
				$lastModifiedAsTime = strtotime($lastModified);
				$time = time();
				$timeBetweenNowAndLastChange = $time - $lastModifiedAsTime;

				// at most the cache time should be 20% the time between access and last modification
				$maxCache = floor($timeBetweenNowAndLastChange * .2);
				$maxCache = ($this->maxClientCache > $maxCache) ? $maxCache : $this->maxClientCache;
				$offset = (isset($this->cacheExpirationOffset) && $this->cacheExpirationOffset < $maxCache)
									? $this->cacheExpirationOffset : $maxCache;

				$etag .= $lastModifiedAsTime;

				$this->addHeader('Expires', gmdate('D, d M y H:i:s T', $time + $offset ));
				$cacheControl .= ',max-age=' . $offset;
			}

			$serverEtag = hash('crc32', $etag);

			$this->addHeader('ETag', $serverEtag);
			$this->addHeader('Pragma', 'Asparagus');
			$this->addHeader('Cache-Control', $cacheControl);

			if(!isset($lastModified))
				$lastModified = 1;

			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH']))
			{
				 if((!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || $lastModified == $_SERVER['HTTP_IF_MODIFIED_SINCE'])
				 && (!isset($_SERVER['HTTP_IF_NONE_MATCH']) || $serverEtag == $_SERVER['HTTP_IF_NONE_MATCH']))
				{
					$this->setHttpCode(304);
				}
			}
		}

		foreach($this->headers as $name => $value)
			header($name . ':' . $value);
	}


	public function close()
	{
		if(session_id() != '')
			session_commit();
	}

	public function setHttpCode($code)
	{
		if(!is_numeric($code))
			throw new TypeMismatch(array('Numeric', $code));

		$this->responseCode = $code;
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


class ResponseCodeLookup
{
	static $lookupArray = array(
					100 => 'Continue',
					101 => 'Switching Protocols',

					200 => 'OK',
					201 => 'Created',
					202 => 'Accepted',
					203 => 'Non-Authoritative Information',
					204 => 'No Content',
					205 => 'Reset Content',
					206 => 'Partial Content',
					207 => 'Multi-Status',

					300 => 'Multiple Choices',
					301 => 'Moved Permanently',
					302 => 'Found',
					303 => 'See Other',
					304 => 'Not Modified',
					305 => 'Use Proxy',
					307 => 'Temporary Redirect',
					400 => 'Bad Request',
					401 => 'Unauthorized',
					402 => 'Payment Required',
					403 => 'Forbidden',
					404 => 'Not Found',
					405 => 'Method Not Allowed',
					406 => 'Not Acceptable',
					407 => 'Proxy Authentication Required',
					408 => 'Request Timeout',
					409 => 'Conflict',
					410 => 'Gone',
					411 => 'Length Required',
					412 => 'Precondition Failed',
					413 => 'Request Entity Too Large',
					414 => 'Request-URI Too Long',
					415 => 'Unsupported Media Type',
					416 => 'Request Range Not Satisfiable',
					417 => 'Expectation Failed',

					500 => 'Internal Server Error',
					501 => 'Not Implemented',
					502 => 'Bad Gateway',
					503 => 'Service Unavailable',
					504 => 'Gateway Timeout',
					505 => 'HTTP Version not Supported');



	static public function stringFromCode($code)
	{
		if(isset(self::$lookupArray[$code]))
		{
			return self::$lookupArray[$code];
		}else{
			false;
		}
	}
}

?>
<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage RequestWrapper
 */

/**
 * This class processes HTTP input and output
 *
 * @package System
 * @subpackage RequestWrapper
 */
class IOProcessorHttp extends IOProcessorCli
{

	/**
	 * This is a list of http headers to be sent out
	 *
	 * @access protected
	 * @var array
	 */
	protected $headers = array();

	/**
	 * The amount of time, in seconds, to give as the max age and expiration for a resource
	 *
	 * @access public
	 * @var int
	 */
	public $cacheExpirationOffset;

	/**
	 * Time, in seconds, to allow multiple sessions to remain active
	 *
	 * @var int
	 */
	public $obsoleteTime = 5;

	/**
	 * Max time, in seconds, that the cacheExpirationOffset can be
	 *
	 * @var int
	 */
	public $maxClientCache = 21600;

	/**
	 * The current compression level (gzip and deflate) used for compression the page.
	 *
	 * @static
	 * @var int Must be between 1 and 9
	 */
	static $compressionLevel = 6;

	/**
	 * The smallest length the output should be if its going to be compressed
	 *
	 * @static
	 * @var int
	 */
	static $compressionMinimum = 128;

	/**
	 * Session cookie expiration time (0 makes it a session only cookie)
	 *
	 * @static
	 * @var int
	 */
	static $cookieTimeLimit = 0;


	/**
	 * This function sets the programming environment to match that of the system and method calling it
	 *
	 * @access protected
	 */
	protected function setEnvironment()
	{
		$query = Query::getQuery();

		if(!isset($query['format']))
			$query['format'] = 'Html';

		if(INSTALLMODE === true && $query['format'] == 'Html')
			$query['format'] = 'Admin';

		$query->save();
	}

	/**
	 * This function is called by the initialization function on load
	 *
	 * @access protected
	 */
	protected function start()
	{
		ignore_user_abort(true);

		if(INSTALLMODE === true)
			return false;

		if(session_id() == '')
		{
			$site = ActiveSite::getSite();
			$siteLocation = $site->getLocation();
			$cookieName = $siteLocation->getName() . '_Session';

			session_name($cookieName);
			session_set_cookie_params(self::$cookieTimeLimit, '/', null, isset($_SERVER["HTTPS"]), true);
			session_start();
			$sessionObserver = new SessionObserver();
			ActiveUser::attach($sessionObserver);
		}
	}

	/**
	 * Adds a new http header to be sent out
	 *
	 * @param string $name
	 * @param string $value
	 * @return bool
	 */
	public function addHeader($name, $value)
	{
		$name = (string) $name;
		$value = (string) $value;

		if(strpos($name, "\n") || strpos($value, "\n"))
			return false;

		$this->headers[$name] = $value;
		return true;
	}

	/**
	 * This function outputs any values its sent to the system
	 *
	 * @param string $output
	 */
	public function output($output)
	{
		// this frees up the session for any other scripts or requests calling it
		if(session_id() != '')
			session_commit();

		$sendOutput = true;

		$size = strlen($output);

		$encoding = false;
		$this->addHeader('Vary', 'Accept-Encoding');

		if( (defined('OUTPUT_COMPRESSION') && OUTPUT_COMPRESSION) &&
			isset($_SERVER['HTTP_ACCEPT_ENCODING']) &&
			self::$compressionLevel > 0 &&
			$size > self::$compressionMinimum &&
			!headers_sent() &&
			!ini_get('zlib.output_compression') && // let php.ini handle compression if it wants
			ini_get('output_handler') != 'ob_gzhandler')
		{
			if(strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'deflate') !== false){
				$encoding = 'deflate';
			}elseif(strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip') !== false){
				$encoding = 'gzip';
			}
		}

		if($encoding)
		{
			$start = microtime(true);
			if($encoding == 'deflate' && function_exists('gzdeflate'))
			{
				$this->addHeader('Content-Encoding', 'deflate');
				$output = gzdeflate($output, self::$compressionLevel);
			}elseif($encoding == 'gzip' && function_exists('gzencode')){
				$this->addHeader('Vary', 'Accept-Encoding');
				$this->addHeader('Content-Encoding', 'gzip');
				$output = gzencode($output, self::$compressionLevel);
			}
			$compressedSize = strlen($output);
			$stop = microtime(true);

			if($compressedSize != $size)
			{
				if(defined('OUTPUT_COMPRESSION_HEADERS') && OUTPUT_COMPRESSION_HEADERS)
				{
					$this->addHeader('X-Compression-Results',
										round(($compressedSize/$size) * 100  ) . '% ' . $compressedSize . '/' . $size);
					$this->addHeader('X-Compression-Time', $stop - $start);
				}
				$size = $compressedSize;
			}
		}

		$this->addHeader('Content-MD5', md5($output));
		$this->addHeader('Content-Length', $size);

		$serverEtag = hash('crc32',
					$this->headers['Content-Length'] . $this->headers['Content-MD5'] .
						(isset($this->headers['Last-Modified']) ? $this->headers['Last-Modified'] : 1));

		$this->addHeader('ETag', $serverEtag);

		$method = strtolower($_SERVER['REQUEST_METHOD']);


		if(!headers_sent())
		{
			$this->sendHeaders();

			if($this->responseCode != 200 && $codeString = ResponseCodeLookup::stringFromCode($this->responseCode))
				header('HTTP/1.1 ' . $this->responseCode . ' ' . $codeString);
		}

		if(!isset($this->responseCode))
			$this->responseCode = 200;

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
			$sendOutput = false;

		if($sendOutput)
		{
			echo $output;
			flush();
		}
	}

	/**
	 * Outputs the stored http headers, as well as some extra caching related headers
	 *
	 * @access protected
	 */
	protected function sendHeaders()
	{
		// basic clickjacking protection
		$this->addHeader('X-FRAME-OPTIONS', 'SAMEORIGIN');
		$this->addHeader('Date',gmdate('D, d M y H:i:s T'));

		if(isset($this->headers['Content-MD5']))
			$contentMd5 = $this->headers['Content-MD5'];

		// if the request is read-only we'll return some caching headers
		$requestMethod = strtolower($_SERVER['REQUEST_METHOD']);

		if(($requestMethod == 'head' || $requestMethod == 'get') &&
					(!defined('DISABLECLIENTCACHE') || DISABLECLIENTCACHE !== true) &&
					(!isset($this->responseCode)))
		{


			if(isset($this->headers['Last-Modified']))
			{
				$cacheControl = 'must-revalidate';
				$lastModified = $this->headers['Last-Modified'];
				$lastModifiedAsTime = strtotime($lastModified);
				$time = time();
				$timeBetweenNowAndLastChange = $time - $lastModifiedAsTime;

				// at most the cache time should be 20% the time between access and last modification
				$maxCache = floor($timeBetweenNowAndLastChange * .2);
				$maxCache = ($this->maxClientCache > $maxCache) ? $maxCache : $this->maxClientCache;
				$offset = (isset($this->cacheExpirationOffset) && $this->cacheExpirationOffset < $maxCache)
									? $this->cacheExpirationOffset : $maxCache;

				if(isset($this->headers['Expires']))
				{
					$offset = strtotime($this->headers['Expires']) - $time;
				}else{
					$this->addHeader('Expires', gmdate('D, d M y H:i:s T', $time + $offset ));
				}

				$cacheControl = 'must-revalidate,max-age=' . $offset;
				$this->addHeader('Cache-Control', $cacheControl);
			}

			$pragma = defined('HEADER_MESSAGE') ? HEADER_MESSAGE : 'tedivm was here';
			$this->addHeader('Pragma', $pragma); // if something isn't sent out, apache sends no-cache


			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH']))
			{
				 if((!isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
				 				|| $this->headers['Last-Modified'] == $_SERVER['HTTP_IF_MODIFIED_SINCE'])
					&& (!isset($_SERVER['HTTP_IF_NONE_MATCH'])
								|| $this->headers['ETag'] == $_SERVER['HTTP_IF_NONE_MATCH']))
				{
					$this->setStatusCode(304);
				}
			}
		}

		foreach($this->headers as $name => $value)
			header($name . ':' . $value);
	}

}

/**
 * This class attaches to the active user as an observer to keep the user active across requests
 *
 */
class SessionObserver
{
	protected $userId;

	/**
	 * This is the delay between marking a session as expired and actually killing it this is needed because quick
	 * concurrent connections (ajax) doesn't handle immediate session changes well
	 *
	 * @access protected
	 * @var int
	 */
	protected $obsoleteTime = 15;

	/**
	 * This function gets called whenever the activeuser gets changed
	 *
	 * @param ActiveUser $object
	 */
	public function update($object)
	{
		// If this class hasn't been loaded before and the session is good, load it
		if(!is_numeric($this->userId) && $this->checkSession())
		{
			$this->userId = 0; // prevents looping
			ActiveUser::changeUserById($_SESSION['user_id']);
		}

		$this->userId = $object->getId();
		$this->regenerateSession();

	}

	/**
	 * This function stores certain data in the session for security purposes and handles regenerating the session id
	 * in a secure manner.
	 *
	 * @access protected
	 */
	protected function regenerateSession()
	{
		// This forces the system to reload certain variables when the user changes.
		$reload = (!isset($_SESSION['user_id']) ||
						$_SESSION['user_id'] != $this->userId ||
						($_SESSION['idExpiration'] < time()));

		$_SESSION['user_id'] = $this->userId;
		// This token is used by forms to prevent cross site forgery attempts
		if(!isset($_SESSION['nonce']))
		{
			$_SESSION['nonce'] = md5($this->userId . START_TIME . rand());
		}
		if(!isset($_SESSION['IPaddress']) || $_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR'])
			$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];

		if(!isset($_SESSION['userAgent']) || $_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT'])
			$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];

		// there's a one percent of the session id changing to help prevent session theft

		if(isset($_SESSION['OBSOLETE']))
		{
			if(!isset($_SESSION['idExpiration']) || $_SESSION['idExpiration'] < time())
			{
				$_SESSION = array();
				session_destroy();
			}
		}elseif((!isset($_SESSION['idExpiration']) || $_SESSION['idExpiration'] < time()) || mt_rand(1, 100) == 1){

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

	/**
	 * This function checks certain values besides the session id, such as the ip address and user agent, of a session
	 * to prevent session hijacking attempts
	 *
	 * @return bool
	 */
	protected function checkSession()
	{
		try{

			if(isset($_SESSION['OBSOLETE']) && ($_SESSION['EXPIRES'] < time()))
				throw new BentoWarning('Attempt to use expired session.');

			if(!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']))
			{
				throw new Exception('No session started');
			}

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

/**
 * This class acts as a lookup for http status strings
 *
 * @package System
 * @subpackage RequestWrapper
 */
class ResponseCodeLookup
{
	/**
	 * This is an array with the http code as the index and its detailed string as the value
	 *
	 * @static
	 * @var array
	 */
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

	/**
	 * Returns the full status string from a status code
	 *
	 * @static
	 * @param int $code
	 * @return string
	 */
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
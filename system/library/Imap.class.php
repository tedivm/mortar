<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package		Library
 * @subpackage	Imap
 */

/**
 * This library is a wrapper around the Imap library functions included in php. This class in particular manages a
 * connection to the server (imap, pop, etc) and allows for the easy retrieval of stored messages.
 *
 * @package		Library
 * @subpackage	Imap
 */
class ImapConnection
{
	/**
	 * This is the domain or server path the class is connecting to.
	 *
	 * @var string
	 */
	protected $serverPath;

	/**
	 * This is the name of the current mailbox the connection is using.
	 *
	 * @var string
	 */
	protected $mailbox;

	/**
	 * This is the username used to connect to the server.
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * This is the password used to connect to the server.
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * This is an array of flags that modify how the class connects to the server. Examples include "ssl" to enforce a
	 * secure connection or "novalidate-cert" to allow for self-signed certificates.
	 *
	 * @link http://us.php.net/manual/en/function.imap-open.php
	 * @var array
	 */
	protected $flags = array();

	/**
	 * This is the port used to connect to the server
	 *
	 * @var unknown_type
	 */
	protected $port;

	/**
	 * This is the resource connection to the server. It is required by a number of imap based functions to specify how
	 * to connect.
	 *
	 * @var resource
	 */
	protected $imapStream;

	/**
	 * This is the name of the service currently being used. Imap is the default, although pop3 and nntp are also
	 * options
	 *
	 * @var string
	 */
	protected $service = 'imap';

	/**
	 * This constructor takes the location and service thats trying to be connected to as its arguments.
	 *
	 * @param string $serverPath
	 * @param null|int $port
	 * @param null|string $service
	 */
	public function __construct($serverPath, $port = 143, $service = 'imap')
	{
		$this->serverPath = $serverPath;

		$this->port = $port;

		if($port == 993)
			$this->addFlag('ssl');

		$this->service = $service;
	}

	/**
	 * This function sets the username and password used to connect to the server.
	 *
	 * @param string $username
	 * @param string $password
	 */
	public function setAuthentication($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * This function sets the mailbox to connect to.
	 *
	 * @param string $mailbox
	 */
	public function setMailBox($mailbox = '')
	{
		$this->mailbox = $mailbox;
		if(isset($this->imapStream))
		{
			$this->setImapStream();
		}
	}

	/**
	 * This function sets or removes flag specifying connection behavior. In many cases the flag is just a one word
	 * deal, so the value attribute is not required. However, if the value parameter is passed false it will clear that
	 * flag.
	 *
	 * @param string $flag
	 * @param null|string|bool $value
	 */
	public function setFlag($flag, $value = null)
	{
		if(isset($value) && $value !== true)
		{
			if($value == false)
			{
				unset($this->flags[$flag]);
			}else{
				$this->flags[] = '/' . $flag . '=' . $value;
			}
		}else{
			$this->flags[] = '/' . $flag;
		}
	}

	/**
	 * This function gets the current saved imap resource and returns it.
	 *
	 * @return resource
	 */
	public function getImapStream()
	{
		if(!isset($this->imapStream))
			$this->setImapStream();

		return $this->imapStream;
	}

	/**
	 * This function takes in all of the connection date (server, port, service, flags, mailbox) and creates the string
	 * thats passed to the imap_open function.
	 *
	 * @return string
	 */
	protected function getServerString()
	{
		$mailboxPath = '{' . $this->serverPath;

		if(isset($this->port))
			$mailboxPath .= ':' . $this->port;

		if($this->service != 'imap')
			$mailboxPath .= '/' . $this->service;

		foreach($this->flags as $flag)
		{
			$mailboxPath .= '/' . $flag;
		}

		$mailboxPath .= '}';

		if(isset($this->mailbox))
			$mailboxPath .= $this->mailbox;

		return $mailboxPath;
	}

	/**
	 * This function creates or reopens an imapStream when called.
	 *
	 */
	protected function setImapStream()
	{
		if(isset($this->imapStream))
		{
			imap_reopen($this->imapStream, $this->mailbox, $this->options, 1);
		}else{
			$this->imapStream = imap_open($this->getServerString(), $this->username, $this->password, $this->options, 1);
		}
	}

	/**
	 * This returns the number of messages that the current mailbox contains.
	 *
	 * @return int
	 */
	public function numMessages()
	{
		return imap_num_msg($this->getImapStream());
	}

	/**
	 * This function returns an array of ImapMessage object for emails that fit the criteria passed. The criteria string
	 * should be formatted according to the imap search standard, which can be found on the php "imap_search" page or in
	 * section 6.4.4 of RFC 2060
	 *
	 * @link http://us.php.net/imap_search
	 * @link http://www.faqs.org/rfcs/rfc2060
	 * @param string $criteria
	 * @param null|int $limit
	 * @return array An array of ImapMessage objects
	 */
	public function search($criteria = 'ALL', $limit = null)
	{
		if($results = imap_search($this->getImapStream(), $criteria, SE_UID))
		{
			if(isset($limit) && count($results) > $limit)
				$results = array_slice($results, 0, $limit);

			$stream = $this->getImapStream();
			$messages = array();

			foreach($results as $messageId)
				$messages[] = new ImapMessage($messageId, $this);

			return $messages;
		}else{
			false;
		}
	}

	/**
	 * This function returns the recently received emails as an array of ImapMessage objects.
	 *
	 * @param null|int $limit
	 * @return array An array of ImapMessage objects for emails that were recently received by the server.
	 */
	public function getRecentMessages($limit = null)
	{
		return $this->search('Recent', $limit);
	}

	/**
	 * Returns the emails in the current mailbox as an array of ImapMessage objects.
	 *
	 * @param null|int $limit
	 * @return array
	 */
	public function getMessages($limit = null)
	{
		$numMessages = $this->numMessages();

		if(isset($limit) && is_numeric($limit) && $limit < $numMessages)
			$numMessages = $limit;

		if($numMessages < 1)
			return false;

		$stream = $this->getImapStream();
		$messages = array();
		for($i = 1; $i <= $numMessages; $i++)
		{
			$uid = imap_uid($stream, $i);
			$messages[] = new ImapMessage($uid, $this);
		}

		return $messages;
	}

}

/**
 * This library is a wrapper around the Imap library functions included in php. This class represents a single email
 * message as retrieved from the ImapConnection.
 *
 * @package		Library
 * @subpackage	Imap
 */
class ImapMessage
{
	/**
	 * This is the connection/mailbox class that the email came from.
	 *
	 * @var ImapConnection
	 */
	protected $imapConnection;

	/**
	 * This is the unique identifier for the message. This corresponds to the imap "uid", which we use instead of the
	 * sequence number.
	 *
	 * @var int
	 */
	protected $uid;

	/**
	 * This is a reference to the Imap stream generated by 'imap_open'.
	 *
	 * @var resource
	 */
	protected $imapStream;

	/**
	 * This as an object which contains header information for the message.
	 *
	 * @var stdClass
	 */
	protected $headers;

	/**
	 * This is an object which contains various status messages and other information about the message.
	 *
	 * @var stdClass
	 */
	protected $messageOverview;

	/**
	 * This is an object which contains information about the structure of the message body.
	 *
	 * @var stdClass
	 */
	protected $structure;

	/**
	 * This is an array with the index being imap flags and the value being a boolean specifying whether that flag is
	 * set or not.
	 *
	 * @var array
	 */
	protected $status = array();

	/**
	 * This is an array of the various imap flags that can be set.
	 *
	 * @var string
	 */
	static protected $flagTypes = array('recent', 'flagged', 'answered', 'deleted', 'seen', 'draft');

	/**
	 * This constructor takes in the uid for the message and the ImapConnection class representing the mailbox the
	 * message should be opened from. This constructor should generally not be called directly, but rather retrieved
	 * through the apprioriate ImapConnection functions.
	 *
	 * @param int $messageUniqueId
	 * @param ImapConnection $mailbox
	 */
	public function __construct($messageUniqueId, ImapConnection $mailbox)
	{
		$this->imapConnection = $mailbox;
		$this->uid = $messageUniqueId;
		$this->imapStream = $this->mailbox->getImapStream();
		$this->loadMessage();
	}

	/**
	 * This function is called when the message class is loaded. It loads general information about the message from the
	 * imap server.
	 *
	 */
	protected function loadMessage()
	{
		// header
		$messageOverview = $this->getOverview();

		foreach(self::$flagTypes as $flag)
		{
			$this->status[$flag] = ($messageOverview->$flag == 1);
		}

		$this->processStructure();

		// body


	}

	/**
	 * This function returns an object containing information about the message. This output is similar to that over the
	 * imap_fetch_overview function, only instead of an array of message overviews only a single result is returned. The
	 * results are only retrieved from the server once unless passed true as a parameter.
	 *
	 * @param bool $forceReload
	 * @return stdClass
	 */
	public function getOverview($forceReload = false)
	{
		if($forceReload || !isset($this->messageOverview))
		{
									// returns an array, and since we just want one message we can grab the only result
			$this->messageOverview = array_shift(imap_fetch_overview($this->imapStream, $this->uid, FT_UID));
		}
		return $this->messageOverview;
	}

	/**
	 * This function returns an object containing the headers of the message. This is done by taking the raw headers
	 * and running them through the imap_rfc822_parse_headers function. The results are only retrieved from the server
	 * once unless passed true as a parameter.
	 *
	 * @param bool $forceReload
	 * @return stdClass
	 */
	public function getHeaders($forceReload = false)
	{
		if($forceReload || !isset($this->headers))
		{
			// raw headers (since imap_headerinfo doesn't use the unique id)
			$rawHeaders = imap_fetchheader($this->imapStream, $this->uid, FT_UID);

			// convert raw header string into a usable object
			$headerObject = imap_rfc822_parse_headers($rawHeaders);

			// to keep this object as close as possible to the original header object we add the udate property
			$headerObject->udate = strtotime($headerObject->date);

			$this->headers = $headerObject;
		}

		return $this->headers;
	}

	/**
	 * This function returns an object containing the structure of the message body. This is the same object thats
	 * returned by imap_fetchstructure. The results are only retrieved from the server once unless passed true as a
	 * parameter.
	 *
	 * @return stdClass
	 */
	public function getStructure($forceReload = false)
	{
		if($forceReload || !isset($this->structure))
		{
			$this->structure = imap_fetchstructure($this->imapStream, $this->uid, FT_UID);
		}
		return $this->structure;
	}


	protected function processStructure($structure = null, $partIdentifier = null)
	{
		if(!isset($structure))
			$structure = $this->getStructure();;

		if(!isset($structure->parts))  // not multipart
		{
			var_dump($partIdentifier);
			var_dump($structure);
			echo '<br><br>';

			$parameters = array();

			if(isset($structure->ifparameters))
				foreach($structure->parameters as $parameter)
					$parameters[$parameter->attribute] = $parameter->value;

			if(isset($structure->ifdparameters))
				foreach($structure->dparameters as $parameter)
					$parameters[$parameter->attribute] = $parameter->value;

			if(isset($parameters['name']) || isset($parameters['filename']))
			{
				// this is an attachment
			}elseif($structure->type==0){

				// this is text


			}


		}else{  // multipart: iterate through each part
			foreach ($structure->parts as $partIndex => $part)
			{
				$partId = $partIndex + 1;

				if(isset($partIdentifier))
					$partId = $partIdentifier . '.' . $partId;

				$this->processStructure($part, $partId);
			}
		}
	}

	/**
	 * This function returns the unique id that identifies the message on the server.
	 *
	 * @return int
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * This function checks to see if an imap flag is set on the email message.
	 *
	 * @param string $flag Recent, Flagged, Answered, Deleted, Seen, Draft
	 * @return bool
	 */
	public function checkFlag($flag = 'flagged')
	{
		return (isset($this->status[$flag]) && $this->status[$flag] == true);
	}

	/**
	 * This function is used to enable or disable a flag on the imap message.
	 *
	 * @param string $flag Flagged, Answered, Deleted, Seen, Draft
	 * @param bool $enable
	 * @return bool
	 */
	public function setFlag($flag, $enable = true)
	{
		if(!in_array($flag, self::$flagTypes) || $flag == 'recent')
			throw new ImapException('Unable to set invalid flag "' . $flag . '"');

		$flag = '\\' . ucfirst($flag);

		if($enable)
		{
			return imap_setflag_full($this->imapStream, $this->uid, $flag, ST_UID);
		}else{
			return imap_clearflag_full($this->imapStream, $this->uid, $flag, ST_UID);
		}
	}

}




class ImapException extends Exception {}
?>
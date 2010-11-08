<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package		Library
 * @subpackage	Email
 */

/**
 * This class can be used to send emails out.
 *
 * @package		Library
 * @subpackage	Email
 */
class Email
{
	/**
	 * This is the subject of the email being sent out.
	 *
	 * @var string
	 */
	protected $subject;

	/**
	 * This is the email address the email is sent from.
	 *
	 * @var string
	 */
	protected $from;

	/**
	 * This is the content of the email
	 *
	 * @var string
	 */
	protected $message;

	/**
	 * This is an array of recipients for the email.
	 *
	 * @var array
	 */
	protected $recipient_list = array();

	/**
	 * This is data regarding an attachment for the email, if one is provided.
	 *
	 * @var array
	 */
	protected $attachment;

	/**
	 * This constructor sets the  sender, subject and message of the email.
	 *
	 * @param string $from
	 * @param string $subject
	 * @param string $message
	 */
	public function __construct($from, $subject, $message)
	{
		$this->setSender($from);
		$this->setSubject($subject);
		$this->setMessage($message);
	}

	/**
	 * Add an address to the list of people who recieve the email.
	 *
	 * @param string $email
	 * @param string $name
	 * @return boolean
	 */
	public function addRecipient($email, $name = null)
	{
		if($this->checkEmailAddress($email))
		{
			$recipient['email'] = $email;
			if(isset($name))
				$recipient['name'] = $name;

			$this->recipient_list[] = $recipient;
			return true;
		}else{
			return false;
		}
	}

	/**
	 * This function sends the email to the list of recipients.
	 *
	 * @return boolean
	 */
	public function sendEmail()
	{
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'From: ' . $this->from . "\r\n";
		$headers .= 'Return-Path: ' . $this->from . "\r\n";
		$headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";

		if(isset($this->attachment['type'])) {
			$hash = md5(date('r', time()));
			$headers .= 'Content-type: multipart/mixed; boundary="PHP-mixed-' . $hash . '"' . "\r\n";
			$message = $this->prepareAttachmentBody($hash);
		} else {
			$headers .= 'Content-type: text/plain; charset=iso-8859-1' . "\r\n";
			$message = $this->message;
		}

		$to = '';
		$loop = false;
		foreach ($this->recipient_list as $recipient)
		{
			if($loop)
				$to .= ', ';
			else
				$loop = true;

			$to .= isset($recipient['name'])
						? $recipient['name'] . ' <' . $recipient['email'] . '>'
						: $recipient['email'];
		}

		return mail($to, $this->subject, $message, $headers);
	}

	public function addAttachment($name, $type, $contents)
	{
		$this->attachment['name'] = $name;
		$this->attachment['type'] = $type;
		$this->attachment['contents'] = chunk_split(base64_encode($contents));
	}

	/**
	 * Set the sender address
	 *
	 * @param string $email
	 * @return boolean
	 */
	protected function setSender($email)
	{
		if($this->checkEmailAddress($email))
		{
			$this->from = $email;
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Set the Subject
	 *
	 * @param string $subject
	 */
	protected function setSubject($subject)
	{
		$this->subject = $this->cleanSubject($subject);
	}

	/**
	 * Set the message
	 *
	 * @param string $message
	 */
	protected function setMessage($message)
	{
		$this->message = $message;
	}

	/**
	 * Clean the subject
	 *
	 * @param string $subject
	 * @return string clean subject
	 */
	protected function cleanSubject($subject)
	{
		$subject = str_replace("\r", "", $subject);
		$subject = str_replace("\n", "", $subject);

		$evil_headers = array("/bcc\:/i", "/Content\-Type\:/i", "/Mime\-Type\:/i", "/cc\:/i", "/to\:/i");

		$subject = preg_replace($evil_headers, "", $subject);

		return $subject;

	}

	/**
	 * Check email address to make sure its valid.
	 *
	 * @param string $email
	 * @return boolean
	 */
	protected function checkEmailAddress($email)
	{
		$emailValidator = new EmailAddressValidator();
		return $emailValidator->check_email_address($email);
	}

	/**
	 * Prepares the message body using a specified divider string for sending text along with an 
	 * attachment.
	 *
	 * @param string $boundary
	 * @return string
	 */
	protected function prepareAttachmentBody($hash)
	{
		$body  = '--PHP-mixed-' . $hash . "\r\n";
		$body .= 'Content-type: text/plain; charset="iso-8859-1"' . "\r\n";
		$body .= 'Content-Transfer-Encoding: 7bit' . "\r\n\r\n";
		$body .= $this->message . "\r\n\r\n";
		$body .= '--PHP-mixed-' . $hash . "\r\n";
		$body .= 'Content-type: ' . $this->attachment['type'];
		$body .= '; name="' . $this->attachment['name'] . '"' . "\r\n";
		$body .= 'Content-Transfer-Encoding: base64' . "\r\n";
		$body .= 'Content-Disposition: attachment' . "\r\n\r\n";
		$body .= $this->attachment['contents'] . "\r\n";
		$body .= '--PHP-mixed-' . $hash;

		return $body;
	}

}

?>
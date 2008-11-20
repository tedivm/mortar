<?php
/**
 * BentoBase
 *
 * A framework for developing modular applications.
 *
 * @package		BentoBase
 * @author		Robert Hafner
 * @copyright	Copyright (c) 2007, Robert Hafner
 * @license		http://www.mozilla.org/MPL/
 * @link		http://www.bentobase.org
 */



/**
 * Email Class
 *
 * Sends email
 * 
 * @package		BentoBase
 * @subpackage	Main_Classes
 * @category	Email
 * @author		Robert Hafner
 */
class Email
{
	protected $subject;
	protected $from;
	protected $message;
	protected $recipient_list = array();
	
	/**
	 * Constructor
	 *
	 * @param string $from
	 * @param string $subject
	 * @param string $message
	 */
	public function __construct($from, $subject, $message)
	{
		$this->set_sender($from);
		$this->set_subject($subject);
		$this->set_message($message);
	}
	
	/**
	 * Add an address to the list of people who recieve the email
	 *
	 * @param string $email
	 * @param string $name
	 * @return boolean
	 */
	public function add_recipient($email, $name = '')
	{
		if($this->check_email_address($email))
		{
			$this->recipient_list[] = array('email' => $email, 'name' => $name);
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Send the email
	 *
	 * @return boolean
	 */
	public function send_email()
	{
		$headers  = 'MIME-Version: 1.0' . "\n";
		$headers .= 'Content-type: text/plain; charset=iso-8859-1' . "\n";
		$headers .= 'From: ' . $this->from . "\n";
		$headers .= 'Return-Path: ' . $this->from . "\n";
		$headers .= 'X-Mailer: PHP/' . phpversion();
		
		
		$to = '';
		foreach ($this->recipient_list as $recipient)
		{
			$to .= ($loop_flag > 0) ? ', ' : '';
			$to .= ($recipient['name']) ? $recipient['name'] . ' <' . $recipient['email'] . '>' : '<' . $recipient['email'] . '>';
			$loop_flag = 2;
		}

		return mail($to, $this->subject, $this->message, $headers);
	}
	
	/**
	 * Set the sender address
	 *
	 * @param string $email
	 * @return boolean
	 */
	protected function set_sender($email)
	{
		if($this->check_email_address($email))
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
	protected function set_subject($subject)
	{
		$this->subject = $this->clean_subject($subject);
	}
	
	/**
	 * Set the message
	 *
	 * @param string $message
	 */
	protected function set_message($message)
	{
		$this->message = $message;
	}
	
	/**
	 * Clean the subject
	 *
	 * @param string $subject
	 * @return string clean subject
	 */
	private function clean_subject($subject)
	{
		
		$subject = str_replace("\r", "", $subject);
		$subject = str_replace("\n", "", $subject);
		
		$evil_headers = array("/bcc\:/i", "/Content\-Type\:/i", "/Mime\-Type\:/i", "/cc\:/i", "/to\:/i");

		$subject = preg_replace($evil_headers, "", $subject);
		
		return $subject;

	}
	
	/**
	 * Check Email Address
	 *
	 * @param string $email
	 * @return boolean
	 */
	private function check_email_address($email) {
	
		// First, we check that there's one @ symbol, and that the lengths are right
		if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
			// Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
			return false;
		}

		// Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		for ($i = 0; $i < sizeof($local_array); $i++) {
			if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
				return false;
			}
		}
		if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) { // Check if domain is IP. If not, it should be valid domain name
			$domain_array = explode(".", $email_array[1]);
			if (sizeof($domain_array) < 2) {
				return false; // Not enough parts to domain
			}
			for ($i = 0; $i < sizeof($domain_array); $i++) {
				if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
					return false;
				}
			}
		}
		return true;
	}	
	
}

?>
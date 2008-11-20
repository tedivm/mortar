<?php
/**
 * Bento Base
 *
 * A framework for developing modular applications.
 *
 * @package		Bento Base
 * @author		Robert Hafner
 * @copyright	Copyright (c) 2007, Robert Hafner
 * @license		http://www.mozilla.org/MPL/
 * @link		http://www.bentobase.org
 */



/**
 * ContactForm Class
 *
 * This class allows the creation of dynamic contact forms using 
 * the Formbuilder Class and are sent using the Email Class.
 * 
 * @package		Bento Base
 * @subpackage	Main_Classes
 * @category	Email
 * @author		Robert Hafner
 */
class ContactForm
{
	protected $form_generator;
	protected $subject;
	protected $from = '';
	protected $recipient_list = array(); 
	
	
	/**
	 * Simple constructor
	 *
	 */
	public function __construct()
	{
		$this->form_generator = new FormBuilder('name');
		
	}
	
	/**
	 * Sets the from address of the email sent by the form.
	 *
	 * @param string $email
	 * @param string $name
	 */
	public function set_from($email, $name = '')
	{
		$this->from = $email;
	}
	
	/**
	 * Add an address to the list of people who recieve the results of the form
	 *
	 * @param string $email
	 * @param string $name
	 */
	public function add_recipient($email, $name = '')
	{
		$this->recipient_list[] = array('email' => $email, 'name' => $name);
	}
	
	/**
	 * Hardcode in a subject for the email
	 *
	 * @param string $subject
	 */
	public function add_subject($subject)
	{
		$this->subject = $subject;
	}
	
	/**
	 * Add a standard text input to the form
	 *
	 * @param string $name
	 * @param string $label
	 * @param mixed $value
	 */
	public function add_input($name, $label, $value = '')
	{
		$this->form_generator->add_text($name, $label, 0 , $value);
	}

	/**
	 * Add a standard text area to the form
	 *
	 * @param string $name
	 * @param string $label
	 * @param mixed $value
	 */
	public function add_textarea($name, $label, $value = '')
	{
		$this->form_generator->add_textarea($name, $label, 0, $value);
	}
	
	/**
	 * Add a standard text input to the form
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $value
	 * @param mixed $selected
	 */
	public function add_radio($name, $label, array $value, $selected = '')
	{
		$this->form_generator->add_radio($name, $label, 0, $value, $selected);
	}
	
	/**
	 * Add a standard hidden input to the form
	 *
	 * @param string $name
	 * @param string $label
	 * @param mixed $value
	 */
	public function add_hidden($name, $label, $value)
	{
		$this->form_generator->add_hidden($name, $label, 0, $value);
	}
	
	/**
	 * Add a set of checkboxes to the form
	 *
	 * @param string $name
	 * @param string $label
	 * @param mixed $value can take a single string or an array for multiple boxes
	 * @param unknown_type $selected
	 */
	public function add_checkbox($name,  $label, $value, $selected = '')
	{
		if(!is_array($value))
			$value = array($value);
		$this->form_generator->add_checkbox($name, $label, 0, $value, $selected);
	}
	
	/**
	 * Adds a drop down select menu to the form
	 *
	 * @param string $name
	 * @param string $label
	 * @param string $id
	 * @param array $value
	 * @param mixed $selected
	 */
	public function add_select($name, $label, $id = 0, array $value, $selected = '')
	{
		$id = (string) $id;
		$this->form_generator->add_select($name, $label, $id, $value, $selected);
	}


	/**
	 * Adds a select multiple menu to the form
	 *
	 * @param string $name
	 * @param string $label
	 * @param string $id
	 * @param array $value
	 * @param mixed $selected
	 */
	public function add_selectmultiple($name, $label, $id = 0, array $value, $selected = '')
	{
		$this->form_generator->add_selectmultiple($name, $label, $id, $value, $selected);
	}
	
	/**
	 * Sends the email
	 *
	 * @return boolean 
	 */
	protected function send_mail()
	{
		$input_array = $this->form_generator->make_description_array();
		
		if(array_key_exists('from', $input_array) && $this->from == '')
			$this->from = $input_array['from'];
		
		foreach($input_array as $input)
		{
			$value = '';
			
			
			if(is_array($_POST[$input['name']]))
			{
				
				foreach($_POST[$input['name']] as $data)
				{
					$value .= $data . ', ';
				}
				
			}else{
				
				$value = $_POST[$input['name']];
			}
			
			
			$message .= $input['label'] . ": " . $value . "\n";
		}
		
		
		$email = new Email($this->from, $this->subject, $this->message);

		foreach($this->recipient_list as $recipient)
		{
			$email->add_recipient($recipient['email'], $recipient['name']);
		}
		
		return $email->send_email();
		
	}


}


?>
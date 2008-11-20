<?php

class PayPalButton
{
	
	public $variables = array();
	public $image_link = 'http://www.paypal.com/en_US/i/btn/x-click-but01.gif';
	public $currency = 'USD';
	public $item_name;
	public $amount;
	public $business;
	public $test = false;
	
	public function __set($key, $value)
	{
		if(property_exists(__CLASS__, '$key'))
		{
			return $this->$key = $value;
		}else{
			return $this->variables[$key] = $value;
		}
	}
	
	public function __get($key)
	{
		return (property_exists(__CLASS__, '$key')) ? $this->$key : $this->variables[$key];
	}
	
	public function make_display()
	{
		
		if(!isset($this->amount) || !isset($this->image_link) || !isset($this->currency) || !isset($this->item_name) || !isset($this->business))
			return false;
		
		$url = ($this->test) ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
			
		$output = '<FORM ACTION="' . $url .'" METHOD="POST">
		<input type="hidden" name="cmd" value="_xclick">
		<input type="hidden" name="business" value="' . $this->business . '">
		<input type="hidden" name="currency_code" value="' . $this->currency . '">
		<input type="hidden" name="item_name" value="' . $this->item_name . '">
		<input type="hidden" name="amount" value="' . $this->amount . '">';
		
		foreach($this->variables as $name => $value)
		{
			$output .= '<INPUT TYPE="hidden" NAME="' . $name . '" VALUE="' . $value . '">';
		}
		

		$output .= '<INPUT TYPE="image" SRC="' . $this->image_link . '" BORDER="0" NAME="submit" ALT=Make payments with PayPal - it\'s fast, free and secure!>';
		$output .= '</FORM>';
		
		return $output;
		
	}
}





?>
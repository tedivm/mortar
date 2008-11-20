<?php


class Password
{
	public $hash;
	public $stored;
	public $salt_start;
	public $salt_end;
	protected $salt_length = 15;
	
	/*
	
	stored = salt::password::salt
	
	*/
	
}


class StoredPassword extends Password
{
	
	public function __construct($password)
	{
		$this->stored = $password;
		$this->get_salts();
	}

	public function is_match($password)
	{
		$test_password = new NewPassword($password, $this->salt_start, $this->salt_end);
		
		if($test_password->hash == $this->hash)
		{
			return true;
		}else{
			return false;
		}
		
	}
	
	protected function get_salts()
	{
		$split = explode('::', $this->stored);
		$this->salt_start = $split[0];
		$this->salt_end = $split[1];
		$this->hash = $split[2];
	}
	
	
}

class NewPassword extends Password
{
	
	public function __construct($password, $start = false, $end = false)
	{
		$this->salt_start = ($start) ? $start : substr(md5(time() * mt_rand()), 0, $this->salt_length);
		$this->salt_end =  ($end) ? $end : substr(md5(time() * mt_rand()), 0, $this->salt_length);
		
		
		$this->hash = hash('sha256', $this->salt_start . $password . $this->salt_end);
		$this->stored = $this->salt_start . '::' . $this->salt_end . '::' . $this->hash;
	}

}

?>
<?php


class Password
{
	public $hash;
	public $stored;
	public $salt_start;
	public $salt_end;

	// The stored result needs to fit in the database, which currently has a length of 192
	// ((salt_length * 2) + 4 + algorithm hash length) <= 192
	protected $salt_length = 30;
	protected $cryptoAlgorithm = 'whirlpool';
	protected $hashDepth = 10000;
	// whirlpool length is 128
	// whirlpool is really slow, which is good for a password hash as it makes generating rainbow tables more consuming

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
		$this->salt_start = ($start) ? $start : substr(md5(uniqid(rand(), true)), 0, $this->salt_length);
		$this->salt_end =  ($end) ? $end : substr(md5(uniqid(rand(), true)), 0, $this->salt_length);

		$hash = $this->salt_start . $password . $this->salt_end;

		if($this->hashDepth < 1)
			$this->hashDepth = 1;

		for($x = 0; $x < $this->hashDepth; $x++)
			$hash = hash($this->cryptoAlgorithm, $hash);

		$this->hash = $hash;

		$this->stored = $this->salt_start . '::' . $this->salt_end . '::' . $this->hash;
	}

}

?>
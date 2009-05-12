<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 */

/**
 * This class handles password hashing and matching
 *
 * @package MainClasses
 */
class Password
{
	/**
	 * This is the processed hash
	 *
	 * @access protected
	 * @var string
	 */
	protected $hash;

	/**
	 * This is the salt that was appended to the start of the password hashing
	 *
	 * @access protected
	 * @var string
	 */
	protected $saltStart;

	/**
	 * This is the salt that was appended to the end of the password before hashing
	 *
	 * @access protected
	 * @var unknown_type
	 */
	protected $saltEnd;

	/**
	 * This doesn't do much now, but if we change the password mechinism later this will be useful
	 *
	 * @access protected
	 * @var int
	 */
	protected $version = 1;

	// The stored result needs to fit in the database, which currently has a length of 192
	// ((saltLength * 2) + 4 + algorithm hash length) <= 192

	/**
	 * This is the length of each salt (so the total salt length ends up as twice this number
	 *
	 * @access protected
	 * @var int
	 */
	protected $saltLength = 30;

	/**
	 * This is the algorithm used to hash the password
	 *
	 * @access protected
	 * @var string
	 */
	protected $cryptoAlgorithm = 'whirlpool';

	/**
	 * This is how many times we hash the string. This makes the hashing slower, but doesn't add any additional security
	 *
	 * @access protected
	 * @var int
	 */
	protected $hashDepth = 10000;

	/**
	 * This array tells the storage functions which values to save in the storage string
	 *
	 * @var unknown_type
	 */
	protected $storeValues = array('version', 'cryptoAlgorithm', 'hashDepth', 'saltStart', 'saltEnd', 'hash');


	/**
	 * Builds the password object up from a stored password string
	 *
	 * @param string $storedHash
	 */
	public function fromStored($storedHash)
	{
		$split = explode('::', $storedHash);

		foreach($this->storeValues as $name)
		{
			$this->$name = array_shift($split);
		}
	}

	/**
	 * Returns a storable version of the password
	 *
	 * @return string
	 */
	public function getStored()
	{
		$output = '';
		foreach($this->storeValues as $name)
		{
			$output .= $this->$name;

			if($name != 'hash')
				$output .= '::';
		}

		return $output;
	}

	/**
	 * Builds the password object up from a cleartext string. The optional arguments are needed for password matching
	 * and shouldn't be used.
	 *
	 * @param string $passwordString
	 * @param null|string $start
	 * @param null|string $end
	 */
	public function fromString($passwordString, $start = null, $end = null)
	{
		$this->saltStart = isset($start) ? $start : substr(md5(uniqid(rand(), true)), 0, $this->saltLength);
		$this->saltEnd =  isset($end) ? $end : substr(md5(uniqid(rand(), true)), 0, $this->saltLength);

		$hash = $this->saltStart . $passwordString . $this->saltEnd;

		if($this->hashDepth < 1)
			$this->hashDepth = 1;

		for($x = 0; $x < $this->hashDepth; $x++)
			$hash = hash($this->cryptoAlgorithm, $hash);

		$this->hash = $hash;
	}

	/**
	 * Returns the hash value
	 *
	 * @return string
	 */
	public function getHash()
	{
		return $this->hash;
	}

	/**
	 * Takes a string and checks to see if it is a match for the password
	 *
	 * @param string $passwordString
	 * @return bool
	 */
	public function isMatch($passwordString)
	{
		$password = new Password();
		$password->fromString($passwordString, $this->saltStart, $this->saltEnd);
		return ($this->hash == $password->getHash());
	}

}

?>
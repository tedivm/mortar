<?php

/**
 * This class is used to generate 'random', unique strings based off of a passed key. This is similar to using a one way
 * hash function only with more control over which charactors are used.
 *
 */
class UniqueString
{
	/**
	 * This string contains all of the charactors allowed from the string. Its default is all numbers and charactors
	 * except those which are too similar looking on screen, such as 'O', '0', 'i', 'l'
	 *
	 * @var String
	 */
	protected $allowedCharactors = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

	public function createString($seed, $length)
	{
		if(!is_numeric($length))
			throw new UniqueStringException('Length must be an integer.');

		// anything larger than a 32bit hash will choke on systems with 32bit processors. adler32 does not distribute
		// randomally enough, while crc32 does.
		//
		$hash = hash('crc32', $seed);
		$hashAsInt = hexdec($hash);

		// turn the allowed charactors string into an array (minor function, since php stores string as arrays)
		$allowedCharArray = str_split($this->allowedCharactors);

		// Get the number of charactors and use the modulus operator against the hash integer to get our charactor index
		$numCharactors = count($allowedCharArray);
		$charNumber = $hashAsInt % $numCharactors;

		// construct the string by starting with the selected charactor and recursively building the rest of the string
		$output = $allowedCharArray[$charNumber];
		$length--;
		if($length > 0)
			$output .= $this->createString($hash . $seed, $length);

		return $output;
	}

	/**
	 * This function is used to set which charactors will make up the string.
	 *
	 * @param string $chars
	 */
	public function setAllowedCharactors($chars)
	{
		$this->allowedCharactors = $chars;
	}

}

class UniqueStringException extends CoreError {}

?>
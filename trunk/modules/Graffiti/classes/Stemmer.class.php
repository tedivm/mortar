<?php
/**
 * GraffitiStemmer
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @package Mortar
 * @subpackage Classes
 */

/**
 * This class takes in a word and returns a stem for that word.
 *
 * This code was based off of the English Porter2 stemming algorithm.
 *
 * @link http://snowball.tartarus.org/algorithms/english/stemmer.html
 * @package Graffiti
 * @subpackage Classes
 */
class GraffitiStemmer
{
	static $vowels = 'aeiouy';
	static $shortWordVowels = 'aeiouywxY';
	static $double = array('bb', 'dd', 'ff', 'gg', 'mm', 'nn', 'pp', 'rr', 'tt');
	static $validLi = 'cdeghkmnrt';

	static $invariantForms = array( 'sky', 'news', 'howe', 'atlas', 'cosmos', 'bias', 'andes');

	static $exceptions = array('skis' => 'ski',
								'skies' => 'sky',
								'dying' => 'die',
								'lying' => 'lie',
								'tying' => 'tie',
								'idly' => 'idl',
								'gently' => 'gentl',
								'ugly' => 'ugli',
								'early' => 'earli',
								'only' => 'onli',
								'singly' => 'singl');

	static $secondLevel = array('inning', 'outing', 'canning', 'herring','earring', 'proceed', 'exceed', 'succeed');

	static $segmentExceptions = array('gener', 'commun', 'arsen');

	static public function stem($word)
	{
		if(strlen($word) <= 2)
			return $word;

		$word = strtolower($word);

		if($value = self::firstException($word))
			return $value;

		$word = self::markVowels($word);
		$word = self::step0($word);
		$word = self::step1a($word);

		if($value = self::secondException($word))
		{
			$word = $value;
		}else{
			if(strlen($word) <= 2)
				return $word;

			$word = self::step1b($word);
			$word = self::step1c($word);
			$word = self::step2($word);
			$word = self::step3($word);
			$word = self::step4($word);
			$word = self::step5($word);
		}
		$word = str_replace('Y', 'y', $word);
		return $word;
	}

	static protected function firstException($word)
	{
		if(in_array($word, self::$invariantForms))
			return $word;

		if(isset(self::$exceptions[$word]))
			return self::$exceptions[$word];

		return false;
	}

	static protected function secondException($word)
	{
		if(in_array($word, self::$secondLevel))
			return $word;

		return false;
	}

	static protected function step0($word)
	{
		if(substr($word, 0, 1) == '\'')
			$word = substr($word, 1);

		if(substr($word, -3) == '\'s\'')
		{
			$word = substr($word, 0, strlen($word) - 3);
		}elseif(substr($word, -2) == '\'s'){
			$word = substr($word, 0, strlen($word) - 2);
		}elseif(substr($word, -1) == '\''){
			$word = substr($word, 0, strlen($word) - 1);
		}

		return $word;
	}

	static protected function step1a($word)
	{
		$suffix = substr($word, -4);
		if($suffix == 'sses')
		{
			$word = substr($word, 0, strlen($word) - 2);
			return $word;
		}

		$suffix = substr($word, -3);
		if($suffix == 'ied' || $suffix == 'ies')
		{
			$word = substr($word, 0, strlen($word) - 2);
			if(strlen($word) <= 2)
				$word .= 'e';

			return $word;
		}

		$suffix = substr($word, -2);
		if($suffix == 'us' || $suffix == 'ss')
			return $word;

		if(substr($word, -1) == 's' && self::containsVowel(substr($word, 0, strlen($word) - 2)))
			$word = substr($word, 0, strlen($word) - 1);

		return $word;
	}

	static protected function step1b($word)
	{
		$array = array();
		$array['ingly'] = '2';
		$array['eedly'] = '1';
		$array['edly'] = '2';
		$array['eed'] = '1';
		$array['ing'] = '2';
		$array['ed'] = '2';

		foreach($array as $string => $method)
		{
			$checkStringSize = strlen($string);
			if(substr($word, -$checkStringSize) == $string)
			{
				if($method == 1)
				{
					$segments = self::getSegments($word);
					$r1 = (isset($segments['r1'])) ? $segments['r1'] : '';
					if(strpos($r1, $string) !== false)
					{
						$newWord = substr($word, 0, -$checkStringSize);
						$newWord .= 'ee';
						$word = $newWord;
					}

				}elseif($method == 2){
					$newWord = substr($word, 0, -$checkStringSize);
					if(self::containsVowel($newWord))
					{
						$end = substr($newWord, -2);

						if($end == 'at' || $end == 'bl' || $end == 'iz')
						{
							$newWord .= 'e';
							return $newWord;
						}

						if(in_array($end, self::$double))
						{
							$newWord = substr($newWord, 0, strlen($newWord) - 1);
							return $newWord;
						}

						$segments = self::getSegments($newWord);
						$r1 = (isset($segments['r1'])) ? $segments['r1'] : '';

						if(self::isShort($newWord) && $r1 == ''){
							$newWord .= 'e';
							return $newWord;
						}

						return $newWord;
					}
					return $word;
				}

				return $word;
			} // if(substr($word, -$checkStringSize) == $string)
		}
		return $word;
	}

	static protected function step1c($word)
	{
		$strlen = strlen($word);

		if($strlen < 3)
			return $word;

		$suffix = substr($word, -1);

		if($suffix == 'y' || $suffix == 'Y' && !self::containsVowel(substr($word, 1, strlen($word) - 1)))
			$word = substr($word, 0, $strlen - 1) . 'i';

		return $word;
	}

	static protected function step2($word)
	{
		$step2replacements = array(
			'ization' => 'ize',
			'ousness' => 'ous',
			'iveness' => 'ive',
			'ational' => 'ate',
			'fulness' => 'ful',
			'tional' => 'tion',
			'lessli' => 'less',
			'biliti' => 'ble',
			'entli' => 'ent',
			'ation' => 'ate',
			'alism' => 'al',
			'aliti' => 'al',
			'ousli' => 'ous',
			'iviti' => 'ive',
			'fulli' => 'ful',
			'enci' => 'ence',
			'anci' => 'ance',
			'abli' => 'able',
			'izer' => 'ize',
			'ator' => 'ate',
			'alli' => 'al',
			'bli' => 'ble');

		$segments = self::getSegments($word);
		$r1 = (isset($segments['r1'])) ? $segments['r1'] : '';

		foreach($step2replacements as $string => $replacement)
		{
			$suffixSize = strlen($string);
			if(substr($word, -$suffixSize) == $string && strpos($r1, $string) !== false)
			{
				$word = substr($word, 0, strlen($word) - $suffixSize);
				$word .= $replacement;
				return $word;
			}
		}

		if(substr($word, -3) == 'ogi' && strpos($r1, 'ogi') !== false)
		{
			if(substr($word, -4, 1) == 'l')
			{
				$word = substr($word, 0, strlen($word) - 3);
				$word .= 'og';
			}
			return $word;
		}

		if(substr($word, -2) == 'li' && strpos($r1, 'li') !== false)
		{
			$char = substr($word, -3, 1);

			if(strpos(self::$validLi, $char) !== false)
				return substr($word, 0, strlen($word) - 2);
		}

		return $word;
	}

	static protected function step3($word)
	{

		$step3tests = array(
				'ational' => 'ate',
				'tional' => 'tion',
				'ative' => true,
				'alize' => 'al',
				'icate' => 'ic',
				'iciti' => 'ic',
				'ical' => 'ic',
				'ness' => false,
				'ful' => false
				);

		foreach($step3tests as $string => $rule)
		{
			$stringLen = strlen($string);
			if(substr($word, -$stringLen) == $string)
			{
				$segments = self::getSegments($word);
				$r1 = (isset($segments['r1'])) ? $segments['r1'] : '';
				$r2 = (isset($segments['r2'])) ? $segments['r2'] : '';

				if(strpos($r1, $string) === false)
					return $word;


				if(is_string($rule))
				{
					$newWord = substr($word, 0, strlen($word) - $stringLen);
					$newWord .= $rule;
					return $newWord;
				}elseif($string == 'ative'){
					if(strpos($r2, 'ative') !== false)
					{
						$newWord = substr($word, 0, strlen($word) - $stringLen);
						return $newWord;
					}
					return $word;

				}elseif($rule === false){
					$newWord = substr($word, 0, strlen($word) - $stringLen);
					return $newWord;
				}
				return $word;
			}
		}

		return $word;
	}

	static protected function step4($word)
	{
		$segments = self::getSegments($word);
		$r1 = (isset($segments['r1'])) ? $segments['r1'] : '';
		$r2 = (isset($segments['r2'])) ? $segments['r2'] : '';

		$step4Tests = array(
					'ement',
					'ance',
					'ence',
					'able',
					'ible',
					'ment',
					'ant',
					'ent',
					'ism',
					'ate',
					'ion',
					'iti',
					'ous',
					'ive',
					'ize',
					'er',
					'ic',
					'al');

		foreach($step4Tests as $test)
		{
			$testLen = strlen($test);
			if(substr($word, -$testLen) == $test)
			{
				if(strpos($r2, $test) !== false)
				{
					if($test == 'ion')
					{
						$char = substr($word, -4, 1);
						if($char == 's' || $char == 't')
						{
							$newWord = substr($word, 0, strlen($word) - $testLen);
							return $newWord;
						}
						return $word;

					}else{
						$newWord = substr($word, 0, strlen($word) - $testLen);
						return $newWord;
					}
				}
				return $word;
			}
		}
		return $word;
	}

	static protected function step5($word)
	{
		$lastChar = substr($word, -1);
		if($lastChar == 'e')
		{
			$segments = self::getSegments($word);
			$r1 = (isset($segments['r1'])) ? $segments['r1'] : '';
			$r2 = (isset($segments['r2'])) ? $segments['r2'] : '';

			if(strpos($r2, 'e') !== false)
			{
				return substr($word, 0, strlen($word) - 1);
			}else{

				if(strpos($r1, 'e') !== false)
				{
					$subString = substr($word, 0, strlen($word) - 1);

					if(!self::isShort($subString))
						return $subString;
				}
				return $word;
			}

			return $word;

		}elseif($lastChar == 'l'){

			$segments = self::getSegments($word);
			$r2 = (isset($segments['r2'])) ? $segments['r2'] : '';

			if(strpos($r2, 'l') !== false && substr($word, -2, 1) == 'l')
				return substr($word, 0, strlen($word) - 1);
		}
		return $word;
	}

	static protected function markVowels($word)
	{
		$chars = str_split($word);

		if($chars[0] == 'y')
			$chars[0] = 'Y';

		$yChars = array_keys($chars, 'y');

		foreach($yChars as $index)
			if(self::containsVowel($chars[$index - 1]))
				$chars[$index] = 'Y';

		$word = implode('', $chars);
		return $word;
	}

	static protected function getSegments($word)
	{
		$output = array();

		foreach(self::$segmentExceptions as $exception)
		{
			$exceptionLength = strlen($exception);
			if(substr($word, 0, $exceptionLength) == $exception)
			{
				if($word === $exception)
					return array();

				$word = substr($word, strlen($exception));
				$output['r1'] = $word;
				break;
			}
		}

		$chars = str_split($word);
		$vowel = false;
		$const = false;
		foreach($chars as $index => $char)
		{
			if($vowel && $const)
			{
				$vowel = false;
				$const = false;
				if(!isset($output['r1']))
				{
					$output['r1'] = substr($word, $index);
					$vowel = false;
				}else{
					$output['r2'] = substr($word, $index);
					break;
				}
			}

			if(self::containsVowel($char))
			{
				$vowel = true;
			}elseif($vowel){
				$const = true;
			}
		}
		return $output;
	}

	static protected function isShort($word)
	{
		$sortString = str_split($word);
		$sortString = array_reverse($sortString);



		// Remember we're testing in reverse! $sortArray[0] is the last charactor.

		if( !self::containsVowel($sortString[0], true)
			&& self::containsVowel($sortString[1]) && !self::containsVowel($sortString[2]))
		{
			return true;
		}elseif(!self::containsVowel($sortString[0]) && self::containsVowel($sortString[1]) && !isset($sortString[2])){
			return true;
		}

		return false;
	}

	static protected function containsVowel($letter, $wxy = false)
	{
		$vowels = ($wxy) ? self::$shortWordVowels : self::$vowels;
//		var_dump($vowels);
		$searchString = "#[$vowels]#";
		return (preg_match($searchString, $letter) != 0);
	}
}

?>
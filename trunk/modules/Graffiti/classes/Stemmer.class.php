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

	static $segmentCache = array();


	static protected $step1Brules = array(
			'ingly' => array('count' => 5, 'rule' => '2'),
			'eedly' => array('count' => 5, 'rule' => '1'),
			'edly' => array('count' => 5, 'rule' => '2'),
			'eed' => array('count' => 5, 'rule' =>  '1'),
			'ing' => array('count' => 5, 'rule' =>  '2'),
			'ed' => array('count' => 5, 'rule' =>  '2'));

	static protected $step2rules = array(
			'ization' => array('count' => 7, 'rule' => 'ize'),
			'ousness' => array('count' => 7, 'rule' => 'ous'),
			'iveness' => array('count' => 7, 'rule' => 'ive'),
			'ational' => array('count' => 7, 'rule' => 'ate'),
			'fulness' => array('count' => 7, 'rule' => 'ful'),
			'tional' => array('count' => 6, 'rule' => 'tion'),
			'lessli' => array('count' => 6, 'rule' => 'less'),
			'biliti' => array('count' => 6, 'rule' => 'ble'),
			'entli' => array('count' => 5, 'rule' => 'ent'),
			'ation' => array('count' => 5, 'rule' => 'ate'),
			'alism' => array('count' => 5, 'rule' => 'al'),
			'aliti' => array('count' => 5, 'rule' => 'al'),
			'ousli' => array('count' => 5, 'rule' => 'ous'),
			'iviti' => array('count' => 5, 'rule' => 'ive'),
			'fulli' => array('count' => 5, 'rule' => 'ful'),
			'enci' => array('count' => 4, 'rule' => 'ence'),
			'anci' => array('count' => 4, 'rule' => 'ance'),
			'abli' => array('count' => 4, 'rule' => 'able'),
			'izer' => array('count' => 4, 'rule' => 'ize'),
			'ator' => array('count' => 4, 'rule' => 'ate'),
			'alli' => array('count' => 4, 'rule' => 'al'),
			'bli' => array('count' => 3, 'rule' => 'ble')
		);

	static protected $step3rules = array(
			'ational' => array('count'=> 7, 'rule' => 'ate'),
			'tional' => array('count'=> 6, 'rule' => 'tion'),
			'ative' => array('count'=> 5, 'rule' => true),
			'alize' => array('count'=> 5, 'rule' => 'al'),
			'icate' => array('count'=> 5, 'rule' => 'ic'),
			'iciti' => array('count'=> 5, 'rule' => 'ic'),
			'ical' => array('count'=> 4, 'rule' => 'ic'),
			'ness' => array('count'=> 4, 'rule' => false),
			'ful' => array('count'=> 3, 'rule' => false)
		);

	static public function stem($word)
	{
		if(strlen($word) <= 2)
			return $word;

		$word = strtolower($word);

		if($value = self::firstException($word))
			return $value;

		self::$segmentCache = array();
		$word = self::markVowels($word);
		$word = self::step0($word);
		$word = self::step1a($word);

		if($value = self::secondException($word))
		{
			$word = $value;
		}else{
			if(strlen($word) <= 2)
			{
				self::$segmentCache = array();
				return $word;
			}

			$word = self::step1b($word);
			$word = self::step1c($word);

			$word = self::step2($word);

			$word = self::step3($word);

			$word = self::step4($word);

			$word = self::step5($word);
		}
		$word = str_replace('Y', 'y', $word);
		self::$segmentCache = array();
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
		if(strpos($word, '\'') === 0)
			$word = substr($word, 1);

		$wordLen = strlen($word);
		$endLength = ($wordLen < 3) ? $wordLen : 3;
		$lastChar = substr($word, -$endLength);

		if(strpos($lastChar, '\'') === false)
			return $word;

		if($lastThree == '\'s\'')
		{
			$word = substr($word, 0, strlen($word) - 3);
		}elseif($endLength >= 2 && substr($word, -2) == '\'s'){
			$word = substr($word, 0, strlen($word) - 2);
		}elseif($endLength >= 1 && substr($word, -1) == '\''){
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

		if(substr($word, -1) == 's')
		{
			$front = substr($word, 0, strlen($word) - 2);
			if(preg_match('#[aeiouy]#', $front) !== 0)
				$word = substr($word, 0, strlen($word) - 1);
		}
		return $word;
	}

	static protected function step1b($word)
	{
		$pieces = array();
		foreach(self::$step1Brules as $string => $info)
		{
			$method = $info['rule'];
			$checkStringSize = $info['count'];
			$checkStringSize = strlen($string);

			if(!isset($pieces[$checkStringSize]))
				$pieces[$checkStringSize] = substr($word, -$checkStringSize);

			if($pieces[$checkStringSize] == $string)
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
					if(preg_match('#[aeiouy]#', $newWord) != 0)
					{
						$end = substr($newWord, -2);

						if($end == 'at' || $end == 'bl' || $end == 'iz')
						{
							$newWord .= 'e';
							return $newWord;
						}

						$double = array('bb', 'dd', 'ff', 'gg', 'mm', 'nn', 'pp', 'rr', 'tt');
						if(in_array($end, $double))
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

		if($suffix == 'y' || $suffix == 'Y' && !(preg_match('#[aeiouy]#', substr($word, 1, strlen($word) - 1)) != 0))
			$word = substr($word, 0, $strlen - 1) . 'i';

		return $word;
	}

	static protected function step2($word)
	{
		$suffixPiece = array();
		$pieces = array();
		$wordLen = strlen($word);

		foreach(self::$step2rules as $string => $info)
		{
			$replacement = $info['rule'];
			$suffixSize = $info['count'];

			if(!isset($pieces[$suffixSize]))
				$pieces[$suffixSize] = substr($word, -$suffixSize);

			if($suffixSize > $wordLen)
				continue;

			if($pieces[$suffixSize] == $string)
			{
				$segments = self::getSegments($word);
				$r1 = (isset($segments['r1'])) ? $segments['r1'] : '';

				if(strpos($r1, $string) === false)
					break;

				$word = substr($word, 0, strlen($word) - $suffixSize);
				$word .= $replacement;
				return $word;
			}
		}

		if(!isset($pieces[3]))
			$pieces[3] = substr($word, -3);

		if($pieces[3] == 'ogi')
		{
			$segments = self::getSegments($word);
			$r1 = (isset($segments['r1'])) ? $segments['r1'] : '';

			if(strpos($r1, 'ogi') === false)
				return $word;

			if(substr($word, -4, 1) == 'l')
			{
				$word = substr($word, 0, strlen($word) - 3);
				$word .= 'og';
			}
			return $word;
		}

		if(!isset($pieces[2]))
			$pieces[2] = substr($word, -2);

		if($pieces[2] == 'li')
		{

			$segments = self::getSegments($word);
			$r1 = (isset($segments['r1'])) ? $segments['r1'] : '';

			if(strpos($r1, 'li') === false)
				return $word;

			$char = substr($word, -3, 1);

			if(strpos(self::$validLi, $char) !== false)
				return substr($word, 0, strlen($word) - 2);
		}

		return $word;
	}

	static protected function step3($word)
	{
		$pieces = array();
		$wordLen = strlen($word);

		foreach(self::$step3rules as $string => $info)
		{
			$rule = $info['rule'];
			$stringLen = $info['count'];

			if($stringLen > $wordLen)
				continue;

			if(!isset($pieces[$stringLen]))
				$pieces[$stringLen] = substr($word, -$stringLen);

			if($pieces[$stringLen] == $string)
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
		$step4Tests = array(
					'ement' => 5,
					'ance' => 4,
					'ence' => 4,
					'able' => 4,
					'ible' => 4,
					'ment' => 4,
					'ant' => 3,
					'ent' => 3,
					'ism' => 3,
					'ate' => 3,
					'ion' => 3,
					'iti' => 3,
					'ous' => 3,
					'ive' => 3,
					'ize' => 3,
					'er' => 2,
					'ic' => 2,
					'al' => 2);

		$pieces = array();

		foreach($step4Tests as $test => $suffixSize)
		{
			if(!isset($pieces[$suffixSize]))
				$pieces[$suffixSize] = substr($word, -$suffixSize);

			if($pieces[$suffixSize] == $test)
			{
				$segments = self::getSegments($word);
				$r1 = (isset($segments['r1'])) ? $segments['r1'] : '';
				$r2 = (isset($segments['r2'])) ? $segments['r2'] : '';

				if($r2 != '' && strpos($r2, $test) !== false)
				{
					if($test == 'ion')
					{
						$char = substr($word, -4, 1);
						if($char == 's' || $char == 't')
						{
							$newWord = substr($word, 0, strlen($word) - $suffixSize);
							return $newWord;
						}
						return $word;

					}else{
						$newWord = substr($word, 0, strlen($word) - $suffixSize);
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
			if(preg_match('#[aeiouy]#', $chars[$index - 1]) != 0)
				$chars[$index] = 'Y';

		$word = implode('', $chars);
		return $word;
	}

	static protected function getSegments($word)
	{
		if(isset(self::$segmentCache[$word]))
			return self::$segmentCache[$word];

		$realWord = $word;
		$output = array();

		foreach(self::$segmentExceptions as $exception)
		{
			$exceptionLength = strlen($exception);
			if(substr($word, 0, $exceptionLength) == $exception)
			{
				if($word === $exception)
				{
					self::$segmentCache[$word] = array();
					return array();
				}

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

			if((preg_match('#[aeiouy]#', $char) != 0))
			{
				$vowel = true;
			}elseif($vowel){
				$const = true;
			}
		}

		self::$segmentCache[$realWord] = $output;
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
		$searchString = "#[$vowels]#";
		return (preg_match($searchString, $letter) != 0);
	}
}

?>
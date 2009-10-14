<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Display
 */

/**
 * DisplayMaker
 *
 * The main template class. This class processes templates with using a special tagging system
 *
 * @package System
 * @subpackage Display
 */
class DisplayMaker
{
	/**
	 * This string contains the template content
	 *
	 * @access private
	 * @var string
	 */
	private $mainString = '';

	/**
	 * This array contains all of the tags found in the template as well as their arguments
	 *
	 * @access protected
	 * @var array
	 */
	protected $tags = array();

	/**
	 * This array is used to map datetime formats in templates to their php constants
	 *
	 * @access protected
	 * @var array
	 */
	protected $dateConstants = array('date_atom' => DATE_ATOM,
'cookie' 	=> DATE_COOKIE,
'iso8601' 	=> DATE_ISO8601,
'rfc822' 	=> DATE_RFC822,
'rfc850' 	=> DATE_RFC850,
'rfc1036' 	=> DATE_RFC1036,
'rfc1123' 	=> DATE_RFC1123,
'rfc2822' 	=> DATE_RFC2822,
'rfc3339' 	=> DATE_RFC3339,
'rss' 		=> DATE_RSS,
'w3c' 		=> DATE_W3C  );

	/**
	 * This array contains the replacements for the template tags
	 *
	 * @access protected
	 * @var array
	 */
	protected $replacementContent = array();

	/**
	 * This array contains timestamps to be added to the template
	 *
	 * @access protected
	 * @var array
	 */
	protected $replacementDates = array();

	/**
	 * Returns either a simple array containing the names of all tags used, or a more details array
	 * containing the tags arguments
	 *
	 * @param bool $withAttributes
	 * @return array
	 */
	public function getTags($withAttributes = false)
	{
		if(!is_array($this->tags))
			return false;

		if(!$withAttributes)
		{
			$tagNames = array();
			foreach($this->tags as $tag)
				$tagNames[] = $tag['name'];

			return array_unique($tagNames);
		}

		return $this->tags;
	}

	/**
	 * Depreciated in favor of $this->getTags();
	 *
	 * @deprecated
	 * @param bool $withAttributes
	 * @return array
	 */
	public function tagsUsed($withAttributes = false)
	{
		return $this->getTags($withAttributes);
	}


	/**
	 * This function takes in a string to be used as the basis of the template. If this argument is passed a string and
	 * concise_html is disabled the template will be wrapped in tags for designers to identify it with
	 *
	 * @param string $text
	 * @param string|null $name
	 */
	public function setDisplayTemplate($text, $name = null)
	{
		$textString = (string) $text;
		if(!is_string($textString))
			throw new TypeMismatch(array('string', $text));

		$this->processDisplayTemplate($textString);

		if(isset($name) && (!(defined('CONCISE_HTML') && CONCISE_HTML === true)))
		{
			$this->mainString = '<!-- @begin template: ' . $name . '-->' . $this->mainString . PHP_EOL;
			$this->mainString .= PHP_EOL . '<!-- @end template: ' . $name . '-->';
		}
	}

	/**
	 * This function takes in a string, processes it for tags (or retrieves the information from cache)
	 * and initializes this class
	 *
	 * @cache template scheme *md5(string)
	 * @param string $text
	 */
	public function processDisplayTemplate($text)
	{
		if(!is_string($text))
			throw new TypeMismatch(array('String', $text));

		$this->mainString = $text;
		$cache = new Cache('templates', 'schema', md5($this->mainString));
		$cache->storeMemory = false;
		$cache->cacheTime = 86400; // this can be ridiculously high because the keyname changes when the string does
		$tags = $cache->getData();

		if($cache->isStale())
		{
			preg_match_all('{\{# (.*?) #\}}', $this->mainString, $matches, PREG_SET_ORDER);

			// go through each found tag and process it
			foreach($matches as $unprocessed_tag)
			{
				$tagChars = str_split($unprocessed_tag[1]);

				$enclosed = false;
				$curName = 'name';
				$curValue = null;
				$curString = '';
				$args = array();


				/*
					Here we are going to go through each tag, charactor by charactor,in order to pull out arguements

					Arguement types:
						standalone
						key=value
						key="continious string"

					My first approach was to try to split the string into chunks, by spaces. This failed miserably
					when I added in the date format code, which needs to contain spaces, so now we go charactor by
					charactor to figure out arguments.

				*/


				// unfortunately the easiest way to do this seems to be charactor by charactor through each tag
				// yay caching!
				foreach($tagChars as $char)
				{
					switch($char)
					{
						case ' ':
							if($enclosed) // inside quotes, charactors are just part of the string
							{
								$curString .= $char;
								break;

							}elseif(isset($curName)){ // outside of quotes a space means its time for a new value

								// if there is a curString set it means the argument has its own values, otherwise
								// it just demarks a one word setting that is enabled, so we mark it as true.
								$curValue = isset($curString) ? $curString : true;

								$args[$curName] = $curValue;
								unset($curName);
								unset($curValue);
								$curString = '';
							}
							break;

						case '"':

							if($enclosed) // if its already enclosed, this marks the end of a string
							{
								if(!isset($curName))
								{
									// since no name is set, this string must be the name
									$curName = $curString;

								}else{
									// since a name is set but a value isn't, this must be that
									$curValue = $curString;
								}

								$curString = '';
								$enclosed = false;
							}else{
								// if not enclosed in quotes already, mark it as such
								$enclosed = true;
							}

							break;

						case '=':
							if(!$enclosed)
							{
								// if not wrapped in a string, this marks the switch between name and value
								$curName = $curString;
								$curString = '';
							}else{
								$curString .= $char;
							}
							break;

						default:
							$curString .= $char;
							break;
					}
				}

				if(!isset($curName))
				{
					$curName = $curString;
				}elseif(!isset($curValue) && strlen($curString) > 0){
					$curValue = $curString;
				}

				if(!isset($curValue))
					$curValue = true;

				$args[$curName] = $curValue;

				$tag_chunks = explode(' ', $unprocessed_tag[1]);
				$tag_name = array_shift($tag_chunks);

				$tagArray = array('original' => $unprocessed_tag[0], 'name' => $tag_name);

				if(isset($args['name']))
					unset($args['name']);

				foreach($args as $name => $value)
				{
					$tagArray[$name] = trim($value, ' \'"');
				}
				$tags[] = $tagArray;
			}
			$cache->storeData($tags);
		}
		$this->tags = $tags;
	}

	/**
	 * This will load a template from a modules package
	 *
	 * @access public
	 * @param string $template
	 * @param int|string $package A package identifier
	 * @return bool Whether the template was able to load or not
	 */
	public function loadTemplate($template, $package)
	{
		$config = Config::getInstance();
		$path_to_theme = $config['url']['theme'];

		if(is_numeric($package))
		{
			$modelInfo = new PackageInfo($package);
			$package = $modelInfo->getName();
		}

		if(isset($package))
			$path_to_theme .= 'package/' . $package . '/';

		$path_to_theme .= $template . '.template.php';

		if($this->setDisplayTemplateByFile($path_to_theme))
			return true;

		if(isset($package))
		{
			$path_to_package = $config['path']['modules'] . $package . '/templates/' . $template . '.template.php';
			if($this->setDisplayTemplateByFile($path_to_package))
				return true;
		}

		return false;

	}

	/**
	 * Attempts to load a template from the specified path
	 *
	 * @access public
	 * @param string $filepath
	 * @return bool True on success
	 */
	public function setDisplayTemplateByFile($filepath)
	{
		try{
			if(!file_exists($filepath))
			{
				return false;
			}

			$this->setDisplayTemplate(file_get_contents($filepath), $filepath);
			return true;

		}catch(Exception $e){

		}
	}

	/**
	 * Adds content to be added to the template
	 *
	 * @access public
	 * @param string $tag
	 * @param string $content
	 */
	public function addContent($tag, $content)
	{
		$this->replacementContent[$tag] = $content;
	}

	/**
	 * This adds a timestamp (or string that can be converted to one) to the template, which is then formatted
	 * according to the tag argument in the template. This class takes in a UTC/GMT time and converts it to the system
	 * time, as all saved data in this project is saved in GMT.
	 *
	 * @access public
	 * @param string $name
	 * @param string|int $timestamp
	 */
	public function addDate($name, $timestamp)
	{
		if(is_numeric($timestamp))
			$timestamp = gmdate("Y-m-d H:i:s", $timestamp);

		$this->replacementDates[$name] = $timestamp;
	}

	/**
	 * This method takes the content added and places it into the main string, replacing the tag
	 *
	 * @access public
	 * @param bool $cleanup If set to true, all left over tags (those without content) are removed
	 * @return string the final processed template
	 */
	public function makeDisplay($cleanup = false)
	{
		$processTags = array();
		$processContent = array();

		if(!is_array($this->tags))
			return $this->mainString;

		$preferedTimeZone = new DateTimeZone(date_default_timezone_get());
		$utcTimeZone = new DateTimeZone('UTC');


		foreach($this->tags as $tagArray)
		{
			if(isset($this->replacementContent[$tagArray['name']]))
			{
				$processTags[] = $tagArray['original'];
				$processContent[] = $this->replacementContent[$tagArray['name']];

			}elseif(isset($this->replacementDates[$tagArray['name']])){

				if(isset($tagArray['format']))
				{
					$templateFormat = $tagArray['format'];

					$format = (isset($this->dateConstants[$templateFormat]))
								? $this->dateConstants[$templateFormat]
								: $templateFormat;

				}else{
					$format = DATE_RFC850;
				}

				$dateTime = new DateTime($this->replacementDates[$tagArray['name']], $utcTimeZone);
				$dateTime->setTimezone($preferedTimeZone);

				$processTags[] = $tagArray['original'];
				$processContent[] = $dateTime->format($format);;

			}elseif($cleanup){
				$processTags[] = $tagArray['original'];
				$processContent[] = '';
			}
		}

		return str_replace($processTags, $processContent, $this->mainString);
	}

}

?>
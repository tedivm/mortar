<?php
/**
 * BentoBase
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
'cookie' => DATE_COOKIE,
'iso8601' => DATE_ISO8601,
'rfc822' => DATE_RFC822,
'rfc850' => DATE_RFC850,
'rfc1036' => DATE_RFC1036,
'rfc1123' => DATE_RFC1123,
'rfc2822' => DATE_RFC2822,
'rfc3339' => DATE_RFC3339,
'rss' => DATE_RSS,
'w3c' => DATE_W3C  );

	/**
	 * This array contains the replacements for the template tags
	 *
	 * @access protected
	 * @var array
	 */
	protected $replacementContent = array();

	/**
	 * Returns either a simple array containing the names of all tags used, or a more details array
	 * containing the tags arguments
	 *
	 * @access public
	 * @param bool $withAttributes
	 * @return array
	 */
	public function tagsUsed($withAttributes = false)
	{
		if(!is_array($this->tags))
			return false;

		$tags = ($withAttributes) ? $this->tags : array_keys($this->tags);
		return $tags;
	}

	/**
	 * This function takes in a string, processes it for tags (or retrieves the information from cache)
	 * and initializes this class
	 *
	 * @access public
	 * @param string $text
	 */
	public function setDisplayTemplate($text)
	{
		if(!is_string($text))
			throw new TypeMismatch(array('String', $text));

		$this->mainString = $text;
		$cache = new Cache('templates', 'schema', md5($this->mainString));
		$cache->storeMemory = false;
		$cache->cache_time = '86400'; // this can be ridiculously high because the keyname changes when the string does
		$tags = $cache->getData();

		if(!$cache->cacheReturned)
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

								if(!isset($curValue))
									$curValue = true;

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

				$tags[$tag_name] = array('original' => $unprocessed_tag[0]);

				foreach($args as $name => $value)
				{
					$tags[$tag_name][$name] = trim($value, ' \'"');
				}
			}

			$cache->store_data($tags);
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

			$this->setDisplayTemplate(file_get_contents($filepath));
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
		// This will keep us from looking for tags that aren't there
		if(is_array($this->tags) && key_exists($tag, $this->tags))
			$this->replacementContent[$tag] = $content;
	}

	/**
	 * This adds a timestamp (or string that can be converted to one) to the template, which is then formatted
	 * according to the tag argument in the template
	 *
	 * @access public
	 * @param string $name
	 * @param string|int $timestamp
	 */
	public function addDate($name, $timestamp)
	{
		if(!key_exists($name, $this->tags))
			return;

		if(!is_numeric($timestamp))
			$timestamp = strtotime($timestamp);

		if(isset($this->tags[$name]['format']))
		{
			$templateFormat = $this->tags[$name]['format'];

			$format = (isset($this->dateConstants[$templateFormat]))
						? $this->dateConstants[$templateFormat]
						: $templateFormat;

		}else{
			$format = DATE_RFC850;
		}

		$this->addContent($name, date($format, $timestamp));
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

		if(is_array($this->tags))
			foreach($this->tags as $tagName => $tagArray)
		{
			if(isset($this->replacementContent[$tagName]))
			{
				$processTags[] = $tagArray['original'];
				$processContent[] = $this->replacementContent[$tagName];

			}elseif($cleanup){
				$processTags[] = $tagArray['original'];
				$processContent[] = '';
			}
		}

		return str_replace($processTags, $processContent, $this->mainString);
	}

}

?>
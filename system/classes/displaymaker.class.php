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
 * DisplayMaker
 *
 * The main template class
 *
 * @package		Bento Base
 * @subpackage	Main_Classes
 * @category	Display
 * @author		Robert Hafner
 */
class DisplayMaker
{
	private $main_string = '';
	public $cleanup = false;

	protected $tags = array();

	// strings
	protected $replacement_array = array();


	public function tagsUsed($withAttributes = false)
	{
		$tags = ($withAttributes) ? $this->tags : array_keys($this->tags);
		return $tags;
	}

	// Any template setting functions should ultimately pass the text to this function
	// This way all tag processing will get called whenever the text is changed
	public function setDisplayTemplate($text)
	{
		if(!is_string($text))
			throw new TypeMismatch(array('String', $text));


		$this->main_string = $text;
		$cache = new Cache('templates', 'schema', md5($this->main_string));
		$cache->cache_time = '86400';

		if(!($tags = $cache->get_data()))
		{
			preg_match_all('{\{# (.*?) #\}}', $this->main_string, $matches, PREG_SET_ORDER);

			foreach($matches as $unprocessed_tag)
			{
				$tag_chunks = explode(' ', $unprocessed_tag[1]);
				$tag_name = array_shift($tag_chunks);
				$tags[$tag_name] = array('original' => $unprocessed_tag[0]);

				foreach($tag_chunks as $argument_string)
				{
					$arg_temp = explode('=', $argument_string);
					$tags[$tag_name][$arg_temp[0]] = trim($arg_temp[1], ' \'"');
				}
			}
			$cache->store_data($tags);
		}

		$this->tags = $tags;
	}

	public function set_display_template($text)
	{
		return $this->setDisplayTemplate($text);
	}


	public function loadTemplate($template, $package = '')
	{
		$config = Config::getInstance();
		$path_to_theme = $config['url']['theme'];
		if($package != '')
			$path_to_theme .= 'package/' . $package . '/';
		$path_to_theme .= $template . '.template.php';

		if($this->set_display_template_byfile($path_to_theme))
			return true;

		if($package != '')
		{
			$path_to_package .= $config['path']['modules'] . $package . '/templates/' . $template . '.template.php';
			if($this->set_display_template_byfile($path_to_package))
				return true;
		}

		return false;

	}

	public function load_template($template, $package = '')
	{
		$this->loadTemplate($template, $package);
	}



	public function setDisplayTemplateByFile($filepath)
	{
		try{
			if(!file_exists($filepath))
			{
				//throw new BentoError('Unable to load template file ' . $filepath, 0);
				return false;
			}

			$this->set_display_template(file_get_contents($filepath));
			return true;

		}catch(Exception $e){

		}
	}

	public function set_display_template_byfile($filepath)
	{
		return $this->setDisplayTemplateByFile($filepath);
	}



	// Depreciated in favor of ::addContent
	public function add_replacement($tag, $replacement)
	{
		$this->addContent($tag, $content);
	}


	public function addContent($tag, $content)
	{
		if(key_exists($tag, $this->tags)) // This will keep us from looking for tags that aren't there
			$this->replacement_array[$tag] = $content;
	}

	public function add_content($tag, $content)
	{
		$this->addContent($tag, $content);
	}


	public function addDate($name, $timestamp)
	{
		if(!key_exists($name, $this->tags))
			return;

		$format = (isset($this->tags[$name]['format'])) ? $this->tags[$name]['format'] : DATE_RFC850;
		return $this->add_content($name, date($format, $timestamp));
	}


	public function add_date($name, $timestamp)
	{
		return $this->addDate($name, $timestamp);
	}

	public function makeDisplay($cleanup = false)
	{

		$processTags = array();
		$processContent = array();
		foreach($this->tags as $tagName => $tagArray)
		{
			if(isset($this->replacement_array[$tagName]))
			{
				$processTags[] = $tagArray['original'];
				$processContent[] = $this->replacement_array[$tagName];

			}elseif($cleanup){
				$processTags[] = $tagArray['original'];
				$processContent[] = '';
			}
		}

		return str_replace($processTags, $processContent, $this->main_string);
	}

	public function make_display($cleanup = false)
	{
		return $this->makeDisplay($cleanup);
	}

}


/**
 * PageMaker
 *
 * A singleton which prepares the final output of the engine
 *
 * @package		Bento Base
 * @subpackage	Main_Classes
 * @category	Display
 * @author		Robert Hafner
 */
class PageMaker
{
	protected $css = array();
	protected $meta = array();
	protected $DisplayMaker;
	protected $template = '';
	static $instance;


	/**
	 * Protected Constructor
	 *
	 */
	private function __construct()
	{
		$this->DisplayMaker = new DisplayMaker();
	}

	/**
	 * Returns the stored instance of the Page object. If no object
	 * is stored, it will create it
	 *
	 * @return Config allows
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object;
		}
		return self::$instance;
	}

	/**
	 * Set the template initial value
	 *
	 * @param string $string
	 */
	public function set_template($string)
	{
		$this->template = $string;
	}

	/**
	 * Set the template initial value from a file
	 *
	 * @param string $path The path to the template file
	 */
	public function set_template_file($path)
	{
		$this->set_template(file_get_contents($path));
	}

	public function set_theme_file($file)
	{
		$config = Config::getInstance();
		$path = $config['path']['theme'] . '$file';
		$this->set_template_file($path);
	}

	/**
	 * Set the page title
	 *
	 * @param string $replacement
	 */
	public function set_title($replacement)
	{
		$this->DisplayMaker->add_replacement('title', $replacement);
	}

	/**
	 * Set the main content of the page
	 *
	 * @param string $replacement
	 */
	public function set_main($replacement)
	{
		$this->DisplayMaker->add_replacement('main content', $replacement);
	}

	/**
	 * Add a metatag to the site
	 *
	 * @param string $name
	 * @param string $content
	 */
	public function set_meta($name, $content)
	{
		$this->meta[$name] = $content;
	}

	/**
	 * Add a custom tag and content to the site
	 *
	 * @param string $tag
	 * @param string $content
	 */
	public function add_custom_tag($tag, $content)
	{
		$this->DisplayMaker->add_replacement($tag, $content);
	}

	/**
	 * Add a stylesheet
	 *
	 * @param string $stylelink path to the stylesheet
	 */
	public function add_css($stylelink)
	{
		if(!in_array($stylelink, $this->css))
			$this->css[] = $stylelink;
	}

	/**
	 * Creates the final page and returns it as a string
	 *
	 * @return string final page
	 */
	public function make_display()
	{
		$this->add_css_to_rest();
		$this->add_meta_to_rest();

		if($this->template == '')
		{
			$config = Config::getInstance();
			$path = $config['path']['theme'] . 'index.html';
			$this->set_template_file($path);
		}

		$this->DisplayMaker->set_display_template($this->template);

		return $this->DisplayMaker->make_display(true);
	}

	/**
	 * Parses the CSS array to add the tags to the page
	 *
	 */
	protected function add_css_to_rest()
	{
		foreach ($this->css as $key -> $value)
		{
			$css_tags .= '<link href="' . $value . '" rel="stylesheet" type="text/css">' . "\n";
		}

		$this->DisplayMaker->add_replacement('css', $css_tags);

	}

	/**
	 * Parses the meta array to add the tags to the page
	 *
	 */
	protected function add_meta_to_rest()
	{

		foreach ($this->meta as $name => $content)
		{
			$meta_tags .= '<META name="' . $value . '" content="'. $content .'">' . "\n";
		}
		$this->DisplayMaker->add_replacement('metatags', $meta_tags);
	}

}



class Page2
{
	public $title;

	protected $meta = array();
	protected $script = array();
	protected $scriptStartup = array();
	protected $scriptIncludes = array();

	public $template;
	public $headTemplate;

	protected $templateTags = array();
	protected $displayMaker;
	protected $replacements;
	protected $replacementDates;

	protected $css = array();

	public function __construct()
	{
		$this->displayMaker = new DisplayMaker();
	}

	public function addContent($name, $content)
	{
		$this->replacements[$name] = $content;
	}

	public function setTemplate($string)
	{
		$this->displayMaker->set_display_template($string);
	}

	public function getTags($withAttributes = false)
	{
		$this->displayMaker->tagsUsed($withAttributes);
	}

	public function addMeta($name, $content)
	{
		$this->meta[] = '<META name="' . $name . '" content="'. $content .'">' . "\n";
	}

	public function addScript($javascript)
	{
		$this->script[] = $javascript;
	}

	public function addStartupScript($javascript)
	{
		$this->scriptStartup[] = $javascript;
	}


	public function addCss($path)
	{
		$this->css[] = $path;
	}

	public function addScriptInclude($path)
	{
		$this->scriptIncludes[] = $path;
	}

	public function buildPage()
	{
		$displayMaker = new DisplayMaker();

		$tags = $this->displayMaker->tagsUsed();

		if(in_array('header', $tags))
		{
			$this->displayMaker->addContent('head', $this->getHead());
		}

		foreach($this->replacements as $tag => $content)
		{
			$this->displayMaker->addContent($tag, $content);
		}

		foreach($this->replacementDates as $tag => $content)
		{
			$this->displayMaker->add_date($tag, $content);
		}

		$cleanup = (DEBUG == 1);
		return $this->displayMaker->make_display($cleanup);
	}

	protected function getCss()
	{
		$css_tags = '';
		$cssArray = array_unique($this->css);
		foreach ($cssArray as $path)
		{
			$css_tags .= '<link href="' . $path . '" rel="stylesheet" type="text/css">' . "\n";
		}
		return $css_tags;
	}


	protected function getScriptIncludes()
	{
		$output = '';
		$scriptArray = array_unique($this->scriptIncludes);
		foreach ($scriptArray as $path)
		{
			$output .= '<script src="' . $path . '" type="text/javascript"></script>' . "\n";
		}
		return $output;
	}

	protected function getHead()
	{
		$headerTemplate = new DisplayMaker();
		$headerTemplate->set_display_template($this->headTemplate);
		$headerTags = $headerTemplate->tagsUsed();

		if(in_array('meta', $headerTags))
		{
			$meta = array_unique($this->meta);
			$headerTemplate->addContent('meta', $meta);
		}

		if(in_array('script', $headerTags))
		{
			$script = $this->script;

			$startupScript = '$(' . "/n/n";
			foreach($this->scriptStartup as $startupAddition)
			{
				$startupScript .= $startupAddition . "/n/n/n";
			}
			$startupScript .= ');' . "/n/n";

			$script[] = $startupScript;

			$scriptOutput = '<script>' . "/n/n";
			foreach($script as $scriptAddition)
			{
				$scriptOutput .= $scriptAddition . "/n/n/n";
			}

			$scriptOutput .= '</script>' . "/n/n";
		}

		if(in_array('title', $headerTags))
		{
			$headerTemplate->addContent('title', $this->title);
		}

		if(in_array('script_includes', $headerTags))
		{
			$headerTemplate->addContent('title', $this->getScriptIncludes());
		}

		if(in_array('css', $headerTags))
		{
			$headerTemplate->addContent('css', $this->getCss());
		}


		return $headerTemplate->make_display();
	}

}

class HtmlPage extends Page2
{
	protected $modId;
	protected $pageId;
	protected $pageName;

	public function loadCmsData($id)
	{
		$cms = new CMSPage();

		if($this->pageId > 0)
		{
			$cms->load_by_pageid($id);
		}elseif($this->modId > 0 && strlen($this->pageName) > 0){
			$cms->load_by_pagename($name, $modId);
		}

	}

	public function setPageByName($name, $modId)
	{
		$this->modId = $modId;
		$this->pageName = $name;
		$this->pageId = 0;
	}

	public function setPageById($id)
	{
		$this->modId = 0;
		$this->pageName = '';
		$this->pageId = $id;
	}


	public function buildPage()
	{
		$cms = new CMSPage();

		if($this->pageId > 0)
		{
			$cms->load_by_pageid($id);
		}elseif($this->modId > 0 && strlen($this->pageName) > 0){
			$cms->load_by_pagename($name, $modId);
		}


		$this->addMeta('Keywords', $cms->keywords);
		$this->addMeta('Description', $cms->description);

		$this->title = $cms->title;

		$cms->createdon;
		$cms->regions;



		return parent::buildPage();
	}

}

?>
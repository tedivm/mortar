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
 * This class builds HTML pages for display to the browsers
 *
 * @package System
 * @subpackage Display
 */
class Page implements ArrayAccess
{
	/**
	 * This is an array of meta tags
	 *
	 * @access protected
	 * @var array
	 */
	protected $meta = array();

	/**
	 * This is an array of javascript that will be placed in the header
	 *
	 * @access protected
	 * @var array
	 */
	protected $script = array();

	/**
	 * This is an array of javascript that will be run on startup
	 *
	 * @access protected
	 * @var array
	 */
	protected $scriptStartup = array();

	/**
	 * These are the template tags which are being replaced and their replacements
	 *
	 * @access protected
	 * @var array
	 */
	protected $regions = array();

	/**
	 * This is the current theme being used
	 *
	 * @var string
	 */
	public $theme = 'default';

	/**
	 * The template processor for the page
	 *
	 * @access protected
	 * @var DisplayMaker
	 */
	protected $display;

	/**
	 * This contains an associative array used to link submenus to their containers.
	 *
	 * @var array
	 */
	protected $menuLookup;

	/**
	 * This contains an array of NavigationMenu objects.
	 *
	 * @var unknown_type
	 */
	protected $menuObjects = array();

	/**
	 * This is an associative array used to link container names back to their origin name.
	 *
	 * @var array
	 */
	protected $menuReverseLookup;

	/**
	 * This contains an array of strings (or string convertable objects) that are passed to the system as messages
	 *
	 * @var array
	 */
	protected $messages = array();

	// actual paths

	/**
	 * A list of javascript paths to include
	 *
	 * @access protected
	 * @var array
	 */
	protected $jsIncludes = array();

	/**
	 * A list of css paths to include
	 *
	 * @access protected
	 * @var array
	 */
	protected $cssIncludes = array();

	/**
	 * Javescript code that needs to be run before at the top of the page
	 *
	 * @access protected
	 * @var array
	 */
	protected $preStartupJs = array();

	/**
	 * The contents of this array get added to the "head" section of the page, outside of any javascript or CSS tag
	 *
	 * @var array
	 */
	protected $headerContent = array();

	/**
	 * The current theme file to use (out of the files in the root of the theme's directory)
	 *
	 * @var string
	 */
	protected $templateFile = 'index.html';

	/**
	 * This is a subtemplate containing technical headers for the html
	 *
	 * @access protected
	 * @var string
	 */
	protected $headerTemplate = '
	<title>{# title #}</title>
	{# cssIncludes #}
	<script type="text/javascript">
	{# preStartupJs #}
	</script>
	{# meta #}
	{# headerContent #}
';

	protected $footerTemplate = '
	{# jsIncludes #}
	<script type="text/javascript">
		$(function(){
			{# scriptStartup #}
		});
		{# script #}
	</script>';


	/**
	 * This function changes the current theme and template
	 *
	 * @cache theme *name *file
	 * @param string $file
	 * @param string $theme
	 */
	public function setTemplate($file = 'index.html', $theme = 'default')
	{
		$config = Config::getInstance();
		if($theme != '')
		{
			$basePath = $config['path']['theme'] .  $theme . '/';
			if(is_dir($basePath))
			{
				$this->theme = $theme;
			}
		}

		$cache = new Cache('theme', $this->theme, $file);
		$template = $cache->getData();

		if($cache->isStale())
		{
			$template = array();
			$template['menuLookup'] = array();
			$template['reverseLookup'] = array();

			$basePath = $config['path']['theme'] . $this->theme . '/';
			$path = $basePath . $file;

			if(!file_exists($path))
				$path = $config['path']['theme'] . $this->theme . 'index.html';

			$rawTemplate = file_get_contents($path);

			$templateProcess = new DisplayMaker();
			$templateProcess->setDisplayTemplate($rawTemplate, $path);

			$tags = $templateProcess->tagsUsed(true);

			foreach($tags as $index => $tagArray)
			{
				if(isset($tagArray['type']) && $tagArray['type'] == 'navbar')
				{
					(!isset($navCount)) ? $navCount = 1 : $navCount++;
					$newName = '__navbar_' . $navCount;

					$templateProcess->addContent($tagArray['name'], '{# ' . $newName . ' #}');

					$template['reverseLookup'][$newName] = $tagArray['name'];
					$template['menuLookup'][$tagArray['name']] = $newName;

					if(isset($tagArray['menus']))
					{
						$menus = explode(',', $tagArray['menus']);

						foreach($menus as $menuName)
						{
							$template['menuLookup'][$menuName] = $newName;
						}
					}elseif(!isset($template['menuLookup']['main'])){
						$template['menuLookup']['main'] = $newName;
					}


				}
			}

			$templateProcess->addDate('currentDate', time());
			$templateProcess->addContent('head', $this->headerTemplate);

			$template['string'] = $templateProcess->makeDisplay(false);
			$template['string'] .= PHP_EOL . $this->footerTemplate;
			$cache->storeData($template);
		}

		$this->menuLookup = $template['menuLookup'];
		$this->menuReverseLookup = $template['reverseLookup'];
		$this->display = new DisplayMaker();
		$this->display->setDisplayTemplate($template['string']);
	}

	/**
	 * This function returns a NavigationMenu specified by the subtype, menu and specific template settings. This
	 * function should be called instead of the NavigationMenu::setMenu() function, as the needed subtype may be in a
	 * different container if the template designer desires.
	 *
	 * @param string $subtype
	 * @param string $menu
	 * @return NavigationMenu
	 */
	public function getMenu($subtype, $menu = 'main')
	{
		switch (true) {
			case isset($this->menuLookup[$subtype]):
				$finalMenu = $this->menuLookup[$subtype];
				break;

			case $menu == false:
				return false;
				break;

			case isset($this->menuLookup[$menu]):
				$finalMenu = $this->menuLookup[$menu];
				break;

			default:
				$finalMenu = $this->menuLookup['main'];
				break;
		}

		if(!isset($this->menuObjects[$finalMenu]))
			$this->menuObjects[$finalMenu] = new NavigationMenu($this->menuReverseLookup[$finalMenu]);

		$menuObject = $this->menuObjects[$finalMenu];
		$menuObject->setMenu($subtype);
		return $menuObject;
	}

	/**
	 * Returns the URL to the current theme
	 *
	 * @return string
	 */
	public function getThemeUrl()
	{
		return ActiveSite::getLink('theme') . $this->theme . '/';
	}

	/**
	 * Returns the path to the current theme
	 *
	 * @return string
	 */
	public function getThemePath()
	{
		$config = Config::getInstance();
		return $config['path']['theme'] . $this->theme . '/';
	}

	/**
	 * This function returns the Theme being used by the current page.
	 *
	 * @return Theme
	 */
	public function getTheme()
	{
		$theme = new Theme($this->theme);
		return $theme;
	}

	/**
	 * This function adds a message to be displayed by the page.
	 *
	 * @param string $message
	 */
	public function addMessage($message)
	{
		$this->messages[] = $message;
	}

	/**
	 * Adds a meta tag to the page
	 *
	 * @param string $name
	 * @param string $content
	 */
	public function addMeta($name, $content)
	{
		$this->meta[$name] = '<META name="' . $name . '" content="'. $content .'">' . PHP_EOL;
	}

	/**
	 * Adds javascript to the header
	 *
	 * @param string $javascript
	 */
	public function addScript($javascript)
	{
		$this->script[] = $javascript;
	}

	/**
	 * This function adds content directly to the head section of the html document.
	 *
	 * @param string $content
	 */
	public function addHeaderContent($content)
	{
		$this->headerContent[] = $content;
	}

	/**
	 * Adds javascript to the jQuery startup function
	 *
	 * @param string $javascript
	 */
	public function addStartupScript($javascript)
	{
		if(is_array($javascript))
		{
			$this->scriptStartup = array_merge($this->scriptStartup, $javascript);
		}else{
			$this->scriptStartup[] = $javascript;
		}
	}

	/**
	 * Adds script include tags to the template for these javascript urls
	 *
	 * @access protected
	 * @param string|array $jsfiles
	 */
	protected function addJSInclude($jsfiles)
	{
		if(!is_array($jsfiles))
			$jsfiles = array($jsfiles);

		foreach($jsfiles as $file)
		{
			if(!in_array($file, $this->jsIncludes))
				$this->jsIncludes[] = '<script type="text/javascript" src="' . $file . '"></script>';
 		}
	}

	/**
	 * Adds css include tags to the template for these css urls
	 *
	 * @access protected
	 * @param string|array $cssfiles
	 */
	protected function addCssInclude($cssfiles)
	{
		if(!is_array($cssfiles))
			$cssfiles = array($cssfiles);

		foreach($cssfiles as $file)
		{
			if(!in_array($file, $this->cssIncludes))
				$this->cssIncludes[] = '<link href="' . $file . '" rel="stylesheet" type="text/css"/>';
 		}
	}

	/**
	 * Creates and returns the final page
	 *
	 * @return string
	 */
	public function makeDisplay()
	{
		$config = Config::getInstance();

		if(!($this->display instanceof DisplayMaker))
		{
			$this->setTemplate();
		}

		$this->runtimeProcessTemplate();
		$display = $this->display;
		$tags = $display->tagsUsed(false);

		// This is a list of all of the 'array' items that need to be cycled through and added as a single items
		$groups = array('script',
						'scriptStartup',
						'meta',
						'jsIncludes',
						'cssIncludes',
						'preStartupJs',
						'headerContent');

		$output = PHP_EOL;
		foreach($groups as $variable)
		{
			$content = '';
			$output = '';
			if(in_array($variable, $tags))
			{
				foreach($this->$variable as $content)
				{
					$output .= $content . PHP_EOL;
				}
				$display->addContent($variable, $output);
			}
		}

		foreach($this->regions as $name => $content)
		{
			$display->addContent($name, $content);
		}

		return $this->postProcessTemplate($display->makeDisplay(false));
	}

	/**
	 * Add content to the page at the specified tag
	 *
	 * @param string $tag
	 * @param string $content
	 */
	public function addRegion($tag, $content)
	{
		$this->regions[$tag] = $content;
	}

	/**
	 * Append content to a region
	 *
	 * @param string $tag
	 * @param string $content
	 */
	public function appendToRegion($tag, $content)
	{
		if(!isset($this->regions['tag']))
			$this->regions[$tag] = '';

		$this->regions[$tag] .= $content;
	}

	/**
	 * Prepend content to a region
	 *
	 * @param string $tag
	 * @param string $content
	 */
	public function prependToRegion($tag, $content)
	{
		if(!isset($this->regions['tag']))
			$this->regions[$tag] = '';

		$this->regions[$tag] = $content . $this->regions[$tag];
	}

	/**
	 * This is a runtime preprocessor allow some preprocessing before running all the tag replacement code.
	 *
	 * @access protected
	 */
	protected function runtimeProcessTemplate()
	{
	// Here we grab the theme to get the javascript and css include file.
		$theme = new Theme($this->theme);
		$this->addJSInclude($theme->getUrl('js'));
		$this->addCssInclude($theme->getUrl('css'));

		$this->preStartupJs[] = 'var baseUrl = ' . json_encode(ActiveSite::getLink()) . ';';

		if(isset($this->menuObjects))
			foreach($this->menuObjects as $name => $menuDisplay)
		{
			$this->addRegion($name, $menuDisplay->makeDisplay());
		}

	// Since breadcrumbs rely on database access we bail out here during install mode.
		if(defined('INSTALLMODE') && INSTALLMODE) return true;
		$user = ActiveUser::getUser();
		$userId = $user->getId();
		$query = Query::getQuery();

	// if the location is set we'll attempt to add breadcrumbs
		if(isset($query['location']) && is_numeric($query['location']))
		{
			$location = new Location($query['location']);
			$urlList = array();
			$x = 1;
			do
			{
				if($location->getType() == 'Root')
					break;

				$url = new Url();
				$url->location = $location->getId();
				$url->format = $query['format'];

				if($url->checkPermission($userId))
					$urlList[] = $url->getLink(str_replace('_', ' ', $location->getName()));

			}while($location = $location->getParent());

			if(count($urlList) > 1)
			{
				$urlList = array_reverse($urlList);

				$breadCrumb = new HtmlObject('div');
				$breadCrumb->property('id', count($urlList)."_level_breadcrumbs");
				$breadCrumb->addClass('breadcrumbs');

				$breadCrumbList = new HtmlObject('ul');
				$breadCrumbList->addClass('breadcrumblist');
				$breadCrumb->wrapAround($breadCrumbList);

				$breadCrumbClean = new HtmlObject('div');
				$breadCrumbClean->addClass('clean');
				$breadCrumb->wrapAround($breadCrumbClean);

				foreach($urlList as $url)
				{
					$listItem = $breadCrumbList->insertNewHtmlObject('li');
					$listItem->wrapAround($url);
				}
				$listItem->addClass('current');
				$this->addRegion('breadcrumbs', (string) $breadCrumb);
			}
		}

	// Add the messages to the page.
		if(count($this->messages) > 0)
		{
			$outputMessage = new HtmlObject('div');
			$outputMessage->addClass('messageContainer');

			foreach($this->messages as $message)
			{
				$outputMessage->insertNewHtmlObject('div')->wrapAround($message);
			}
			$this->addRegion('messages', (string) $outputMessage);
		}
	}

	/**
	 * This acts as a final processing point, allow some tags to 'float' to the top through other replacement cycles
	 * before being processed. This function also takes care of the final cleanup of tags (assuming CLEAN_TEMPLATES
	 * constant is not set to false)
	 *
	 * @param string $templateString
	 * @return string
	 */
	protected function postProcessTemplate($templateString)
	{
		$template = new DisplayMaker();
		$template->setDisplayTemplate($templateString);
		$template->addContent('theme_path', $this->getThemeUrl());

		$jsInclude = ActiveSite::getLink() . 'javascript/';

		$template->addContent('js_path', $jsInclude);

		return $template->makeDisplay(!(defined('CLEAN_TEMPLATES') && CLEAN_TEMPLATES === FALSE));
	}

	public function offsetGet($offset)
	{
		return $this->regions[$offset];
	}
	public function offsetSet($offset, $value)
	{
		return ($this->regions[$offset] = $value);
	}
	public function offsetUnset($offset)
	{
		unset($this->regions[$offset]);
	}
	public function offsetExists($offset)
	{
		return isset($this->regions[$offset]);
	}
}

/**
 * This class is a singleton containing the current running page. I hate this with a fiery passion and will be
 * reworking it at some point
 *
 * @package System
 * @subpackage Display
 */
class ActivePage extends Page
{
	public $template;
	public $meta = array();
	public $css = array();
	public $containers = array();

	static $instance;


	/**
	 * Protected Constructor
	 *
	 */
	private function __construct()
	{

	}

	/**
	 * Returns the stored instance of the Page object. If no object
	 * is stored, it will create it
	 *
	 * @return ActivePage allows
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object;
		}
		return self::$instance;
	}
}

?>

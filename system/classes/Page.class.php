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
	 * The title of the page currently being displayed.
	 *
	 * @access protected
	 * @var string
	 */
	protected $title;

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
	 * @var ViewThemeTemplate
	 */
	protected $display;

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
	 * Whether to display menus when rendering this page.
	 *
	 * @var bool
	 */
	protected $showMenus = true;

	/**
	 * This is a subtemplate containing technical headers for the html
	 *
	 * @access protected
	 * @var string
	 */
	protected $headerTemplate = '
	{{ cssIncludes }}
	<script type="text/javascript">
	{{ preStartupJs }}
	</script>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	{{ meta }}
	{{ headerContent }}';

	protected $footerTemplate = '
	{{ jsIncludes }}
	<script type="text/javascript">
		$(function(){
			{{ scriptStartup }}
		});
		{{ script }}
	</script>';

	/**
	 * Sets the page title.
	 *
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * Returns the current page title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Sets the showMenus setting
	 *
	 * @param $b bool
	 */
	public function showMenus($b)
	{
		$this->showMenus = $b ? true : false;
	}

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

		$templateProcess = new ViewThemeTemplate(new Theme($theme), $file);

		$this->display = $templateProcess;
	}

	/**
	 * This function contains all content tags which are universally applied to the page view template. 
	 *
	 * @param ViewModelTemplate $template
	 * @return ViewModelTemplate
	 */
	protected function addTemplateContent($template)
	{
		// if we're installing, do the bare minimum and get the heck outta here
		if(defined('INSTALLMODE') && INSTALLMODE) {
			$menuSys = new MenuSystem();
			$menuSys->installMode();
			$theme = $this->getTheme();

			$menuBox = new TagBoxMenu($menuSys, $theme);
			$content['menu'] = $menuBox;

			$envBox = new TagBoxEnv();
			$content['env'] = $envBox;

			$breadBox = new TagBoxBreadcrumbs();
			$content['breadcrumbs'] = $breadBox;

			$content['pagetitle'] = "Install Mortar";

			$template->addContent($content);
			return $template;
		}

		$query = Query::getQuery();
		$content = array();

		// set location and model variables *if* they currently apply
		if(isset($query['location']) && is_numeric($query['location'])) {
			$location = Location::getLocation($query['location']);
			$model = $location->getResource();
		} elseif ($query['type'] && $query['id']) {
			$model = ModelRegistry::loadModel($query['type'], $query['id']);
			if ($model === false)
				unset($model);
		}

		// all tagboxes that require a location go here. if there's a location there's always a model
		if(isset($location)) {
			$locHook = new Hook();
			$locHook->loadPlugins('Page', 'Template', 'Location');
			$addContent = Hook::mergeResults($locHook->getBoxes($location));
			$content = array_merge($content, $addContent);

			$navBox = new TagBoxNav($location);
			$content['nav'] = $navBox;
		}

		// all tagboxes that require a model but not a location go here
		if(isset($model)) {
			$modHook = new Hook();
			$modHook->loadPlugins('Page', 'Template', 'Model');
			$addContent = Hook::mergeResults($modHook->getBoxes($model));
			$content = array_merge($content, $addContent);

			$modelBox = new TagBoxModel($model);
			$content['model'] = $modelBox;
		}

		// all other tagboxes that are not location- or model-dependent go here
		$breadBox = new TagBoxBreadcrumbs();
		$content['breadcrumbs'] = $breadBox;

		$tagHook = new Hook();
		$tagHook->loadPlugins('Page', 'Template', 'None');
		$addContent = Hook::mergeResults($tagHook->getBoxes());
		$content = array_merge($content, $addContent);

		$theme = $this->getTheme();
		$themeBox = new TagBoxTheme($theme);
		$content['theme'] = $themeBox;

		$envBox = new TagBoxEnv();
		$content['env'] = $envBox;

		// the menu system, which is unique and has its own special concerns

		if($this->showMenus) {
			$menuSys = new MenuSystem();
			isset($model) ? $menuSys->initMenus($model) : $menuSys->initMenus();
			$menuBox = new TagBoxMenu($menuSys, $theme);
			$content['menu'] = $menuBox;
		}

		// finally, any non-box standalone tags go here
		$content['pagetitle'] = $this->title;
		$content['format'] = $query['format'];

		$template->addContent($content);

		return $template;
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
	public function addJSInclude($jsfiles)
	{
		if(!is_array($jsfiles))
			$jsfiles = array($jsfiles);

		foreach($jsfiles as $file)
		{
			if(!in_array($file, $this->jsIncludes))
				array_unshift($this->jsIncludes, '<script type="text/javascript" src="' . $file . '"></script>');
 		}
	}

	/**
	 * Adds css include tags to the template for these css urls
	 *
	 * @access protected
	 * @param string|array $cssfiles
	 */
	public function addCssInclude($cssfiles)
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

		$this->runtimeProcessTemplate();
		$display = $this->display;

		$headerTemplate = new ViewStringTemplate($this->headerTemplate);
		$footerTemplate = new ViewStringTemplate($this->footerTemplate);

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
			foreach($this->$variable as $content)
			{
				$output .= $content . PHP_EOL;
			}
			$headerTemplate->addContent(array($variable => $output));
			$footerTemplate->addContent(array($variable => $output));
		}

		foreach($this->regions as $name => $content)
		{

			$display->addContent(array($name => $content));
		}

		$jsInclude = ActiveSite::getLink() . 'javascript/';
		$headerFinal = $headerTemplate->getDisplay();
		$footerFinal = $footerTemplate->getDisplay();

		$display->addContent(array('js_path' => $jsInclude, 'head' => $headerFinal, 'foot' => $footerFinal));
		$this->addTemplateContent($display);

		return $display->getDisplay();
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

	// This line ensures that any additional content we add below that requires a fully-functional system doesn't
	// choke the installer.
		if(defined('INSTALLMODE') && INSTALLMODE) return true;

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
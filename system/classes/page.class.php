<?php

class Page implements ArrayAccess
{
	protected $meta = array();
	protected $script = array();
	protected $scriptStartup = array();	
	protected $regions = array();
	public $theme = 'default';

	
	protected $display;

	// plugins
	protected $css = array();
	protected $javascript = array();
	
	// actual paths
	protected $jsIncludes = array();
	protected $cssIncludes = array();
	
	
	protected $templateFile = 'index.html';
	
	protected $headerTemplate = '
	<title>{# title #}<title>
	{# meta #}
	{# cssIncludes #}
	{# jsIncludes #}
	
	<script type="text/javascript">
	
		$(function(){
			{# scriptStartup #}
		});	
			
		{# script #}
		
	</script>';
	
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
		
		$cache = new Cache('themes', $this->theme, $file);
		$template = $cache->get_data();
		
		if(!$cache->cacheReturned)
		{
			$basePath = $config['path']['theme'] . $this->theme . '/';
			$path = $basePath . $file;
			
			if(!file_exists($path))
			{
				$path = $config['path']['theme'] . $this->theme . 'index.html';
			}
	
			$template = file_get_contents($path);
			$template = $this->preProcessTemplate($template);
			$cache->store_data($template);
		}
		
		$this->display = new DisplayMaker();
		$this->display->set_display_template($template);
	}
	
	public function getThemeUrl()
	{
		$config = Config::getInstance();
		$info = InfoRegistry::getInstance();
		
		return $info->Site->currentLink . $info->Configuration['url']['theme'] . $this->theme . '/';
	}
	
	public function getThemePath()
	{
		$config = Config::getInstance();
		return $config['path']['theme'] . $this->theme . '/';
	}	
	
	/*
	public function loadCmsPage($idOrName, $modId = 0)
	{
		$cms = new CMSPage();
		if($modId > 0)
		{
			$result = $cms->load_by_pagename($idOrName, $modId);
		}else{
			$result = $cms->load_by_pageid($idOrName);
		}
				
		if(!$result)
			return false;
		
		$this->regions['moduleId'] = $cms->mod_id;
		$this->regions['title'] = $cms->title;
		$this->regions['name'] = $cms->name;
		$this->regions['content'] = $cms->content;

		$this->addMeta('keywords', $cms->keywords);
		$this->addMeta('description', $cms->description);
	}
	*/
	
	public function addMeta($name, $content)
	{
		$this->meta[$name] = '<META name="' . $name . '" content="'. $content .'">' . "\n";		
	}
	
	public function addScript($javascript)
	{
		$this->script[] = $javascript;
	}
	
	public function addStartupScript($javascript)
	{
		$this->scriptStartup[] = $javascript;
	}
	
	public function addJavaScript($name, $library = 'none')
	{
		if(!is_array($name))
		{
			$this->javascript[$library][] = $name;
		}elseif(is_array($name))
		{
			$this->javascript = array_merge_recursive($this->javascript, $name);
		}
	}

	public function addCss($name, $library = 'none')
	{
		if(!is_array($name))
		{
			$this->css[$library][] = $name;
		}elseif(is_array($name))
		{
			$this->css = array_merge_recursive($this->css, $name);
		}
	}
	
	protected function addJSInclude($jsfiles)
	{
		if(is_string($jsfiles))
			$jsfiles = array($jsfiles);
			
		foreach($jsfiles as $file)
		{
			if(!in_array($file, $this->jsIncludes))
				$this->jsIncludes[] = '<script type="text/javascript" src="' . $file . '"></script>';
 		}
	}
	
	protected function addCssInclude($cssfiles)
	{
		if(is_string($cssfiles))
			$cssfiles = array($cssfiles);
			
		foreach($cssfiles as $file)
		{
			if(!in_array($file, $this->cssIncludes))
				$this->cssIncludes[] = '<link href="' . $file . '" rel="stylesheet" type="text/css"/>';
 		}
	}
	
	public function addJQueryInclude($plugin)
	{
		$this->addJavaScript($plugin, 'jquery');
	}
	
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
		$groups = array('script', 'scriptStartup', 'meta', 'jsIncludes', 'cssIncludes');
				
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
		
		return $this->postProcessTemplate($display->make_display(false));
	}
	
	public function addRegion($tag, $content)
	{
		$this->regions[$tag] = $content;
	}

	public function appendToRegion($tag, $content)
	{
		$this->regions[$tag] .= $content;
	}	
	
	public function prependToRegion($tag, $content)
	{
		$this->regions[$tag] = $content . $this->regions[$tag];
	}	
	
	// processes the raw template from the theme folder
	// used to add information that stays mostly static (current year, for instance)
	protected function preProcessTemplate($templateString)
	{
		$template = new DisplayMaker();
		$template->set_display_template($templateString);
		
		$template->add_content('currentYear', date('y'));
		$template->add_content('head', $this->headerTemplate);
		return $template->make_display(false);
	}
	
	// This function adds any dynamic, runtime tags
	// can include user-specific information
	// run every time page is loaded
	protected function runtimeProcessTemplate()
	{
		//$this->addJavaScript('defaults', 'jquery');
		//$this->addJavaScript('defaults', 'bento');
		
		$theme = new Theme($this->theme);
		
		$jsUrls = array();
		foreach($this->javascript as $library => $plugins)
		{
			foreach($plugins as $pluginName)
			{
				if($url = $theme->jsUrl($pluginName, $library))
					$jsUrls[] = $url;
			}
		}
		
		$this->addJSInclude($jsUrls);
		
		$cssUrls = array();
		foreach($this->css as $library => $plugins)
		{
			foreach($plugins as $pluginName)
			{
				if($url = $theme->cssUrl($pluginName, $library))
					$cssUrls[] = $url;				
			}
		}
		
		$this->addCssInclude($cssUrls);
	}
	
	// doesn't do much yet
	protected function postProcessTemplate($templateString)
	{
		$template = new DisplayMaker();
		$template->set_display_template($templateString);		
		$template->add_content('theme_path', $this->getThemeUrl());

		$get = Get::getInstance();
		$jsInclude = $get['currentUrl'] . 'javascript/';
		
		$template->add_content('js_path', $jsInclude);
		
		//(DEBUG>1)
		
		return $template->make_display(!(DEBUG>2));
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
		$this->addJavaScript(array( 'jquery' => array('1_2_6', 'ui-1_6b', 'metadata', 'dimensions')));
		$this->addCss(array('none' => array('all')));
//		$this->addJQueryInclude(array('1_2_6', 'ui-1_6b', 'metadata', 'demensions'));

	}
	
	/**
	 * Returns the stored instance of the Page object. If no object
	 * is stored, it will create it
	 * 
	 * @return ActivePage allows 
	 */
	public static function get_instance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object;			
		}
		return self::$instance;
	}
	
	
	public function clear()
	{
		$this->setTemplate();		
		$this->id = '';
		$this->mod_id = '';
		$this->title = '';
		$this->name = '';
		$this->content = '';
		$this->keywords = '';
		$this->description = '';
		$this->createdon = '';
		$this->template = '';
		$this->regions = array();
		$this->template = '';
		$this->meta = array();
		$this->css = array();
	}
	
	
}

?>
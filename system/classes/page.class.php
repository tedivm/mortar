<?php

class CMSPage
{
		public $id;
		public $mod_id;
		public $title;
		public $name;
		public $content;
		public $keywords;
		public $description;
		
		public $createdon;

		public $template;
		public $regions = array();
		
		

		public function load_by_pagename($name, $modId = 0)
		{
			
			if($modId > 0)
				$this->mod_id = $mod_id;
			
			$cache = new Cache('cms', 'lookup', $this->mod_id, $name);
			
			$pageId = $cache->get_data();
			
			if(!$cache->cacheReturned)
			{
				$lookup = new ObjectRelationshipMapper('cms_pages');
				$lookup->mod_id = $this->mod_id;
				$lookup->page_name = $name;
				$lookup->select(1);
				if($lookup->total_rows() == 1)
				{
					$pageId = $lookup->page_id;
				}else{
					$pageId = false;
				}
				
				$cache->store_data($pageId);
			}
			
			if($pageId > 0)
			{
				return $this->load_by_pageid($pageId);
			}
			
			return false;
		}
		
		public function load_by_pageid($id)
		{
			
			$cache = new Cache('cms', 'pages', $id);
			
			if(!($page_results = $cache->get_data()))
			{
				$db = db_connect('default_read_only');
				$select_stmt = $db->stmt_init();
				
				$select_stmt->prepare('SELECT * FROM cms_pages WHERE page_id = ?');
				$select_stmt->bind_param_and_execute('i', $id);
				
				if($select_stmt->num_rows == 1)
				{
					$page_results = $select_stmt->fetch_array();
					$cache->store_data($page_results);
				}else{
					return false;
				}
			}
			
			
			if(is_array($page_results))
			{
				$this->parse_sqlresults($page_results);
				return true;
			}			
			
			return false;
		}
		
		public function save_page()
		{
			$db_write = db_connect('default');
				
			if($this->id > 0)
			{
	
				$save_stmt = $db_write->stmt_init();
				$save_stmt->prepare('UPDATE cms_pages SET 
						mod_id = ?,
						page_title = ?,
						page_name = ?,
						page_keywords = ?,
						page_description = ?,
						page_content = ?
						WHERE page_id = ?');
				$save_stmt->bind_param_and_execute('isssssi', $this->mod_id, $this->title, $this->name, $this->keywords, $this->description, $this->content, $this->id);
		
				
			}else{
				
				$insert_stmt = $db_write->stmt_init();
				$insert_stmt->prepare('INSERT INTO cms_pages ( 
						page_id,
						mod_id,
						page_title,
						page_name,
						page_keywords,
						page_description,
						page_content,
						page_createdon)
						VALUES (NULL, ?, ?, ?, ?, ?, ?, NOW())');
				$insert_stmt->bind_param_and_execute('isssss', $this->mod_id, $this->title, $this->name, $this->keywords, $this->description, $this->content);
				$this->id = $insert_stmt->insert_id;
				
			}
			
			
			$this->save_regions();
			
			return ($this->id > 0);
			
		}
		
		protected function parse_sqlresults($results_array)
		{
			$this->id = $results_array['page_id'];
			$this->mod_id = $results_array['mod_id'];
			$this->title = $results_array['page_title'];
			$this->name = $results_array['page_name'];
			$this->content = $results_array['page_content'];
			$this->keywords = $results_array['page_keywords'];
			$this->description = $results_array['page_description'];
			$this->createdon = $results_array['page_createdon'];
			$this->template = ($results_array['page_template']) ? $results_array['page_template'] : 'index.html';
		}
		
		protected function load_extra_regions()
		{
/*			$cache = new Cache('modules', $this->mod_id, 'pages', regions, $this->id);
			$this->regions = $cache->get_data();
		
			if(!$cache->cacheReturned)
			{
				$db = db_connect('default_read_only');
				$select_stmt = $db->stmt_init();
				
				$select_stmt->prepare('SELECT * FROM page_content, template_regions WHERE page_contents.region_id = template_regions.region_id AND page_id = ?');
				$select_stmt->bind_param_and_execute('i', $this->id);
				
				if($select_stmt->num_rows > 0)
				{
					while($results = $select_stmt->fetch_array())
					{
						$results['page_content'];
						$results['region_name'];
						
						$tmp_region = new CMSRegion();
						$tmp_region->id = $results['region_id'];
						$tmp_region->name = $results['region_name'];
						$tmp_region->tag = $results['region_tag'];
						$tmp_region->locked_by = $results['region_lockedby_mod_id'];
						
						if(!($tmp_region->locked_by > 0) || $tmp_region->locked_by == $this->mod_id)
						{
							$tmp_region->content = $results['page_content'];
							$tmp_region_array[$results['region_id']] = $tmp_region;
							$this->regions[$results['region_id']] = $tmp_region;
						}
					}
				}
				$cache->store_data($tmp_region_array);
				$this->regions = $tmp_region_array;
			}*/

		}
		
		protected function save_regions()
		{
			$db_read = db_connect('default_read_only');
			$db_write = db_connect('default');
			
			// self- seriously, clean this up at some point. doing a seperate select query each time is dumb
			
			foreach($this->regions as $region)
			{

				$read = $db_read->stmt_init();
				$read->prepare('SELECT * FROM page_content WHERE page_id = ? AND region_id = ?');
				$read->bind_params_and_executre('ii', $this->id, $region->id);
				
				if($read->num_rows > 0)
				{
					if(trim($region->content) != '')
					{
						$save_stmt = $db_write->stmt_init();
						$save_stmt->prepare('UPDATE page_content SET page_content.page_content = ? WHERE page_id = ? AND region_id = ?');
						$save_stmt->bind_param_and_execute('sii', $region->content, $this->id, $region->id);
					}else{
						$delete_stmt = $db_write->stmt_init();
						$delete_stmt->prepare('DELETE FROM page_content WHERE page_id = ? AND region_id = ? LIMIT 1');
						$delete_stmt->bind_param_and_execute('ii', $this->id, $region->id);
					}
					
				}else{
					$insert_stmt = $db_write->stmt_init();
					$insert_stmt->prepare('INSERT INTO page_content (page_id, region_id, page_content) VALUES (?, ?, ?)');
					$insert_stmt->bind_param_and_execute('iis', $this->id, $region->id, $region->content);
				}
				
				
			}
		}
		
		public function delete()
		{
			if($this->id > 0)
			{
				$db_write = db_connect('default');
				$delete_stmt = $db_write->stmt_init();
				$delete_stmt->prepare('DELETE FROM page_content WHERE page_id = ?');
				$delete_stmt->bind_param_and_execute('i', $this->id);
				
				$delete_stmt = $db_write->stmt_init();
				$delete_stmt->prepare('DELETE FROM cms_pages WHERE page_id = ?');
				$delete_stmt->bind_param_and_execute('i', $this->id);
			}
		}
		
}

class CMSRegion
{
	public $id;
	public $pageId;
	public $name;
	public $tag;
	public $locked_by;
	public $content = '';
	
	public function load_by_id($id)
	{
		$db = db_connect('default_read_only');
		$select_stmt = $db->stmt_init();
		
		$select_stmt->prepare('SELECT * FROM template_regions WHERE region_id = ?');
		$select_stmt->bind_param_and_execute('i', $id);
		
		if($select_stmt->num_rows == 1)
		{
			$results = $select_stmt->fetch_array();
			$this->parse_sqlresults($results);
		}
		
	}
	
	public function load_by_tag($tag)
	{
/*		$cache = new Cache('page', $this->id, 'regions');
		
		
		
		$db = db_connect('default_read_only');
		$select_stmt = $db->stmt_init();	
		$select_stmt->prepare('SELECT * FROM template_regions WHERE region_id = ?');
		$select_stmt->bind_param_and_execute('i', $this->id);
		
		if($select_stmt->num_rows == 1)
		{
			$results = $select_stmt->fetch_array();
			$this->parse_sqlresults($results);
		}else{
			$this->id = $tag;
			$this->tag = $tag;
			$this->name = $tag;
		}
		*/
		
		
	}
	
	protected function parse_sqlresults($results)
	{
		$this->id = $results['region_id'];
		$this->name = $results['region_id'];
		$this->tag = $results['region_tag'];
		$this->locked_by = $results['region_lockedby_mod_id'];
		
		
		$thing['id'] = $results['region_id'];
		$thing['name'] = $results['region_name'];
		$thing['tag'] = $results['region_tag'];
		$thing['locked_by'] = $results['region_lockedby_mod_id'];
		
		return $thing;
		
	}
}


class Page implements ArrayAccess
{
	protected $meta = array();
	protected $script = array();
	protected $scriptStartup = array();	
	protected $regions = array();
	protected $css = array();
	public $theme = 'default';

	
	protected $display;
	protected $jsIncludes = array();
	
	protected $templateFile = 'index.html';
	
	protected $headerTemplate = '
	<title>{# title #}<title>
	
	{# meta #}
	
	{# css #}
	
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
	
	public function addJSInclude($jsfiles)
	{
		if(is_string($jsfiles))
			$jsfiles = array($jsfiles);
			
		foreach($jsfiles as $file)
		{
			if(!in_array($file, $this->jsIncludes))
				$this->jsIncludes[] = '<script type="text/javascript" src="' . $file . '"></script>';
 		}
	}
	
	public function addJQueryInclude($plugin)
	{
		
		if(is_string($plugin))
			$plugin = array($plugin);
		
		$info = InfoRegistry::getInstance();
		$extensionPrefix = '.min';
		$pathStart = $info->Site->getLink('javascript');
						
		foreach($plugin as $file)
		{		
			$path = $pathStart . 'jquery.' . $file . $extensionPrefix . '.js';
			$this->addJSInclude($path);
		}
	}
	
	public function makeDisplay()
	{
		$config = Config::getInstance();

		if(!is_a($this->display, 'DisplayMaker'))
		{
			$this->setTemplate();
		}

		$this->runtimeProcessTemplate();
		$display = $this->display;
		$tags = $display->tagsUsed(false);
		
		// This is a list of all of the 'array' items that need to be cycled through and added as a single items
		$groups = array('script', 'scriptStartup', 'meta', 'jsIncludes', 'css');
		
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
		$theme = new Theme($this->theme);
		$js = $theme->jsUrl('defaults', 'jquery');
		$info = InfoRegistry::getInstance();
		$pathStart = $info->Site->getLink('javascript');

		
		$this->addJSInclude($pathStart . 'bento.defaults.js');
		
		if($js)
			$this->addJSInclude($js);
		
		
		
		
		
		
		$this->display;
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
		$this->addJQueryInclude(array('1_2_6', 'ui-1_6b', 'metadata', 'demensions'));

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
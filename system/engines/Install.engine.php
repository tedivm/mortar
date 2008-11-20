<?php

class InstallEngine // extends Engine 
{
	public $engine_type = 'Install';
	protected $content;
	protected $mainAction;
	
	
	public function __construct()
	{
		
	}
	
	public function runModule()
	{
		$config = Config::getInstance();
		
		$path = $config['path']['packages'] . 'BentoBase/actions/Install.class.php';
		
		// Load Action Class
		try {

			$classname = 'BentoBaseActionInstall'; 
			$runMethod = 'viewInstall';
			
			
			if(!class_exists($classname, false))
			{
				if(!file_exists($path))
					throw new ResourceNotFoundError('Unable to load action file: ' . $path);
				include($path);	
			}
							
			$this->mainAction = new $classname();
		
			$this->content = $this->mainAction->$runMethod();
			
		}catch (ResourceNotFoundError $e) {	
				throw $e;
				
		}catch (Exception $e){
			throw $e;
		}	
		
	}
	
	
	
	
	public function display()
	{
		//$displayMaker = new DisplayMaker();
		$config = Config::getInstance();

		$contentArea = file_get_contents($config['path']['base'] . 'modules/BentoBase/templates/admin_display_content_area.html');
		$contentArea = file_get_contents($config['path']['base'] . 'data/themes/admin/adminContent.html');
		$content = new DisplayMaker();
		
		$content->set_display_template($contentArea);

		$content->addContent('content', $this->content);
		$content->addContent('title', 'BentoBase Installation');
		$content->addContent('subtitle', $this->mainAction->subtitle);
		
		$theme = file_get_contents($config['path']['base'] . 'data/themes/admin/install.html');
		$display = new DisplayMaker();
		$display->set_display_template($theme);
		
		$baseUrl = 'http://' . $_SERVER['SERVER_NAME'] . substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], DISPATCHER));
		$themeUrl = $baseUrl .  'data/themes/admin/';
		$javascriptUrl = $baseUrl .  'javascript/';
		
		$display->addContent('theme_path', $themeUrl);
		$display->addContent('javascript_path', $javascriptUrl);
		$display->addContent('content', $content->make_display());
		
		return $display->make_display();
	}
	
	public function finish()
	{
		
	}
}


?>
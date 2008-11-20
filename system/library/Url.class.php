<?php

class Url
{
	protected $attributes = array();
	
	public function fromString()
	{
		
	}
	
	public function __toString()
	{
		$config = Config::getInstance();
		$get = get::getInstance();
		$site = ActiveSite::get_instance();
		
		$attributes = $this->attributes;
		$urlString = $site->currentLink;


		//if module is an integer, load the moduleinfo class
		if(is_int($attributes['module']))
		{
			$moduleInfo = new ModuleInfo($attributes['module']);
			$attributes['module'] = $moduleInfo['Name'];
				
		}
					
		
		if($config['modRewrite'] && $attributes['engine'] == 'html')
		{
			// ModRewrite check 
			unset($attributes['engine']);
			
			$urlString .= $moduleInfo['Name'] . '/';
			
			$parameters = new DisplayMaker();
			
			if(!$parameters->load_template('UrlPath', $moduleInfo['Package']))
				$parameters->set_display_template('{# action #}/{# id #}/');
			
			$urlTags = $parameters->tagsUsed();
			
			foreach($urlTags as $tag)
			{
				$string = (isset($attributes[$tag])) ? $attributes[$tag] : '_';
				
				if(isset($attributes[$tag]))
				{
					$parameters->add_content($tag, htmlentities($attributes['tag']));
					unset($attributes[$tag]);
				}else{
					$parameters->add_content($tag, '_');
				}
				
			}
			
			$urlString .= $parameters->make_display(true);
			$urlString = rtrim(trim($urlString), '_/');
			
			
		}else{
			
			$urlString .= 'index.php';
			
		}
		
		
		if(count($attributes) > 0)
		{
			
			$urlString .= '?';
			
			foreach($attributes as $name => $value)
			{
				$urlString .= htmlentities($name) . '=' . htmlentities($value) . '&';
			}		
		}
		
		$urlString = rtrim(trim($urlString), '?&');
		
		return $urlString;
	}
	
	public function property($name, $value = false)
	{
		if($value !== false)
		{
			$this->attributes[$name] = $value;
			return $this;
		}else{
			return $this->attributes[$name];
		}
		
	}
	
	public function __get($name)
	{
		return $this->attributes[$name];
	}
	
	public function __set($name, $value)
	{
		$this->attributes[$name] = $value;
	}
	
	public function __isset($name)
	{
		return isset($this->attributes[$name]);
	}
	
	public function __unset($name)
	{
		unset($this->attributes[$name]);
	}
	
}
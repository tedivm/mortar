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
		$site = ActiveSite::getInstance();

		$info = InfoRegistry::getInstance();

		$attributes = $this->attributes;
		$urlString = $site->currentLink;


		//if module is an integer, load the moduleinfo class
		if(is_numeric($attributes['module']))
		{
			$moduleInfo = new ModuleInfo($attributes['module'], 'moduleId');
			$attributes['moduleId'] = $attributes['module'];
			unset($attributes['module']);
		}


		if($info->Configuration['url']['modRewrite'] && isset($moduleInfo))
		{
			// ModRewrite check
		//	unset($attributes['engine']);
			unset($attributes['moduleId']);

			$location = new Location($moduleInfo['locationId']);
			$pathString = '';

			while($parent = $location->getParent())
			{
				if($parent->getResource() == 'site')
					break;

				$pathString = str_replace(' ', '_', $parent->getName()) . '/' . $pathString;

				$location = $parent;
			}

			$urlString .= $pathString . str_replace(' ', '_', $moduleInfo['Name']) . '/';

			$parameters = new DisplayMaker();

			if(!$parameters->load_template('UrlPath', $moduleInfo['Package']))
			{
				$parameters->set_display_template('{# action #}/{# id #}/');
			}

			$urlTags = $parameters->tagsUsed();

			foreach($urlTags as $tag)
			{

				$string = (isset($attributes[$tag])) ? $attributes[$tag] : '_';


				if(isset($attributes[$tag]))
				{
					$parameters->add_content($tag, htmlentities($attributes[$tag]));
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

		$urlString = rtrim(trim($urlString), '?& ');

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

	public function getLink($text)
	{
		$link = new HtmlObject('a');
		$link->property('href' , (string) $this);
		$link->wrapAround($text);
		return $link;
	}


	public function __get($name)
	{
		return $this->attributes[$name];
	}

	public function __set($name, $value)
	{
		if($name == 'id')
			$value = str_replace(' ', '_', $value);

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
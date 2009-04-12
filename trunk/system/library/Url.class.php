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
		$site = ActiveSite::getSite();
		$info = InfoRegistry::getInstance();

		$attributes = $this->attributes;
		ksort($attributes);
		$urlString = $site->currentLink;

		if(isset($info->Configuration['url']['modRewrite']) && !$info->Configuration['url']['modRewrite'])
		{
			$urlString .= 'index.php?p=';
		}

		// make path

		if(isset($attributes['format']) && strtolower($attributes['format']) == 'admin')
		{
			$urlString .= 'admin/';
			unset($attributes['format']);
		}

		if(isset($attributes['rest']) && $attributes['rest'])
		{
			$urlString .= 'rest/';
			unset($attributes['rest']);
		}

		if(isset($attributes['module']))
		{
			$urlString .= 'module/' . $attributes['module'] . '/';
			unset($attributes['module']);
			if(isset($attributes['action']))
			{
				$urlString .= $attributes['action'] . '/';
				unset($attributes['action']);
			}
		}

		if(isset($attributes['location']))
		{
			if($attributes['location'] instanceof Location)
			{
				$location = $attributes['location'];
				unset($attributes['location']);
			}elseif(is_numeric($attributes['location'])){
				$location = new Location($attributes['location']);
				unset($attributes['location']);
			}
		}elseif(isset($attributes['locationId']) && is_numeric($attributes['locationId'])){
			$location = new Location($attributes['location']);
			unset($attributes['locationId']);
		}

		if(isset($location))
		{
			$tempLoc = $location;
			while($tempLoc->getType() != 'Site')
			{
				$urlString .= str_replace(' ', '-', $tempLoc->getName()) . '/';

				if(!$parent = $tempLoc->getParent())
					break;

				$tempLoc = $parent;
			}


			if($site->getId() == $location->getSite())
			{
				$urlString = ActiveSite::getLink() . $urlString;
			}else{
				$site = ModelRegistry::loadModel('Site', $location->getSite());
				$urlString = $site['primaryUrl'] . $urlString;
			}

			$handler = ModelRegistry::getHandler($location->getType());

			$pathCache = new Cache($location->getType(), $handler['module'], 'url', 'pathCache');
			$pathTemplate = $pathCache->getData();

			if(!$pathCache->cacheReturned)
			{
				$pathCacheDisplay = new DisplayMaker();
				if(!$pathCacheDisplay->loadTemplate('UrlPath', $handler['module']))
					if(!$pathCacheDisplay->loadTemplate('UrlPath' . $location->getType() , $handler['module']))
						$pathCacheDisplay->set_display_template('{# action #}/{# id #}/');

				$pathTemplate = $pathCacheDisplay->makeDisplay(false);
				$pathCache->storeData($pathTemplate);
			}


			$parameters = new DisplayMaker();
			$parameters->setDisplayTemplate($pathTemplate);
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
		}


		$site = (isset($location)) ? ModelRegistry::loadModel('Site', $location->getSite()) : ActiveSite::getSite();

		if(isset($attributes['ssl']))
		{
			if($attributes['ssl'] == true)
				$ssl = true;

			unset($attributes['ssl']);
		}elseif(isset($_SERVER['HTTPS'])){
			$ssl = true;
		}else{
			$ssl = false;
		}


		$urlString = $site->getUrl($ssl) . $urlString;

		if(count($attributes) > 0)
		{
			$urlString .= '?';

			foreach($attributes as $name => $value)
				$urlString .= urlencode($name) . '=' . urlencode($value) . '&';
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

	public function attributesFromArray($attributes)
	{
		foreach($attributes as $name => $value)
			$this->__set($name, $value);
	}

	public function __get($name)
	{
		return $this->attributes[$name];
	}

	public function __set($name, $value)
	{
		if($value instanceof Location)
		{
			$name = 'locationId';
			$value = $value->getId();
		}

		if(!is_scalar($value))
			return false;

		if($name == 'id')
			$value = str_replace(' ', '-', $value);

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

	// for the sake of caching
	public function __sleep()
	{
		return array('attributes');
	}
}

?>
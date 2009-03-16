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
		ksort($attributes);
		$urlString = $site->currentLink;


		if(!$info->Configuration['url']['modRewrite'])
		{
			$urlString .= 'index.php?';

			if(isset($attributes['locationId']))
				$urlString .= 'p=';
		}

		$location = new Location($attributes['locationId']);
		unset($attributes['locationId']);
		$pathString = '';

		while($parent = $location->getParent())
		{
			if($parent->getResource() == 'site')
				break;

			$pathString = str_replace(' ', '-', $parent->getName()) . '/' . $pathString;

			$location = $parent;
		}

		$modelInfo = $location->getResource(true);


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

}

?>
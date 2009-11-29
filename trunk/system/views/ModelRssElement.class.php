<?php

class ViewModelRssElement
{
	static public function getDisplay(Model $model, SimpleXMLElement $parentXml)
	{
		$xmlItem = simplexml_load_string('<item></item>');

		if(isset($model['title']))
			$parentXml->addChild('title', $model['title']);

		$url = $model->getUrl();
		$parentXml->addChild('link', (string) $url);

		if(isset($model['content']))
			$parentXml->addChild('description', $model['content']);

		if($model instanceof LocationModel)
		{
			$location = $model->getLocation();

			$publishedDate = $location->getPublishDate();
			$parentXml->addChild('pubDate', gmdate('D, d M y H:i:s T', $publishedDate));

			if($author = $location->getOwner())
				$parentXml->addChild('author', $author['name']);

		}else{

		}

		$id = $model->getId();
		$type = $model->getType();

		$guid = md5($id . $type);

		$parentXml->addChild('guid', $guid);

		// enclosures
		//category
		//comments

		return $parentXml;
	}
}

?>
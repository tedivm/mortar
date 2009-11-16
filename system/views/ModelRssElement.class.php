<?php

class ViewModelRssElement
{
	static public function getDisplay(Model $model, SimpleXMLElement $parentXml)
	{
		$url = new Url();
		$url['model'] = $model;


		$xmlItem = simplexml_load_string('<item></item>');


		$parentXml->addChild('title', $model['title']);
		$parentXml->addChild('link', (string) $url);
		$parentXml->addChild('description', $model['content']);

		if($model instanceof LocationModel)
		{
			$location = $model->getLocation();

			$publishedDate = $location->getPublishDate();
			$parentXml->addChild('pubDate', gmdate(DATE_RFC822, $publishedDate));

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

		return $this->parentXml;
	}
}

?>
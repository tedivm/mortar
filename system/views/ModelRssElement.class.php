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
			$parentXml->addChild('pubDate', gmdate(HTTP_DATE, $publishedDate));

//			if($author = $location->getOwner())
//				$parentXml->addChild('author', $author['name']);

		}else{

		}

		$id = $model->getId();
		$type = $model->getType();

		$guid = md5($id . $type);

		$guidX = $parentXml->addChild('guid', $guid)->addAttribute('isPermaLink', 'false');

		// enclosures
		//category
		//comments

		return $parentXml;
	}
}

?>
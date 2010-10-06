<?php

class ViewModelRssElement
{
	static public function getDisplay(Model $model, SimpleXMLElement $parentXml)
	{
		$xmlItem = simplexml_load_string('<item></item>');

		if($title = $model->getDesignation())
			$parentXml->addChild('title', $title);

		$url = $model->getUrl();
		$parentXml->addChild('link', (string) $url);

		if(isset($model['content'])) {
			$entityContent = self::xmlEncode($model['content']);
			$parentXml->addChild('description', $entityContent);
		}

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

	static protected function xmlEncode($string, $trans='') { 
		$trans = (is_array($trans)) ? $trans : get_html_translation_table(HTML_ENTITIES, ENT_QUOTES); 
		foreach ($trans as $k=>$v)
			$trans[$k]= "&#".ord($k).";"; 

		return strtr($string, $trans); 
}

}

?>
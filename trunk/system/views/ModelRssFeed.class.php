<?php

class ViewModelRssFeed
{
	protected $modelList;
	protected $model;

	public function __construct($modelList, Model $baseModel = null)
	{
		if(!is_array($modelList))
			throw new TypeMismatch(array('array', $modelList));

		$this->modelList = $modelList;
	}

	public function getDisplay()
	{
		$baseModel = $this->model;

		$rssFeed = simplexml_load_string("<rss><channel></channel></rss>");
		$rssFeed->channel[0]->addChild('title', $feedTitle);
		$rssFeed->channel[0]->addChild('link', $feedUrl);
		$rssFeed->channel[0]->addChild('description', $feedContent);

		foreach($this->modelList as $model)
		{
			if(!($model instanceof Model))
			{
				// no need to through it since we'd just catch it in the loop and continue, but we want the error to be
				// logged or displayed.
				new CoreWarning('ViewModelRssFeed can not take non-model items.');
				continue;
			}

			$itemXml = $rssFeed->channel[0]->addChild($item);
			ViewModelRssElement::getDisplay($model, $itemXml);
		}


		return $rssFeed->asXML();
	}
}

?>
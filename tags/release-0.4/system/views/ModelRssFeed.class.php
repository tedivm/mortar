<?php

class ViewModelRssFeed
{
	protected $modelList;
	protected $model;
	protected $channelElements = array();

	public function __construct($modelList, Model $baseModel = null)
	{
		if(!is_array($modelList))
			throw new TypeMismatch(array('array', $modelList));

		$this->modelList = $modelList;
		$this->model = $baseModel;
	}

	public function addChannelElement($name, $value)
	{
		if($name == 'lastBuildDate')
			$value = gmdate('D, d M y H:i:s T', $value);

		$this->channelElements[$name] = $value;
	}

	public function getDisplay()
	{
		$baseModel = $this->model;


		$title = isset($baseModel['title']) ? $baseModel['title'] : $baseModel['name'];

		$url = $baseModel->getUrl();

		$url->format = 'html';
		$rssFeed = simplexml_load_string('<rss version="2.0"><channel></channel></rss>');

		//$rssFeed->channel[0]->addChild('title', $title);

		$rssFeed->channel[0]->addChild('link', (string) $url);
		$rssFeed->channel[0]->addChild('generator', PROGRAM_NAME);

		foreach($this->channelElements as $name => $value)
			$rssFeed->channel[0]->addChild($name, $value);

		//$rssFeed->channel[0]->addChild('description', $feedContent);

		foreach($this->modelList as $model)
		{
			if(!($model instanceof Model))
			{
				// no need to through it since we'd just catch it in the loop and continue, but we want the error to be
				// logged or displayed.
				new CoreWarning('ViewModelRssFeed can not take non-model items.');
				continue;
			}

			$itemXml = $rssFeed->channel[0]->addChild('item');
			ViewModelRssElement::getDisplay($model, $itemXml);
		}

		$output = $rssFeed->asXML();

		if(!(defined('CONCISE_HTML') && CONCISE_HTML == true))
			$output = preg_replace("/>\s*</",">\n<", $rssFeed->asXML());

		return $output;
	}
}

?>
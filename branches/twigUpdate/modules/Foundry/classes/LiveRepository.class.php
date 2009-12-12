<?php

class LiveRepository
{
	protected $id;
	protected $repoModel;

	public function __construct($repositoryId)
	{
		$this->id = $repositoryId;
		$this->repoModel = ModelRegistry::loadModel('Repository', $this->id);
	}

	protected function getUpdates()
	{
		// http/s/://path.to.site/install/resources/repositories/name
		$url = $this->repoModel['url'];

		// add last modified

		if(isset($This->repoModel['lastupdate']))
			$url .= '?lastUpdate=' . $this->repoModel['lastupdate'];

		$serverData = file_get_contents($url);

		$xml = new SimpleXMLElement($serverData);

		$channel['title'] = $xml->channel->title;
		$channel['link'] = $xml->channel->link;
		$channel['description'] = $xml->channel->description;
		$channel['pubDate'] = strtotime($xml->channel->pubDate);
		$channel['generator'] = $xml->channel->generator;

		foreach($xml->channel->item as $item)
		{

			if(!$packageDetails = $this->processItem($item))
			{
				CoreInfo('Unable to process repository item.');
				continue;
			}

			if(!$this->validatePackageOwner($item['title']))
				continue;

			$this->clearPackage($packageName);
			$this->addPackage($repository, $packageName, $packageDetails);
		}
	}

	protected function processItem($itemXml)
	{
		$item['title'] = $itemXml->title;
		$item['link'] = $itemXml->link;
		$item['description'] = $itemXml->description;
		$item['author'] = $itemXml->author;
		$item['pubDate'] = strtotime($itemXml->pubDate);

		foreach($itemXml->category as $category)
			$item['categories'][] = $category;

//		$item['comments'] = $itemXml->comments;

		return $item;
	}

	protected function validatePackageOwner($package)
	{
		$stmt = DatabaseConnection::getStatement('default_read_only');

		$stmt->prepare('SELECT * FROM ');

	}

	protected function clearPackage($packageName)
	{

	}

	protected function addPackage($packageDetails)
	{

	}

}

?>
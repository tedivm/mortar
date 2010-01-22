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

		if(isset($This->repoModel['lastupdate']))
			$url .= '?lastUpdate=' . $this->repoModel['lastupdate'];

		libxml_use_internal_errors(true);
		if(!($xml = simplexml_load_file($url)))
		{


			// zomg error!

			// at some point we should do something useful here.
			libxml_clear_errors();
			throw new CoreInfo('Received invalid XML from the server while updating repository');
		}


		$this->repoModel['name'] = $xml->channel->title;
		$this->repoModel['website'] = $xml->channel->link;
		$this->repoModel['description'] = $xml->channel->description;
		$this->repoModel['lastupdated'] = strtotime($xml->channel->pubDate);

		if(isset($xml->channel->generator))
			$this->repoModel['serverSoftware'] = $xml->channel->generator;

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
		$stmt->prepare('SELECT * FROM foundryRepositories, foundryRepoHasPackages, foundryPackages
						WHERE foundryRepositories.id = foundryRepoHasPackages.repoId
							AND foundryRepoHasPackages.packageId = foundryPackages.id
							AND foundryPackage.name = ?
							AND foundryRepositories.priority < ?');
		$stmt->bindAndExecute('??', $package, $this->repoModel['priority']);

		return !((bool) $stmt->num_rows);
	}

	protected function clearPackage($packageName)
	{

	}

	protected function addPackage($packageDetails)
	{
		// add package
		// add dependencies
		// add conflicts
		// add link to repo
	}

}

?>
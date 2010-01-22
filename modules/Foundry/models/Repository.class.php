<?php

class FoundryModelRepository extends ModelBase
{
	static public $type = 'Repository';
	protected $table = array('foundryRepositories', 'foundryRepositoriesInformation');


	protected function getUpdates()
	{
		// http/s/://path.to.site/install/resources/repositories/name
		$url = $this->repoModel['url'];

		if(isset($This->repoModel['lastupdate']))
			$url .= '?lastUpdate=' . $this->repoModel['lastupdate'];

		// at some point we'll make a more indepth downloader and add some verification to the feed

		libxml_use_internal_errors(true);
		if(!($xml = simplexml_load_file($url)))
		{

			// zomg error!

			// at some point we should do something useful here.
			libxml_clear_errors();
			throw new CoreInfo('Received invalid XML from the server while updating repository');
		}

		$this->offsetSet('name', $xml->channel->title);
		$this->offsetSet('website', $xml->channel->link);
		$this->offsetSet('description', $xml->channel->description);
		$this->offsetSet('lastupdated', strtotime($xml->channel->pubDate));

		if(isset($xml->channel->generator))
			$this->offsetSet('serverSoftware', $xml->channel->generator);

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
		$stmt->bindAndExecute('??', $package, $this->getOffset('priority'));

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
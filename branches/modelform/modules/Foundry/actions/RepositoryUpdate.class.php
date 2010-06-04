<?php

class FoundryActionRepositoryUpdate extends ModelActionBase
{
	protected $isFullRebuild = false;

	protected function logic()
	{
		// ignore the cache for this
		CacheControl::disableCache();


		// check some flag to see if its a full wipe
		// $this->isFullRebuild = true;


		$repositories = $this->getRepositories();

		foreach($repositories as $index => $repository)
		{
			$packageList = $this->getPackageList($repository);
			$higherPriorityRepositories = array_slice($repositories, $index + 1, null, true);

			foreach($packageList as $packageName => $packageDetails)
			{
				if(!$this->validatePackageOwner($higherPriorityRepositories, $repository, $packageName))
					continue;

				$this->clearPackage($packageName);
				$this->addPackage($repository, $packageName, $packageDetails);
			}
		}

		// Renable Caching
		CacheControl::disableCache(false);

		// Clear system cache
		CacheControl::clearCache('system');
		CacheControl::clearCache('modules');

	}

	protected function getRepositories()
	{
		// load repositories in order of priority
	}

	protected function getPackageList($repository)
	{
		// make call to server
		// process results into array
		return array();
	}

	protected function validatePackage($repositories, $repository, $packageName)
	{
		// make sure package isn't owned by another, higher priority, repository
		return true;
	}

	protected function deletePackage($packageName)
	{
		// delete dependencies

		// delete repository links

		// delete package
	}

	protected function addPackage($repository, $packageName, $packageDetails)
	{
		$repositoryId = $repository->getId();




	}

}

?>
<?php

class FoundryBuildQueue
{

	/**
	 * This array gets filled with packages that are currently processing dependencies but have not yet been added to
	 * the queue. This is used to prevent loops from forming.
	 *
	 * @var array
	 */
	static $activePackages = array();

	static public function getBuildQueue($packages, $levels)
	{
		if(!is_array($packages))
			$packages = array($packages);

		if(!is_array($levels))
			$levels = array($levels);

		$queue = array();
		foreach($packages as $package)
			foreach($levels as $requirementLevel)
				$queue = self::buildForPackage($package, $requirementLevel, $queue);

		return $queue;
	}

	static public function buildForPackage($package, $requirementLevel = 'Required', $queue = null, $callingPackage = null)
	{
		try {

			$packagesInQueue = array_keys($queue);

			// Check to prevent loops and to see if the package is already in queue
			if(in_array($package, self::$activePackages) || in_array($package, $packagesInQueue))
					return true;

			$packageRequirements = new QuarryPackage($package);

			// check to see if the package is already installed, and then see if it matches the needed versions
			$packageInfo = new QuarryPackage($package);
			if(PackageInfo::checkModuleStatus($package) == 'installed')
			{
				if(!isset($callingPackage))
					return true;

				$packageInfo = new PackageInfo($package);
				$version = $packageInfo->getVersion();

				if($callingPackage->checkVersion($package, $version))
					return $queue;
			}

			/*
				Identify a version to install.
			*/


			/*
				Run through dependencies
			*/

			$dependencies = $packageRequirements->getDependencies($requirementLevel);

			if($dependencies && count($dependencies) > 0)
			{
				// before descending into the dependencies we add the current package to the active list so we don't
				// descend into it and create a loop.
				self::$activePackages[] = $package;


				// If a dependency is not in the queue recursively add it
				foreach($dependencies as $requiredPackage)
					if(!in_array($requiredPackage, $queue))
						$queue = self::buildForPackage($requiredPackage, $requirementLevel, $queue, $packageRequirements);

				// Since the $queue has been adjusted for dependencies we need to rebuild the packagesInQueue list
				$packagesInQueue = array_keys($queue);

				// Calculate the position the current package should be installed at in order to have its dependencies
				// installed first.
				$highestDependency = count($queue);
				foreach($dependencies as $requiredPackage)
				{
					// If there is a dependency we were unable to satisfy throw an exception.
					if(!($packageLocation = array_search($requiredPackage, $packagesInQueue))
						&& PackageInfo::checkModuleStatus($requiredPackage) != 'installed')
							throw new QuarryBuildQueueMissingPackage('Unable to load package' . $requiredPackage);

					// The queue is processed from the bottom up so if the current dependency is closer to the top
					// of the queue we need to adjust our 'highest dependency' position.
					if($packageLocation < $highestDependency)
						$highestDependency = $packageLocation;
				}

				unset(self::$activePackages[array_search($package, self::$activePackages)]);
				return self::addToQueue($package, $packageOptions, $queue, $highestDependency);
			}else{
				return self::addToQueue($package, $packageOptions, $queue);
			}

		}catch(Exception $e){

		}
	}


	static protected function addToQueue($package, $packageOptions, &$queue, $position = null)
	{

		if(!isset($position) || $position > count($queue) - 1)
		{
			$queue[$package] = $packageOptions;
			return $queue;
		}elseif($position == 0){
			$queue = array_merge(array($package => $packageOptions), $queue);
			return $queue;
		}else{

			$firstPiece = array_slice($queue, 0, $position, true);
			$endPiece = array_slice($queue, $position, null, true);
			$queue = array_merge($firstPiece, array($package => $packageOptions), $endPiece);
			return $queue;
		}
	}
}

class QuarryBuildQueueError extends CoreError {}
class QuarryBuildQueueMissingPackage extends QuarryBuildQueueError {}
?>
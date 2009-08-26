<?php

class QuarryPackageRequirements
{

	public function getDependencies()
	{

	}

	public function getAllowedVersions($package)
	{
		$dependencies = $this->getDependencies();

		if(!in_array($package, $dependencies))
			return true;
	}


	public function getDependencyInfo($package, $info)
	{

	}


	public function checkVersion($package, Version $version)
	{
		$allowedVersions = $this->getDependencyInfo($package, 'allowedVersions');
		foreach($allowedVersions as $range)
		{
			if($version->inRange(isset($range['minimum']) ? $range['minumum'] : null,
								isset($range['maximum']) ? $range['maximum'] : null))
			{
					return true;
			}
		}
		return false;
	}



}
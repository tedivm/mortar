<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Module
 */

/**
 * This class allows developers check the requirements of their modules against the installed system and php
 * environment.
 *
 * @package System
 * @subpackage Module
 */
class RequirementsCheck
{
	/**
	 * The minimum acceptable php version. This should be a standard php version string (or null)
	 *
	 * @var string
	 */
	protected $minVersion;

	/**
	 * The maximum acceptable php version. This should be a standard php version string (or null)
	 *
	 * @var string
	 */
	protected $maxVersion;

	/**
	 * These are the extensions required by the loaded modules.
	 *
	 * @var array
	 */
	protected $requiredExtensions = array();

	/**
	 * These are the extensions recommended for the loaded modules.
	 *
	 * @var array
	 */
	protected $recommendedExtensions = array();

	/**
	 * These are extensions that the module author knows can be loaded but aren't really needed for most installations
	 *
	 * @var array
	 */
	protected $optionalExtensions = array();

	/**
	 * This function adds to the list of modules to check the requirements for.
	 *
	 * @param string|int $module
	 */
	public function addModule($module)
	{
		$info = new PackageInfo($module);
		$requirements = $info->getPhpRequirements();

		if(isset($requirements['version']))
		{
			if(isset($requirements['version']['min']))
			{
				if(!isset($this->minVersion)
					|| version_compare($requirements['version']['min'], $this->minVersion, '>'))
						$this->minVersion = $requirements['version']['min'];
			}

			if(isset($requirements['version']['max']))
			{
				if(!isset($this->maxVersion)
					|| version_compare($requirements['version']['max'], $this->maxVersion, '<'))
						$this->maxVersion = $requirements['version']['max'];
			}
		}

		$this->mergeExtensions('required', $requirements);
		$this->mergeExtensions('recommended', $requirements);
		$this->mergeExtensions('optional', $requirements);


		$this->removeExtensions('recommended', $this->requiredExtensions);

		$otherExtensions = array_merge($this->requiredExtensions, $this->recommendedExtensions);
		$this->removeExtensions('optional', $otherExtensions);

		return;
	}

	/**
	 * Returns an array of extensions.
	 *
	 * @param string $type required, recommended or optional
	 * @return array
	 */
	public function getExtensions($type = 'required')
	{
		$propertyName = $type . 'Extensions';
		return isset($this->$propertyName) ? $this->$propertyName : array();
	}

	/**
	 * Returns an array containing the min and max versions the system requires.
	 *
	 * @return array
	 */
	public function getRequiredVersion()
	{
		$version = array();
		if(isset($this->maxVersion))
			$version['max'] = $this->maxVersion;

		if(isset($this->minVersion))
			$version['min'] = $this->minVersion;

		return $version;
	}

	/**
	 * This function checks against all of the requirements. Optional and recommended packages are only included if
	 * passed true;
	 *
	 * @param bool $includeOptional
	 * @return bool
	 */
	public function checkRequirements($includeOptional = false)
	{
		return ($this->checkPhpMinimumVersion()
					&& $this->checkPhpMaximumVersion()
					&& $this->checkRequiredExtensions() === true)
					&& ($includeOptional == false ||
							($this->checkRecommendedExtensions()
							&& $this->checkOptionalExtensions()));
	}

	/**
	 * This function checks the php minimum version against the system requirements.
	 *
	 * @return bool
	 */
	public function checkPhpMinimumVersion()
	{
		if(isset($this->minVersion) && version_compare($this->minVersion, PHP_VERSION, '>'))
			return false;
		return true;
	}

	/**
	 * This function checks the php maximum version against the system requirements.
	 *
	 * @return bool
	 */
	public function checkPhpMaximumVersion()
	{
		if(isset($this->maxVersion) && version_compare($this->maxVersion, PHP_VERSION, '<'))
			return false;
		return true;
	}

	/**
	 * This function checks to see that all the required extensions are available in the system. If they are not, this
	 * function returns an array of the missing extensions.
	 *
	 * @return bool|array
	 */
	public function checkRequiredExtensions()
	{
		return $this->checkExtensions('required');
	}

	/**
	 * This function checks to see that all the recommended extensions are available in the system. If they are not,
	 * this function returns an array of the missing extensions.
	 *
	 * @return bool|array
	 */
	public function checkRecommendedExtensions()
	{
		return $this->checkExtensions('recommended');
	}

	/**
	 * This function checks to see that all the optional extensions are available in the system. If they are not,
	 * this function returns an array of the missing extensions.
	 *
	 * @return bool|array
	 */
	public function checkOptionalExtensions()
	{
		return $this->checkExtensions('optional');
	}

	/**
	 * This function is used by the public extension checking functions to do the real work of checking extensions,
	 * since the majority of the work is the same regardless of the 'type' or requirement level of the extension.
	 *
	 * @param string $extensionType
	 * @return bool|array
	 */
	protected function checkExtensions($extensionType)
	{
		$propertyName = $extensionType . 'Extensions';

		if(!property_exists($this, $propertyName))
			throw new RequirementsCheckError('Unexpected extension type ' . $extensionType);

		$missingExtensions = array();

		foreach($this->$propertyName as $extension)
		{
			if(!phpInfo::isLoaded($extension))
				$missingExtensions[] = $extension;
		}

		return (count($missingExtensions) > 0) ? $missingExtensions : true;
	}

	/**
	 * This function merges new extensions into one of the extension lists while preventing duplicates.
	 *
	 * @param string $type
	 * @param array $requirements This should be the requirements array returned by PackageInfo->getPhpRequirements()
	 */
	protected function mergeExtensions($type, $requirements)
	{
		if(isset($requirements['extensions'][$type]) && is_array($requirements['extensions'][$type]))
		{
			$propertyName = $type . 'Extensions';
			$extensions = array_merge($this->$propertyName, $requirements['extensions'][$type]);
			$this->$propertyName = array_unique($extensions);
		}
	}

	/**
	 * This function purges extensions from  list. Its primarily used to keep an extension from being listed in multiple
	 * requirement levels (so things can't be both optional and required).
	 *
	 * @param string $type
	 * @param array $extensions
	 */
	protected function removeExtensions($type, $extensions)
	{
		$propertyName = $type . 'Extensions';

		if($intersectingExtensions = array_intersect($extensions, $this->$propertyName))
		{
			foreach($intersectingExtensions as $extension)
			{
				$key = array_search($extension, $this->$propertyName);
				unset($this->{$propertyName}[$key]);
			}
		}
	}

}

class RequirementsCheckError extends CoreError {}
?>
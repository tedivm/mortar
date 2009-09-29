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

		if(isset($requirements['extensions']['required']) && is_array($requirements['extensions']['required']))
		{
			$extensions = array_merge($this->requiredExtensions, $requirements['extensions']['required']);
			$this->requiredExtensions = array_unique($extensions);
		}

		if(isset($requirements['extensions']['recommended']) && is_array($requirements['extensions']['recommended']))
		{
			$extensions = array_merge($this->recommendedExtensions, $requirements['extensions']['recommended']);
			$this->recommendedExtensions = array_unique($extensions);
		}

		if(isset($requirements['extensions']['optional']) && is_array($requirements['extensions']['optional']))
		{
			$extensions = array_merge($this->optionalExtensions, $requirements['extensions']['optional']);
			$this->optionalExtensions = array_unique($extensions);
		}
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
			throw new CoreError('Unexpected extension type ' . $extensionType);

		$missingExtensions = array();

		foreach($this->$propertyName as $extension)
		{
			if(!phpInfo::isLoaded($extension))
				$missingExtensions[] = $extension;
		}

		return (count($missingExtensions) > 0) ? $missingExtensions : true;
	}
}

?>
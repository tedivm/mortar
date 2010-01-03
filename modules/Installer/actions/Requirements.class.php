<?php

class InstallerActionRequirements implements ActionInterface //extends Action
{

	public $adminSettings = array('headerTitle' => 'Install Requirements');
	protected $dbConnection;
	protected $ioHandler;
	public $subtitle = '';

	/**
	 * Loaded with all of the modules from the installation profile
	 *
	 * @var RequirementsCheck
	 */
	protected $requirementsCheck;
	protected $tests;

	public function __construct($identifier, $handler)
	{
		$this->ioHandler = $handler;
	}

	public function start()
	{
		if(INSTALLMODE !== true)
			exit();

		$input = Input::getInput();
		$query = Query::getQuery();

		$config = Config::getInstance();

		$pathToInstaller = $config['path']['modules'] . 'Installer/';
		if(!class_exists('InstallerProfileReader'))
		{
			$pathToProfiler = $pathToInstaller . 'classes/ProfileReader.class.php';
			include($pathToProfiler);
		}

		InstallerProfileReader::$path = $config['path']['base'] . 'data/installation/';
		$profile = new InstallerProfileReader();

		if(file_exists(InstallerProfileReader::$path . 'custom.xml'))
		{
			$profile->loadProfile('custom');
		}elseif(file_exists(InstallerProfileReader::$path . 'base.xml')){
			$profile->loadProfile('base');
		}else{
			throw new CoreError('Unable to load installation profile.');
		}

		$requirementsCheck = new RequirementsCheck();
		$this->requirementsCheck = $requirementsCheck;
		$modulesToInstall = $profile->getModules();
		foreach($modulesToInstall as $module => $moduleInfo)
			$requirementsCheck->addModule($module);

		$tests = array();
		$tests['version']['min'] = $requirementsCheck->checkPhpMinimumVersion();
		$tests['version']['max'] = $requirementsCheck->checkPhpMaximumVersion();
		$tests['version']['test'] = ($tests['version']['min'] && $tests['version']['max']);

		$extensionTypes = array('required', 'recommended', 'optional');

		foreach($extensionTypes as $extensionType)
		{
			$extensions = $requirementsCheck->getExtensions($extensionType);
			foreach($extensions as $extension)
				$tests['extensions'][$extensionType][$extension] = phpInfo::isLoaded($extension);
		}

		$this->tests = $tests;
	}

	public function viewAdmin($page)
	{
		$theme = $page->getTheme();
		$iconset = $theme->getIconset();

		$successIcon = $iconset->getIcon('success');
		$failureIcon = $iconset->getIcon('failure');

		$jsMin = $theme->getMinifier('js');
		$cssMin = $theme->getMinifier('css');
		$css = '<style type="text/css">' . $cssMin->getBaseString() . '</style>';
		$page->addHeaderContent($css);

		$version = $this->requirementsCheck->getRequiredVersion();

		$versionTable = new Table('phpversion');

		if(isset($version['min']))
		{
			$versionTable->newRow();
			$versionTable->addField('label', 'Minimum Version:');
			$versionTable->addField('version', $version['min']);
		}

		if(isset($version['max']))
		{
			$versionTable->newRow();
			$versionTable->addField('label', 'Maximum Version:');
			$versionTable->addField('version', $version['max']);
		}

		$versionTable->newRow();
		$versionTable->addField('label', 'PHP Version:');
		$versionTable->addField('version', PHP_VERSION);
		$class = ($this->tests['version']['test']) ? 'test_passed' : 'test_failed';
		$resultImage = ($this->tests['version']['test']) ? $successIcon : $failureIcon;
		$versionTable->addField('result', $resultImage);
		$versionTable->addRowClass($class);

		$output = $versionTable->makeHtml();

		$extensionsTable = new Table('extensions_test');
		$extensionsTable->addColumnLabel('Extension', 'Status');

		$tested = false;
		foreach($this->tests['extensions'] as $extensionType => $extensionType)
		{
			if(!isset($this->tests['extensions'][$extensionType]))
				continue;

			foreach($this->tests['extensions'][$extensionType] as $extensionName => $extensionStatus)
			{
				$tested = true;
				$extensionsTable->newRow();
				$extensionsTable->addField('extension', $extensionName);
				$extensionsTable->addField('requirementLevel', $extensionType);

				$extensionsTable->addRowClass('requirement_' . $extensionType);
				if($extensionStatus)
				{
					$extensionsTable->addField('status', 'Installed');
					$extensionsTable->addRowClass('test_passed');
					$extensionsTable->addField('result', $successIcon);

				}else{
					$extensionsTable->addField('status', 'Not Found');
					$extensionsTable->addRowClass('test_failed');
					$extensionsTable->addField('result', $failureIcon);
				}



			}
		}

		if($tested)
			$output .= $extensionsTable->makeHtml();


		return $output;

	}

	public function checkAuth($action = NULL)
	{
		return true;
	}

}

?>
<?php

class InstallerActionInstall extends ActionBase
{

	public static $settings = array( 'Base' => array('headerTitle' => 'Install Mortar') );

	protected $form = true;
	protected $error = array();
	protected $installed = false;
	protected $dbConnection;

	protected $ioHandler;

	public $subtitle = '';

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

		if(!isset($query['skipRequirements']))
		{
			$requirementsCheck = new RequirementsCheck();
			$modulesToInstall = $profile->getModules();

			foreach($modulesToInstall as $family -> $modules)
			{
				if($family == 'orphan')
					$family = null;

				foreach($modules as $module)
				{
					$packageInfo = PackageInfo::loadByName($family, $name);
					$requirementsCheck->addModule($packageInfo);
				}
			}

			$optionalValues = isset($query['skipRecommendations']);
			if(!$requirementsCheck->checkRequirements($optionalValues))
			{
				$url = new Url();
				$url->format = 'admin';
				$url->module = 'Installer';
				$url->action = 'Requirements';
				$this->ioHandler->addHeader('Location', (string) $url);
				// redirect away to the requirements page
			}
		}

		include($pathToInstaller . 'classes/InstallationForm.class.php');
		$form = new InstallerInstallationForm('Installation');
		$this->form = $form;

		if($form->wasSubmitted())
		{
			if($form->checkSubmit())
			{
				$installerPath = $pathToInstaller . 'classes/Install.class.php';
				require($installerPath);
				$installer = new InstallerInstaller($profile);

				if($installer->install())
				{
					$this->installed = true;
				}else{
					$this->error = $installer->error;
				}

			}else{
				$this->error[] = 'There was an error in your form submission, please review it and try again.';
			}

		}else{

		}

	}

	public function viewAdmin($page)
	{
		$theme = $page->getTheme();
		$jsMin = $theme->getMinifier('js');
		//$page->addScript($jsMin->getBaseString());

		$cssMin = $theme->getMinifier('css');


		$css = '<style type="text/css">' . $cssMin->getBaseString() . '</style>';


		$page->addHeaderContent($css);

		$config = Config::getInstance();
		$output = '';

		$modulePath = $config['path']['modules'] . 'Installer/';

		foreach($this->error as $errorMessage)
		{
			$output .= '<div id="error" class="error">' . $errorMessage . '</div>' . PHP_EOL;
		}

		if($this->installed)
		{
			$input = Input::getInput();
			$cookieName = str_replace(' ', '_', $input['setup_location_root_Site_name']) . '_Session';
			session_name($cookieName);
			session_set_cookie_params(0, '/', null, isset($_SERVER["HTTPS"]), true);
			session_start();
			$_SESSION['user_id'] = 1;
			$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
			$_SESSION['idExpiration'] = time() + (300);
			$_SESSION['nonce'] = md5(1 . START_TIME);

			$output .= file_get_contents($modulePath . 'templates/installationComplete.template.php');
			$this->subtitle = 'Installation Complete';
			$url = new Url();
			$url->format = 'admin';
			$this->ioHandler->addHeader('Location', (string) $url);

		}elseif($this->form){
			$output .= $this->form->getFormAs('Html');
		}

		return $output;
	}

	public function checkAuth($action = NULL)
	{
		return true;
	}

}

?>
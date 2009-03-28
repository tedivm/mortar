<?php

class BentoBaseActionInstall implements ActionInterface //extends Action
{

	public $AdminSettings = array('headerTitle' => 'Installer');

	protected $form = true;
	protected $error = array();
	protected $installed = false;
	protected $dbConnection;
	public $subtitle = '';

	public function __construct($identifier, $handler)
	{

	}

	public function start()
	{

		if(INSTALLMODE !== true)
			exit();

		$input = Input::getInput();
		$config = Config::getInstance();

		$modulePath = $config['path']['modules'] . 'BentoBase/';

		include($modulePath . 'classes/InstallationForm.class.php');
		$form = new InstallationForm('Installation');
		$this->form = $form;

		if($form->wasSubmitted())
		{
			if($form->checkSubmit())
			{
				$installerPath = $modulePath . 'classes/Install.class.php';
				require($installerPath);
				$installer = new BentoBaseInstaller();

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

	public function viewAdmin()
	{
		$config = Config::getInstance();
		$output = '';

		$modulePath = $config['path']['modules'] . 'BentoBase/';

		foreach($this->error as $errorMessage)
		{
			$output .= '<div id="error" class="error">' . $errorMessage . '</div>' . PHP_EOL;
		}

		if($this->installed)
		{
			$output .= file_get_contents($modulePath . 'templates/installationComplete.template.php');
			$this->subtitle = 'Installation Complete';
		}elseif($this->form){

			$output .= $this->form->makeDisplay();
		}

		return $output;
	}

	public function checkAuth($action = NULL)
	{
		return true;
	}

}

?>
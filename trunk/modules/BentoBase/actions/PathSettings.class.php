<?php

class BentoBaseActionPathSettings extends PackageAction  
{
	static $requiredPermission = 'System';
	
	public $AdminSettings = array('linkLabel' => 'Path Settings',
									'linkTab' => 'System',
									'headerTitle' => 'Path Settings',
									'linkContainer' => 'Configuration');
								
	protected $formSuccessful = false;
	protected $form;
								
	public function logic()
	{
		$info = InfoRegistry::getInstance();
		
		$configIni = new IniFile($info->Configuration['path']['config'] . 'configuration.php');
		
		$form = new Form('SystemSettings');
		$this->form = $form;
		$form->changeSection('Paths')->
				createInput('base')->
					setLabel('Installation Base')->
					property('value', $configIni->get('path', 'base'))->
				getForm()->
				
				createInput('theme')->
					setLabel('Theme')->
					property('value', $configIni->get('path', 'theme'))->
				getForm()->
				
				createInput('config')->
					setLabel('Configuration')->
					property('value', $configIni->get('path', 'config'))->
				getForm()->
				
				createInput('mainclasses')->
					setLabel('Main Classes')->
					property('value', $configIni->get('path', 'mainclasses'))->
				getForm()->
				
				createInput('packages')->
					setLabel('Packages')->
					property('value', $configIni->get('path', 'packages'))->
				getForm()->
				
				createInput('abstract')->
					setLabel('Abstract Classes')->
					property('value', $configIni->get('path', 'abstracts'))->
				getForm()->
				
				createInput('engines')->
					setLabel('Engine Classes')->
					property('value', $configIni->get('path', 'engines'))->
				getForm()->
				
				createInput('javascript')->
					setLabel('Javascript')->
					property('value', $configIni->get('path', 'javascript'))->
				getForm()->
				
				createInput('temp')->
					setLabel('Temporary Files')->
					property('value', $configIni->get('path', 'temp'))->
				getForm()->
				
				createInput('library')->
					setLabel('Shared Library')->
					property('value', $configIni->get('path', 'library'))->
				getForm();
				

				if($form->checkSubmit())
				{
					
					$inputHandler = $form->getInputhandler();
					
					
					foreach(array('base', 'theme', 'config', 'mainclasses', 'packages', 'abstracts', 'engines', 'library', 'javascript') as $value)
					{
						$configIni->set('path', $value, $inputHandler[$value]);
					}
					
					if($configIni->write())
					{
						$this->formSuccessful = true;;
					}
					
					
				}
		
		
	}
	
	public function viewAdmin()
	{
		if($this->formSuccessful)
		{
			$output = '<h2>Settings updated</h2>';
		}elseif($this->form->wasSubmitted()){
			$output = '<h2>Error updating settings</h2>';
		}
		
		$output .= $this->form->makeDisplay();
		return $output;
	}
									
}


?>
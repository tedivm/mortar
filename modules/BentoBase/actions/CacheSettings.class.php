<?php

class BentoBaseActionCacheSettings extends PackageAction  
{
	static $requiredPermission = 'System';
	
	public $AdminSettings = array('linkLabel' => 'Settings',
									'linkTab' => 'System',
									'headerTitle' => 'Cache Settings',
									'linkContainer' => 'Cache');
									
	protected $form;
	protected $success = false;
					
	public function logic()
	{
		$info = InfoRegistry::getInstance();
		$configIni = new IniFile($info->Configuration['path']['config'] . 'configuration.php');
		
		$this->form = new Form('cacheSettingsForm');
		
		$cacheHandlers = Cache::getHandlers();
		
		$handlerInput = $this->form->createInput('cacheHandler');
		
		$handlerInput->setType('select')->
				setLabel('Cache Method');
		
		foreach($cacheHandlers as $handlerName => $handlerClass)
		{
			$attributes = array();
			if($configIni->get('cache', 'handler') == $handlerName)
				$attributes = array('selected' => 'selected');
			$handlerInput->setOptions($handlerName, $handlerName, $attributes);	
		}
		
		if($this->form->checkSubmit())
		{
			$inputHandler = $this->form->getInputhandler();
			
			$configIni->set('cache', 'handler', $inputHandler['cacheHandler']);
			$this->success = $configIni->write();
			
			
			
			
		}
	}
	
	public function viewAdmin()
	{
		if($this->form->wasSubmitted())
		{
			if($this->success)
			{
				$this->AdminSettings['headerSubTitle'] = 'Cache Handler Saved';
			}
		}

		$output .= $this->form->makeDisplay();		
		return $output;
	}
									
}


?>
<?php

class Form
{
	public $name;
	
	protected $activeSection = 'main';
	protected $inputs = array();
	protected $action = false;
	protected $sectionLegends = array();
	protected $sectionIntro = array();
	
	//protected $handlerClass = 'FormHandlerHtml';
	protected $availableMethods = array('post' => 'FormHandlerHtml', 'get' => 'FormHandlerHtml', 'cli' => 'FormHandlerCli');
	protected $method = 'post';
	protected $methodOptions = array();
	protected $submitButton = false;
	
	protected $xsfrProtection = true;
	protected $wasSubmitted = false;
	protected $error = array();
	protected $cacheEnabled = false;
	protected $cacheKey = array();
	// These types of inputs do not get the user input resent out
	protected $discardInput = array('password', 'hidden');
	
	public function __construct($name)
	{
		$this->name = $name;
	}
	
	public function createInput($name)
	{
		$input = new Input($name);
		$input->attachToForm($this);
		return $input;
	}
	
	public function changeSection($name)
	{
		$this->activeSection = $name;
		return $this;
	}
	
	public function attachInput($input)
	{
		$this->inputs[$this->activeSection][] = $input; 
		return true;
	}
	
	public function setAction($action)
	{
		$this->action = $action;
		return $this;
	}
	
	public function setMethod($method, $methodOptions = '')
	{
		$this->method = $method;
		$this->handlerClass = $this->availableMethods[$this->method];
		$this->methodOptions = is_array($methodOptions) ? $methodOptions : array();
		return $this;
	}
	
	public function setLegend($text)
	{
		$this->sectionLegends[$this->activeSection] = $text;
		return $this;
	}
	
	public function setSectionIntro($text)
	{
		$this->sectionIntro[$this->activeSection] = $text;
		return $this;
	}
	
	public function enableCache($cacheKey = false)
	{
		$this->cacheEnabled = true;
		
		if(is_array($cacheKey))
		{
			$this->cacheKey = $cacheKey;
		}elseif(is_string($cacheKey)){
			$this->cacheKey = array($cacheKey);
		}
		
	}
	
	public function makeDisplay()
	{
		$cacheKey = array_merge(array('forms', $this->name), $this->cacheKey);
		$cache = new Cache($cacheKey);
		
		$formHtml = $cache->getData();

		if(!$cache->cacheReturned || !$this->cacheEnabled)
		{
			if($this->xsfrProtection)
			{
				$nonce = $this->getNonce();
				if($nonce != 0)
				{
					$this->createInput('nonce')->
						setType('hidden')->
						property('value', $this->getNonce());
				}
			}
	
			$this->createInput('formIdentifier')->
				setType('hidden')->
				property('value', $this->name);
	
			$formHtml = new HtmlObject('form');
			$formHtml->property('method', $this->method)->
						property('id', $this->name)->
						property('action', (($this->action) ? $this->action : $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) );
			
			foreach($this->inputs as $section => $inputs)
			{
				$sectionHtml = new HtmlObject('fieldset');
				$sectionHtml->property('id', $this->name . "_section_" . $section);
				
				if(isset($this->sectionLegends[$section]))
				{
					$sectionHtml->insertNewHtmlObject('legend')->
						wrapAround($this->sectionLegends[$section]);
				}
				
				if(isset($this->sectionIntro[$section]))
				{
					$sectionHtml->insertNewHtmlObject('div')->
						wrapAround($this->sectionIntro[$section])->
						addClass('intro');
				}			
				
				
				foreach($inputs as $input)
				{
					$input->property('id', $this->name . "_" . $input->name);
	
					
					
					// preprocess special types
					
					switch ($input->type) {
						case 'location':
							$input->type = 'select';
							// check property for base location
							// check for array of location types
							if(is_array($input->property('types')))
							{
								$types = $input->property('types');
								unset($input->properties['types']);
							}elseif(is_string($input->property('types'))){
								$types = $input->property('types');
							}else{
								$types = '';
							}

							if(is_numeric($input->property('baseLocation')))
							{
								$locationId = $input->property('baseLocation');
								unset($input->properties['baseLocation']);
							}else{
								$locationId = 1;
							}							
							
							if(is_numeric($input->property('value')))
							{
								$value = $input->property('value');
								unset($input->properties['value']);
							}else{
								$value = 1;
							}							
							
							
							
							$baseLocation = new Location($locationId);
							$locationList = $baseLocation->getTreeArray($types);

							foreach($locationList as $id => $string)
							{
								$attributes = array();
								if($value == $id)
									$attributes = array('selected' => 'selected');
								$input->setOptions($id, $string, $attributes);
							}
							
							break;
							
						case 'module':
							
							if(!isset($input->properties['moduleName']))
								throw new BentoError('Module type required for input type module.');
								
							$packageInfo = new PackageInfo($input->properties['moduleName']);
							$permission = ($input->properties['permission']) ? $input->properties['permission'] : '';
							$moduleList = $packageInfo->getModules($permission);
							$input->type = 'select';
							
							foreach($moduleList as $module)
							{
								$location = new Location($module['locationId']);								
								$input->setOptions($module['modId'], (string) $location);
							}
							
							
							break;
							
						default:
							break;
					}
					
					
					
					
					
					
					
					// process raw types
					
					
					switch ($input->type)
					{
						case'html':// for now we'll dump it in the text area, but we need to wire in the javascript code as some point
						case 'textarea':
							$inputHtml = new HtmlObject('textarea');
							$inputHtml->tightEnclose();
							$inputHtml->wrapAround($input->property('value'));
							break;
							
						case 'select':
							$inputHtml = new HtmlObject('select');
							foreach($input->options as $option)
							{
								$optionHtml = $inputHtml->insertNewHtmlObject('option')->
									property('value', $option['value'])->
									wrapAround($option['label']);
								
								if(is_array($option['properties']))
								{
									$optionHtml->property($option['properties']);
								}
	
							}
							break;
						
						case 'radio':
							$inputHtml = new HtmlObject('input');
							break;
						
						case 'checkbox':
							$inputHtml = new HtmlObject('input');
							break;
							
							
						case 'submit':
							$this->submitButton = true;
						case 'hidden':	
						case 'image':				
						case 'text':
						default:
							$inputHtml = new HtmlObject('input');
							$inputHtml->property('type', $input->type);
							break;
					}
					
					$inputHtml->property($input->properties)->
						property('name', $input->name);
						
					$validationRules = $input->getRules();
					
					if(!is_null($validationRules))
					{
						$validationClasses = json_encode(array('validation' => $validationRules));				
						$inputHtml->addClass($validationClasses);					
					}
					
					if($input->type == 'hidden')
					{
						$inputHtml->noClose();
						$formHtml->wrapAround($inputHtml);
					}else{	
						
						$labelHtml = new HtmlObject('label');
														
						$labelHtml->property('for', $input->property('id'))->property('id', $input->property('id') . '_label');
														
						if(isset($input->label))
						{					
							$labelHtml->wrapAround($input->label);
						}
						
						$br = new HtmlObject('br');
						$br->noClose();
						
						$labelHtml->property('for', $input->property('id'))->
							property('id', $input->property('id') . '_label');
							
						$sectionHtml->wrapAround($labelHtml)->
							wrapAround($inputHtml)->
							wrapAround($br);
						
					}
				}
				
				$formHtml->wrapAround($sectionHtml);
			}
	
			if(!$this->submitButton)
			{
				
				$sectionHtml = new HtmlObject('div');
				$sectionHtml->property('id', $this->name . "_section_" . 'control');
				$inputHtml = new HtmlObject('input');
				$inputHtml->name = $input->name;
				$inputHtml->property('name', 'Submit')->property('type', 'Submit')->property('value', 'Submit');
				
				$labelHtml = new HtmlObject('label');
				$sectionHtml->wrapAround($labelHtml)->wrapAround($inputHtml)->wrapAround('<br>');
				$formHtml->wrapAround($sectionHtml);		
			}
			
			$formHtml = (string) $formHtml;
			$cache->storeData($formHtml);
		}
		
		$output = $formHtml;
		
		$javascript = '$(\'#' . $this->name . '\').validate()';
		$jqueryPlugins = array('jquery' => array('form', 'validate', 'validate-methods', 'cluetip'));
		if(class_exists('ActivePage', false))
		{
			$page = ActivePage::get_instance();
			$page->addStartupScript($javascript);
			$page->addJQueryInclude($jqueryPlugins);
			$page->addCss($this->name, 'forms');
			//$page->
			
		}else{
			
		}
		
		return $output;
	}
	
	public function wasSubmitted()
	{
		return $this->wasSubmitted;
	}
	
	public function getInputhandler()
	{
		switch($this->method)
		{		
			case 'get':
				$inputHandler = Get::getInstance();
				break;
					
			case 'post':
				$inputHandler = Post::getInstance();
				break;				
		}
		
		return $inputHandler;
	}
	
	public function checkSubmit()
	{
		
		$inputHandler = $this->getInputhandler();
		
		if($inputHandler['formIdentifier'] != $this->name)
			return false;
		
		try 
		{
			
			foreach($this->inputs as $section => $inputs)
			{
				foreach($inputs as $input)
				{
					$validationResults = $input->validate($inputHandler[$input->name]);

					if($validationResults !== true)
						$error[$input->name] = $validationResults;
				
					if(!in_array($input->type, $this->discardInput))
						$input->property('value', $inputHandler[$input->name]);	
						
					if(isset($inputHandler[$input->name]))
						$this->wasSubmitted = true;
				}
			}
			
			if($this->xsfrProtection && $inputHandler['nonce'] == $this->getNonce())
			{
				throw new BentoWarning('Potential XSFR attack blocked');
			}
			
			if(count($error) > 0)
			{
				
				$this->error = $error;
				throw new BentoNotice('Form submit cancelled do to validation rules.');
			}
					
			return true;
			
		}catch(Exception $e){
			
			return false;
		}
			
	}
	
	protected function getNonce()
	{
		if(!$this->xsfrProtection &&class_exists('ActiveUser', false))
		{
			$activeUser = ActiveUser::get_instance();
			$output = $activeUser->session('nonce');
			return $output;
		}
		
		return '0';
	}
	
	public function disableXsfrProtection()
	{
		$this->xsfrProtection = false;
		return $this;
	}	
	
	public function merge($form)
	{
		$package = $form->getMergePackage();
		
		$this->inputs = array_merge_recursive($this->inputs, $package['inputs']);
		$this->sectionIntro = array_merge_recursive($this->sectionIntro, $package['intros']);
		$this->sectionLegends = array_merge_recursive($this->sectionLegends, $package['legends']);
	}
	
	public function getMergePackage()
	{
		$package['inputs'] = $this->inputs;
		$package['intros'] = $this->sectionIntro;
		$package['legends'] = $this->sectionLegends;
		return $package;
	}
	
	public function getInputList()
	{
		$inputList = array();
		foreach($this->inputs as $section)
		{
			foreach($section as $input)
			{
				$inputList[] = $input->name;
			}
		}
		return $inputList;
	}
}


class Input
{
	public $name;
	public $label;
	public $properties = array();
	public $type;
	//public $value;
	public $options;
	
	protected $required = false;
	protected $form;
	protected $validationRules = array();
	protected $validationMessages = array();
	
	public function __construct($name)
	{
		$this->name = $name;
	}
	

	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}
	
	public function setLabel($label)
	{
		$this->label = $label;
		return $this;
	}
	
	public function attachToForm($form)
	{
		if(get_class($form) == 'Form' && $form->attachInput($this))
		{
			$this->form = $form;
		}
		return $this;
	}
	
	public function isRequired($bool = '')
	{
		$this->required = ($bool === false);
		return $this;
	}
	
	public function getForm()
	{
		return $this->form;
	}
	
	public function property($property, $value = false)
	{
		if(is_array($property))
		{
			foreach($property as $name => $value)
			{
				$this->properties[$name] = (!is_null($value)) ? $value : false;
			}
			return $this;
			
		}elseif($value !== false){
			
			$this->properties[$property] = $value;
			return $this;
		}
		
		return $this->properties[$property];
	}
	
	public function addRule($rule, $params = false, $errorMessage = false)
	{
		$this->validationRules[$rule] = (!$params) ? true : $params;
		if($errorMessage !== false)
			$this->validationMessages[$rule] = $errorMessage;

		return $this;
	}
	
	public function getRules()
	{
		if(count($this->validationRules) > 0)
		{
			$array = $this->validationRules;
		
			if(count($this->validationMessages) > 1)
			{
				$array['messages'] = $this->validationMessages;
			}
		}
		return $array;
	}

	public function validate()
	{
		foreach($this->validationRules as $rule)
		{
			$this->form;
			$this->name;
			// grab handler (
		}
		
		return true;
	}

	public function setOptions($value, $label = false, $properties = false)
	{
		if(is_array($value))
		{
			$this->options = array_merge($this->options, $value);
		}else{
			$option['value'] = $value;
			$option['label'] = ($label) ? $label : $value;
			$option['properties'] = (is_array($properties) && count($properties)) ? $properties : false;
			$this->options[] = $option;
		}
		
		return $this;
	}
}


?>
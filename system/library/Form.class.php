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
		if(method_exists($this, 'define'))
			$this->define();
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


				$jsIncludes = array();
				$jsStartup = array();

				foreach($inputs as $input)
				{
					$input->property('id', $this->name . "_" . $input->name);
					$inputJavascript = $this->getInputJavascript($input);

					if(is_array($inputJavascript['startup']))
						$jsStartup = array_merge_recursive($jsStartup, $inputJavascript['startup']);

					if(is_array($inputJavascript['includes']))
						$jsIncludes = array_merge_recursive($jsIncludes, $inputJavascript['includes']);


					$this->processSpecialInputFields($input);
					$inputHtml = $this->getInputHtmlByType($input);

					if($input->type == 'hidden')
					{
						$inputHtml->noClose();
						$formHtml->wrapAround($inputHtml);
					}else{

						$labelHtml = new HtmlObject('label');

						$labelHtml->property('for', $input->property('id'))->
							property('id', $input->property('id') . '_label');

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



				}//foreach($this->inputs as $section => $inputs)

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

		$jsStartup[] = '$(\'#' . $this->name . '\').validate()';


		$jqueryPlugins = array('jquery' => array('form', 'validate', 'validate-methods', 'cluetip', 'FCKeditor'));

		if(class_exists('ActivePage', false))
		{
			$page = ActivePage::getInstance();
			$page->addStartupScript($jsStartup);
			$page->addJQueryInclude($jqueryPlugins);
			$page->addJavaScript($jsIncludes);
			$page->addCss($this->name, 'forms');
			//$page->

		}else{

		}

		return $output;
	}


	protected function processSpecialInputFields(Input $input)
	{

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
					$locationId = 1; // If no location is set, default toroot
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

	}

	protected function getInputHtmlByType(Input $input)
	{

		$tagByType = array(
		'html' => 'textarea',
		'textarea' => 'textarea',
		'select' => 'select',
		'checkbox' => 'input',
		'submit' => 'input',
		'radio' => 'input',
		'hidden' => 'input',
		'image' => 'input',
		'text' => 'input',
		'input' => 'input'
		);

		$tagType = ($tagByType[$input->type]) ? $tagByType[$input->type] : 'input';
		$inputHtml = new HtmlObject($tagByType[$tagType]);
		$inputHtml->property('name', $input->name);

		if($tagByType[$input->type] == 'input');
		{
			$inputHtml->property('type', $input->type);
		}

		switch ($input->type)
		{
			case'html':
			case 'textarea':
				$inputHtml->tightEnclose();
				$inputHtml->wrapAround($input->property('value'));
				unset($input->properties['value']);
				break;

			case 'select':
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


			// Checkboxes need to be arrays if they have multiple items, but we'll just make them all arrays
			// If only one checkbox item exists with a single name, we'll take care of it in 'checkSubmit'
			case 'checkbox':
				$inputHtml->property('name', $input->name . '[]');
				break;

			case 'submit':
				$this->submitButton = true;
		}//switch ($input->type)

		$inputHtml->property($input->properties);

		$validationRules = $input->getRules();

		if(!is_null($validationRules))
		{
			$validationClasses = json_encode(array('validation' => $validationRules));
			$inputHtml->addClass($validationClasses);
		}


		return $inputHtml;

	}

	protected function getInputJavascript(Input $input)
	{

		// to require a javascript file, return $include['Library'][] = 'Name';
		$includes = $startup = array();

		switch ($input->type) {
			case 'html':
				if(is_array($input->property('options')))
					$fckOptions = json_encode($input->property('options'));

				$includes['jquery'] = array('FCKEditor');
				$startup[] = '$(\'textarea#' . $input->property('id') . '\').fck(' . $fckOptions . ');';
				break;

			default:
				break;
		}


		if(count($plugin) > 0 || count($startup) > 0)
			return array('includes' => $includes, 'startup' => $startup);


		return false;
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
					{

						switch ($input->type)
						{
							case 'checkbox':

								if(isset($inputHandler[$input->name]))
								{
									$checkboxInputs = $this->getInput($input->name);

									if(count($checkboxInputs) == 1)
									{
										$inputHandler[$input->name] = $inputHandler[$input->name][0];

										if($inputHandler[$input->name] == 'on')
											$inputHandler[$input->name] = true;

										$input->check(true);
									}else{
										$input->check(in_array($input->property('value'), $inputHandler[$input->name]));
									}
								}else{
									$input->check(false);
								}

								break;

							default:
								$input->property('value', $inputHandler[$input->name]);
						}

					}
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

	public function getInput($name, $section = false)
	{
		$inputList = (!$section) ? $this->inputs : array($this->inputs[$section]);
		$matchedInputs = array();
		foreach($inputList as $inputs)
		{
			foreach($inputs as $input)
			{
				if($input->name == $name)
					$matchedInputs[] = $input;
			}
		}

		switch(count($matchedInputs))
		{
			case 0:
				return false;
			case 1:
				return array_pop($matchedInputs);
			default:
				return $matchedInputs;
		}
	}
}


class Input
{
	public $name;
	public $label;
	public $properties = array();
	public $type = 'input';
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

	public function getLabel()
	{
		return $this->label;
	}

	public function attachToForm($form)
	{
		if($form instanceof Form && $form->attachInput($this))
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

	public function check($isChecked)
	{
		if($isChecked == true)
		{
			$this->property('checked', 'checked');
		}elseif($isChecked === false && isset($this->properties['checked'])){
			unset($this->properties['checked']);
		}
		return $this;
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
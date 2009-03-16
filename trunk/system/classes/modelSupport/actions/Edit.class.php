<?php

class ModelActionEdit extends ModelActionBase
{
	protected $formClassName;
	protected $formStatus;

	public function start()
	{
		$form = $this->getForm();

		if($form->checkSubmit())
		{
			$this->formStatus = $this->processInput($form->getInputHandler());
		}
	}

	protected function getForm()
	{
		$formClassName = (isset($this->formClassName)) ? $this->formClassName : $this->model->getType() . 'Form';

		if(!class_exists($formClassName, false))
		{
			$config = Config::getInstance();
			$path = $config['path']['modules'] . $this->package . '/' . $formClassName . '.class.php';

			if(!include($path) || !class_exists($formClassName, false))
				throw new BentoError('Unable to load form class ' . $formClassName . ' in model ' . get_class($this));
		}

		$form = new $formClassName(get_class($this));

		$content = $this->model->getContent();
		foreach($content as $name => $value)
		{
			// if is possible for the value to be null or black, in which case we will still
			// want to pass it along to clear out any defaults
			if(is_scalar($value) && $input = $form->getInput($name))
				$input->setValue($value);
		}

		return $form;
	}

	protected function processInput($input)
	{
		//
		$filter = new FilterHtmlEntities();
		$input->addFilter($filter);

		$content = $this->model->getContent();
		foreach($content as $name => $value)
		{
			// Important notes- this input handler is filtered through the Form class and its validation rules
			// additional filters *should* be added at will, because the input class should extend FilteredArray
			// XSS, SQL Injection and other goodies are *not* handled by the Input class
			$this->model->$name = $input[$name];
		}

		return ($this->model->save());
	}






	public function viewAdmin()
	{
		if($this->formStatus)
		{
			$url = new Url();
			$url->location = $location;
			$url->action = 'Edit';
			$url->status = 'saved';



			return;

		}elseif(!is_null($this->formStatus)){

			// form was submitted but failed
			// since the form was already checked, it will by default spit errors out on reload
			// so there isn't too much to do

		}else{

			// form was not submitted

		}

		return $this->form->makeDisplay();
	}

	public function viewHtml()
	{
		if($this->formStatus)
		{
			$url = new Url();
			$url->location = $location;
			$url->action = 'Read';
			$url->status = 'saved';
			return;

		}elseif(!is_null($this->formStatus)){

			// form was submitted but failed
			// since the form was already checked, it will by default spit errors out on reload
			// so there isn't too much to do

		}else{
			// form was not submitted
		}

		return $this->form->makeDisplay();
	}

	public function viewXml()
	{
		if($this->formStatus)
		{
			$xml = ModelToXml::convert($this->model);
		}elseif(!is_null($this->formStatus)){

		}else{

		}
	}

	public function viewJson()
	{
		if($this->formStatus)
		{
			$json = ModelToJson::convert($this->model);
		}elseif(!is_null($this->formStatus)){

		}else{

		}
	}
}

?>
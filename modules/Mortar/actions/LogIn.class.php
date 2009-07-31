<?php

class MortarActionLogIn extends ActionBase
{
	static $requiredPermission = 'Read';

	public $AdminSettings = array('linkLabel' => 'Log In',
									'linkTab' => 'Universal',
									'headerTitle' => 'Log In',
									'EnginePermissionOverride' => true);

	protected $form;
	protected $loginSuccessful = false;

	protected function logic()
	{
		$info = InfoRegistry::getInstance();

		$form = new Form('logIn');

		$this->form = $form;

		$form->createInput('username')->
				setLabel('Username: ')->
				addRule('required')->
			getForm()->
			createInput('password')->
				setLabel('Password: ')->
				setType('password')->
				addRule('required')->
			getForm()->
			createInput('redirect')->
				setType('hidden')->
				property('value', $info->Configuration['id']);


		if($inputHandler = $form->checkSubmit())
		{
			try{
				$this->loginSuccessful = (bool) (ActiveUser::changeUserByNameAndPassword($inputHandler['username'],
																						$inputHandler['password']));
			}catch(Exception $e){

			}
		}
		$this->ioHandler->setStatusCode(200);
	}

	public function viewHtml()
	{
		$output = '';
		if($this->loginSuccessful)
		{
			if($redirectUrl = $this->getUrl())
			{
				$urlString = (string) $redirectUrl;
				$this->ioHandler->addHeader('Location', $urlString);
			}
			return 'You have successfully logged in.';
		}else{
			if($this->form->wasSubmitted())
				$this->AdminSettings['headerSubTitle'] = 'Invalid login';

			$output .= $this->form->getFormAs('Html');
		}

		return $output;
	}

	public function viewAdmin()
	{
		return $this->viewHtml();
	}

	protected function getUrl()
	{
		$query = Query::getQuery();

		$url = new Url();
		$url->format = $query['format'];

		$action = isset($query['a']) ? strtolower($query['a']) : false;
		if(isset($query['l']) && is_numeric($query['l']) && ($action == 'index' || $action == 'read'))
		{
			$location = new Location($query['l']);
			$model = $location->getResource();

			if(!$model->getAction($query['a']))
				return false;

			$url->location = $query['l'];
			$url->action = $query['a'];
		}elseif(isset($query['m']) && $action){
			$packageInfo = new PackageInfo($query['m']);
			if(!$packageInfo->getActions($query['a']))
				return false;

			$url->module = $query['m'];
			$url->action = $query['a'];
		}else{
			return false;
		}

		$user = ActiveUser::getUser();
		if(!$url->checkPermission($user->getId()))
			return false;

		return $url;
	}
}


?>
<?php

class BentoBaseActionLogIn extends ActionBase
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
		//	$this->engineHelper->page->addMeta('refresh', '5;url=' . $url);
			return 'You have successfully logged in.';

		}else{

			if($this->form->wasSubmitted())
			{
				$this->AdminSettings['headerSubTitle'] = 'Invalid login';
			}

			$output .= $this->form->makeHtml();

		}

		return $output;
	}


	public function viewAdmin()
	{
		return $this->viewHtml();
	}

}


?>
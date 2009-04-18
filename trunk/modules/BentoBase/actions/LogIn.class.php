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

		if($form->checkSubmit())
		{
			try{
				$inputHandler = $form->getInputhandler();
				$active_user = ActiveUser::get_instance();

				if($active_user->changeUser($inputHandler['username'], $inputHandler['password']))
				{
					$this->loginSuccessful = true;
				}

			}catch(Exception $e){

			}

		}
	}


	public function viewHtml()
	{
		$output = '';
		if($this->loginSuccessful)
		{
			$url = new Url();
			//$url->fromString();
			$post = Post::getInstance();

			$info = InfoRegistry::getInstance();

			$url->engine =$info->Configuration['engine'];

			if(strlen($post['redirect']))
			{

			}

		//	$this->engineHelper->page->addMeta('refresh', '5;url=' . $url);
			return 'You have successfully logged in.';

		}else{

			if($this->form->wasSubmitted())
			{
				$this->AdminSettings['headerSubTitle'] = 'Invalid login';
			}

			$output .= $this->form->makeDisplay();

		}

		return $output;
	}


	public function viewAdmin()
	{
		return $this->viewHtml();
	}

}


?>
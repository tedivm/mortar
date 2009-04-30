<?php

class BentoBaseActionAddUser extends FormActionBase
{
	static $requiredPermission = 'System';

	public $AdminSettings = array('linkLabel' => 'Add User',
									'linkTab' => 'System',
									'headerTitle' => 'Add User',
									'linkContainer' => 'Users');

	protected $formName = 'BentoBaseUserForm';
	protected $user;

	protected function getForm()
	{
		$form = parent::getForm();

		$inputs = $form->getInput('memberGroup');

		foreach($inputs as $input)
		{

			if($input->getLabel() == 'User')
			{
				$input->check(true);
			}
		}

		return $form;
	}

	protected function processInput($inputHandler)
	{
		try
		{
			$user = new User();
			$user->setEmail($inputHandler['email']);
			$user->setName($inputHandler['name']);
			$user->setPassword($inputHandler->getRaw('password'));
			$user->setMemberGroups($inputHandler['memberGroup']);
			$user->setAllowLogin($inputHandler['login']);
			$user->save();
			$this->user = $user;
		}catch(Exception $e){
			return false;
		}
		return true;
	}

	public function viewAdmin()
	{
		return $this->viewAdminForm();
	}

	protected function adminSuccess()
	{
		$url = $this->linkToSelf();
		$url->property('id', $this->user->getId());
		$url->property('action', 'EditUser');
		$url->property('message', 'added');
		$url->property('engine', 'Admin');
		header('Location:' . $url);
	}

	protected function adminError()
	{
		$this->AdminSettings['headerSubTitle'] = 'An error has occured while trying to add this user';
	}

}

?>
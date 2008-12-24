<?php

class BentoBaseActionEditUser extends FormPackageAction
{
	static $requiredPermission = 'System';

	public $AdminSettings = array('linkLabel' => 'Edit User',
									'linkTab' => 'System',
									'headerTitle' => 'Edit User',
									'linkContainer' => 'Users');


	protected $formName = 'BentoBaseUserForm';
	protected $user;

	protected function getForm()
	{
		$info = InfoRegistry::getInstance();
		$info->Runtime['id'];
		$user = new User();
		$results = $user->load_user($info->Runtime['id']);

		if(!$user->load_user($info->Runtime['id']))
		{
			throw new BentoError('Unable to find user to edit');
		}

		$this->user = $user;
		$form = parent::getForm();

		$form->getInput('name')->
			property('value', $user->getName());

		$form->getInput('email')->
			property('value', $user->getEmail());

		$form->getInput('name')->
			property('value', $user->getName());

		if($user->isAllowedLogin())
		{
			$form->getInput('login')->
				check(true);
		}

		$memberGroupInputs = $form->getInput('memberGroup');
		$groupIds = $user->getMemberGroups();

		foreach($memberGroupInputs as $groupInput)
		{
			if(in_array($groupInput->property('value'), $groupIds))
				$groupInput->check(true);

		}

		return $form;
	}

	protected function processInput($inputHandler)
	{
		try{
			$user = $this->user;

			$user->setEmail($inputHandler['email']);
			$user->setName($inputHandler['name']);

			if(strlen($inputHandler->getRaw('password')) > 1)
				$user->setPassword($inputHandler->getRaw('password'));

			$user->setMemberGroups($inputHandler['memberGroup']);
			$user->setAllowLogin($inputHandler['login']);
			$user->save();
			return true;
		}catch(Exception $e){

		}
		return false;
	}

	public function viewAdmin()
	{
		return $this->viewAdminForm();
	}

	protected function adminSuccess()
	{
		$this->AdminSettings['headerSubTitle'] = 'User Updated';
	}

	protected function adminError()
	{
		$this->AdminSettings['headerSubTitle'] = 'An error has occured while trying to add this user';
	}

	protected function adminMessage($messageId)
	{
		switch($messageId)
		{
			case 'added':
				$this->AdminSettings['headerSubTitle'] = 'User Added';
				break;

			case 'updated':
				$this->AdminSettings['headerSubTitle'] = 'User Updated';
				break;

			default:

		}

	}

}

?>
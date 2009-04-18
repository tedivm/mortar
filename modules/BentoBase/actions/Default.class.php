<?php

class BentoBaseActionDefault extends ActionBase
{
	//static $requiredPermission = 'Read';

	protected function logic()
	{

	}

	public function viewHtml()
	{
		$info = InfoRegistry::getInstance();
		if($info->Runtime['id'] == 'admin')
		{
			$adminUrl = new Url();
			$adminUrl->engine = 'Admin';
			header('Location: ' . $adminUrl);
		}

		$page = ActivePage::getInstance();
		$page['title'] = 'Test Title';
		return 'This is the main page';
	}

	public function viewAdmin()
	{
		$user = ActiveUser::getInstance();

		if($user->getName() == 'guest')
		{
			$url = $this->linkToSelf();
			$url->property('action', 'LogIn');
			$url->property('engine', 'Admin');
			header('Location:' . $url);
 		}




		$get = Get::getInstance();
		$tab = ($get['tab']) ? $get['tab'] : 'Main';
		$this->AdminSettings['linkTab'] = $tab;
		$this->AdminSettings['headerTitle'] = $tab;
		return 'This is the main page';
	}


}



?>
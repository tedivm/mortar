<?php

class MortarActionLogOut extends ActionBase
{
	static $requiredPermission = 'Read';

	public static $settings = array( 'Base' => array( 'headerTitle' => 'Log Out' ) );

	public function logic()
	{
		ActiveUser::changeUserByName('Guest');
		$this->ioHandler->setStatusCode(200);
	}


	public function viewAdmin($page)
	{
		return 'You have been logged out.';
	}


	public function viewHtml($page)
	{
		if($redirectUrl = $this->getUrl()) {
			$urlString = (string) $redirectUrl;
			$this->ioHandler->addHeader('Location', $urlString);
		}
		return 'You have been logged out.';
	}

	protected function getUrl()
	{
		$query = Query::getQuery();

		$url = new Url();
		$url->format = $query['format'];

		$action = isset($query['a']) ? strtolower($query['a']) : false;
		if(isset($query['l']) && is_numeric($query['l']) && ($action == 'index' || $action == 'read'))
		{
			$location = Location::getLocation($query['l']);
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
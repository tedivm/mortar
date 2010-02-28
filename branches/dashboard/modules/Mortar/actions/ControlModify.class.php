<?php

class MortarActionControlModify extends ActionBase
{
	static $requiredPermission = 'System';

	public function logic()
	{
		$query = Query::getQuery();
		$input = Input::getInput();

		$url = new Url();
		$url->module = 'Mortar';
		$url->action = 'Dashboard';
		$url->format = 'admin';
		$this->ioHandler->addHeader('Location', (string) $url);

		if(!isset($input['user']) || !isset($input['id']) || !isset($input['modify']) || 
			!isset($query['id'])) {
			return false;
		}

		$cs = new ControlSet($input['user']);
		$cs->loadControls();
		$info = $cs->getInfo();

		if((int) $info[$query['id']]['id'] !== (int) $input['id']) {
			return false;
		}

		switch($input['modify']) {
			case 'Remove':
				$cs->removeControl($query['id']);
				break;
			case 'Move Up':
				$cs->swapControls($query['id'], true);
				break;
			case 'Move Down':
				$cs->swapControls($query['id'], false);
				break;
		}

		$cs->saveControls();
	}

	public function viewAdmin($page)
	{
		return '';
	}
}

?>
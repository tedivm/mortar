<?php

class MortarActionControlSettings extends FormAction
{
	static $requiredPermission = 'System';

	public $adminSettings = array( 'headerTitle' => 'Control Settings', 'useRider' => true);

	protected $control;

	public function logic()
	{
		$query = Query::getQuery();

		$user = ActiveUser::getUser();
		$cs = new ControlSet($user->getId());
		$cs->loadControls();
		$info = $cs->getInfo();

		if(isset($query['id']) && isset($info[$query['id']])) {
			$this->control = $cs->getControl($query['id']);
		}
	}

	public function viewAdmin($page)
	{
		if(!isset($this->control)) {
			return "<h3>Control not found.</h3>";
		}

		ob_start(); echo "<pre>"; var_dump($this->control); echo "</pre>"; return ob_get_clean();
	}

	protected function processInput($input)
	{
		return true;
	}
}

?>
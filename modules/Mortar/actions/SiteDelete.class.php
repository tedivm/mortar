<?php

class MortarActionSiteDelete extends ModelActionLocationBasedDelete
{

	protected $error;


	public function logic()
	{
		$parent = $this->model->getLocation();
		$sites = $parent->getChildren('Sites');

		if($sites == false || count($sites) < 2)
		{
			$this->error = 'You can not delete the only active site in the system.';
		}else{
			return parent::logic();
		}
	}

	public function viewAdmin()
	{
		if(isset($this->error))
		{
			return $this->error;
		}else{

			return parent::viewAdmin();
		}
	}


}

?>
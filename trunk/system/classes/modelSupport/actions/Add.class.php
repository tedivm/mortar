<?php

class ModelActionAdd extends ModelActionEdit
{

	protected function processInput($input)
	{
		$location = new Location();


		return parent::processInput($input);
	}


}

?>
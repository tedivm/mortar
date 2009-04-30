<?php

class JsonOutputController extends AbstractOutputController
{
	public $mimeType = 'application/json';

	protected function bundleOutput($output)
	{
		$this->activeResource = $output;
	}

	protected function makeDisplayFromResource()
	{
		return json_encode($this->activeResource);
	}
}

?>
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
		$query = Query::getQuery();
		$json = json_encode($this->activeResource);
		if(isset($query['callback']))
		{
			$this->mimeType = 'application/javascript';
			$callback = preg_replace('[^A-Za-z0-9]', '', $query['callback']);
			return  $callback . '(' . $json . ')';
		}else{
			return $json;
		}
	}
}

?>
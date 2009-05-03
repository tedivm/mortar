<?php

class XmlOutputController extends AbstractOutputController
{
	public $mimeType = 'application/xml';

	protected function bundleOutput($output)
	{
		$this->activeResource = $output;
	}

	protected function makeDisplayFromResource()
	{
		if($this->activeResource instanceof SimpleXMLElement)
		{
			return $this->activeResource->asXml();
		}
	}
}

?>
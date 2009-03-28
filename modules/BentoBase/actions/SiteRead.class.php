<?php

class BentoBaseActionSiteRead extends ModelActionBase
{

	public function logic()
	{
		echo 1111;
	}


	public function viewAdmin()
	{

	}

	public function viewHtml()
	{
		$html = ModelToHtml::convert($this->model, $this->requestHandler);
		return $html;
	}

	public function viewXml()
	{
		$xml = ModelToXml::convert($this->model, $this->requestHandler);
		return $xml;
	}

	public function viewJson()
	{
		$array = ModelToArray::convert($this->model, $this->requestHandler);
		return $array;
	}
}

?>
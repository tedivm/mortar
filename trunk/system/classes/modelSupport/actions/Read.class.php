<?php

class ModelActionRead extends ModelActionBase
{

	public function start()
	{

	}


	public function viewAdmin()
	{

	}

	public function viewHtml()
	{
		$html = ModelToHtml::convert($this->model);
		return $html;
	}

	public function viewXml()
	{
		$xml = ModelToXml::convert($this->model);
		return $xml;
	}

	public function viewJson()
	{
		$array = ModelToArray::convert($this->model);
		return $array;
	}
}

?>
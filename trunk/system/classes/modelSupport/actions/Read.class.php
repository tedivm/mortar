<?php

class ModelActionRead extends ModelActionBase
{

	public function logic()
	{

	}


	public function viewAdmin()
	{
		return 'Model Title: ' . $this->model['title'];
	}

	public function viewHtml()
	{
		$page = ActivePage::getInstance();

		if(isset($this->model['title']))
			$page->addRegion('title', $this->model['title']);

		if(isset($this->model->keywords))
			$page->addMeta('keywords', $this->model->keywords);

		if(isset($this->model->description))
			$page->addMeta('description', $this->model->description);

		$html = ModelToHtml::convert($this->model, $this->ioHandler);
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
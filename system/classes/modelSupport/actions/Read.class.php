<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * This class acts as the default 'read' action for any model. It is ridiculous simple, as all the heavy lifting is done
 * by the ModelActionBase class.
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelActionRead extends ModelActionBase
{

	/**
	 * This literally does nothing at all.
	 *
	 */
	public function logic()
	{

	}


	/**
	 * This is incredibly basic right now, but thats because I'm working woth the Joshes on getting the interface
	 * for it set up.
	 *
	 * @return string
	 */
	public function viewAdmin()
	{
		$output = 'Model Type: ' . $this->model->getType() . '<br>' . PHP_EOL;
		$output .= 'Model Id: ' . $this->model->getId() . '<br>';

		$url = new Url();
		$url->id = $this->model->getId();
		$url->type = $this->model->getType();
		$url->format = 'Admin';
		$url->action = 'Read';

		$output .= 'Model Url: ' . (string) $url;

		return $output;
	}

	/**
	 * This function takes the model's data and puts it into a template, which gets injected into the active page. It
	 * also takes out some model data to place in the rest of the template (title, keywords, descriptions).
	 *
	 * @return string This is the html that will get injected into the template.
	 */
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
		$html = $this->model['name'];


		return $html;
	}

	/**
	 * This will convert the model into XML for outputting.
	 *
	 * @return string XML
	 */
	public function viewXml()
	{
		$xml = ModelToXml::convert($this->model, $this->requestHandler);
		return $xml;
	}

	/**
	 * This takes the model and turns it into an array. The output controller converts that to json, which gets
	 * outputted.
	 *
	 * @return array
	 */
	public function viewJson()
	{
		$array = ModelToArray::convert($this->model, $this->requestHandler);
		return $array;
	}
}

?>
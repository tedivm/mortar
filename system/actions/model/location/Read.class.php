<?php
/**
 * Mortar
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
class ModelActionLocationBasedRead extends ModelActionLocationBasedBase
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
	public function viewAdmin($page)
	{
		$output = '';

		if(isset($this->model['title'])) {
			$output .= '<h1>' . $this->model['title'] . "</h1>\n";
			$page->setTitle($this->model['title']);
		} elseif(isset($this->model['name'])) {
			$output .= '<h1>' . $this->model['name'] . "</h1>\n";
			$page->setTitle($this->model['name']);
		}

		$output .= $this->modelToHtml($page, $this->model, 'adminDetails.html');

		$hook = new Hook();
		$hook->loadPlugins('Template', 'admin', 'extraDetails');
		$results = $hook->getDetails($this->model);

		foreach($results as $detail)
			$output .= $detail;

		$output .= $this->modelToHtml($page, $this->model, 'Display.html');

		return $output;
	}

	/**
	 * This function takes the model's data and puts it into a template, which gets injected into the active page. It
	 * also takes out some model data to place in the rest of the template (title, keywords, descriptions).
	 *
	 * @return string This is the html that will get injected into the template.
	 */
	public function viewHtml($page)
	{
		$location = $this->model->getLocation();
		$unpub = !($location->checkPublished()); 

		if ($unpub === true) {
			if($this->checkAuth('Admin') === true) {
				$titlePrefix = 'Preview -- ';
			} else {
				throw new ResourceNotFoundError();
			}
		} else {
			$titlePrefix = '';
		}

		if(isset($this->model['title'])) {
			$page->setTitle($titlePrefix . $this->model['title']);
		} elseif(isset($this->model->name)) {
			$page->setTitle($titlePrefix . $this->model->name);
		}

		if(isset($this->model->keywords))
			$page->addMeta('keywords', $this->model->keywords);

		if(isset($this->model->description))
			$page->addMeta('description', $this->model->description);

		return $this->modelToHtml($page, $this->model, 'Display.html');
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
		$htmlConverter = $this->model->getModelAs('Array');
		return $htmlConverter->getOutput();
	}
}

?>
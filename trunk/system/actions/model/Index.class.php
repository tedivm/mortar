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
class ModelActionIndex extends ModelActionBase
{
        public static $settings = array( 'Base' => array('headerTitle' => 'Index', 'listType' => 'table', 'paginate' => true ));

	protected $format = 'Html';
	protected $getAs = 'HtmlList';
	protected $listOptions = array();

	/**
	 * Creates a listing of models along with relevant qualities and actions for use in an admin page.
	 *
	 * @return string
	 */
	public function viewAdmin($page)
	{
		$this->format = 'Admin';
		return $this->viewHtml($page);
	}

	public function viewHtml($page)
	{
		$listType = $this->getSetting('listType', $this->format);
		if($listType === 'template') {
			$template = 'Display.html';
		} else {
			$template = null;
			$listType = 'table';
		}

		$paginate = $this->getSetting('paginate', $this->format);
		if(!isset($paginate))
			$paginate = false;

		$htmlConverter = $this->model->getModelAs($this->getAs, $template, true);
		$htmlConverter->addOptions($this->listOptions);
		$htmlConverter->setListType($listType);
		$htmlConverter->paginate($paginate);

		return $htmlConverter->getOutput();
	}

	public function viewXml()
	{

	}

	public function viewJson()
	{
		$htmlConverter = $this->model->getModelAs($this->getAs);
		$childModels = $htmlConverter->getChildrenList();

		$children = array();
		if(count($childModels) > 0)
		{
			foreach($childModels as $model)
			{
				$children[] = $model->__toArray();
			}
			return $children;
		}else{
			return false;
		}
	}
}

?>
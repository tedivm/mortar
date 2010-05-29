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
        public $adminSettings = array( 'headerTitle' => 'Index', 'listType' => 'table', 'paginate' => true );
        public $htmlSettings = array( 'headerTitle' => 'Index', 'listType' => 'table', 'paginate' => true );

	protected $getAs = 'HtmlList';
	protected $listOptions = array();

	/**
	 * Creates a listing of models along with relevant qualities and actions for use in an admin page.
	 *
	 * @return string
	 */
	public function viewAdmin($page)
	{
		if(isset($this->adminSettings['listType']) && $this->adminSettings['listType'] === 'template') {
			$template = 'Display.html';
			$listType = $this->adminSettings['listType'];
		} else {
			$template = null;
			$listType = 'table';
		}

		$paginate = isset($this->adminSettings['paginate']) ? $this->adminSettings['paginate'] : false;

		$htmlConverter = $this->model->getModelAs($this->getAs, $template, true, $this->listOptions);
		$htmlConverter->setListType($listType);
		$htmlConverter->paginate($paginate);

		return $htmlConverter->getOutput();
	}

	public function viewHtml($page)
	{
		if(isset($this->htmlSettings['listType']) && $this->htmlSettings['listType'] === 'template') {
			$template = 'Display.html';
			$listType = $this->htmlSettings['listType'];
		} else {
			$template = null;
			$listType = 'table';
		}

		$paginate = isset($this->htmlSettings['paginate']) ? $this->htmlSettings['paginate'] : false;

		$htmlConverter = $this->model->getModelAs($this->getAs, $template, true, $this->listOptions);
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
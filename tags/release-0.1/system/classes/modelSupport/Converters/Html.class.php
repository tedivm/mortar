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
 * This class returns an HTML representation of the model
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelToHtml
{
	protected $model;
	protected $modelProperties = array(array('name' => 'content', 'permission' => 'Read'),
										array('name' => 'title', 'permission' => 'Read'));
	protected $template;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function useTemplate($template)
	{
		$this->template = $template;
	}

	/**
	 * This function converts a model into an HTML string
	 *
	 * @param Model $model
	 * @return string
	 */
	public function getOutput()
	{
		$properties = $this->modelProperties;
		$modelDisplay = new DisplayMaker();
		$modelDisplay->setDisplayTemplate($this->template);

		$user = ActiveUser::getUser();
		$permission = new Permissions($this->model->getLocation(), $user->getId());

		foreach($properties as $property)
		{
			try {
				if(!isset($property['name']))
					continue;

				if(isset($property['permission']) && !$permission->isAllowed($property['permission']))
					continue;

				if(isset($this->model[$property['name']]))
					$modelDisplay->addContent('model_' . $property['name'], $this->model[$property['name']]);

			}catch(Exception $e){
				// if one property fails we don't want to abort the listing
			}
		}

		$location = $this->model->getLocation();
		$url = new Url();
		$url->location = $location->getId();

		$query = Query::getQuery();
		$url->format = $query['format'];

		$modelDisplay->addContent('permalink', (string) $url);

		$userId = $location->getOwner();

		if(is_numeric($userId))
		{
			$user = ModelRegistry::loadModel('User', $userId);
			$modelDisplay->addContent('model_owner', $user['name']);
		}

		$modelDisplay->addDate('model_creationTime', $location->getCreationDate());
		$modelDisplay->addDate('model_lastModified', $location->getLastModified());
		$modelDisplay->addContent('model_name', $location->getName());

		$modelOutput = $modelDisplay->makeDisplay(true);

		return $modelOutput;
	}

}

?>
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

	protected $convertedProperties;

	/**
	 * The constructor sets the protected vars and prepares the relevant information for Html display which can be output in a template or accessed directly
	 *
	 * @param Model $model
	 * @param String $template
	 * @return string
	 */

	public function __construct(Model $model)
	{
		$this->model = $model;
		$properties = $this->modelProperties;
		$this->convertedProperties = array();

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
					$this->convertedProperties['model_' . $property['name']] = $this->model[$property['name']];

			}catch(Exception $e){
				// if one property fails we don't want to abort the listing
			}
		}
		
		$location = $this->model->getLocation();
		$url = new Url();
		$url->location = $location->getId();

		$query = Query::getQuery();
		$url->format = $query['format'];

		$this->convertedProperties['permalink'] = (string) $url;

		$locOwner = $location->getOwner();
		$this->convertedProperties['model_owner'] = $locOwner['name'];
		
		$baseUrl = new Url();
		$baseUrl->locationId = $location->getId();
		$baseUrl->format = "Admin";
		
		$actionTypes = array('Read', 'Edit', 'Delete');
		if(isset($location) && $location->hasChildren())
			array_unshift($actionTypes, 'Index');
			
		$user = ActiveUser::getUser();
		$userId = $user->getId();
		$actionList = '';	
		foreach($actionTypes as $action)
		{
			$modelListAction = new DisplayMaker();
			$modelListAction->setDisplayTemplate("<li>{# action #}</li>");
			$actionUrl = clone $baseUrl;
			$actionUrl->action = $action;

			if($actionUrl->checkPermission($userId))
			{
				$modelListAction->addContent('action', $actionUrl->getLink(ucfirst($action)));
				$actionList .= $modelListAction->makeDisplay();
			}
		}
		
		$this->convertedProperties['model_creationTime'] = $location->getCreationDate();
		$this->convertedProperties['model_lastModified'] = $location->getLastModified();
		$this->convertedProperties['model_name'] = $location->getName();
		$this->convertedProperties['model_actions'] = $actionList;
	}

	public function useTemplate($template)
	{
		$this->template = $template;
	}

        public function __toArray()
        {
                $array = array();

                if(isset($this->convertedProperties))
                        $array['properties'] = $this->convertedProperties;

                return $array;
        }

	/**
	 * This function outputs the loaded model into an HTML string by inserting its values into the used template
	 *
	 * @return string
	 */
	public function getOutput()
	{
		$modelDisplay = new DisplayMaker();
		$modelDisplay->setDisplayTemplate($this->template);

		foreach ($this->convertedProperties as $propName => $propValue) 
			($propName == "model_creationTime" || $propName == "model_lastModified") ? $modelDisplay->addDate($propName, $propValue) : $modelDisplay->addContent($propName, $propValue);

		$modelOutput = $modelDisplay->makeDisplay(true);

		return $modelOutput;
	}

}

?>
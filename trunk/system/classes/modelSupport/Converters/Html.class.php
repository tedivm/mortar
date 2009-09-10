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
											
	protected $modelDefaults = array('model_title', 'model_content', 'model_owner', 'model_action_list', 'model_status', 'model_type', 'model_actionList', 'model_creationTime', 'model_lastModified', 'model_name', 'model_actions');
	protected $template;
	protected $modelDisplay;

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
		/* $baseUrl->format = "Admin"; */
		$baseUrl->format = $query['format']; 
		
		$actionTypes = array('Read', 'Edit', 'Delete');
		if(isset($location) && $location->hasChildren())
			array_push($actionTypes, 'Index');
			
		$allowedActionTypes = array();
		$user = ActiveUser::getUser();
		$userId = $user->getId();
		$actionList = '';
		foreach($actionTypes as $action)
		{
			$modelListAction = new DisplayMaker();
			$modelListAction->setDisplayTemplate("<li class='action action_$action'>{# action #}</li>");
			$actionUrl = clone $baseUrl;
			$actionUrl->action = $action;

			if($actionUrl->checkPermission($userId))
			{
				$modelListAction->addContent('action', $actionUrl->getLink(ucfirst($action)));
				$actionList .= $modelListAction->makeDisplay();
				$this->convertedProperties['model_action_' . $action] = $actionUrl->__toString();
				array_push($allowedActionTypes, $action);
			}
		}
		
		$this->convertedProperties['model_status'] = $this->model->status;
		$this->convertedProperties['model_type'] = $this->model->getType();
		$this->convertedProperties['model_action_list'] = $allowedActionTypes;
		$this->convertedProperties['model_creationTime'] = $location->getCreationDate();
		$this->convertedProperties['model_lastModified'] = $location->getLastModified();
		$this->convertedProperties['model_name'] = $location->getName();
		$this->convertedProperties['model_actions'] = $actionList;
	}

	/**
	 * Provides a template for model data to be inserted into when getOutput() is called
	 *
	 * @param String $template
	 */

	public function useTemplate($template)
	{
		$matchResults = array();
	
		$this->template = $template;
		$this->modelDisplay = new DisplayMaker();
		$this->modelDisplay->setDisplayTemplate($template);				
	}


	/**
	 * This function outputs the loaded model into an HTML string by inserting its values into the used template
	 *
	 * @return string
	 */
	public function getOutput()
	{

		$this->modelDisplay->setDisplayTemplate($this->template);

		foreach ($this->convertedProperties as $propName => $propValue) 
			($propName == "model_creationTime" || $propName == "model_lastModified") ? $this->modelDisplay->addDate($propName, $propValue) : $this->modelDisplay->addContent($propName, $propValue);
			
		$modelTags = $this->modelDisplay->getTags();
		foreach ($modelTags as $tag) {
			if ((preg_match('/^model_(?!action_)(.*)/', $tag, $matchResults) && !(in_array($tag, $this->modelDefaults)))) {
				if (isset($this->convertedProperties[$matchResults[1]])) 
					$this->modelDisplay->addContent($tag, $this->convertedProperties[$matchResults[1]]);
				else
					$this->modelDisplay->addContent($tag, $this->model->$matchResults[1]);
			}
		}
		
		$modelOutput = $this->modelDisplay->makeDisplay(true);

		return $modelOutput;
	}
	
	
	/**
	 * This function returns the array of properties.
	 *
	 * @return array
	 */	
	public function getProperties()
	{
		return $this->convertedProperties;
	}
	
	public function __get($offset)
	{
		if (isset($this->convertedProperties[$offset])) 
			return $this->convertedProperties[$offset];
		else
			return $this->model->$offset;
	}
}

?>
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

	protected $modelDefaults = array('model_title', 'model_content', 'model_owner', 'model_action_list', 'model_status',
										'model_type', 'model_actionList', 'model_creationTime', 
										'model_lastModified', 'model_name', 'model_actions');
	protected $template;
	protected $modelDisplay;
	protected $convertedProperties;

	/**
	 * The constructor sets the protected vars and prepares the relevant information for Html display which can be output in a 
	 * template or accessed directly
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

		$location = method_exists($this->model, 'getLocation') ? $this->model->getLocation() : new Location(1);

		$user = ActiveUser::getUser();
		$permission = new Permissions($location, $user->getId());

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

		$url = new Url();
		$url->location = $location->getId();

		$query = Query::getQuery();
		$url->format = $query['format'];

		$this->convertedProperties['permalink'] = (string) $url;

		$locOwner = $location->getOwner();
		$this->convertedProperties['model_owner'] = $locOwner['name'];

		$actionUrls = $this->model->getActionUrls($query['format']);
		$allowedActions = $this->model->getActions($user);

		$actionTypes = array();
		foreach(array('Read', 'Edit', 'Delete', 'Index') as $action) 
			if(isset($allowedActions[$action]))
				array_push($actionTypes, $action);

		$allowedActionTypes = array();
		$actionList = '';
		foreach($actionTypes as $action)
		{
			if (isset($allowedActions[$action])) {
				$actionUrl = $actionUrls[$action];
				$actionLink = $actionUrl->getLink(ucfirst($action));

				$modelListAction = new ViewStringTemplate("<li class='action action_$action'>{{ action }}</li>");

				$modelListAction->addContent(array('action' => $actionLink));
				$actionList .= $modelListAction->getDisplay();
				$this->convertedProperties['model_action_' . $action] = $actionUrl->__toString();
				array_push($allowedActionTypes, $action);
			}
		}

		$this->convertedProperties['model_status'] = $this->model->status;
		$this->convertedProperties['model_type'] = $this->model->type;
		$this->convertedProperties['model_action_list'] = $allowedActionTypes;
		$this->convertedProperties['model_creationTime'] = $this->model->createdOn;
		$this->convertedProperties['model_lastModified'] = $this->model->lastModified;
		$this->convertedProperties['model_publishDate'] = $this->model->publishDate;
		$this->convertedProperties['model_name'] = $this->model->name;
		$this->convertedProperties['model_actionList'] = $actionList;
	}

	/**
	 * Provides a template for model data to be inserted into when getOutput() is called
	 *
	 * @param String $template
	 */
	public function useTemplate($template)
	{
		$this->template = $template;
		$this->modelDisplay = new ViewStringTemplate($template);
	}
	
	public function useView($view)
	{
		$this->modelDisplay = $view;
	}

	public function addContent($content)
	{
		return is_array($content) ? $this->modelDisplay->addContent($content) : false;
	}

	/**
	 * This function outputs the loaded model into an HTML string by inserting its values into the used template
	 *
	 * @return string
	 */
	public function getOutput()
	{ 
		foreach ($this->convertedProperties as $propName => $propValue)
			($propName == "model_creationTime" || $propName == "model_lastModified" || $propName == "model_publishDate")
				? $this->modelDisplay->addContent(array($propName => date('l jS \of F Y h:i:s A', $propValue)))
				: $this->modelDisplay->addContent(array($propName => $propValue));

/*		$modelTags = $this->modelDisplay->getTags();

		if($modelTags)
			foreach($modelTags as $tag)
		{
			if(!in_array($tag, $this->modelDefaults) && (strpos($tag, 'model_') === 0))
			{
				$customTag = substr($tag, 6);
				if(isset($this->convertedProperties[$customTag]))
				{
					$this->modelDisplay->addContent($tag, $this->convertedProperties[$customTag]);
				}elseif(isset($this->model->$customTag)){
					$this->modelDisplay->addContent($tag, $this->model->$customTag);
				}
			}
		}*/

		$modelOutput = $this->modelDisplay->getDisplay();

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
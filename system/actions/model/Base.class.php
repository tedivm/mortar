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
 * This is the base class for all the other model based actions. It handles loading various information, checking
 * permissions, and setting cache headers based on the models age.
 *
 * @abstract
 * @package System
 * @subpackage ModelSupport
 */
abstract class ModelActionBase extends ActionBase
{
	/**
	 * This is the model that called up the action.
	 *
	 * @access protected
	 * @var Model
	 */
	protected $model;

	/**
	 * This is the type of model thats being acted on. This can be set by inheriting classes to restrict an action
	 * to a certain type of resource, but that would make it slightly more difficult for people to expand off of those
	 * classes.
	 *
	 * @access protected
	 * @var string
	 */
	protected $type;

	/**
	 * This defines the permission action that the user needs to run this. Permissions are based off of an action and
	 * a resource type, so this value is used with the model type to generate a permissions object
	 *
	 * @access public
	 * @var string
	 */
	public static $requiredPermission = 'Read';

	/**
	 * This constructor takes a model and the ioHandler. It saves these, and some other meta data about the class,
	 * for future use.
	 *
	 * @param Model $identifier
	 * @param ioHandler $handler
	 */
	public function __construct($identifier, $handler)
	{
		// because the action interface doesn't specify the aguement type, we have to check seperately
		// to make sure we don't conflict with that interface
		if(!($identifier instanceof Model))
			throw new TypeMismatch(array('Model', $identifier));

		if(isset($this->type) && $identifier->getType() !== $this->type)
			throw new TypeMismatch(array($this->type, $identifier));

		$this->model = $identifier;
		$this->ioHandler = $handler;
		$this->type = $this->model->getType();
		$namingInfo = explode('Action', get_class($this));
		$this->actionName = array_pop($namingInfo);
		$this->package = array_shift($namingInfo);

		$query = Query::getQuery();

		if($query['format'] == 'Admin')
		{
			$this->cacheExpirationOffset = 0;
		}
	}

	/**
	 * This function starts the action. It runs a check on permissions, to make sure the user can run the action
	 * (which is a little redundent, as the ioHandler should also be checking permissions before loading the class, but
	 * whatever), then runs the logic function (which should be defined by the children classes), before finally sending
	 * along any headers to the iohandler.
	 *
	 */
	public function start()
	{
		if($this->checkAuth() !== true)
			throw new AuthenticationError('Insufficient permissions to access this action');

		if(method_exists($this, 'logic'))
			$this->logic();


		if(method_exists($this, 'setHeaders'))
			$this->setHeaders();
	}

	/**
	 * This function sends along the Last-Modified headers, and if $this->cacheExpirationOffset is set it also sends
	 * that to the ioHandler. This is vital for client side http caching
	 *
	 * @access protected
	 */
	protected function setHeaders()
	{

	}

	/**
	 * This class checks to make sure the user has permission to access this action. If passed an argument it will check
	 * for other action types at this location, with this resource (this is useful for checking before redirecting to a
	 * different action on the same location).
	 *
	 * @param string $action
	 * @return bool
	 */
	public function checkAuth($action = NULL)
	{
		$action = isset($action) ? $action : static::$requiredPermission;
		return $this->model->checkAuth($action);
	}

	/**
	 * Returns the name of the action.
	 *
	 * @return string
	 */
	public function getName()
	{
		if($descent = $this->model->getDescent()) {
			foreach($descent as $model) {
				if (strpos($this->actionName, $model) === 0)
					return substr($this->actionName, strlen($model));
			}
		}

		if (strpos($this->actionName, $this->type) === 0)
			return substr($this->actionName, strlen($this->type));

		return $this->actionName;
	}

	/**
	 * This creates the permission object and saves it. This function can be overwritten for special purposes, such as
	 * with the Add class which needs to check the parent models permission, not the current model.
	 *
	 */
	protected function setPermissionObject()
	{
		$user = ActiveUser::getUser();
		$this->permissionObject = new Permissions(1, $user);
	}

	/**
	 * This method is called by the viewAdmin methods of various actions to append a list of details about 
	 * the model being viewed to their output. It returns the result of inserting the model into the
	 * adminDetails.html template, along with any additional results added via plugin.
	 *
	 * @param Page $page
	 */
	protected function getAdminDetails($page)
	{
		$output = $this->modelToHtml($page, $this->model, 'adminDetails.html');

		$hook = new Hook();
		$hook->loadPlugins('Template', 'admin', 'extraDetails');
		$results = $hook->getDetails($this->model);

		foreach($results as $detail)
			$output .= $detail;

		return $output;
	}

	/**
	 * This method is used to transform a model's content into HTML for output. Taking the current page
	 * and model, it inserts the model's content into the provided template name, also inserting any
	 * additional content tags passed in the $content parameter.
	 *
	 * @param Page $page
	 * @param Model $model
	 * @param string $templateName
	 * @param array $content
	 */
	protected function modelToHtml($page, $model, $templateName, $content = array())
	{
		$htmlConverter = $model->getModelAs('Html', $templateName);
		$htmlConverter->addContent($content);
		return $htmlConverter->getOutput();
	}

	/*
	public function viewAdmin()
	{

	}

	public function viewHtml()
	{

	}

	public function viewXml()
	{

	}

	public function viewJson()
	{

	}
	*/
}

?>
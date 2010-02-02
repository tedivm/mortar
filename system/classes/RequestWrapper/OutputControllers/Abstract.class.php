<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage RequestWrapper
 */

/**
 * This class takes an action, runs it, and then formats the output.
 *
 * @abstract
 * @package System
 * @subpackage RequestWrapper
 */
abstract class AbstractOutputController
{
	/**
	 * This is the current action being run
	 *
	 * @var Action
	 */
	protected $action;

	/**
	 * This is the format the user wants to have returned
	 *
	 * @var string
	 */
	protected $format;

	/**
	 * This is the permissions object for the current location and user
	 *
	 * @var Permissions
	 */
	protected $permission;

	/**
	 * This is the model type (Site, Directory, CmsPage, etc)
	 *
	 * @var string
	 */
	protected $resourceType;

	/**
	 * Some output filters, such as the html one, have a class they use to generate their output. In this case the item
	 * is stored here.
	 *
	 * @var mixed
	 */
	protected $activeResource;

	/**
	 * This is the input/output processer (rest, http, cli) running the current request
	 *
	 * @var ioHandler
	 */
	protected $ioHandler;

	/**
	 * This is an array of objects that the content (responses from the model's action class) are filtered through.
	 *
	 * @var array
	 */
	protected $contentFilters = array();

	/**
	 * This is an array of object that the output it filtered through before being sent to the user
	 *
	 * @var array
	 */
	protected $outputFilters = array();

	/**
	 * This is the mime type that the web browser sends out for this particular format.
	 *
	 * @var string
	 */
	public $mimeType;

	/**
	 * This constructor takes in and saves the ioHandler
	 *
	 * @param ioHandler $ioHandler
	 */
	public function __construct($ioHandler)
	{
		$this->ioHandler = $ioHandler;
	}

	/**
	 * This function sets the class up to handle the action. It also calls $this->start, which can be overloaded by
	 * inheriting classes
	 *
	 * @param Action $action
	 */
	public function initialize($action)
	{
		if(!$this->format)
		{
			$class = get_class($this);
			$this->format = substr($class, 0, strpos($class, 'OutputController'));
		}

		$this->action = $action;

		$this->start();

		if(isset($this->mimeType))
			$this->ioHandler->addHeader('Content-type', $this->mimeType);
	}


	/**
	 * This function puts together and returns the output. It calls on processAction() to get the result, which it
	 * sents to bundleOutput(). From there it iterates through the outputFilters, passing $this to it, before
	 * running and returning makeDisplayFromResource().
	 *
	 * @return string
	 */
	public function getFinalOutput()
	{
		$actionResults = $this->processAction();
		$this->bundleOutput($actionResults);

		foreach($this->outputFilters as $filter)
			$filter->update($this);

		$output = $this->makeDisplayFromResource();
		return $output;
	}

	/**
	 * Returns the current action
	 *
	 * @return Action
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Returns the current format
	 *
	 * @return string
	 */
	public function getFormat()
	{
		return $this->format;
	}

	/**
	 * Returns the active resource (Page, xml, null).
	 *
	 * @return mixed
	 */
	public function getResource()
	{
		return $this->activeResource;
	}

	/**
	 * This function adds a content filter to the class. These filters are run on the value that the model action
	 * returns.
	 *
	 * @param object $filterClass
	 */
	public function addContentFilter($filterClass)
	{
		$this->contentFilters[] = $filterClass;
	}

	/**
	 * This function adds an output filter to the class. These filters are passed this entire class directly before
	 * the final output is sent out.
	 *
	 * @param object $filterClass
	 */
	public function addOutputFilter($filterClass)
	{
		$this->outputFilters[] = $filterClass;
	}

	/**
	 * This function should be set by the inheriting classes. It is passed the output of the model action to be bundled
	 * with the current active resource.
	 *
	 * @param unknown_type $output
	 */
	protected function bundleOutput($output)
	{
		$this->activeResource = $output;
	}

	/**
	 * This function should take the active resource and turn it into a string to be displayed.
	 *
	 * @return string
	 */
	abstract protected function makeDisplayFromResource();

	/**
	 * This is blank by default, but can be used by inheriting classes to define actions that should take place when
	 * initialize() is called.
	 *
	 */
	protected function start()
	{

	}

	/**
	 * This is where the action is run. It filters the output of the action through the objects in contentFilters
	 * before outputing the results.
	 *
	 * @return mixed
	 */
	protected function processAction()
	{
		$runMethod = 'view' . $this->format;
		$this->action->start();
		$output = $this->action->$runMethod($this->activeResource);

		foreach($this->contentFilters as $filter)
			$output = $filter->update($this, $output);

		return $output;
	}

	/**
	 * This checks to make sure the action can actually be run with this particular format.
	 *
	 * @param Action $action
	 * @return bool
	 * @todo Write format-specific permissions
	 */
	public function checkAction($action, $format)
	{
		try
		{
			if(!$action)
				throw new TypeMismatch(array('Action', $action));

			$settingsArrayName = strtolower($format) . 'Settings';

			$viewMethod = 'view' . $format;

			// check to see that it has an output for the current format
			if(!in_array($viewMethod, get_class_methods($action)))
				throw new ResourceNotFoundError('Action ' . get_class_methods($action) . ' needs ' . $viewMethod
							. ' method to be available for this format');

			if(isset($this->permission)
				&& !isset($action->{$settingsArrayName}['EnginePermissionOverride']))
			{
				if(!$action->checkAuth($this->permission))
					throw new AuthenticationError('Not allowed to access this engine at this location.');
			}

		}catch(Exception $e){
			return false;
		}

		return true;
	}

}


?>
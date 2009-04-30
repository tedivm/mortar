<?php

abstract class AbstractOutputController
{
	protected $action;
	protected $format;
	protected $permission;
	protected $runMethod;

	protected $resourceType;
	protected $activeResource;

	protected $ioHandler;

	protected $contentFilters = array();
	protected $outputFilters = array();

	public $mimeType;

	public function __construct($ioHandler)
	{
		$this->ioHandler = $ioHandler;
	}

	public function initialize($action)
	{
		if(!$this->checkAction($action))
			throw new TypeMismatch(array('Action', $action));

		$this->action = $action;

		if(!$this->format)
		{
			$class = get_class($this);
			$this->format = substr($class, 0, strpos($class, 'OutputController'));
		}

		if(isset($this->mimeType))
			$this->ioHandler->addHeader('Content-type', $this->mimeType);


		$this->start();
	}


	public function getFinalOutput()
	{
		$actionResults = $this->processAction();
		$this->bundleOutput($actionResults);

		foreach($this->outputFilters as $filter)
			$filter->update($this);

		$output = $this->makeDisplayFromResource();
		return $output;
	}




	public function getAction()
	{
		return $this->action;
	}

	public function getFormat()
	{
		return $this->format;
	}

	public function getResource()
	{
		return $this->activeResource;
	}

	public function addContentFilter($filterClass)
	{
		$this->contentFilters[] = $filterClass;
	}

	public function addOutputFilter($filterClass)
	{
		$this->outputFilters[] = $filterClass;
	}


	abstract protected function bundleOutput($output);

	abstract protected function makeDisplayFromResource();

	protected function start()
	{

	}

	protected function processAction()
	{
		$runMethod = (isset($this->runMethod)) ? $this->runMethod : 'view' . $this->format;
		$this->action->start();
		$output = $this->action->$runMethod($this->activeResource);
		return $this->filterOutput($output);
	}

	protected function filterOutput($output)
	{
		foreach($this->contentFilters as $filter)
			$output = $filter->update($this, $output);

		return $output;
	}

	protected function checkAction($action)
	{
		if(!$action)
			throw new TypeMismatch(array('Action', $action));

		$query = Query::getQuery();
		$settingsArrayName = $query['format'] . 'Settings';

		$viewMethod = 'view' . $query['format'];

		// check to see that it has an output for the current format
		if(!in_array($viewMethod, get_class_methods($action)))
			throw new ResourceNotFoundError('Action ' . get_class_methods($action) . ' needs ' . $viewMethod
						. ' method to be available for this format');

		if(isset($this->permission)
			&& !isset($action->{$settingsArrayName}['EnginePermissionOverride']))
		{
			if(!$action->checkAuth(staticHack($formatFilter, 'permission')))
				throw new AuthenticationError('Not allowed to access this engine at this location.');
		}

		return true;
	}
}


?>
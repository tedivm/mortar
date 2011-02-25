<?php

class MortarSearchSearch
{
	static $runtimeDisable = false;
	static $liveIndex = true;
	static $currentEngine;
	static $excludedModels = array('Root', 'TrashCan', 'TrashBag', 'Site');
	static $maxResults = 1000;
	static $batchSize = 500;

	protected static $defaultEngine = 'Lucene';
	protected static $includedEngines = array('Lucene' => 'MortarSearchEngineLucene');

	protected $searchEnabled = true;
	protected $engine;

	public function __construct(MortarSearchEngine $engine)
	{
		if((defined('DISABLESEARCH') && DISABLESEARCH) || self::$runtimeDisable)
			$this->searchEnabled = false;

		$this->engine = $engine;
	}

	static public function disableSearch()
	{
		self::$runtimeDisable = true;
	}

	static public function enableSearch()
	{
		self::$runtimeDisable = false;
	}

	static public function getEngines()
	{
		$engines = self::loadEngines();
		return array_keys($engines);
	}

	static public function getSearch()
	{
		if(isset(self::$currentEngine)) {
			$engineClass = self::$currentEngine;
		} else {
			$engines = self::loadEngines();

			$config = Config::getInstance();
			$engineType = (isset($config['system']['searchEngine'])
				&& isset($handlers[$config['system']['searchEngine']]))
					? $config['system']['searchEngine']
					: self::$defaultEngine;

			$engineClass = $engines[$engineType];

			if(!class_exists($engineClass))
			{
				self::$enableSearch = false;
				throw new SearchError('Unable to load search engine ' . $handlerType);
			}
		}

		$engine = new $engineClass($config['path']['temp'] . 'search');
		return new Search($engine);
	}

	static protected function loadEngines()
	{
		$hook = new Hook('system', 'search', 'engines');
		$plugins = Hook::mergeResults($hook->getEngineClasses());

		return array_merge(self::$includedEngines, $plugins);
	}

	public function liveIndex()
	{
		$engine = get_class($this->engine);
		if((defined('DISABLELIVEINDEX') && DISABLELIVEINDEX) || !(self::$liveIndex) || !staticHack($engine, 'liveIndex'))
			return false;

		return true;
	}

	public function index($models)
	{
		$alreadyIndexed = array();

		if((defined('DISABLESEARCH') && DISABLESEARCH) || self::$runtimeDisable || !$this->searchEnabled)
			return false;

		if(!is_array($models))
			$models = array($models);

		foreach($models as $model) {
			$target = $model->getIndexedModel();
			if(in_array($model->getType() . '_' . $model->getId(), $alreadyIndexed))
				continue;

			$alreadyIndexed[] = $model->getType() . '_' . $model->getId();
			$extraFields = $model->getExtraFields();

			$this->engine->index($target, $extraFields);
		}

		return $this->engine->commit();
	}

	public function getSize()
	{
		if((defined('DISABLESEARCH') && DISABLESEARCH) || self::$runtimeDisable || !$this->searchEnabled)
			return false;

		return $this->engine->getSize();
	}

	public function query($query, $size = null)
	{
		if((defined('DISABLESEARCH') && DISABLESEARCH) || self::$runtimeDisable || !$this->searchEnabled)
			return false;

		if(!isset($size) || $size > self::$maxResults)
			$size = self::$maxResults;

		$results = $this->engine->search($query, $size);

		return $this->filterResults($results);
	}

	public function reindex()
	{
		if((defined('DISABLESEARCH') && DISABLESEARCH) || self::$runtimeDisable || !$this->searchEnabled)
			return false;

		$this->engine->resetIndex();
		$modelTypes = ModelRegistry::getModelList();

		foreach($modelTypes as $type) {
			if(in_array($type, self::$excludedModels))
				continue;

			if(!$model = ModelRegistry::loadModel($type))
				continue;

			$modelClass = get_class($model);
			$isSearchable = staticHack($modelClass, 'isSearchable');
			if($isSearchable === false)
				continue;

			if(!$tables = $model->getTables())
				continue;

			if(!method_exists($model, 'getLocation')) {
				$listing = new ModelListing($tables[0], $type);
			} else {
				$listing = new LocationListing();
				$listing->addRestriction('type', $type);
			}

			$offset = 0;
			while($results = $listing->getListing(self::$batchSize, self::$batchSize * $offset++)) {
				$items = array();
				foreach($results as $info) {
					$items[] = ModelRegistry::loadModel($info['type'], $info['id']);
				}
				$this->index($items);
			}
		}

		return true;
	}

	public function optimize()
	{
		return $this->engine->optimize();
	}

	protected function filterResults($results)
	{
		$user = ActiveUser::getUser();
		$filteredModels = array();
		foreach($results as $modelInfo) {
			try {
				$model = ModelRegistry::loadModel($modelInfo['type'], $modelInfo['id']);

				if($model->checkAuth('Read', $user))
					$filteredModels[] = $modelInfo;

			} catch(Exception $e) {

			}
		}
		return $filteredModels;
	}
}

class SearchError extends CoreError
{
}

interface MortarSearchEngine
{
	public function __construct($path);
	public function clear($model);
	public function index($model, $extraFields = array());
	public function commit();
	public function search($query, $size = null);
	public function getSize();
	public function resetIndex();
	public function optimize();
}

?>
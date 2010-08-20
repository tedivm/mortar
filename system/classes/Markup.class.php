<?php

class Markup
{
	static $defaultEngines = array('html' => 'MarkupHtml', 'markdown' => 'MarkupMarkdown');
	static $availableEngines;
	static protected $defaultEngine = 'html';

	protected $engine;

	public function __construct(MarkupEngine $engine)
	{
		$this->engine = $engine;
	}

	public function markupText($text)
	{
		$text = $this->engine->markupText($text);
		$text = $this->engine->filterText($text);
		$text = $this->engine->prettifyText($text);

		return $text;
	}

	static function getMarkup($markupType)
	{
		if(!($markupType instanceof MarkupEngine)) {
			if(!isset(self::$availableEngines))
				self::$availableEngines = self::loadEngines();

			$markupType = strtolower($markupType);

			if(isset(self::$availableEngines[$markupType])) {
				$engineObject = new self::$availableEngines[$markupType]();
			} else {
				return false;
			}
		}

		$markup = new Markup($engineObject);
		return $markup;
	}
	
	static function getEngines()
	{
		$engineInfo = self::loadEngines();
		$engines = array();

		foreach($engineInfo as $name => $class)
			$engines[] = $name;

		return $engines;
	}

	static protected function loadEngines()
	{
		$hook = new Hook('system', 'markup', 'engines');
		$plugins = Hook::mergeResults($hook->getEngineClasses());

		return array_merge(self::$defaultEngines, $plugins);
	}

	static function getModelEngine($resource)
	{
		return self::getMarkup(self::loadModelEngine($resource));
	}

	static function loadModelEngine($resource, $setting = false)
	{
		if($resource instanceof Model) {
			if(method_exists($resource, 'getLocation')) {
				$location = $resource->getLocation();
				if($parent = $location->getParent()) {
					$id = $location->getId();
					$parentM = $parent->getResource();
				}
			}

			$resource = ModelRegistry::getIdFromType($resource->getType());
		}

		if(!is_numeric($resource))
			$resource = ModelRegistry::getIdFromType($resource);

		if(!isset($id))
			$id = 1;

		$cache = CacheControl::getCache('models', $resource, 'settings', 'markup', $id);
		$data = $cache->getData();

		if($cache->isStale())
		{
			$stmt = DatabaseConnection::getStatement('default_read_only');
			$stmt->prepare('SELECT markupEngine FROM markup WHERE modelId = ? AND location = ?');
			$stmt->bindAndExecute('ii', $resource, $id);

			if($row = $stmt->fetch_array()) {
				$data['value'] = ($row['markupEngine']);
				$data['setting'] = true;
			} else {
				$data = false;
			}
			$cache->storeData($data);
		}

		if($setting) {
			return ($data['setting'] ? $data['value'] : false);
		}

		if($data) {
			return $data['value'];
		} elseif(isset($parentM) && $result = self::loadModelEngine($parentM)) {
			return $result;
		} elseif($id === 1) {
			$data['setting'] = false;
			$model = ModelRegistry::loadModel($resource);
			if($engine = staticHack(get_class($model), 'richtext')) {
				return $engine;
			} else {
				return self::$defaultEngine;
			}
		}
	}

	static function setModelEngine($resource, $engine, $loc = 1)
	{
		if(!is_numeric($resource))
			$resource = ModelRegistry::getIdFromType($resource);

		if(!$resource)
			return false;

		$engines = self::getEngines();
		if(!in_array($engine, $engines))
			return false;

		$orm = new ObjectRelationshipMapper('markup');
		$orm->modelId = $resource;
		$orm->select();
		$orm->markupEngine = $engine;
		$orm->location = $loc;
		$orm->save();
	}

	static function clearModelEngine($resource)
	{
		if(!is_numeric($resource))
			$resource = ModelRegistry::getIdFromType($resource);

		if(!$resource)
			return false;

		$orm = new ObjectRelationshipMapper('markup');
		$orm->modelId = $resource;
		$orm->delete();
	}
}

interface MarkupEngine
{
	public function markupText($text);
	public function filterText($text);
	public function prettifyText($text);
}
?>
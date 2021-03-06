<?php

class Markup
{
	static $defaultEngines = array('html' => 'MarkupHtml', 'markdown' => 'MarkupMarkdown');
	static $defaultPost = array('smartypants' => 'MarkupSmartyPants', 'autolinks' => 'MarkupAutoLinks');
	static $availableEngines;

	static protected $defaultEngine = 'html';

	protected $htmlPurifierConfig = null;

	protected $engine;

	public function __construct(MarkupEngine $engine)
	{
		$this->engine = $engine;
	}

	public function markupText($text)
	{
		$text = $this->engine->markupText($text);
		$text = $this->filterText($text);
		$text = $this->prettifyText($text);

		return $text;
	}


	protected function filterText($text)
	{
		$pur = new HTMLPurifier($this->htmlPurifierConfig);
		return $pur->purify($text);
	}

	protected function prettifyText($text)
	{
		$post = self::loadPost();

		foreach($post as $name => $class) {
			if(self::getMarkupPost($name)) {
				$mp = new $class();
				$text = $mp->prettifyText($text);
			}
		}

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
		return array_keys($engineInfo);
	}

	static function getPost()
	{
		$postInfo = self::loadPost();
		return array_keys($postInfo);
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
			$modelClass = get_class($model);
			if($engine = $modelClass::$richtext) {
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


	static function getMarkupPost($post)
	{
		$orm = new ObjectRelationshipMapper('markupPost');
		$orm->markupPost = $post;
		if($orm->select()) {
			$values = $orm->toArray();
			return (bool) $values['enabled'];
		} else {
			return false;
		}
	}

	static function setMarkupPost($post, $value)
	{
		$orm = new ObjectRelationshipMapper('markupPost');
		$orm->markupPost = $post;
		$orm->select();
		$orm->enabled = $value ? '1' : '0';
		$orm->save();
	}

	static protected function loadEngines()
	{
		$hook = new Hook('system', 'markup', 'engines');
		$plugins = Hook::mergeResults($hook->getEngineClasses());

		return array_merge(self::$defaultEngines, $plugins);
	}

	static protected function loadPost()
	{
		$hook = new Hook('system', 'markup', 'post');
		$plugins = Hook::mergeResults($hook->getPostClasses());

		return array_merge(self::$defaultPost, $plugins);	
	}
}

interface MarkupEngine
{
	public function markupText($text);
}

interface MarkupPost
{
	public function prettifyText($text);
}

?>
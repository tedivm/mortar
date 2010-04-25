<?php

class Markup
{
	static $defaultEngines = array('html' => 'MarkupHtml', 'markdown' => 'MarkupMarkdown');
	static $availableEngines;

	protected $defaultEngine = 'html';
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
}

interface MarkupEngine
{
	public function markupText($text);
	public function filterText($text);
	public function prettifyText($text);
}
?>
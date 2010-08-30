<?php

class SearchLucene implements SearchEngine
{
	static public $liveIndex = true;
	static public $reindex = 86400;
	static public $maxResults = 1000;

	protected $searchDirectory = 'Zend_Search_Lucene/';

	protected $defaultAnalyzer = 'Zend_Search_Lucene_Analysis_Analyzer_Common_TextNum_CaseInsensitive';

	protected $path;
	protected $search;

	public function __construct($path)
	{
		$analyzer = $this->defaultAnalyzer;
		Zend_Search_Lucene_Analysis_Analyzer::setDefault(new $analyzer());
		$fullPath = $path . '/' . $this->searchDirectory;
		$zendPath = new Zend_Search_Lucene_Storage_Directory_Filesystem($fullPath);
		$this->path = $zendPath;
		try {
			$this->search = Zend_Search_Lucene::open($zendPath);
		} catch (Zend_Search_Lucene_Exception $e) {
			$this->search = Zend_Search_Lucene::create($zendPath);
		}
	}

	public function clear($model)
	{
		$unique = $model->getType() . '_' . $model->getId();
		$hits = $this->search->find('unique:"' . $unique . '"');
		foreach($hits as $hit) {
			$this->search->delete($hit->id);
		}
		$this->commit();
	}

	public function index($model, $extraFields = array())
	{
		$this->clear($model);

		$doc = new Zend_Search_Lucene_Document();
		$doc->addField(Zend_Search_Lucene_Field::Keyword('unique', $model->getType() . '_' . $model->getId()));
		$doc->addField(Zend_Search_Lucene_Field::Keyword('type', $model->getType()));
		$doc->addField(Zend_Search_Lucene_Field::UnIndexed('modelid', $model->getId()));
		$doc->addField(Zend_Search_Lucene_Field::Text('designation', $model->getDesignation()));
		if($content = $model['content'])
			$doc->addField(Zend_Search_Lucene_Field::UnStored('content', $content));

		foreach($extraFields as $name => $type) {
			if(isset($model[$name])) {
				$value = $model[$name];
			} elseif(isset($model->$name)) {
				$value = $model->$name;
			}

			if(isset($value)) {
				if($type === 'key') {
					$doc->addField(Zend_Search_Lucene_Field::Keyword($name, $value));
				} elseif($type === 'text') {
					$doc->addField(Zend_Search_Lucene_Field::Text($name, $value));
				} else {
					$doc->addField(Zend_Search_Lucene_Field::UnStored($name, $value));
				}
			}
		}

		if(method_exists($model, 'getLocation')) {
			$key = '';
			$loc = $model->getLocation();
			$descent = ($loc->getId())
				? $loc->getPathToRoot()
				: array();
			foreach($descent as $id) {
				$key .= 'l_' . $id . '_l ';
			}
			$doc->addField(Zend_Search_Lucene_Field::Text('descent', trim($key)));
		}

		$this->search->addDocument($doc);
	}

	public function commit()
	{
		return $this->search->commit();
	}

	public function search($query, $size = null)
	{
		if(!isset($size) || $size > self::$maxResults) {
			$size = self::$maxResults;
		}
		Zend_Search_Lucene::setResultSetLimit($size);
		$results = $this->search->find($query);

		$info = array();
		foreach($results as $result) {
			$item = array();
			$item['id'] = $result->modelid;
			$item['type'] = $result->type;
			$item['score'] = $result->score;
			$info[] = $item;
		}

		return $info;
	}

	public function getSize()
	{
		return $this->search->numDocs();
	}

	public function resetIndex()
	{
		$this->search = Zend_Search_Lucene::create($this->path);
		$this->search->commit();
	}

	public function optimize()
	{
		$this->search->optimize();
	}
}

?>
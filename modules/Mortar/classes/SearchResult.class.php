<?php

class MortarSearchResult {

	protected $results = array();
	protected $models = array();
	protected $info = array();
	protected $browseField;
	protected $count;
	protected $offset;
	protected $page;

	protected $pageSize = 20;
	protected $listType = 'table';
	protected $tableDisplayList = 'ViewTableDisplayList';
	protected $templateDisplayList = 'ViewTemplateDisplayList';


	public function __construct($results)
	{
		$this->results = $results;
		$this->count = count($results);
	}

	public function getOutput()
	{
		$this->process();

		$pagination = $this->getPagination();

		$indexList = $this->getDisplayList($this->listType);
		$indexList->addPage(ActivePage::getInstance());

		return $pagination . $indexList->getListing(). $pagination;
	}

	protected function process()
	{
		$query = Query::getQuery();
		$this->page = (isset($query['page']) && is_numeric($query['page']))
			? $query['page']
			: 1;

		$this->browseField = (isset($query['browseBy']))
			? $query['browseBy']
			: 'score';

		$order = (isset($query['order']) && in_array(strtolower($query['order']), array('asc', 'desc')))
			? $query['order']
			: 'asc';

		$callback = array($this, 'browseCompare');
		usort($this->results, $callback);
		if($order === 'desc') {
			$this->results = array_reverse($this->results);
		}


		$this->offset = $this->pageSize * ($this->page - 1);
		$subset = array_slice($this->results, $this->offset, $this->pageSize);

		foreach($subset as $info) {
			$this->models[] = ModelRegistry::loadModel($info['type'], $info['id']);
			$unique = $info['type'] . '_' . $info['id'];
			$this->info[$unique] = $info;
		}
	}

	protected function getPagination()
	{
		$model = ActiveSite::getSite();
		$p = new TagBoxPagination($model);
		$url = Query::getUrl();
		$p->defineListing($this->count, $this->pageSize, $this->page, $url, 
			$this->offset + 1, $this->offset + count($this->models));

		if($this->page === 0)
			$p->setOnPage(false);

		return $p->pageList();
	}

	protected function getDisplayList($type = 'table')
	{
		if($type == 'template') {
			$class = $this->templateDisplayList;
		} else {
			$class = $this->tableDisplayList;
		}

		$model = ActiveSite::getSite();
		$indexList = new $class($model, $this->models);

		if($type == 'table') {
			$indexList->useIndex(true, $this->offset);
			$indexList->filterable(false);
		}

		return $indexList;
	}

	protected function browseCompare($model1, $model2)
	{
		$field = $this->browseField;

		$m1 = ModelRegistry::loadModel($model1['type'], $model1['id']);
		$m2 = ModelRegistry::loadModel($model2['type'], $model2['id']);

		$field = $this->browseField;
		$getter = 'get' . $this->browseField;

		$s = array();
		foreach(array($m1, $m2) as $m) {
			if(is_callable($m, $getter)) {
				$s[] = $m->$getter();
			} elseif(isset($m->$field)) {
				$s[] = $m->$field;
			} elseif(isset($m[$field])) {
				$s[] = $m[$field];
			} else {
				$s[] = null;
			}
		}

		return strcasecmp($s[0], $s[1]);
	}

}

?>
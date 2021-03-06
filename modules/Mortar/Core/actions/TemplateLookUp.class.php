<?php

class MortarCoreActionTemplateLookUp extends ActionBase
{
	static $requiredPermission = 'Read';

	protected $list = array();

	protected $maxLimit = 25;
	protected $limit = 10;

	public function logic()
	{
		$offset = 43200;
		$this->ioHandler->addHeader('Expires', gmdate(HTTP_DATE, time() + $offset));

		$query = Query::getQuery();
		if(isset($query['term']) && ActiveUser::isLoggedIn() && isset($query['t'])) {

			$themeName = $query['t'];

			$limit = isset($query['limit']) && is_numeric($query['limit']) ? $query['limit'] : $this->limit;

			if($limit > $this->maxLimit)
				$limit = $this->maxLimit;

			$cache = CacheControl::getCache('templateLookup', 'bystring', $themeName, $query['term'], $limit);
			$locList = $cache->getData();

			if($cache->isStale())
			{
				$templateList = array();

				$theme = new Theme($themeName);
				$themePath = $theme->getPath();

				$templates = glob($themePath . $query['term'] . "*.html");

				foreach($templates as $temp) {
					$path = explode('/', $temp);
					$filename = $path[count($path) - 1];
					$tempname = substr($filename, 0, -5);
					$templateList[] = array('value' => $tempname, 'label' => $tempname, 'id' => $tempname);
				}

				$cache->storeData($templateList);
			}
			$this->list = $templateList;

		}
	}

	public function viewAdmin($page)
	{
		$output = '';
		foreach($this->list as $template)
			$output .= $template['value'] . '<br>';
		return $output;
	}

	public function viewHtml($page)
	{
		return $html;
	}

	public function viewXml()
	{
		return $xml;
	}

	public function viewJson()
	{
		return $this->list;
	}
}

?>
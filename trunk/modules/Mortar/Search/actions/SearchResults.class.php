<?php

class MortarSearchActionSearchResults extends ActionBase
{
	static $requiredPermission = 'Read';

	public static $settings = array( 'Base' => array( 'headerTitle' => 'Search Results' ) );

	protected $searchResults;
	protected $query;

	public function logic()
	{
		$query = Query::getQuery();
		if(!isset($query['query'])) {
	                $url = Query::getUrl();
	                $url->action = 'Search';
	                $this->ioHandler->addHeader('Location', (string) $url);
		}

		$this->getResults((array) $query['query']);
	}

	protected function getResults($query)
	{
		$q = implode(' ', $query);
		$search = MortarSearchSearch::getSearch();
		$this->query = filter_var($q, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
		$this->searchResults = $search->query($this->query);
	}

	public function viewAdmin($page)
	{
		$output = '<p>Search results for <i>' . $this->query . '</i>:</p>';

		if(isset($this->searchResults)) {
			$result = new MortarSearchResult($this->searchResults);

			$output .= $result->getOutput();
		} else {
			$output .= 'Please return to the search page and submit a query.';
		}

		return $output;
	}

	public function viewHtml($page)
	{
		return $this->viewAdmin($page);
	}
}

?>
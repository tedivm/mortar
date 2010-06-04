<?php

class TagBoxPagination
{
	protected $model;
	protected $theme;

	protected $count;
	protected $pagesize;
	protected $start;
	protected $end;
	protected $current;
	protected $url;
	protected $onPage = true;

	protected $prev = "&lt;";
	protected $next = "&gt;";

	public function __construct($model)
	{
		$this->model = $model;
		$page = ActivePage::getInstance();
		$this->theme = $page->getTheme();
	}

	public function setDelimiters($prev, $next)
	{
		$this->prev = $prev;
		$this->next = $next;
	}

	public function defineListing($count, $pagesize, $current, $url, $start = null, $end = null)
	{
		$this->count = $count;
		$this->pagesize = $pagesize;
		$this->current = $current;
		$this->url = $url;

		if(isset($start) && isset($end)) {
			if($start > $count || $end > $count) {
				$start = 0;
				$end = 0;
			} elseif($start > $end) {
				list($start, $end) = array($end, $start); //swap 'em
			}

			$this->start = $start;
			$this->end = $end;
			if(!($this->start % $pagesize === 1)) {
				$this->onPage = false;
			}
		} else {
			$this->start = $pagesize * ($current - 1) + 1;
			$this->end = $pagesize * $current;
		}
	}

	public function setOnPage($onPage)
	{
		$this->onPage = $onPage;
	}

	public function pageList($prevNext = true, $numeric = true, $margin = 2)
	{
		if(!$this->onPage)
			$this->current = 1;

		$numPages = ceil($this->count / $this->pagesize);
		$content = array('start' => $this->start, 'end' => $this->end, 'total' => $this->count);
		$pages = array();
		$li = new HtmlObject('li');

		$startPage = ($this->current - $margin) > 1 ? $this->current - $margin : 1;
		$endPage   = ($this->current + $margin) < $numPages ? $this->current + $margin : $numPages;

		if($prevNext && $startPage > 1) {
			$url = clone($this->url);
			$item = clone($li);
			$url->page = 1;
			$pages[] = $item->wrapAround($url->getLink($this->prev . $this->prev . ' First'));
		}

		if($prevNext && $this->current > 1) {
			$url = clone($this->url);
			$item = clone($li);
			$url->page = ($this->current - 1);
			$pages[] = $item->wrapAround($url->getLink($this->prev . ' Previous'));
		}

		if($numeric) {
			if($startPage > 1) {
				$item = clone($li);
				$pages[] = $item->wrapAround('...');
			}

			for($i = $startPage; $i <= $endPage; $i++) {
				$item = clone($li);

				if($this->onPage && $i === (int) $this->current) {
					$pages[] = $item->wrapAround($i);
				} else {
					$url = clone($this->url);
					$url->page = $i;
					$pages[] = $item->wrapAround($url->getLink(' ' . $i . ' '));
				}
			}

			if($endPage < $numPages) {
				$item = clone($li);
				$pages[] = $item->wrapAround('...');
			}
		}

		if($prevNext && $this->current < $numPages) {
			$url = clone($this->url);
			$item = clone($li);
			$url->page = ($this->current + 1);
			$pages[] = $item->wrapAround($url->getLink('Next ' . $this->next));
		}

		if($prevNext && $endPage < $numPages) {
			$url = clone($this->url);
			$item = clone($li);
			$url->page = $numPages;
			$pages[] = $item->wrapAround($url->getLink('Last ' . $this->next . $this->next));
		}

		$content['pages'] = $pages;
		$pageView = new ViewModelTemplate($this->theme, $this->model, 'Pagination.html');
		$pageView->addContent($content);
		return $pageView->getDisplay();
	}
}

?>
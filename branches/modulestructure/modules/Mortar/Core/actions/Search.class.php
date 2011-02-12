<?php

class MortarCoreActionSearch extends FormAction
{
	static $requiredPermission = 'Read';

	public static $settings = array( 'Base' => array( 'headerTitle' => 'Search' ) );

	public function getForm()
	{
		$form = new MortarCoreSimpleSearchForm('SimpleSearch');

		return $form;
	}

	public function processInput($inputHandler)
	{
		$search = $inputHandler['search_query'];

		$items = explode(' ', $search);
		$query = array();
		foreach($items as $item) {
			if(trim($item) != '') {
				$query[] = $item;
			}
		}

                $url = Query::getUrl();
                $url->action = 'SearchResults';
                $url->query = $query;
                $this->ioHandler->addHeader('Location', (string) $url);

		return true;
	}

	public function viewAdmin($page)
	{
		$output = '';
		$page = ActivePage::getInstance();

		if($this->form->wasSubmitted()) {
			return 'Searching...';
		}

		$output .= $this->form->getFormAs('Html');
		return $output;
	}

	public function viewHtml($page)
	{
		return $this->viewAdmin($page);
	}
}

?>
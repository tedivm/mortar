<?php

class MortarCoreSimpleSearchForm extends MortarFormForm
{
	static public $xsfrProtection = false;

	protected function define()
	{
		$this->changeSection('query')->
			setLegend('Search Query');

		$this->createInput('search_query')->
			setLabel('Enter your search query:')->
			addRule('alphanumericpunc')->
			addRule('required');
	}
}

?>
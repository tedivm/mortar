<?php
AutoLoader::import('BentoCMS');

class BentoBlogPostForm extends BentoCMSPageForm
{

	protected function define()
	{
		parent::define();

		$this->changeSection('info')->
			createInput('tags')->
				setLabel('Tags');

		$this->changeSection('info')->
			setlegend('Blog Information');

		$this->changeSection('content')->
			setlegend('Entry');

	}

}

?>
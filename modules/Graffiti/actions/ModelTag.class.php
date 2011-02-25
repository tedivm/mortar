<?php

class GraffitiActionModelTag extends ModelActionLocationBasedEdit
{
	public static $settings = array( 'Base' => array('headerTitle' => 'Tag') );

	public static $requiredPermission = 'Read';

	protected $allowed = true;

	public function logic()
	{
		$query = Query::getQuery();
		$url = new Url();
		$url->location = $query['location'];
		$url->format = $query['format'];
		$url->action = 'Read';

		if(!GraffitiTagger::canTagModelType($this->model->getType())) {
			$this->ioHandler->addHeader('Location', (string) $url);
			return $this->allowed = false;
		}

		if(!method_exists($this->model, 'getLocation')) {
			$this->ioHandler->addHeader('Location', (string) $url);
			return $this->allowed = false;
		}
			
		return parent::logic();
	}

	protected function getForm()
	{
		$loc = $this->model->getLocation();
		$user = ActiveUser::getUser();
		$values = GraffitiTagLookUp::getUserTags($loc, $user);

		$form = new MortarFormForm('location_tags');
		$form->changeSection('Tags');
		$form->setLegend('Tags');

		$input = $form->createInput('tags')->
			setLabel('Tags')->
			setType('tag')->
			property('multiple', 'true');

		$input->property('value', $values);

		return $form;
	}

	protected function processInput($input)
	{
		$loc = $this->model->getLocation();
		$user = ActiveUser::getUser();

		GraffitiTagger::clearTagsFromLocation($loc, $user);
		GraffitiTagger::tagLocation((array) $input['tags'], $loc, $user);

		return true;
	}

	protected function onSuccess()
	{
	
	}

	public function viewAdmin($page)
	{
		if(!$this->allowed)
			return false;

		return parent::viewAdmin($page);
	}
}

?>
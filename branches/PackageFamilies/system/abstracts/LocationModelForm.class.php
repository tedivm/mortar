<?php

class LocationModelForm extends ModelForm
{

	protected function define()
	{
		parent::define();

		// If the parent location isn't set, there should be some form to do so.
		if($this->model->getLocation()->getParent() === false)
			throw new CoreError('Unspecified  parent', 400);

		$this->changeSection('location_information')->setLegend('Location Information');

		if(!staticHack($this->model, 'autoName') && !$this->getInput('location_name'))
		{ 
			$this->createInput('location_name')->
				setLabel('Name')->
				addRule('alphanumeric')->
				addRule('required');
		}

		$query = Query::getQuery();

		if(staticHack($this->model, 'editStatus') && $statusTypes = $this->model->getStatusTypes())
		{
			$selectInput = $this->createInput('location_status')->
				setLabel('Status')->
				setType('select');

			foreach($statusTypes as $type)
				$selectInput->setOptions($type, $type);

			if(isset($this->model->status))
				$selectInput->setValue($this->model->status);
		}

		if(staticHack($this->model, 'usePublishDate')) {
			$this->createInput('location_publishDate')->
				setType('datetime')->
				setLabel('Publish Date');
		}
	}

	public function populateInputs()
	{
		parent::populateInputs();

		$inputGroups = $this->getInputGroups(($this->getInputList()));

		if(isset($inputGroups['model'])) {
			foreach($inputGroups['model'] as $name) {
				$input = $this->getInput('model_' . $name);

				if($input->type == 'richtext') {
					if($value = $this->model['raw' . ucfirst($name)]) {
						$input->setValue($value);
					} else {
						$input->setValue($this->model[$name]);
					}
				} else {
					$input->setValue($this->model[$name]);
				}

				if($name === 'title')
					$input->setType('input');
			}
		}

		if(isset($inputGroups['location']))
		{
			if(in_array('name', $inputGroups['location']))
			{
				$input = $this->getInput('location_name');
				$input->setValue($this->model->getLocation()->getName());
			}

			if(in_array('owner', $inputGroups['location']))
			{
				$input = $this->getInput('location_owner');
				$input->setValue($this->model->getLocation()->getOwner());
			}

			if(in_array('groupOwner', $inputGroups['location']))
			{
				$input = $this->getInput('location_groupOwner');
				$input->setValue($this->model->getLocation()->getOwnerGroup());
			}

			if(in_array('publishDate', $inputGroups['location']))
			{
				$input = $this->getInput('location_publishDate');
				$pubdate = date( 'm/d/y h:i a' , $this->model->getLocation()->getPublishDate());
				$input->setValue($pubdate);
			}
		}

	}

	/**
	 * This function seperates out the specific input groups (specified by groupname_) and adds them to the object
	 * that uses them.
	 *
	 * @access protected
	 * @param array $input
	 * @return bool This is the status on whether the model was successfully saved
	 */
	public function processInput($input)
	{
		$inputNames = array_keys($input);
		$inputGroups = $this->getInputGroups($inputNames);

		if(isset($inputGroups['model']))
			foreach($inputGroups['model'] as $name)
		{
			$this->model[$name] = $input['model_' . $name];
		}

		$location = $this->model->getLocation();

		if($location->getName() == 'tmp') // this is a new, unsaved location
		{
			$user = ActiveUser::getUser();
			$location->setOwner($user);
		}

		if(isset($input['location_name']))
		{
			$this->model->name = $input['location_name'];
		}

		if(isset($input['location_publishDate']) && is_numeric($input['location_publishDate']))
		{
			$location->setPublishDate($input['location_publishDate']);
		}

		$query = Query::getQuery();
		if($query['format'] == 'Admin')
		{
			$statusTypes = $this->model->getStatusTypes();
			if(isset($input['location_status']) && in_array($input['location_status'], $statusTypes))
			{
				$this->model->status = $input['location_status'];
			}
		}

		if(!$this->processCustomInputs($input))
			return false;

		$this->processPluginInputs($input, false);

		$success = $this->model->save();
		if($success) {
			$success = $this->postProcessCustomInputs($input);
			$this->processPluginInputs($input, true);
		}
		return $success;
	}

}

?>
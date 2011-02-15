<?php

class TesseraCoreMessageForm extends LithoCorePageForm {

	protected function createCustomInputs()
	{
		parent::createCustomInputs();

		$inputGroups = $this->getInputGroups($this->getInputList());

		$query = Query::getQuery();

		$reply = $this->changeSection('info')->
			setLegend('Info')->
			createInput('model_replyTo')->
			setType('hidden');

		if(isset($inputGroups['model'])) {
			$input = $this->getInput('model_title');
			$parent = $this->model->getLocation()->getParent()->getResource();
			if($input)
				$input->setValue('Re: ' . $parent->getDesignation());

			if(isset($query['replyTo']))
				$reply->setValue($query['replyTo']);
		}
	}

	protected function populateCustomInputs()
	{
		$input = $this->getInput('model_replyTo');
		$input->setValue($this->model['replyTo']);
	}
}

?>

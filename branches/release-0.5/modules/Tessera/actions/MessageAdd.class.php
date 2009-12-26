<?php

class TesseraActionMessageAdd extends ModelActionLocationBasedAdd
{
	protected function getForm()
	{
		$form = parent::getForm();
		$inputGroups = $this->getInputGroups($form->getInputList());

		$query = Query::getQuery();

		if(isset($inputGroups['model'])) {
			$input = $form->getInput('model_title');
			$parent = $this->model->getLocation()->getParent()->getResource();
			if($input !== false)
				$input->setValue('Re: ' . $parent['title']);

			$input = $form->getInput('model_replyTo');
			if(isset($query['replyTo']) && $input !== false)
				$input->setValue($query['replyTo']);
		}

		if(isset($inputGroups['location'])) {
			foreach($inputGroups['location'] as $name) {
				$input = $form->getInput('location_' . $name);

				if ($name === 'publishDate')
					$input->setValue(strtotime($input->property('value')));

				$input->setType('hidden');
			}
		}

		return $form;
	}

}

?>

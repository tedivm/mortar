<?php

class LithoActionPageEdit extends ModelActionLocationBasedEdit
{
	protected function getForm()
	{
		$form = parent::getForm();

		$form->createInput('model_note')->
			setLabel('Note')->
			addRule('maxlength', 200);
		return $form;
	}
}

?>
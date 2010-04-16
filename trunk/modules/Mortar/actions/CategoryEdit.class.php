<?php

class MortarActionCategoryEdit extends ModelActionEdit
{
	protected function getForm()
	{
		$form = parent::getForm();

		$input = $form->createInput('model_parent')->
			setLabel('Parent Category')->
			setType('select')->
			setOptions('', '', array());

		$cats = MortarCategorizer::getDisplayTree();

		foreach($cats as $cat) {
			if((int) $cat['id'] === $this->model->getId())
				continue;

			if($this->model->hasAncestor($cat['id']))
				continue;

			if((int) $cat['id'] === $this->model['parent']) {
				$props = array('selected' => 'yes');
			} else {
				$props = array();
			}

			$name = str_repeat('&nbsp;', $cat['level'] * 4) . $cat['name'];
			$input->setOptions($cat['id'], $name, $props);
		}

		return $form;
	}
}

?>
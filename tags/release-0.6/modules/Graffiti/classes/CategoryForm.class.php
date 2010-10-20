<?php

class GraffitiCategoryForm extends ModelForm
{
	protected function createCustomInputs()
	{
		$this->changeSection('Info');
		$this->setLegend('Category Information');

		$this->createInput('model_name')->
			setLabel('Category Name')->
			addRule('required');

		$input = $this->createInput('model_parent')->
			setLabel('Parent Category')->
			setType('select')->
			setOptions('', '', array());

		$cats = GraffitiCategorizer::getDisplayTree();

		foreach($cats as $cat) {
			if((int) $cat['id'] === $this->model->getId())
				continue;

			if($this->model->hasAncestor($cat['id']))
				continue;

			$name = str_repeat('&nbsp;', $cat['level'] * 4) . $cat['name'];
			$input->setOptions($cat['id'], $name, array());
		}
	}

	protected function populateCustomInputs()
	{
		$input = $this->getInput('model_parent');
		$parent = $this->model['parent'];
		$input->setValue($parent);
	}
}

?>
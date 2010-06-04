<?php

class GraffitiActionCategoryAdd extends ModelActionAdd
{
	public function getForm()
	{
		$form = parent::getForm();

		$input = $form->createInput('model_parent')->
			setLabel('Parent Category')->
			setType('select')->
			setOptions('', '', array());

		$cats = GraffitiCategorizer::getDisplayTree();

		foreach($cats as $cat) {
			$name = str_repeat('&nbsp;', $cat['level'] * 4) . $cat['name'];
			$input->setOptions($cat['id'], $name, array());
		}

		return $form;
	}
}

?>
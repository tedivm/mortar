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

		$db = DatabaseConnection::getConnection('default_read_only');
		$results = $db->query('	SELECT categoryId, name
					FROM categories');

		while($row = $results->fetch_array()) {
			if((int) $row['categoryId'] === $this->model->getId())
				continue;

			if($this->model->hasAncestor($row['categoryId']))
				continue;

			if((int) $row['categoryId'] === $this->model['parent']) {
				$props = array('selected' => 'yes');
			} else {
				$props = array();
			}

			$input->setOptions($row['categoryId'], $row['name'], $props);
		}

		return $form;
	}
}

?>
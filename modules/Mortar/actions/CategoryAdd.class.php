<?php

class MortarActionCategoryAdd extends ModelActionAdd
{
	public function getForm()
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
			$input->setOptions($row['categoryId'], $row['name'], array());
		}

		return $form;
	}
}

?>
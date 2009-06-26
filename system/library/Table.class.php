<?php

class Table
{

	protected $name;
	protected $caption;
	protected $columnLabels = array();
	protected $columns = array();
	protected $rows = array();
	protected $index = 0;


	public function __construct($name)
	{
		$name = str_replace(' ', '_', $name);
		$this->name = $name;
	}

	public function setCaption($caption)
	{
		$this->caption = $caption;
		return $this;
	}

	public function newRow()
	{
		$this->index = count($this->rows);
		return $this;
	}

	public function addColumnLabel($column, $label)
	{
		$column = str_replace(' ', '_', $column);
		$this->columnLabels[$column] = $label;
		return $this;
	}

	public function addField($name, $value)
	{
		$name = str_replace(' ', '_', $name);
		if(!in_array($name, $this->columns))
			$this->columns[] = $name;

		$this->rows[$this->index][$name] = $value;
		return $this;
	}

	public function makeHtml()
	{
		$tableHtml = new HtmlObject('table');

		$tableId = 'table_' . $this->name;
		$tableHtml->property('id', $tableId);

		if(isset($this->caption))
		{
			$tableHtml->insertNewHtmlObject('caption')->
				wrapAround($this->caption);
		}

		if(count($this->columnLabels) > 0)
		{
			$tableHeader = $tableHtml->insertNewHtmlObject('thead');
			$headerId = $tableId . '_head';
			$tableHeader->property('id', $headerId);

			foreach($this->columns as $column)
			{
				$tableColumnHeader = $tableHeader->insertNewHtmlObject('th');
				$tableColumnHeader->property('id', $headerId . '_' . $column);

				if(isset($this->columnLabels[$column]))
				{
					$tableColumnHeader->wrapAround($this->columnLabels[$column]);
				}else{
					$tableColumnHeader->wrapAround('&nbsp;');
				}
			}
		}


		$tableBody = $tableHtml->insertNewHtmlObject('tbody');
		$bodyId = $tableId . '_body';
		$tableBody->property('id', $bodyId);

		$y = 0;
		foreach($this->rows as $row)
		{
			$y++;
			$rowId = $bodyId . '_' . $y;

			$tableRow = $tableBody->insertNewHtmlObject('tr');
			$tableRow->property('id', $rowId);

			if(!($y % 2))
				$tableRow->addClass('y_even');

			switch ($y % 3) {
				case 1:
					$tableRow->addClass('y_one');
					break;

				case 2:
					$tableRow->addClass('y_two');
					break;

				case 0:
					$tableRow->addClass('y_three');
					break;
			}

			$x = 0;
			foreach($this->columns as $column)
			{
				$x++;
				$fieldId = $rowId . 'x' . $x;
				$tableRowField = $tableRow->insertNewHtmlObject('td');
				$tableRowField->property('id', $fieldId);
				$tableRowField->addClass($column);

				if(!($x % 2))
					$tableRowField->addClass('x_even');

				switch ($x % 3) {
					case 1:
						$tableRowField->addClass('x_one');
						break;

					case 2:
						$tableRowField->addClass('x_two');
						break;

					case 0:
						$tableRowField->addClass('x_three');
						break;
				}

				if(isset($row[$column]))
				{
					$tableRowField->wrapAround($row[$column]);
				}else{
					$tableRowField->wrapAround('&nbsp;');
				}
			}
		}

		return (string) $tableHtml;
	}



}

?>
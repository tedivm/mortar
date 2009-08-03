<?php

class Table
{

	protected $name;
	protected $caption;
	protected $columnLabels = array();
	protected $columns = array();
	protected $rows = array();
	protected $index = 0;

	protected $repeatHeader = true;
	protected $enableIndex = false;

	protected $classes = array();

	static $headerInverval = 15;
	static $headerMinimumSubRows = 6;

	public function __construct($name)
	{
		$name = str_replace(' ', '_', $name);
		$this->name = $name;
	}

	public function addClass($name)
	{
		$this->classes[] = $name;
	}


	public function enableIndex($enable = true)
	{
		array_push($this->columns, 'index');
		$this->enableIndex = (bool) $enable;
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

	protected function makeHeader($baseId)
	{
		if(count($this->columnLabels) > 0)
		{
			$tableHeader = new HtmlObject('tr');//$tableHtml->insertNewHtmlObject('thead');
			$tableHeader->property('id', $baseId);

			foreach($this->columns as $column)
			{
				$tableColumnHeader = $tableHeader->insertNewHtmlObject('th');
				$tableColumnHeader->property('id', $baseId . '_' . $column);

				if(isset($this->columnLabels[$column]))
				{
					$tableColumnHeader->wrapAround($this->columnLabels[$column]);
				}else{
					$tableColumnHeader->wrapAround('&nbsp;');
				}
			}
			return $tableHeader;
		}else{
			return false;
		}

	}

	public function makeHtml()
	{
		$tableHtml = new HtmlObject('table');

		$tableId = 'table_' . $this->name;
		$tableHtml->property('id', $tableId);

		foreach($this->classes as $class)
			$tableHtml->addClass($class);

		if(isset($this->caption))
		{
			$tableHtml->insertNewHtmlObject('caption')->
				wrapAround($this->caption);
		}

		if(count($this->columnLabels) > 0)
		{
			$headerId = $tableId . '_header';

			if($header = $this->makeHeader($headerId . '_1'))
			{
				$tableHeader = $tableHtml->insertNewHtmlObject('thead');
				$tableHeader->property('id', $headerId);
				$tableHeader->wrapAround($header);
			}
		}

		$tableBody = $tableHtml->insertNewHtmlObject('tbody');
		$bodyId = $tableId . '_body';
		$tableBody->property('id', $bodyId);

		$y = 0;
		$headerCount = 1;
		$totalRows = count($this->rows);
		foreach($this->rows as $row)
		{
			if($this->repeatHeader &&
				$y > 0 && ($y % self::$headerInverval) == 0
				&& ($totalRows - $y) >= self::$headerMinimumSubRows)
			{
				$headerCount++;
				$headerId = $bodyId . '_header_' . $headerCount;
				$headerRow = $this->makeHeader($headerId);
				$tableBody->wrapAround($headerRow);
			}

			$y++;
			$rowId = $bodyId . '_y' . $y;
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
				$fieldId = $rowId . 'x_' . $column;
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

					if($column == 'index' && $this->enableIndex)
					{
						$tableRowField->wrapAround($y . '.');
					}

					$tableRowField->wrapAround('&nbsp;');
				}
			}
		}
		return (string) $tableHtml;
	}
}

?>
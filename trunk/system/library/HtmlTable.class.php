<?php

class HtmlTable implements ArrayAccess
{
	protected $name;
	protected $displayHeader;
	protected $data = array(array());
	protected $emptyCell = '&nbsp;';
	protected $columns;
	protected $classes = array();
	protected $properties = array();


	public function __construct($name, $columns)
	{
		if(!is_string($name))
			throw new BentoError('Unable to create table without a name.');


		if(!is_array($columns))
			throw new BentoError('Unable to create table without columns.');

		$this->columns = $columns;
		$this->name = $name;
	}

	public function addClass($class)
	{
		if(is_array($class))
		{
			$this->classes = array_merge($this->classes, $class);
		}elseif(is_scalar($class)){
			$this->classes[] = $class;
		}
	}

	public function makeDisplay()
	{
		$div = new HtmlObject('div');
		$table = $div->insertNewHtmlObject('table');
		$table->property('id', $this->name);

		$table->addClass($this->classes);
		$table->property($this->properties);

		$tableBody = $table->insertNewHtmlObject('tbody');

		if($this->displayHeader)
			$this->addHeader($tableBody);

		$this->addData($tableBody);


		if(class_exists('ActivePage', false))
		{
			$page = ActivePage::getInstance();
			$page->addStartupScript($javascript);
			$page->addJQueryInclude($jqueryPlugins);
			$page->addCss($this->name, 'tables');
			//$page->

		}else{

		}

		return (string) $div;
	}

	public function nextRow()
	{
		$this->data[] = array();
	}

	protected function addHeader($tableBody)
	{
		if($this->singleHeader)
		{
			$columns = count($this->columns);
			$th = $tableBody->insertHtmlObject('tr')->insertHtmlObject('th');
			$th->addClass('full');
			$th->property('colspan', $columns);

		}else{

		}
	}

	protected function addData($tableBody)
	{
		$x = 1;
		foreach($this->data as $row)
		{
			if(count($row) < 1)
				break;

			$tableRow = $tableBody->insertNewHtmlObject('tr');


			if(isset($row['properties']))
				$tableRow->property($row['properties']);

			if(isset($row['classes']))
				$tableRow->addClass($row['classes']);

			$x++;
			if($x % 2)
				$tableRow->addClass('bg');

			$rowTemp = array();
			foreach($this->columns as $index => $name)
			{
				if($row[$name])
				{
					$rowTemp[$index] = $row[$name];
				}else{
					$rowTemp[$index] = $this->emptyCell;
				}
			}

			$first = true;
			foreach($rowTemp as $rowContent)
			{
				$td = $tableRow->insertNewHtmlObject('td')->
					wrapAround($rowContent);

				if($first)
				{
					$td->addClass('first');
					$first = false;
				}
			}
			$td->addClass('last');

		}

	}


	public function offsetSet($offset, $value)
	{
		$keys = array_keys($this->data);
		$this->data[array_pop($keys)][$offset] = $value;
	}

	public function offsetGet($offset)
	{
		$keys = array_keys($this->data);
		return $this->data[array_pop($keys)][$offset];
	}

	public function offsetExists($offset)
	{
		$keys = array_keys($this->data);
		return isset($this->data[array_pop($keys)][$offset]);
	}

	public function offsetUnset($offset)
	{
		$keys = array_keys($this->data);
		unset($this->data[array_pop($keys)][$offset]);
	}

}




?>
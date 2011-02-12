<?php

class OrmSelectBuilder
{

	/**
	 * This array is used to define the datatype that is required for each column type. This is used when sending the
	 * arguments along to prevent sql injection. Anything column not in here is assumed to be a string.
	 *
	 * @var array column_type => data_type
	 */
	static protected $data_types = array(
	'tinyint' => 'i',
	'smallint' => 'i',
	'mediumint' => 'i',
	'int' => 'i',
	'bigint' => 'i',

	'float' => 'd',
	'double' => 'd',
	'decimal' => 'd',
	'numeric' => 'd',

	'binary' => 'b',
	'barbinary' => 'b',
	'blob' => 'b',
	'tinyblob' => 'b',
	'mediumblob' => 'b',
	'longblob' => 'b'
	);

	protected $sqlInputs = array();
	protected $sqlInputsTypeString = '';


	/*

	SELECT columns
	FROM tables
	WHERE things = values


	UPDATE table
	SET things = values
	WHERE things = values

	INSERT INTO tables (things)
	VALUES (values)

	*/



	public function getQuery($columns)
	{
		$columnRestrictions = $this->getColumnClause($columns);
		$tableSql = $this->getTableClause();

		$sql_select = 'SELECT ' . $columnRestrictions . ' FROM ' . $tableSql;

		$tmp = $this->getWhereClause();
		$sql_where = rtrim($tmp, ' ,');

		$tmp = $this->getOrderClause();
		$sql_orderby =  rtrim($tmp, ' ,');

		$tmp = $this->getLimitClause();
		$sql_limit = rtrim($tmp, ' ,');

	// create query
		$query = $sql_select . ' ' . $sql_where . ' ' . $sql_orderby . ' ' . $sql_limit;

		return $query;
	}


	protected function getColumnClause($columns = null)
	{
		if(isset($columns))
		{
			$columnRestrictions = '';
			foreach($columns as $selectColumnName)
				$columnRestrictions .= $selectColumnName . ', ';

			$columnRestrictions = rtrim($columnRestrictions, ', ');
		}else{
			$columnRestrictions = '*';
		}
		return $columnRestrictions;
	}

	protected function getTableClause()
	{
		// this will get more complicated with joins
		return $this->table;
	}

	protected function getWhereClause()
	{
		$sqlWhere = 'WHERE ';
		$whereLoop = false;
		foreach($this->values as $column => $value)
		{
			if(!isset($this->columns[$column]['Type']))
				throw new OrmError('Column ' . $column . ' not found in table ' . $this->table);

			if($where_loop)
				$sqlWhere .= 'AND ';
			$sqlWhere .= $column . ' = ? ';

			$this->addInput($value, $this->columns[$column]['Type']);
			$whereLoop == true;
		}

		if(!$whereLoop)
			$sqlWhere = '';

		return $sqlWhere;
	}

	protected function getOrderClause()
	{
		if(isset($orderby))
		{
			if(is_array($orderby))
			{
				$sql_orderby = 'ORDER BY ';
				$orderby_loop = 0;
				foreach($orderby as $column_name)
				{
					if($this->columns[$column_name])
					{
						if($orderby_loop > 0)
							$sql_orderby .= ', ';

						$sql_orderby .= $column_name;
						$orderby_loop++;
					}
				}
			}elseif(is_string($orderby))		{
				$sql_orderby = 'ORDER BY ' . $orderby;
			}
		}

		$sql_orderby = (isset($sql_orderby)) ? $sql_orderby .= ($order == 'ASC') ? ' ASC' : ' DESC' : $sql_orderby = '';
		return $sql_orderby;
	}

	protected function getLimitClause($limit, $position)
	{
		if(is_numeric($limit) && $limit > 0)
		{
			$sqlLimit = 'LIMIT ';
			$sqlLimit = (is_numeric($position) && $position > 0) ? $position . ',' . $limit : $limit;

		}else{
			$sqlLimit = '';
		}
		return $sqlLimit;
	}

	protected function addInput($input, $columnType)
	{
		$this->sqlInputs[] = $value;
		$this->sqlInputsTypeString .= self::getType($columnType);
	}

	public function runQuery()
	{
		$query = $this->getQuery();
		$inputs = $this->getInputs();
		$typeString = $this->getTypeString();

		if(!isset($inputs) || count($inputs) < 1)
		{
			$db->query($query);
			$results = $db->query($query);
		}else{
			array_unshift($inputs, $typeString);
			$select_stmt = $db->stmt_init();
			$select_stmt->prepare($query);
			call_user_func_array(array($select_stmt, 'bindAndExecute'), $inputs);
			$results = $select_stmt;
		}
	}

	/**
	 * This function takes in a column type and returns the flag needed for binding data using an stmt object.
	 *
	 * @param string $datatype
	 * @return string
	 */
	static protected function getType($datatype)
	{
		return isset(self::$data_types[$datatype]) ? self::$data_types[$datatype] : 's';
	}

}

?>
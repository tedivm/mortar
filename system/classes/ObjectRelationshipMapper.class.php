<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Database
 */

/**
 * This class acts as a wrapper to easily call rows from database tables.
 *
 * All properties of this class should correspond to database tables, with the exception of those listed below.
 *
 * @property mixed primaryKey
 * @property-read int errno
 * @property-read int num_rows
 * @package System
 * @subpackage Database
 */
class ObjectRelationshipMapper
{
	/**
	 * This is the table this instance is mapped to
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * These are the values of the current object. The index corresponds to a column.
	 *
	 * @var array
	 */
	protected $values = array();

	/**
	 * This is an array of values that bypass the all the internal security checking and parameterization to get placed
	 * directly into the SQL statement itself. This should be used for things like MySQL functions, not for user input.
	 *
	 * @var array The index is the column
	 */
	protected $direct_values = array();

	/**
	 * This is an array containing function names which are to be applied to the value of a column before comparison.
	 *
	 * @var array The index is the column
	 */
	protected $column_functions = array();

	/**
	 * This is an array of columns that the current table has, with information about each of those columns.
	 *
	 * @var array
	 */
	protected $columns = array();

	/**
	 * This is an array of columns that act as primary keys.
	 *
	 * @var array
	 */
	protected $primary_keys = array();

	/**
	 * This is a flag to see if the item has been synced with the database
	 *
	 * @var bool
	 */
	private $selected = false;

	/**
	 * This item defines how long the table meta data is stored in the cache.
	 *
	 * @var int
	 */
	public static $cacheTime = 43200;

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

	/**
	 * This is the name of the database connection used for read operations. This should correlate to a connection in
	 * the database.ini file.
	 *
	 * @var string
	 */
	protected $db_read = 'default_read_only';

	/**
	 * This is the name of the database connection used for write operations. This should correlate to a connection in
	 * the database.ini file.
	 *
	 * @var string
	 */
	protected $db_write = 'default';

	/**
	 * In the case where there are multiple rows, this is an array of results that have already been itereated through.
	 *
	 * @var array
	 */
	protected $previous_results = array();

	/**
	 * In the case where there are multiple rows, this is an array of results that have been retrieved from the database
	 * and are next in line to be iterated through.
	 *
	 * @var array
	 */
	protected $next_results = array();

	/**
	 * This is the copy of the Mystmt class used to retrieve the row(s) from the database.
	 *
	 * @var Mystmt
	 */
	protected $select_stmt;

	/**
	 * If set this array limits the columns selected by the ORM class.
	 *
	 * @var array
	 */
	protected $restrictColumns;

	/**
	 * This is the number of rows retrieved or affected.
	 *
	 * @var int
	 */
	protected $num_rows = 0;

	/**
	 * The join phrase used in the select query if a single-field join has been added.
	 *
	 * @var string
	 */
	protected $join;

	/**
	 * The restriction string for the single-column join, if one is present.
	 *
	 * @var string
	 */
	protected $joinColumn;

	protected $joinWhere;

	/**
	 * In the event of a mysql error, this is changed to the error number. If 0 there is no error.
	 *
	 * @var int
	 */
	public $sql_errno = 0;

	/**
	 * In the event of a mysql error, this is changed to the error string.
	 *
	 * @var string
	 */
	public $errorString;


	/**
	 * This constructor takes the table name that the object should be mapped to, and two optional arguments identifying
	 * the database connections to use. This function calls loadSchema, which loads the meta data needed for the class
	 * from the database.
	 *
	 * @param string $table
	 * @param null|string $db_write should correlate to a connection in the database.ini file with full permissions.
	 * @param null|string $db_read should correlate to a connection in the database.ini file read permissions.
	 */
	public function __construct($table, $db_write = null, $db_read = null)
	{
		if(is_null($table))
			throw new OrmError('ORM class requires a table name.');

		$this->table = $table;

		if(isset($db_read))
			$this->db_read =  $db_read;

		if(isset($db_write))
			$this->db_write =  $db_write;

		$this->loadSchema();
	}

	/**
	 * Causes the primary table to be joined to a single secondary table in order to include a single extra field
	 * into the returned listing (usually for filtering or sorting). Takes as parameters the table to be joined to,
	 * the join column in the first table, the corresponding column in the secondary table, and the column to select
	 * from the secondary table. Optionally, the last parameter allows the column to be selected under a different
	 * name.
	 *
	 * @param string $table
	 * @param string $column1
	 * @param string $column2
	 * @param string $select
	 * @param null|string $selectAs = null
	 */
	public function join($table, $column1, $column2, $select, $selectAs = null)
	{
		$tableInfo = new OrmTableStructure($table, $this->db_write);

		$columns = $tableInfo->columns;

		if(!isset($this->columns[$column1]))
			return false;

		if($column2 === 'primarykey') {
			$pks = $tableInfo->primaryKeys;
			$column2 = array_shift($pks);
		}

		if(!isset($columns[$column2]))
			return false;

		if(!isset($columns[$select]))
			return false;

		if(!isset($selectAs))
			$selectAs = $select;

		if(isset($this->columns[$selectAs])) {
			$res = array();

			if(isset($this->restrictColumns)) {
				$cols = $this->restrictColumns;
			} else {
				$cols = array();
				foreach($this->columns as $name => $value) {
					$cols[] = $name;
				}
			}

			foreach($cols as $col) {
				if($col !== $selectAs) {
					$res[] = $col;
				}
			}

			$this->restrictColumns = $res;
		}

		$this->columns[$selectAs] = $columns[$select];

		$this->join = ' JOIN ' . $table . ' ON ' . $this->table . '.' . $column1 . ' = ' . $table . '.' . $column2 . ' ';
		$this->joinColumn = $table . '.' . $select . ' AS ' . $selectAs;

		return true;
	}

	// create, record, update, delete

	/**
	 * This function selects a number of rows from the database that match the attributes set with the set/get magic
	 * functions.
	 *
	 * @param int $limit This is the number of rows to return- the default, 0, means no limit.
	 * @param int $position If set, this is the starting position of rows retrieved from the database
	 * @param string $orderby This defines the column to order results by
	 * @param string $order If orderby is set, this defines whether it ASC or DESC (asceneds or descends)
	 * @return bool returns true if successful and rows return
	 */
	public function select($limit = 0, $position = 0, $orderby = null, $order = null)
	{

	// retreive database connection
		$db = DatabaseConnection::getConnection($this->db_read);

	// setup SELECT


		if(isset($this->restrictColumns))
		{
			$columnRestrictions = '';

			foreach($this->restrictColumns as $selectColumnName)
				$columnRestrictions .= $this->table . '.' . $selectColumnName . ', ';

			if(isset($this->joinColumn))
				$columnRestrictions .= $this->joinColumn;

			$columnRestrictions = rtrim($columnRestrictions, ', ');
		}else{
			if(isset($this->join)) {
				$columnRestrictions = $this->table . '.*, ' . $this->joinColumn;
			} else {
				$columnRestrictions = '*';
			}
		}

		$sql_select = 'SELECT ' . $columnRestrictions . ' FROM ' . $this->table;

		if(isset($this->join))
			$sql_select .= $this->join;

	// END setup SELECT

	// setup WHERE
		$sql_where = 'WHERE ';
		$sql_typestring = '';
		$where_loop = 0;
		foreach($this->values as $column => $value)
		{
			if($where_loop > 0)
				$sql_where .= 'AND ';

			if(is_array($value)) {
				$ors = '(';
				$first = true;
				foreach ($value as $item) {
					if($first) {
						$first = false;
					} else {
						$ors .= 'OR ';
					}
					$sql_input[] = $item;
					$ors .= $column . ' = ? ';
					$sql_typestring .= self::getType($this->columns[$column]['Type']);
				}
				$ors .= ')';
				$sql_where .= $ors;
			} else {
				$sql_input[] = $value;
				$sql_where .= $column . ' = ? ';
				$sql_typestring .= self::getType($this->columns[$column]['Type']);
			}

			if(!isset($this->columns[$column]['Type']))
				throw new OrmError('Column ' . $column . ' not found in table ' . $this->table);

			$where_loop++;
		}

		foreach($this->column_functions as $func)
		{
			if($where_loop > 0)
				$sql_where .= 'AND ';

			$sql_input[] = $func['value'];
			$sql_where .= $func['function'] . '(' . $func['column'] . ')' . ' = ? ';

			if(!isset($this->columns[$column]['Type']))
				throw new OrmError('Column ' . $column . ' not found in table ' . $this->table);

			$sql_typestring .= self::getType($this->columns[$column]['Type']);
			$where_loop++;
		}

		if($where_loop < 1)
			$sql_where = '';
	// END setup WHERE

	// setup ORDER BY
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

	// END setup ORDER BY

	// setup LIMIT

		if($limit > 0)
		{
			$sql_limit = 'LIMIT ' . $limit;
			if($position > 0)
				$sql_limit = 'LIMIT ' . $position . ',' . $limit;
		}else{
			$sql_limit = '';
		}

	// END setup LIMIT

	// create query
		$query = rtrim($sql_select, ' ,') . ' ' . rtrim($sql_where, ' ,') . ' ' . rtrim($sql_orderby, ' ,') . ' ' . rtrim($sql_limit, ' ,');

	// run query
		if(!isset($sql_input) || count($sql_input) < 1)
		{
			$db->query($query);
			$results = $db->query($query);
		}else{
			array_unshift($sql_input, $sql_typestring);
			$select_stmt = $db->stmt_init();
			$select_stmt->prepare($query);
			call_user_func_array(array($select_stmt, 'bindAndExecute'), $sql_input);
			$results = $select_stmt;
		}

	// process results
		if(isset($select_stmt) && $select_stmt->errno > 0)
		{
			$this->sql_errno = $select_stmt->errno;
			$this->errorString = $select_stmt->error;
			return false;
		}

		$this->num_rows = $results->num_rows;

		switch ($results->num_rows)
		{
			case 0:
				$this->num_rows = 0;
				return false;
				break;

			case 1:
				$this->values = $results->fetch_array();
				$this->selected = true;
				return true;
				break;

			default:
				$this->values = $results->fetch_array();
				$this->select_stmt = $results;
				$this->selected = true;
				return true;
				break; // I know it will never be reached, but I can't remove it for OCD reasons
		}
	}

	/**
	 * This function deletes rows based on the current attributes set (either via the set/get functions or from a
	 * previous database call). Be careful. As a safegaurd this requires that at least one attribute be set, it will not
	 * just delete all- for that use a query.
	 *
	 * @param int $limit Defaults to one row. If set to 0, there will be no limit and all matching rows will be removed
	 * @return bool
	 */
	public function delete($limit = 1)
	{

		// retreive database connection
		$db = DatabaseConnection::getConnection($this->db_write);

	// setup DELETE
		$sql_delete = 'DELETE FROM ' . $this->table;
	// END setup DELETE

	// setup WHERE
		$sql_where = 'WHERE ';
		$sql_typestring = '';
		$sql_input = array();

		$loop = 0;
		foreach($this->values as $column => $value)
		{
			if(isset($this->values[$column]))
			{
				if($loop > 0)
				{
					$sql_where .= 'AND ';
				}

				if(is_array($value)) {
					$ors = '(';
					$first = true;
					foreach ($value as $item) {
						if($first) {
							$first = false;
						} else {
							$ors .= 'OR ';
						}
						$sql_input[] = $item;
						$ors .= $column . ' = ? ';
						$sql_typestring .= self::getType($this->columns[$column]['Type']);
					}
					$ors .= ')';
					$sql_where .= $ors;
				} else {
					$sql_input[] = $value;
					$sql_where .= $column . ' = ? ';
					$sql_typestring .= self::getType($this->columns[$column]['Type']);
				}
				$loop++;
			}

		}
	// END setup WHERE

	// setup LIMIT
		$sql_limit = ($limit > 0) ? 'LIMIT ' . $limit : '';
	// END setup LIMIT

	// create query
		if(strlen($sql_where) <= 6)
		{
			// this prevents the query from emptying a table
			return false;
		}

		$query = rtrim($sql_delete, ' ,') . ' ' . rtrim($sql_where, ' ,') . ' ' . rtrim($sql_limit, ' ,');
	// END create query

	// run query
		if(count($sql_input) < 1)
		{
			$db->query($query);
			$results = $db->query($query);

		}else{
			array_unshift($sql_input, $sql_typestring);
			$delete_stmt = $db->stmt_init();
			$delete_stmt->prepare($query);

			call_user_func_array(array($delete_stmt, 'bindAndExecute'), $sql_input);
			$results = $delete_stmt;
		}
	// END run query

	// process results
		if($results->errno > 0)
		{
			$this->sql_errno = $results->errno;
			return false;
		}

		$this->num_rows = $results->affected_rows;
		$this->direct_values = array();
		$this->values = array();
		return true;

	}

	/**
	 * This saved the current current attributes to the database. It relies on two helper functions, update and insert.
	 *
	 * @return bool
	 */
	public function save()
	{
		try{
			// If the primary key is assigned by mysql and exists, it means we need to run an update
			// elsewise, if the 'selected' tag is hit, it means we pulled the info from the database
			// and thus need to do an update as well
			if((isset($this->values[$this->primary_keys[0]])
							&& strpos($this->columns[$this->primary_keys[0]]['Extra'], 'auto_increment') !== false)
						|| $this->selected )
			{
				if($this->update())
				{
					$this->selected = true;
					return true;
				}
			}else{

				try{

					if($this->insert())
					{
						$this->selected = true;
						return true;
					}

				}catch(Exception $e){

					if($this->sql_errno == 1022 && $this->update())
					{
						$this->selected = true;
						return true;
					}

					throw $e;
				}
			}

		}catch(Exception $e){} // just let the system return false

		return false;
	}


	/**
	 * When iterating through results, this moved to the next set of values. This stores the current results on the
	 * previous_results stack, checks to see if the next results are already retrieved, and if not retrieves them from
	 * the database.
	 *
	 * @return bool Returns false when there are no more values.
	 */
	public function next()
	{
		if(count($this->next_result) > 0)
		{
			$this->direct_values = array();
			$this->previous_results[] = $this->values;
			$this->values = array_pop($this->next_results);
			return true;
		}else{

			if(isset($this->select_stmt) && ($next_result = $this->select_stmt->fetch_array()))
			{
				$this->direct_values = array();
				$this->previous_results[] = $this->values;
				$this->values = $next_result;
				return true;
			}
		}

		return false;
	}

	/**
	 * When iterating, this rewinds back to the previous results. When doing so it stores the current results in the
	 * 'next_results' stack.
	 *
	 */
	public function previous()
	{
		if(count($this->previous) > 0)
		{
			$this->direct_values = array();
			$this->next_results[] = $this->values;
			$this->values = array_pop($this->previous);
		}
	}

	/**
	 * This resets the iteration. It works by shuffling around the values, previous_results, and next_results arrays.
	 *
	 */
	public function reset()
	{
		$this->direct_values = array();
 		$this->previous_results[] = $this->values;
 		$this->values = array_shift($this->previous_results);
		array_push($this->previous_results, $this->next_results);
	}

	/**
	 * This returns the number of rows affected by the last database action.
	 *
	 * @return unknown
	 */
	public function totalRows()
	{
		return $this->num_rows;
	}

	/**
	 * This function lets you inject any string into the sql query instead of using the normal attribute/value method.
	 * The purpose behind this is to allow you to use mysql functions directly in the query.
	 *
	 * @param string $column
	 * @param string $string
	 */
	public function querySet($column, $string)
	{
		$this->direct_values[$column] = $string;
	}

	/**
	 * This function adds a comparison of a column modified by a function to a select query.
	 *
	 * @param string $column
	 * @param string $string
	 */
	public function addFunction($column, $function, $value)
	{
		$function = ereg_replace("[^A-Za-z]", "", $function);
		$this->column_functions[] = array('column' => $column, 'function' => $function, 'value' => $value);
	}

	/**
	 * This returns the value from the column name passed for the current active row.
	 *
	 * @param string $name
	 * @return mixed This is the value stored in the database.
	 */
	public function __get($name)
	{

		switch (strtolower($name))
		{
			case 'num_rows':
				$value = $this->num_rows;
				break;

			case 'errno':
				$value = $this->sql_errno;
				break;

			case 'primarykey':
				if(count($this->primary_keys) == 1)
					$value = $this->values[$this->primary_keys[0]];
				break;
			default:
				$value = null;
				if(isset($this->values[$name]))
					$value = $this->values[$name];
				break;
		}

		return $value;
	}

	/**
	 * This is used to set the value corresponding to a specific column.
	 *
	 * @param string $name
	 * @param string|int $value
	 * @return mixed
	 */
	public function __set($name, $value)
	{
		if(strtolower($name) == 'primarykey' && count($this->primary_keys) == 1)
			$name = $this->primary_keys[0];

		return ($this->values[$name] = $value);
	}

	/**
	 * Returns whether a value exists or not.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function __isset($name)
	{
		switch(strtolower($name))
		{
			case 'num_rows':
				return isset($this->num_rows);

			case 'errno':
				return($this->sql_errno);

			case 'primarykey':
				if(count($this->primary_keys) !== 1 || !isset($this->primary_keys[0]))
					return false;

				return isset($this->values[$this->primary_keys[0]]);

			default:
				return isset($this->values[$name]);
		}
	}

	/**
	 * Unsets a value if it is set.
	 *
	 * @param string $name
	 */
	public function __unset($name)
	{
		$lname = strtolower($name);

		switch($lname)
		{
			case('num_rows'):
			case('errno'):
			case('primarykey'):
				return;
		}

		if($this->__isset($name))
			unset($this->values[$name]);
	}



	/**
	 * Returns all of the current values as an array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->values;
	}

	/**
	 * This returns not just the current results, but all of the results, as an array.
	 *
	 * @return array
	 */
	public function resultsToArray()
	{
		$temp = array();

		foreach($this->previous_results as $results)
		{
			$temp[] = $results;
		}

		$temp[] = $this->values;

		foreach($this->next_results as $results)
		{
			$temp[] = $results;
		}

		while($this->next())
		{
			$temp[] = $this->values;
		}
		return $temp;
	}

	/**
	 * This returns an array of columns. The default is to return just an array of column names, however if the
	 * withAttributes argument true it will return various metadata about the column as well.
	 *
	 * @param bool $withAttributes
	 * @param bool $includePrimaryKey
	 * @return array
	 */
	public function getColumns($withAttributes = false, $includePrimaryKey = true)
	{
		$output = $this->columns;

		if(!$includePrimaryKey && count($this->primary_keys) == 1)
			unset($output[$this->primary_keys[0]]);

		return ($withAttributes) ? $output : array_keys($output);
	}

	/**
	 * This function takes in an array of column names and limits the information it retrieves to only those columns.
	 *
	 * @param array $columns
	 */
	public function setColumnLimits($columns)
	{
		$confirmedColumns = array();
		foreach($columns as $column)
		{
			if(strtolower($column) == 'primarykey')
			{
				foreach($this->primary_keys as $primaryKey)
					$confirmedColumns[] = $primaryKey;
			}elseif(isset($this->columns[$column]))	{
				$confirmedColumns[] = $column;
			}else{

			}
		}
		$this->restrictColumns = $confirmedColumns;
	}

	// internal stuff

	/**
	 * This function loads the meta data (columns, types and keys) from the database so we don't need to manage
	 * configuration files. This metadata is cached for performance.
	 *
	 * @cache orm scheme *tableName
	 */
	protected function loadSchema()
	{
		$ormStructure = new OrmTableStructure($this->table, $this->db_write);
		$ormStructure->columns;
		$ormStructure->primaryKeys;

		$this->columns = $ormStructure->columns;
		$this->primary_keys = $ormStructure->primaryKeys;
		return;
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

	/**
	 * This inserts information as a new row. Called by the save function.
	 *
	 * @return bool
	 */
	protected function insert()
	{

		$sql_columns = '(';
		$sql_values = '(';
		$sql_typestring = '';
		//$sql_input = array();

		// the following code snippet is identical to the one in the update function, and should be refractured
		// into its own function
		foreach($this->columns as $column_name => $column_info)
		{
			if(isset($this->direct_values[$column_name]))
			{
				$sql_columns .= $column_name . ', ';
				$sql_values .= $this->direct_values[$column_name] . ', ';

			}else{

				if(isset($this->values[$column_name]))
				{
					$sql_columns .= $column_name . ', ';
					$sql_typestring .= self::getType($column_info['Type']);
					$sql_values .= '?, ';
					if(is_array($this->values[$column_name])) {
						$sql_input[] = $this->values[$column_name][0];					
					} else {
						$sql_input[] = $this->values[$column_name];
					}
				}else{


					if(isset($column_info['Default']))
					{
						$sql_columns .= $column_name . ', ';
						$sql_values .= 'DEFAULT, ';

					}elseif($column_info['Null']){

						$sql_columns .= $column_name . ', ';
						$sql_values .= 'NULL, ';

					}else{

						if(!isset($column_info['autoIncrement']))
						{
							$this->errorString = 'Need a value for column ' . $column_name;
							return false;
						}
					}
				}
			}
		}

		$sql_columns = rtrim($sql_columns, ' ,') . ') ';
		$sql_values = rtrim($sql_values, ' ,') . ') ';

		$query = 'INSERT INTO ' . $this->table . ' ' . $sql_columns . 'VALUES ' . $sql_values;

		$db = DatabaseConnection::getConnection($this->db_write);

		if(!isset($sql_input) || count($sql_input) < 1)
		{
			if(!$db->query($query))
			{
				$this->sql_errno = $db->errno;
				$this->errorString = $db->error;
			}

			if(strpos($this->columns[$this->primary_keys[0]]['Extra'], 'auto_increment') !== false)
				$this->values[$this->primary_keys[0]] = $db->insert_id;

		}else{

			array_unshift($sql_input, $sql_typestring);

			$insert_stmt = $db->stmt_init();
			$insert_stmt->prepare($query);
			call_user_func_array(array($insert_stmt, 'bindAndExecute'), $sql_input);

			if($insert_stmt->errno > 0)
			{
				$this->sql_errno = $insert_stmt->errno;
				$this->errorString = $insert_stmt->error;
				return false;
			}

			if(strpos($this->columns[$this->primary_keys[0]]['Extra'], 'auto_increment') !== false)
				$this->values[$this->primary_keys[0]] = $insert_stmt->insert_id;
		}


		return true;

	}

	/**
	 * This updates a row in the table. Called by the 'save' function.
	 *
	 * @return bool
	 */
	protected function update()
	{

		// UPDATE table SET column = ?, column = value, WHERE id = ?

		$sql_set = 'SET ';
		$sql_input = array();
		$sql_columns = '';
		$sql_typestring = '';
		// the following code snippet is identical to the one in the update function, and should be refractured
		// into its own function
		foreach($this->columns as $column_name => $column_info)
		{
			$sql_columns .= $column_name . ', ';
			if(isset($this->direct_values[$column_name]))
			{
				$sql_columns .= $column_name . ', ';
				$sql_set .= $column_name . ' = ' . $this->direct_values[$column_name] .= ', ';
			}else{

				if(isset($this->values[$column_name]))
				{
					$sql_columns .= $column_name . ', ';
					$sql_typestring .= self::getType($column_info['Type']);
					$sql_set .= $column_name . ' = ?, ';
					if(is_array($this->values[$column_name])) {
						$sql_input[] = $this->values[$column_name][0];					
					} else {
						$sql_input[] = $this->values[$column_name];
					}

				}elseif(isset($this->restrictColumns) && !in_array($column_name, $this->restrictColumns)){

					continue;

				}else{

					if($column_info['Null'])
					{
						$sql_columns .= $column_name . ', ';
						$sql_set .= $column_name . ' = NULL, ';

					}elseif(isset($column_info['Default'])){
						$sql_columns .= $column_name . ', ';
						$sql_set .= $column_name . ' = DEFAULT, ';
					}

				} // if(isset($this->values[$column_name])) else
			} // if($this->direct_values[$column_name]) else
		}//foreach($this->columns as $column_name => $column_info)

		$sql_where = 'WHERE ';
		$loop = 0;
		foreach($this->primary_keys as $primary_key)
		{
			if($loop > 0)
			{
				$sql_where .= 'AND ';
			}else{
				$loop = 1;
			}

			$sql_where .= $primary_key . '= ? ';
			$sql_input[] = $this->values[$primary_key];
			$sql_typestring .= self::getType($this->columns[$primary_key]['Type']);
		}

		$sql_where = rtrim($sql_where, ' ,');
		$sql_set = rtrim($sql_set, ' ,');
		array_unshift($sql_input, $sql_typestring);

		$query = 'UPDATE ' . $this->table . ' ' . $sql_set . ' ' . $sql_where;

		$db = DatabaseConnection::getConnection($this->db_write);
		$update_stmt = $db->stmt_init();
		$update_stmt->prepare($query);

		$results = call_user_func_array(array($update_stmt, 'bindAndExecute'), $sql_input);
		return $results;
	}
}

class OrmError extends CoreError {}
?>
<?

// Copyright Robert Hafner

class ObjectRelationshipMapper
{

	// all the variables are 'protect' as a reminder that get/set has been overridden

	protected $table;
	protected $values = array();
	protected $direct_values = array();
	protected $columns = array();
	private $primary_keys = array();
	private $selected = false;

	public static $cacheTime = 3600;
	protected $data_types = array(
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

	protected $db_read = 'default_read_only';
	protected $db_write = 'default';

	private $next_results = array();
	private $previous_results = array();
	private $select_stmt;
	protected $num_rows = 0;
	public $sql_errno = 0;
	public $errorString;



	public function __construct($table, $db_write = '', $db_read = '')
	{
		$this->table = $table;

		if($db_read != '')
			$this->db_read =  $db_read;

		if($db_write != '')
			$this->db_write =  $db_write;

		$this->load_schema();
	}


	// create, record, update, delete

	public function select($limit = 0, $position = 0, $orderby = '', $order = 'ASC')
	{

		// retreive database connection
		$db = DatabaseConnection::getConnection($this->db_read);
		// setup SELECT

		$sql_select = 'SELECT * FROM ' . $this->table;

		// END setup SELECT

		// setup WHERE

		$sql_where = 'WHERE ';
		$sql_typestring = '';
		$where_loop = 0;
		foreach($this->values as $column => $value)
		{
			if($where_loop > 0)
			{
				$sql_where .= 'AND ';
			}

			$sql_input[] = $value;
			$sql_where .= $column . ' = ? ';

			if(!isset($this->columns[$column]['Type']))
				throw new BentoError('Column ' . $column . ' not found in table ' . $this->table);

			$sql_typestring .= $this->get_type($this->columns[$column]['Type']);
			$where_loop++;
		}

		if($where_loop < 1)
			$sql_where = '';

		// END setup WHERE

		// setup ORDER BY


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
		}

		if(is_string($orderby) && $orderby != '')
		{
			$sql_orderby = 'ORDER BY ' . $orderby;
		}

		if(isset($sql_orderby))
		{
			$sql_orderby .= ($order == 'ASC') ? ' ASC' : ' DESC';
		}else{
			$sql_orderby = '';
		}
		// END setup ORDER BY

		// setup LIMIT

		if($limit > 0)
		{
			$sql_limit = 'LIMIT ' . $limit;
			if($position > 0)
				$sql_limit .= ',' . $position;
		}else{
			$sql_limit = '';
		}

		// END setup LIMIT


		// create query
		$query = rtrim($sql_select, ' ,') . ' ' . rtrim($sql_where, ' ,') . ' ' . rtrim($sql_orderby, ' ,') . ' ' . rtrim($sql_limit, ' ,');

		if(count($sql_input) < 1)
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


		if($select_stmt->errno > 0)
		{
			$this->sql_errno = $select_stmt->errno;
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

	public function delete($limit = 1)
	{

		// retreive database connection
		$db = DatabaseConnection::getConnection($this->db_write);
		// setup SELECT

		$sql_delete = 'DELETE FROM ' . $this->table;

		// END setup SELECT

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

				$sql_input[] = $value;
				$sql_where .= $column . ' = ? ';
				$sql_typestring .= $this->get_type($this->columns[$column]['Type']);
				$loop++;
			}

		}
		// END setup WHERE

		// setup LIMIT

		$sql_limit = ($limit > 0) ? $limit : '';

		// END setup LIMIT


		// create query

		if(strlen($sql_where) <= 6)
		{
			// this prevents the query from emptying a table
			return false;
		}

		$query = rtrim($sql_delete, ' ,') . ' ' . rtrim($sql_where, ' ,') . ' ' . rtrim($sql_limit, ' ,');

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

	public function previous()
	{
		if(count($this->previous) > 0)
		{
			$this->direct_values = array();
			$this->next_results[] = $this->values;
			$this->values = array_pop($this->previous);
		}
	}

	public function reset()
	{
		$this->direct_values = array();
 		$this->previous_results[] = $this->values;
 		$this->values = array_shift($this->previous_results);
		array_push($this->previous_results, $this->next_results);
	}

	public function total_rows()
	{
		return $this->num_rows;
	}

	public function query_set($column, $string)
	{
		$this->direct_values[$column] = $string;
	}

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

	public function __set($name, $value)
	{
		if(strtolower($name) == 'primarykey' && count($this->primary_keys) == 1)
			$name = $this->primary_keys[0];

		return ($this->values[$name] = $value);
	}

	public function to_array()
	{
		return $this->values;
	}

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

	public function getColumns($withAttributes = false, $includePrimaryKey = true)
	{
		$output = $this->columns;

		if(!$includePrimaryKey && count($this->primary_keys) == 1)
			unset($output[$this->primary_keys[0]]);

		return ($withAttributes) ? $output : array_keys($output);
	}


	// internal stuff

	protected function load_schema()
	{

		$cache = new Cache('orm', 'schema', $this->table);
		$cache->cache_time = self::$cacheTime;

		if(!($schema = $cache->get_data()))
		{
			$db = DatabaseConnection::getConnection($this->db_write);
			$result = $db->query('SHOW FIELDS FROM ' . $this->table);
			$primarykey = array();

			if(!$result)
				throw new BentoError('Database Error:' . $db->error . '    ORM unable to load table ' . $this->table);

			while($results = $result->fetch_array())
			{

				$pos = strpos($results['Type'], '(');
				if($pos)
					$results['Type'] = substr($results['Type'], 0, $pos);

				$pos = strpos($results['Type'], ' ');
				if($pos)
					$results['Type'] = substr($results['Type'], 0, $pos);

				$results['Null'] = ($results['Null'] != 'NO');

				$columns[$results['Field']] = $results;


				if($results['Key'] == 'PRI')
				{
					if(isset($results['Extra']) && strpos($results['Extra'], 'auto_increment') !== false)
					{
						$columns[$results['Field']]['autoIncrement'] = true;
						$primarykey[] = $results['Field'];
					}else{
						array_unshift($primarykey, $results['Field']);
					}
				}
			}

			$schema['columns'] = $columns;
			$schema['primarykey'] = $primarykey;

			$cache->store_data($schema);
		} // end cache code

		$this->columns = $schema['columns'];
		$this->primary_keys = $schema['primarykey'];
	}

	protected function get_type($datatype)
	{

		return isset($this->data_types[$datatype]) ? $this->data_types[$datatype] : 's';
	}

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
					$sql_typestring .= $this->get_type($column_info['Type']);
					$sql_input[] = $this->values[$column_name];
					$sql_values .= '?, ';
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

		if(count($sql_input) < 1)
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
				$sql_set .= $column_name . ' = ' . $this->direct_values[$column_name] .= ', ';
			}else{

				if(isset($this->values[$column_name]))
				{
					$sql_typestring .= $this->get_type($column_info['Type']);
					$sql_input[] = $this->values[$column_name];
					$sql_set .= $column_name . ' = ?, ';
				}else{

					if(isset($column_info['Default']))
					{
						$sql_set .= $column_name . ' = DEFAULT, ';

					}elseif($column_info['Null']){

						$sql_set .= $column_name . ' = NULL, ';

					}else{

						return false;

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
			$sql_typestring .= $this->get_type($this->columns[$primary_key]['Type']);
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

	protected function createColumnString()
	{

	}



}

?>
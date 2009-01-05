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
	protected $sql_errno = 0;


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
		$db = db_connect($this->db_read);

		// setup SELECT

		$sql_select = 'SELECT * FROM ' . $this->table;

		// END setup SELECT

		// setup WHERE

		$sql_where = 'WHERE ';
		$where_loop = 0;
		foreach($this->values as $column => $value)
		{
			if($where_loop > 0)
			{
				$sql_where .= 'AND ';
			}

			$sql_input[] = $value;
			$sql_where .= $column . ' = ? ';
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

		if(isset($sql_orderby ))
			$sql_orderby .= ($order == 'ASC') ? ' ASC' : ' DESC';

		// END setup ORDER BY

		// setup LIMIT

		if($limit > 0)
		{
			$sql_limit = 'LIMIT ' . $limit;
			if($position > 0)
				$sql_limit .= ',' . $position;
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

			call_user_func_array(array($select_stmt, 'bind_param_and_execute'), $sql_input);
			$results = $select_stmt;
		}


		if($insert_stmt->errno > 0)
		{
			$this->sql_errno = $insert_stmt->errno;
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
		$db = db_connect($this->db_write);

		// setup SELECT

		$sql_delete = 'DELETE FROM ' . $this->table;

		// END setup SELECT

		// setup WHERE

		$sql_where = 'WHERE ';
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

		if($limit > 0)
		{
			$sql_limit = 'LIMIT ' . $limit;

		}

		// END setup LIMIT


		// create query
		$query = rtrim($sql_delete, ' ,') . ' ' . rtrim($sql_where, ' ,') . ' ' . rtrim($sql_limit, ' ,');

		if(count($sql_input) < 1)
		{
			$db->query($query);
			$results = $db->query($query);

		}else{
			array_unshift($sql_input, $sql_typestring);
			$delete_stmt = $db->stmt_init();
			$delete_stmt->prepare($query);

			call_user_func_array(array($delete_stmt, 'bind_param_and_execute'), $sql_input);
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
		if($this->selected)
		{
			return $this->update();
		}else{
			if($this->insert())
			{
				$this->selected = true;
				return true;
			}else{

				switch ($this->sql_errno) {
					case 0:
						return true;

					case 1022:
					// 1022 == duplicate key

						$this->sq_errno = 0;
						return $this->update();
						break;

					default:
						false;
						break;
				}

				return false;
			}
		}
	}

	protected function insert()
	{
		$sql_columns = '(';
		$sql_values = '(';
		$sql_input = array();

		foreach($this->columns as $column_name => $column_info)
		{
			if($column_info['Null'] == 'No' && !isset($this->values[$column_name]) && !isset($this->values[$column_info['Default']]))
				return false;

			$sql_columns .= $column_name . ', ';
			if($this->direct_values[$column_name])
			{
				$sql_values .= $this->direct_values[$column_name] .= ', ';
			}else{
				if(isset($this->values[$column_name]))
				{
					$sql_typestring .= $this->get_type($column_info['Type']);
					$sql_input[] = $this->values[$column_name];
					$sql_values .= '?, ';
				}else{
					$sql_values .= 'NULL, ';
				}
			}
		}

		$sql_columns = rtrim($sql_columns, ' ,') . ') ';
		$sql_values = rtrim($sql_values, ' ,') . ') ';
		array_unshift($sql_input, $sql_typestring);

		$query = 'INSERT INTO ' . $this->table . ' ' . $sql_columns . 'VALUES ' . $sql_values;

		$db = db_connect($this->db_write);
		$insert_stmt = $db->stmt_init();
		$insert_stmt->prepare($query);
		call_user_func_array(array($insert_stmt, 'bind_param_and_execute'), $sql_input);


		if($insert_stmt->errno > 0)
		{
			$this->sql_errno = $insert_stmt->errno;
			return false;
		}

		if(strpos($this->columns[$this->primary_keys[0]]['Extra'], 'auto_increment') !== false)
			$this->values[$this->primary_keys[0]] = $insert_stmt->insert_id;

		return true;

	}

	protected function update()
	{

		// UPDATE table SET column = ?, column = value, WHERE id = ?

		$sql_set = 'SET ';
		$sql_input = array();


		foreach($this->columns as $column_name => $column_info)
		{
			if($column_info['Null'] == 'No' && !isset($this->values[$column_name]) && !isset($this->values[$column_info['Default']]))
				return false;

			$sql_columns .= $column_name . ', ';
			if($this->direct_values[$column_name])
			{
				$sql_set .= $column_name . ' = ' . $this->direct_values[$column_name] .= ', ';
			}else{
				if(isset($this->values[$column_name]))
				{
					$sql_typestring .= $this->get_type($column_info['Type']);
					$sql_input[] = $this->values[$column_name];
					$sql_set .= $column_name . ' = ?, ';
				}else{
					$sql_set .= $column_name . ' = NULL, ';
				}
			}
		}


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

		$db = db_connect($this->db_write);
		$update_stmt = $db->stmt_init();
		$update_stmt->prepare($query);

		$results = call_user_func_array(array($update_stmt, 'bind_param_and_execute'), $sql_input);
		return $results;
	}

	// programmer interface stuff

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

		switch ($name) {
			case 'num_rows':
				$value = $this->num_rows;
				break;

			case 'errno':
				$value = $this->sql_errno;
				break;

			default:
				$value = $this->values[$name];
				break;
		}

		return $value;
	}

	public function __set($name, $value)
	{
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


	// internal stuff

	protected function load_schema()
	{

		$cache = new Cache('orm', 'schema', $this->table);
		$cache->cache_time = '3600';

		if(!($schema = $cache->get_data()))
		{

			$db = db_connect($this->db_write);
			$result = $db->query('SHOW FIELDS FROM ' . $this->table);




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



				$columns[$results['Field']] = $results;


				if($results['Key'] == 'PRI')
				{

					if(strpos($columns['Extra'], 'autoincrement') === false)
					{
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

		return ($this->data_types[$datatype]) ? ($this->data_types[$datatype]) : 's';
	}

}

?>
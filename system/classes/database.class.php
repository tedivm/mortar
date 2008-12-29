<?php
/**
 * Bento Base
 *
 * A framework for developing modular applications.
 *
 * @package		Bento Base
 * @author		Robert Hafner
 * @copyright	Copyright (c) 2007, Robert Hafner
 * @license		http://www.mozilla.org/MPL/
 * @link		http://www.bantobase.org
 */



/**
 * Database Conntection Class
 *
 * When called upon, it will return the appropriate database link if it
 * exists, otherwise it will establish the connection, store it for future use
 * and then return it.
 *
 * @package		BentoBase
 * @subpackage	Main_Classes
 * @category	Database
 * @author		Robert Hafner
 */
class DB_Connection
{
	private static $instance;
	private $db_connections = array();

	/**
	 * Protected Constructor
	 *
	 */
	protected function __construct()
	{

	}

	/**
	 * Returns the stored instance of the DB_Connection object. If no object
	 * is stored, it will create it
	 *
	 * @return DB_Connection allows
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object= __CLASS__;
			self::$instance=new $object;
		}
		return self::$instance;
	}

	/**
	 * Returns a database connection based off of the database config array
	 *
	 * @param string $param
	 * @return Mysql_Base|false
	 */
	public function getConnection($database = 'default')
	{
		if(isset($this->db_connections[$database]))
		{
			return $this->db_connections[$database];
		}else{


			try
			{

				if(!$this->iniFile)
				{
					$config = Config::getInstance();
					$path_to_dbfile = $config['path']['config'] . 'databases.php';

					$iniFile = new IniFile($path_to_dbfile);

					$this->iniFile = $iniFile;
				}

				$connectionInfo = $this->iniFile->getArray($database);


				$db_connection = new Mysql_Base(
				$connectionInfo['host'],
				$connectionInfo['username'],
				$connectionInfo['password'],
				$connectionInfo['dbname']);

				if($db_connection === false)
					throw new BentoError('Could not connect to database ' . $db_name);

				$this->db_connections[$database] = $db_connection;

				return $this->db_connections[$database];

			}catch(BentoError $e){

				return false;
			}
		}
	}


	public function __destruct()
	{

		foreach ($this->db_connections as $db)
		{
			$db->close();
		}
	}
}



/**
 * Database Class
 *
 * An extention of the MySQLi class, this returns a modified Statement class
 * when called to do so.
 *
 * @package		Bento Base
 * @subpackage	Main_Classes
 * @category	Database
 * @author		Robert Hafner
 */
class Mysql_Base extends mysqli
{
	static $query_count = 0;
	static $query_array = array();
    /**
     * This function overloads the original to return the new Mystmt class
     *
     * @return Mystmt
     */
	public function stmt_init()
	{
		self::$query_count++;
		return new Mystmt($this);
	}

	public function query($query, $resultmode = 0)
	{
		Mysql_Base::$query_count++;

		if(isset(Mysql_Base::$query_array[$query]))
		{
			Mysql_Base::$query_array[$query]++;
		}else{
			Mysql_Base::$query_array[$query] = 1;
		}


		return parent::query($query, $resultmode);
	}
}

/**
 * Database Statement Class
 *
 * An extention of the MySQLi STMT class, this adds the "fetch_array loop"
 * functionality as well as some other enhancements.
 *
 * @package		Bento Base
 * @subpackage	Main_Classes
 * @category	Database
 * @author		Robert Hafner
 */
class Mystmt extends mysqli_stmt
{
	public $myQuery;

	public function prepare($query)
	{
		$this->myQuery = $query;
		$result = parent::prepare($query);

		if(!$result)
			throw new BentoError('Unable to prepare statement: ' . $this->error);

		return parent::prepare($query);
	}

	/**
	 * After executing a statement, you can use this function to return each
	 * result set one row at a time as an associative array. You can use it to
	 * loop through your results
	 *
	 * @return array An associative array of the current result set
	 */
	public function fetch_array()
	{
		if($this->num_rows() < 1)
			return false;

		$data = $this->result_metadata();
		$fields = array();
		$out = array();

		$fields[0] = &$this;
		$count = 0;

		while($field = $data->fetch_field()) {
			$fields[$count] = &$out[$field->name];
			$count++;
		}

		//call_user_func_array(array('Mystmt', 'bind_result'), $fields);
		call_user_func_array(array($this, 'bind_result'), $fields);
		if($this->fetch())
		{
			return $out;
		}else{
			return false;
		}

    }

    /**
     * Combines the bind_param, execute, and store_results into a single function
     *
     *
     * @param string $types
     * @param mixed $var
     */
	public function bind_param_and_execute()
	{
		$params = func_get_args();
		Mysql_Base::$query_count++;

		if(isset(Mysql_Base::$query_array[$this->myQuery]))
		{
			Mysql_Base::$query_array[$this->myQuery]++;
		}else{
			Mysql_Base::$query_array[$this->myQuery] = 1;
		}


		if(!call_user_func_array(array($this, 'bind_param'), $params))
			throw new BentoError('Invalid Resource: ' . $this->error);

		if($this->execute())
		{
			$this->store_result();
			return true;
		}else{
			return false;
		}
	}
}



?>
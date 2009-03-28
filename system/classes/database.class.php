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
class DatabaseConnection
{
//	private static $instance;
	static private $db_connections = array();
	static private $iniFile;



	/**
	 * Returns a database connection based off of the database config array
	 *
	 * @param string $param
	 * @return Mysql_Base|false
	 */
	static public function getConnection($database = 'default_read_only', $useSaved = true)
	{
		if($useSaved && isset(self::$db_connections[$database]))
		{
			return self::$db_connections[$database];
		}else{


			try
			{

				if(!self::$iniFile)
				{
					$config = Config::getInstance();
					$path_to_dbfile = $config['path']['config'] . 'databases.php';

					$iniFile = new IniFile($path_to_dbfile);

					self::$iniFile = $iniFile;
				}

				$connectionInfo = self::$iniFile->getArray($database);

				$db_connection = new Mysql_Base(
				$connectionInfo['host'],
				$connectionInfo['username'],
				$connectionInfo['password'],
				$connectionInfo['dbname']);

				if($db_connection->connect_errno)
					throw new BentoError('Could not connect to database ' . $db_name . ': ' . $db_connection->error);

				$charset = (isset($connectionInfo['charset'])) ? $connectionInfo['charset'] : 'utf8';

				if(!$db_connection->set_charset($charset))
					throw new BentoError('Unable to switch db connection to utf8 charset.');

				if(!isset(self::$db_connections[$database]))
					self::$db_connections[$database] = $db_connection;

				return $db_connection;

			}catch(BentoError $e){

				return false;
			}
		}
	}

	static public function getStatement($database = 'default_read_only', $useSaved = true)
	{
		$db = DatabaseConnection::getConnection($database, $useSaved);
		return $db->stmt_init();
	}


	static function close()
	{
		foreach (self::$db_connections as $dbName => $db)
		{
			$db->close();
			unset(self::$db_connections[$dbName]);
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

	protected $autocommitCounter = 1;

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
		try{
			Mysql_Base::$query_count++;

			if(isset(Mysql_Base::$query_array[$query]))
			{
				Mysql_Base::$query_array[$query]++;
			}else{
				Mysql_Base::$query_array[$query] = 1;
			}

			if(!($result = parent::query($query, $resultmode)))
				$this->throwError();

		}catch(BentoError $e){
			throw $e;
		}catch(Exception $e){

		}

		return $result;
	}

	public function runFile($path)
	{
		try{
			if(!($sql = file_get_contents($path)))
				throw new BentoNotice('SQL file not found at ' . $path);

			if($this->multi_query($sql))
			{
				do
				{
					if($result = $this->store_result())
						$result->free();
				}while($this->more_results() && $this->next_result());
			}else{
				$this->throwError();
			}
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	public function throwError()
	{
		if($this->errno !== 0)
		{
			throw new BentoError($this->error);
		}else{
			throw new BentoNotice($this->error);
		}
	}



	public function autocommit($mode)
	{
		if($mode) //enable
		{
			// highest value should be 1
			if($this->autocommitCounter < 1)
				$this->autocommitCounter++;

			if($this->autocommitCounter == 1)
				parent::autocommit(true);

		}else{ //disable
			$this->autocommitCounter--;
			parent::autocommit(false);
		}
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
			$this->throwError('Unable to prepare statement');

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

    public function bind_param_and_execute()
    {
    	depreciationWarning();
    	$args = func_get_args();
    	return call_user_func_array(array($this, 'bindAndExecute'), $args);
    }

    /**
     * Combines the bind_param, execute, and store_results into a single function
     *
     *
     * @param string $types
     * @param mixed $var
     */
	public function bindAndExecute()
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
			$this->throwError();

		if($this->execute())
		{
			$this->store_result();
			return true;
		}else{
			if($this->errno > 0)
				$this->throwError();
			return false;
		}

	}

	public function throwError($message = '')
	{
		$message .= ' MySQL Error-' .$this->error;
		if($this->errno !== 0)
		{
			throw new BentoError($message);
		}else{
			throw new BentoNotice($message);
		}
	}
}



?>
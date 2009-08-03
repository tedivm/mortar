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
 * Database Conntection Class
 *
 * When called upon, it will return the appropriate database link if it
 * exists, otherwise it will establish the connection, store it for future use
 * and then return it.
 *
 * @package System
 * @subpackage Database
 */
class DatabaseConnection
{

	/**
	 * Everytime a connection is opened it gets stored in this array
	 *
	 * @access private
	 * @static
	 * @var array
	 */
	static private $dbConnections = array();

	/**
	 * Connections that aren't 'saved', so that we can still close them properly when the script closes
	 *
	 * @access private
	 * @static
	 * @var array
	 */
	static private $extraConnections = array();

	/**
	 * This is the database.ini file containing the login information for the databases
	 *
	 * @access private
	 * @static
	 * @var IniFile
	 */
	static private $iniFile;

	/**
	 * Returns the connection saved in the database.ini file
	 *
	 * @access public
	 * @static
	 * @param string $database
	 * @param bool $useSaved if set to false this will return a new connection
	 * @return MysqlBase
	 */
	static public function getConnection($database = 'default_read_only', $useSaved = true)
	{
		if($useSaved && isset(self::$dbConnections[$database]))
		{
			return self::$dbConnections[$database];
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

				$dbConnection = new MysqlBase(
				$connectionInfo['host'],
				$connectionInfo['username'],
				$connectionInfo['password'],
				$connectionInfo['dbname']);

				if($dbConnection->connect_errno)
					throw new DatabaseConnectionError('Could not connect to database ' . $db_name . ': ' . $dbConnection->error);

				$charset = (isset($connectionInfo['charset'])) ? $connectionInfo['charset'] : 'utf8';

				if(!$dbConnection->set_charset($charset))
					throw new DatabaseConnectionError('Unable to switch db connection charset to ' . $charset . ' c.');

				if(($useSaved && !isset(self::$dbConnections[$database])))
				{
					self::$dbConnections[$database] = $dbConnection;
				}else{
					self::$extraConnections[] = $dbConnection;
				}

				return $dbConnection;
			}catch(DatabaseConnectionError $e){
				return false;
			}
		}
	}

	/**
	 * Returns an enhanced version of the mysqli_stmt class using the specified connection
	 *
	 * @access public
	 * @static
	 * @param string $database
	 * @param bool $useSaved if set to false this will return a new connection
	 * @return Mystmt
	 */
	static public function getStatement($database = 'default_read_only', $useSaved = true)
	{
		$db = DatabaseConnection::getConnection($database, $useSaved);
		return $db->stmt_init();
	}

	/**
	 * Closes all of the database connections
	 *
	 * @access public
	 * @static
	 */
	static function close()
	{
		foreach (self::$dbConnections as $dbName => $db)
		{
			$db->close();
			unset(self::$dbConnections[$dbName]);
		}

		foreach(self::$extraConnections as $index => $db)
		{
			$db->close();
			unset(self::$extraConnections[$index]);
		}
	}
}

/**
 * Database Class
 *
 * An extention of the MySQLi class, this returns a modified Statement class
 * when called to do so.
 *
 * @package System
 * @subpackage Database
 */
class MysqlBase extends mysqli
{
	/**
	 * This is a running count of all queries (including those statement calls)
	 *
	 * @access public
	 * @static
	 * @var int
	 */
	static $queryCount = 0;

	/**
	 * This is a record of all queries run.
	 *
	 * @access public
	 * @static
	 * @var array The index is the query and the value is an integer representing the number of times it was called
	 */
	static $queryArray = array();

	/**
	 * When this value is one autocommit is on. Each time autocommit is turned off this number is decremented
	 * and each time its turned on its incremented, making it so the outer most layer of code controls the
	 * autocommit
	 *
	 * @access protected
	 * @var int
	 */
	protected $autocommitCounter = 1;

    /**
     * This function overloads the mysqli stmt_init() function to return the new Mystmt class
     *
     * @access public
     * @return Mystmt
     */
	public function stmt_init()
	{
		return new Mystmt($this);
	}

	/**
	 * This function is identical to the Mysli::query, except it uses our exceptions and logs to queryCount
	 * and queryArray
	 *
	 * @access public
	 * @see $queryArray, $queryCount
	 * @param string $query mysql query
	 * @param int $resultmode
	 * @return MySQLi_Result
	 */
	public function query($query, $resultmode = 0)
	{
		try{
			MysqlBase::$queryCount++;

			if(isset(MysqlBase::$queryArray[$query]))
			{
				MysqlBase::$queryArray[$query]++;
			}else{
				MysqlBase::$queryArray[$query] = 1;
			}

			if(!($result = parent::query($query, $resultmode)))
				$this->throwError();

		}catch(DatabaseConnectionError $e){
			throw $e;
		}catch(Exception $e){

		}

		return $result;
	}

	/**
	 * This function will run all the sql located in a file
	 *
	 * @access public
	 * @param string $path Path to the file
	 * @return bool status of query
	 */
	public function runFile($path)
	{
		try{
			if(!($sql = file_get_contents($path)))
				throw new DatabaseConnectionNotice('SQL file not found at ' . $path);

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


	/**
	 * This throws a BDatabaseConnectionError or DatabaseConnectionNotice, depending on the circumstances
	 *
	 * @access protected
	 */
	protected function throwError()
	{
		if($this->errno !== 0)
		{
			throw new DatabaseConnectionError($this->error);
		}else{
			throw new DatabaseConnectionNotice($this->error);
		}
	}

	/**
	 * Enables or disables autocommit. Because multiple classes can enable or disable this the function keeps track of
	 * the 'depth' of the autocommits to figure out when to properly commit to the database. This only affects
	 * autocommits, so committing manually works fun, and it can be overridden to force whatever option you want through
	 * immediately using the second argument.
	 *
	 * @param bool $mode
	 * @param bool $force
	 */
	public function autocommit($mode, $force = false)
	{
		if($force)
		{
			if($mode) $this->commit();
			$this->autocommitCounter = 1;
			return parent::autocommit((bool) $mode);
		}

		if($mode) //enable
		{
			// highest value should be 1
			if($this->autocommitCounter < 1)
				$this->autocommitCounter++;

			if($this->autocommitCounter == 1)
			{
				$this->commit();
				parent::autocommit(true);
			}

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
 * @package System
 * @subpackage Database
 */
class Mystmt extends mysqli_stmt
{
	/**
	 * This is a stored version of the prepared query
	 *
	 * @access public
	 * @var string sql
	 */
	public $myQuery;

	/**
	 * This is an extension of the Mysqli_stmt function
	 *
	 * @param string $query
	 * @return bool
	 */
	public function prepare($query)
	{
		$this->myQuery = $query;
		$result = parent::prepare($query);

		if(!$result)
			$this->throwError('Unable to prepare statement');

		return $result;
	}

	/**
	 * After executing a statement, you can use this function to return each
	 * result set one row at a time as an associative array. You can use it to
	 * iterate through your results
	 *
	 * @access public
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

		call_user_func_array(array($this, 'bind_result'), $fields);

		return ($this->fetch()) ? $out : false;
    }


    /**
     * Combines the bind_param, execute, and store_results into a single function
     *
     * @access public
     * @param string $types
     * @param mixed $var,...
     */
	public function bindAndExecute()
	{
		$params = func_get_args();
		MysqlBase::$queryCount++;

		$arrayIndex = $this->myQuery . '  ' . implode('::', $params);
		if(isset(MysqlBase::$queryArray[$arrayIndex]))
		{
			MysqlBase::$queryArray[$arrayIndex]++;
		}else{
			MysqlBase::$queryArray[$arrayIndex] = 1;
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

	/**
	 * This throws a BDatabaseConnectionError or DatabaseConnectionNotice, depending on the circumstances
	 *
	 * @access protected
	 */
	public function throwError($message = '')
	{
		$message .= ' MySQL Error-' .$this->error;
		if($this->errno !== 0)
		{
			throw new DatabaseConnectionError($message);
		}else{
			throw new DatabaseConnectionNotice($message);
		}
	}
}

class DatabaseConnectionError extends CoreError {}
class DatabaseConnectionNotice extends CoreNotice {}
?>
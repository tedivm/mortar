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
 * @package System
 * @subpackage Database
 */
class OrmTableStructure
{
	/**
	 * This is the table being checked.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * This is the connection to the database. It needs to be able to load constraints and field information.
	 *
	 * @var MysqlBase
	 */
	protected $connection;

	/**
	 * An array of columns.
	 *
	 * @var array
	 */
	public $columns;

	/**
	 * An array of columns that act as primary keys
	 *
	 * @var array
	 */
	public $primaryKeys;

	/**
	 * An array of foreign key data.
	 *
	 * @var array
	 */
	public $foreignKeys;

	/**
	 * This is an index relating how foreign keys fit with other tables.
	 *
	 * @var array
	 */
	public $foreignKeyLookup;

	/**
	 * This item defines how long the table meta data is stored in the cache.
	 *
	 * @var int
	 */
	public static $cacheTime = 43200;

	public function __construct($table, $connection)
	{
		$this->table = $table;
		$this->connection = $connection;

		$this->loadSchema();
	}

	/**
	 * This function loads the meta data (columns, types and keys) from the database so we don't need to manage
	 * configuration files. This metadata is cached for performance.
	 *
	 * @cache orm scheme *tableName
	 */
	protected function loadSchema()
	{
		$cache = new Cache('orm', 'schema', $this->table);
		$cache->cacheTime = self::$cacheTime;

		if(!($schema = $cache->getData()) || $cache->isStale())
		{
			$db = DatabaseConnection::getConnection($this->connection);

			$fieldData = $this->getFields($db);
			$schema['columns'] = $fieldData['columns'];
			$schema['primarykey'] = $fieldData['primarykey'];

			$foreignKeyInfo = $this->getForeignKeys($db);
			$schema['foreignKeys'] = $foreignKeyInfo['foreignKeys'];
			$schema['foreignKeysTableIndex'] = $foreignKeyInfo['foreignKeysTableIndex'];

			$cache->storeData($schema);
		} // end cache code

		$this->columns = $schema['columns'];
		$this->primaryKeys = $schema['primarykey'];
		$this->foreignKeys = $schema['foreignKeys'];
		$this->foreignKeyLookup = $schema['foreignKeysTableIndex'];
	}

	/**
	 * This function is used to pull all of the fields out of the database.
	 *
	 * @param MysqlBase $db
	 * @return array
	 */
	protected function getFields($db)
	{
		$result = $db->query('SHOW FIELDS FROM ' . $this->table);
		$primarykey = array();

		if(!$result)
			throw new OrmError('Database Error:' . $db->error . '    ORM unable to load table ' . $this->table);

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
		return $schema;
	}

	/**
	 * This function is used to pull all of the foreign keys out of the database.
	 *
	 * @param MysqlBase $db
	 * @return array
	 */
	protected function getForeignKeys($db)
	{
		$result = $db->query('SHOW CREATE TABLE ' . $this->table);
		$primarykey = array();

		if(!$result)
			throw new OrmError('Database Error:' . $db->error . '    ORM unable to load table ' . $this->table);

		if($results = $result->fetch_array())
		{
			$rows = explode(PHP_EOL, $results['Create Table']);
			$tableReference = array();
			$foreignKeys = array();
			foreach($rows as $row)
			{
				$row = trim($row);

				switch(substr($row, 0, 3))
				{
					case 'KEY':

						break;

					case 'CON':
						$foreignKeyStart = strpos($row, 'FOREIGN KEY (') + 12;
						$referenceStart = strpos($row, 'REFERENCES');

						$length = $referenceStart - $foreignKeyStart - 1;
						$referenceColumns = substr($row, $foreignKeyStart, $length);
						$referenceColumns = trim($referenceColumns, '()');
						$referenceColumns = explode(',', $referenceColumns);

						foreach($referenceColumns as $index => $column)
							$referenceColumns[$index] = trim($column, '`');

						$infoStart = $referenceStart + 11;
						$onDeleteStart = strpos($row, 'ON DELETE NO');
						$length = $onDeleteStart - $infoStart - 1;
						$foreignTableResponse = substr($row, $infoStart, $length);

						$firstSpace = strpos($foreignTableResponse, ' ');
						$foreignTable = substr($foreignTableResponse, 0, $firstSpace);
						$foreignTable = trim($foreignTable, '()');

						$foreignColumns = substr($foreignTableResponse, $firstSpace + 1);
						$foreignColumns = trim($foreignColumns, ' ()');
						$foreignColumns = explode(',', $foreignColumns);

						foreach($foreignColumns as $index => $column)
							$foreignColumns[$index] = trim($column, '`');

						if(count($referenceColumns) === 1 && count($foreignColumns) === 1 )
						{
							$tableReference[$foreignTable][] = $referenceColumns[0];
							$foreignKeys[$referenceColumns[0]] = $foreignColumns[0];

						}
						break;

					default:
						break;
				}
			}
		}

		$schema['foreignKeys'] = $foreignKeys;
		$schema['foreignKeysTableIndex'] = $tableReference;
		return $schema;
	}
}

?>
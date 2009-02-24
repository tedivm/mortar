<?php
/**
 * BentoBase
 *
 * A framework for developing modular applications.
 *
 * @package		BentoBase
 * @author		Robert Hafner
 * @copyright	Copyright (c) 2007, Robert Hafner
 * @license		http://www.mozilla.org/MPL/
 * @link		http://www.bentobase.com
 */



/**
 * Config Class
 *
 * This class stores all of the configuation variables from main_config.php
 * and also loads the variables from the URL. This class is a singleton, so
 * it needs to be initialized through GetInstance. It can be accessed as an array,
 * with the array key corrosponding to a config variable.
 *
 * @package		Bento Base
 * @subpackage	Main_Classes
 * @category	Configuration
 * @author		Robert Hafner
 */
class Config implements ArrayAccess
{
	// $instance holds a single instance of the object

	static $instance;
	protected $config = array();
	public $error = false;


	/**
	 * Private constuctor, can only be called through GetInstance
	 *
	 */
	private function __construct()
	{
		$this->reset();
	}

	public function reset()
	{
		$this->config = array();
		$this->error = false;

		try {
			$path = BASE_PATH . 'data/configuration/configuration.php';
			if(!file_exists($path))
				throw new Exception($path . ' does not exist.');

			$iniFile = new IniFile($path);
			$this->config = $iniFile->getArray();
			$this->baseUrls = $iniFile->getArray('url'); // $config['url'];

		}catch (Exception $e){
			$this->error = true;
			$this->buildFromBase();

			if(DEBUG == 1)
			{
				$output = "\n";
				$output .= 'Exception: [' . $e->getCode() . ']: ' . $e->getMessage() . "\n";
				$output .= $e->getFile() . ': ' . $e->getLine() . "\n";
				$output .= $e->getTraceAsString();
				$output .= "\n";
				echo nl2br($output);
			}
		}

	}

	/**
	 * Returns the stored instance of the Config object. If no object is stored, it will create it
	 *
	 * @return Config
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object;
		}
		return self::$instance;
	}


	/**
	 * Takes in a key and returns the associated value.
	 *
	 * @param mixed $key
	 * @return mixed result can be any type of variable.
	 */
	public function setting($key)
	{
		return $this->config[$key];
	}


	/**
	 * Allows you to change a setting in the Config Object
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return true
	 */
	public function change_setting($key, $value)
	{
		$this->config[(string) $key] = $value;
		return true;
	}


	public function buildFromBase()
	{


		if(is_dir(BASE_PATH))
			$this->config['path']['base'] = BASE_PATH ;


		if(is_dir(BASE_PATH . 'system/engines/'))
			$this->config['path']['engines'] = BASE_PATH . 'system/engines/';

		if(is_dir(BASE_PATH . 'system/abstracts/'))
			$this->config['path']['abstracts'] = BASE_PATH . 'system/abstracts/';

		if(is_dir(BASE_PATH . 'system/library/'))
			$this->config['path']['library'] = BASE_PATH . 'system/library/';

		if(is_dir(BASE_PATH . 'modules/'))
			$this->config['path']['modules'] = BASE_PATH . 'modules/';


		if(is_dir(BASE_PATH . 'javascript/'))
			$this->config['path']['javascript'] = BASE_PATH . 'javascript/';






		$this->config['url']['modules'] = 'modules/';

		if(is_dir(BASE_PATH . 'system/classes/'))
			$this->config['path']['mainclasses'] = BASE_PATH . 'system/classes/';

		if(is_dir(BASE_PATH . 'data/themes/'))
			$this->config['path']['theme'] = BASE_PATH . 'data/themes/';

		$this->config['url']['theme'] = 'data/themes/';

		$this->config['url']['javascript'] = 'javascript/';


	}


	public function offsetExists($offset)
	{
		if(isset($this->config[$offset]))
		{
			return true;
		}

		return false;

	}

	public function offsetGet($offset)
	{
		return $this->config[$offset];
	}


	public function offsetSet($key, $value)
	{
		$this->config[$key] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->config[$offset]);
		return true;
	}

}

?>
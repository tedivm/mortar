<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Environment
 */

/**
 * System configuration class
 *
 * This class loads various system configuration values from configuration.ini file. Its also a singleton
 *
 * @package System
 * @subpackage Environment
 */
class Config implements ArrayAccess
{

	/**
	 * Contains the primary instance of the config class
	 *
	 * @access public
	 * @static
	 * @var Config
	 */
	static $instance;

	/**
	 * This associative, multidemensional array contains the values from the configuration.ini file
	 *
	 * @access protected
	 * @var array
	 */
	protected $config = array();

	/**
	 * In the event of an error loading the config file, this value is set to true
	 *
	 * @access public
	 * @var bool
	 */
	public $error = false;


	/**
	 * The constructor has to be locked down to keep this a singleton
	 *
	 * @access private
	 */
	private function __construct()
	{
		$this->reset();
	}

	/**
	 * This method resets the configuration variables and reloads the configuration.ini file
	 *
	 * @access public
	 */
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
	 * @access public
	 * @static
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
	 * @param string $key
	 * @return string|array
	 */
	public function setting($key)
	{
		return $this->config[$key];
	}

	/**
	 * Allows you to change a setting in the Config Object
	 *
	 * This class is primarily needed due to a quirk in the way php handles (or doesn't handle) the ArrayAccess object
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function changeSetting($key, $value)
	{
		$this->config[(string) $key] = $value;
	}

	/**
	 * This function is primarily for the installer. It sets default values to bootstrap the system
	 *
	 * @access public
	 * @internal
	 */
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
		if(isset($this->config[$offset]))
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
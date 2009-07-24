<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage RequestWrapper
 */

/**
 * This class takes an action, runs it, and then formats the output as json.
 *
 * @package System
 * @subpackage RequestWrapper
 */
class JsonOutputController extends AbstractOutputController
{
	/**
	 * Sets the mime type to application/json
	 *
	 * @var string
	 */
	public $mimeType = 'application/json';

	/**
	 * This enables the jsonp functionality. Currently this is disabled because I am not sure what all the security
	 * repercussions are of this- its probably something thats going to need to be restricted to the REST interface.
	 *
	 * @var bool
	 */
	static public $jsonpEnable = false;

	/**
	 * This function takes the output and saves it as the active resource.
	 *
	 * @param mixed $output This can be any datatype that can run through json_encode
	 */
	protected function bundleOutput($output)
	{
		$this->activeResource = $output;
	}

	/**
	 * This function encodes into json the data that was saved into the active resource. If jsonp is enabled it will
	 * check to see if there was a callback function name set in the query and wrap the data around it if there was.
	 *
	 * @return string returns the json_encode() results for the active resource
	 */
	protected function makeDisplayFromResource()
	{
		$query = Query::getQuery();
		$json = json_encode($this->activeResource);
		if(isset($query['callback']) || self::$jsonpEnable)
		{
			$this->mimeType = 'application/javascript';
			$callback = preg_replace('[^A-Za-z0-9]', '', $query['callback']);
			return  $callback . '(' . $json . ')';
		}else{
			return $json;
		}
	}
}

?>
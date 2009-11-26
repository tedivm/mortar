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
 * This class takes an action, runs it, and outputs the results directly.
 *
 * @package System
 * @subpackage RequestWrapper
 */
class DirectOutputController extends AbstractOutputController
{

	/**
	 * The mimetype is left as null so that the action itself can sent the header. If a standard format is available
	 * then that will be used instead.
	 *
	 * @var null|string
	 */
	public $mimeType;

	static protected $formatToMimetype = array();

	static $acceptedFormats = array('Text', 'Css', 'Js');


	protected function makeDisplayFromResource()
	{
		return $this->activeResource;
	}


	/**
	 * This checks to make sure the action can actually be run with this particular format.
	 *
	 * @param Action $action
	 * @return bool
	 * @todo Write format-specific permissions
	 */
	public function checkAction($action, $format = null)
	{
		$query = Query::getQuery();
		if(parent::checkAction($action, $query['format']))
		{
			$query = Query::getQuery();
			$this->setFormat($query['format']);
			return true;
		}elseif(parent::checkAction($action, 'Raw')){
			return true;
		}
		return false;
	}

	protected function setFormat($format)
	{
		if((in_array($format, self::$formatToMimetype)))
			$this->mimeType = self::$formatToMimetype[$format];

		$this->format = $format;
	}

	static function acceptsFormat($format)
	{
		return (in_array($format, self::$acceptedFormats));
	}
}

?>
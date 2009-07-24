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
 * This class takes an action, runs it, and then formats the output as XML. Currently this is more of a placeholder, as
 * it just sends out the action result directly at the moment.
 *
 * @package System
 * @subpackage RequestWrapper
 */
class XmlOutputController extends AbstractOutputController
{
	/**
	 * This is the mimetype for this format
	 *
	 * @var string
	 */
	public $mimeType = 'application/xml';

	/**
	 * This takes the output of the action and stores it as the activeResource. It will look for strings
	 * and SimpleXML classes
	 *
	 * @param string|SimpleXMLElement $output
	 */
	protected function bundleOutput($output)
	{
		$this->activeResource = $output;
	}

	/**
	 * This takes the active resoource and, if its not a string already, turns it into one to display.
	 *
	 * @return string
	 */
	protected function makeDisplayFromResource()
	{
		if($this->activeResource instanceof SimpleXMLElement)
		{
			return $this->activeResource->asXml();
		}else{
			return $this->activeResource;
		}
	}
}

?>
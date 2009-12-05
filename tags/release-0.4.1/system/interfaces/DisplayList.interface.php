<?php
/**
 * Mortar
 *
 * @author Robert Hafner
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * DisplayLists convert arrays of Models into a final HTML display. All such classes should implement this interface.
 *
 * @package System
 * @subpackage ModelSupport
 */
interface DisplayList
{
	/**
	 * This function preps the display list with data from the parent model and an array of models to display.
	 *
	 * @param Model $mmodel
	 * @param array $modelList
	 */
	public function __construct(Model $mmodel, array $modelList);

	/**
	 * This function takes in the Page class which will ultimately display the listing.
	 *
	 * @param Page $page
	 */
	public function addPage(Page $page);

	/**
	 * This function returns the model list into an HTML listing to display in the page.
	 *
	 */
	public function getListing();
}

?>
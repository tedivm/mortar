<?php
/**
 * Mortar
 *
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
	public function __construct(Model $m, array $models, Page $p);
	public function getListing();
}

?>
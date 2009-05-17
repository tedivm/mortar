<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package		Library
 * @subpackage	Html
 */

/**
 * This class is used to created Html structures that are organized and easy to read.
 *
 * @package		Library
 * @subpackage	Html
 */
class HtmlObject
{
	/**
	 * This is an array of properties to display inside the html tag.
	 *
	 * @var array
	 */
	public $properties = array();

	/**
	 * This is the name of the HtmlObject
	 *
	 * @var string
	 */
	public $name;

	/**
	 * This is the type of html tag this item is representing.
	 *
	 * @var string must be a valid html type
	 */
	public $type;

	/**
	 * This is the id of the html tag being generated.
	 *
	 * @var unknown_type
	 */
	public $id;

	/**
	 * This is a list of classes the html tag has.
	 *
	 * @var array
	 */
	public $classes = array();

	/**
	 * This is the tab depth the html structure should have.
	 *
	 * @var int
	 */
	public $tabLevel = 1;

	/**
	 * This is the tab charactor used for indenting html tags.
	 *
	 * @var string
	 */
	public $tabSpace = '  ';

	/**
	 * This defines whether the tag needs a closing argument.
	 *
	 * @var bool
	 */
	protected $close = true;

	/**
	 * This is an array of other HtmlObjects and strings that this object is wrapped around.
	 *
	 * @var array
	 */
	protected $encloses = array();

	/**
	 * This is mostly formating, it defines whether the tag should add new lines around enclosing strings/objects.
	 *
	 * @var bool
	 */
	protected $tightEnclose = false;

	/**
	 * This is an array of tags that get comments after their closing tags. This is used for making structure readable.
	 *
	 * @var array
	 */
	protected $hasClosingComment = array('div', 'fieldset', 'ul');

	/**
	 * This is a list of tags that are, by default, listed as having a tight enclosure.
	 *
	 * @var array
	 */
	protected $hasTightEnclose = array('a', 'label', 'textarea', 'input', 'legend', 'option',
										'h1', 'h2', 'h3', 'h4', 'h5', 'b', 'u', 'i', 'em');

	/**
	 * This constructor takes the type of html tag as its argument.
	 *
	 * @example $div = new HtmlObject('div');
	 * @param string $type
	 */
	public function __construct($type)
	{
		$this->type = $type;

		if(in_array($type,$this->hasTightEnclose))
			$this->tightEnclose = true;

	}

	/**
	 * This makes the tag as one that has no closing tag. Returns itself to allow method chaining.
	 *
	 * @return HtmlObject
	 */
	public function noClose()
	{
		$this->close = false;
		return $this;
	}

	/**
	 * This takes an HtmlObject or string and adds it to the elements inside the html object. By default it adds these
	 * to the bottom of the item, but the second argument can be changed to place things at the top. Returns itself to
	 * allow method chaining.
	 *
	 * @param string|HtmlObject $html
	 * @param string $location top or bottom
	 * @return HtmlObject
	 */
	public function wrapAround($html, $location = 'bottom')
	{
		switch ($location) {
			case 'top':
				array_unshift($this->encloses, $html);
				break;
			case 'bottom':
			default:
				$this->encloses[] = $html;
				break;
		}

		return $this;
	}

	/**
	 * This creates a new html object, of the type specified, and puts it inside this current object.
	 *
	 * @param string $type
	 * @param null|string $location top or bottom
	 * @return HtmlObject
	 */
	public function insertNewHtmlObject($type, $location = null)
	{
		$object = new HtmlObject($type);
		$this->wrapAround($object, $location);
		return $object;
	}

	/**
	 * Adds a class, or an array of classes, to the html tag. Returns itself to allow method chaining.
	 *
	 * @param string|array $class
	 * @return HtmlObject
	 */
	public function addClass($class)
	{
		if(is_array($class))
		{
			$this->classes = array_merge($this->classes, $class);
		}else{
			$this->classes[] = $class;
		}
		return $this;
	}

	/**
	 * Adds a property, or an array of properties, to the html tag. Returns itself to allow method chaining.
	 *
	 * @param string|array $property Arrays should be name => value pairs
	 * @param string|null $value If the first arument is passed an array, this value is disregarded
	 * @return HtmlObject
	 */
	public function property($property, $value = null)
	{
		if(is_string($property)){

			if(isset($value))
				$this->properties[$property] = (string) $value;
			return $this;

		}elseif(is_array($property)){

			foreach($property as $name => $value)
			{
				$this->properties[$name] = ($value !== false) ? (string) $value : false;
			}
			return $this;
		}

		return isset($this->properties[$property]) ? $this->properties[$property] : null;
	}

	/**
	 * This turns the HtmlObject into a string. This includes converting enclosed HtmlObjects into strings. Each layer
	 * of HtmlObject gets indented by an additional level. Some tags, such as divs, get comments added to their closing
	 * tags to make the source code more readable. If the system constant CONCISE_HTML is set to true, all tabs and
	 * comments are not added,
	 *
	 * @return string
	 */
	public function __toString()
	{
		$extendedWhitespace = (!(defined('CONCISE_HTML') && CONCISE_HTML === true));
		$tab = ($extendedWhitespace) ? str_repeat($this->tabSpace, $this->tabLevel) : '';

		$string = ($extendedWhitespace) ? PHP_EOL : '';
		$string = PHP_EOL . $tab .'<' . $this->type;

		if($this->type == 'div' && $extendedWhitespace)
			$string = PHP_EOL . $string;

		$classString = '';
		foreach($this->classes as $class)
			$classString .= $class . ' ';

		$string .= ($classString) ? ' class=\'' . trim($classString) . '\'': '';

		foreach($this->properties as $name => $value)
			$string .= ' ' . $name . '="' . $value . '"';


		$string .= '>';

		if(count($this->encloses) > 0)
		{
			foreach($this->encloses as $item)
			{
				if(get_class($item) == 'HtmlObject')
				{
					$item->tabLevel = $this->tabLevel + 1;
				}else{
					$item = ($this->tightEnclose) ? rtrim($item, ' ') : $item . PHP_EOL;
				}
				$string .= $item;
			}
			$internalStuff = true;
		}

		if($this->close)
		{
			if(!$this->tightEnclose)
				$string .= $tab;

			$string .= '</' . $this->type . '>';

			if(in_array($this->type, $this->hasClosingComment) && isset($this->properties['id'])
				 && $extendedWhitespace)
			{
				$string .= '<!-- #'. $this->properties['id'] .' -->';
			}
		}

		$string .= PHP_EOL;
		return $string;
	}
}

?>
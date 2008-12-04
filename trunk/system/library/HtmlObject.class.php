<?php

class HtmlObject
{
	public $properties = array();
	public $name;
	public $type;
	public $id;
	public $classes = array();
	public $tabLevel = 1;
	
	protected $close = true;
	protected $encloses = array();
	protected $tightEnclose = false;
	
	
	public function __construct($type)
	{
		$this->type = $type;
		
		if(in_array($type, array('a', 'label', 'textarea', 'input', 'legend', 'option', 'h1', 'h2', 'h3', 'h4', 'h5')))
			$this->tightEnclose();
		
	}
	
	public function tightEnclose()
	{
		$this->tightEnclose = true;
		return $this;
	}
	
	public function noClose()
	{
		$this->close = false;
		return $this;
	}
	
	public function wrapAround($text, $location = 'bottom')
	{
		switch ($location) {
			case 'top':
				array_unshift($this->encloses, $text);
				break;
			case 'bottom':
			default:
				$this->encloses[] = $text;
				break;
		}

		return $this;
	}
	
	public function insertNewHtmlObject($type)
	{
		$object = new HtmlObject($type);
		$this->wrapAround($object);
		return $object;
	}
	
	public function addClass($class)
	{
		$this->classes[] = $class;
		return $this;
	}
	
	public function property($property, $value = false)
	{
		if(is_string($property)){
			
			$this->properties[$property] = $value;
			return $this;
			
		}elseif(is_array($property)){
			
			foreach($property as $name => $value)
			{
				$this->properties[$name] = ($value) ? $value : false;
			}
			return $this;
		}
		
		return $this->properties[$property];
	}
	
	public function append($HtmlObject)
	{
		return $this;
	}
	
	public function __toString()
	{
		$tabSpaces = '   ';
		$tab = str_repeat($tabSpaces, $this->tabLevel);
		$string = PHP_EOL . $tab .'<' . $this->type;
		
		$string .= ($this->id) ? ' id="' . $this->id . '"': '';
		
		foreach($this->classes as $class)
		{
			$classString .= $class . ' ';
		}
		
		$string .= ($classString) ? ' class=\'' . trim($classString) . '\'': '';
		
		foreach($this->properties as $name => $value)
		{
			$string .= ' ' . $name . '="' . $value . '"';
		}
		
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

		}
		$string .= PHP_EOL;
		return $string;
	}
}

?>
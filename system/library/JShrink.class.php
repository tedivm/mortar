<?php

class JShrink
{
	protected $input;
	protected $inputLength;
	protected $index = 0;

	protected $output = '';

	protected $a = '';
	protected $b = '';
	protected $c = null;

	function minify($js, $options = array())
	{

		$js = str_replace("\r\n", "\n", $js);
		$this->input = str_replace("\r", "\n", $js);
		$this->inputLength = strlen($this->input);

		$this->a = $this->getReal();
		$this->b = $this->getReal();

		while($this->a !== false)
		{
			switch($this->a)
			{
				// new lines
				case "\n":
					$this->output .= "\n";
					if(strpos('(-+{[', $this->b) !== false)
					{
						$this->output .= $this->a;
						$this->saveString();
						break;
					}

					// if this is spaces continue to the one below
					if($this->b != ' ')
						break;

//echo 1111 . '<hr>';


				// spaces
				case ' ':
					if($this->isAlphaNumeric($this->b))
						$this->output .= $this->a;

					$this->saveString();
					break;

				default:

					switch($this->b)
					{
						case "\n":
							if(strpos('}])+-"\'', $this->a) !== false)
							{
								$this->output .= $this->a;
								break;
							}else{
								if($this->isAlphaNumeric($this->a))
								{
									$this->output .= $this->a;
									$this->saveString();
								}
							}
							break;

						case ' ':
							if(!$this->isAlphaNumeric($this->a))
								break;

						default:
							$this->output .= $this->a;
							$this->saveString();
							break;
					}
			}

			// do reg check of doom
			$this->b = $this->getReal();

			if($this->b == '/' && strpos('(,=:[!&|?', $this->a) !== false)
			{

				$this->output .= $this->a . $this->b;

				while(($this->a = $this->getChar()) !== false)
				{
					if($this->a == '/')
						break;

					if($this->a == '\\')
					{
						$this->output .= $this->a;
						$this->a = $this->getChar();
					}

					if($this->a == "\n")
						throw new JShrinkException('Stray regex pattern. ' . $this->index);

					$this->output .= $this->a;
				}
				$this->b = $this->getReal();
			}
		}
		return $this->output;
	}

	function getChar()
	{
		if(isset($this->c))
		{
			$char = $this->c;
			unset($this->c);
		}else{
			if(isset($this->input[$this->index]))
			{
				$char = $this->input[$this->index];
				$this->index++;
			}else{
				return false;
			}
		}

		if($char === "\n" || ord($char) >= 32)
			return $char;

		return ' ';
	}

	function getReal()
	{
		$char = $this->getChar();

		if($char == '/')
		{
			$this->c = $this->getChar();
			if($this->c == '/')
			{
				// kill rest of line
				$char = $this->getNext("\n");
				$char = $this->getChar();
				$char = $this->getChar();

				unset($this->c);

			}elseif($this->c == '*'){

				// kill everything up to the next */
				if($this->getNext('*/'))
				{
					$char = $this->getChar(); // *
					$char = $this->getChar(); // /
					$char = $this->getChar();
					$char = $this->getChar();
				}else{
					$char = false;
				}

				if($char === false)
					throw new JShrinkException('Stray comment. ' . $this->index);

				unset($this->c);
			}
		}
		return $char;
	}

	function getNext($string)
	{
		$pos = strpos($this->input, $string, $this->index);

		if($pos === false)
			return false;

		$this->index = $pos ;
		return $this->input[$this->index];
	}

	function saveString()
	{
		$this->a = $this->b;
		if($this->a == '\'' || $this->a == '"')
		{
			// save literal string
			$stringType = $this->a;

			while(1)
			{
				$this->output .= $this->a;
				$this->a = $this->getChar();

				switch($this->a)
				{
					case $stringType:
						break 2;

					case "\n":
						throw new JShrinkException('Unclosed string. ' . $this->index);
						break;

					case '\\':
						$this->output .= $this->a;
						$this->a = $this->getChar();
				}
			}
		}
	}

	public function isAlphaNumeric($char)
	{
		return ord($char) > 126 || $char === '\\' || preg_match('/^[\w\$]$/', $char) === 1;
		return preg_match('/^[\w\$]$/', $char) === 1;
	}

}

class JShrinkException extends Exception {}
//class JShrinkException extends CoreError {}
?>
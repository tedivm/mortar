<?php
/*
  JShrink

  Copyright (c) 2009, Robert Hafner
  All rights reserved.

  Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

	* Redistributions of source code must retain the above copyright notice, this list of conditions and the following
		disclaimer.
	* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the
		following disclaimer in the documentation and/or other materials provided with the distribution.
	* Neither the name of the <ORGANIZATION> nor the names of its contributors may be used to endorse or promote
		products derived from this software without specific prior written permission.

  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
  INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
  SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
  SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
  WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
  THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


  Ph'nglui Mglw'nafh Cthulhu R'lyeh wgah'nagl fhtagn
*/


/**
 * JShrink
 *
 * Usage - JShrink::minify($js);
 *
 * @package JShrink
 * @author Robert Hafner <tedivm@tedivm.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 */
class JShrink
{
	protected $input;
	protected $inputLength;
	protected $index = 0;

	protected $a = '';
	protected $b = '';
	protected $c;

	static public function minify($js)
	{
		try{
			ob_start();
			$me = new JShrink();
			$me->breakdownScript($js);
			$output = ob_get_clean();
			return $output;
		}catch(Exception $e){
			ob_end_clean();
			throw $e;
		}
	}

	protected function breakdownScript($js)
	{
		$js = str_replace("\r\n", "\n", $js);
		$this->input = str_replace("\r", "\n", $js);
		$this->inputLength = strlen($this->input);

		$this->a = $this->getReal();
		$this->b = $this->getReal();

		while($this->a !== false && !is_null($this->a) && $this->a !== '')
		{
			switch($this->a)
			{
				// new lines
				case "\n":
					// if the next line is something that can't stand alone preserver the newline
					if(strpos('(-+{[', $this->b) !== false)
					{
						echo $this->a;
						$this->saveString();
						break;
					}

					// if its a space we move down to the string test below
					if($this->b === ' ')
						break;

					// otherwise we treat the newline like a space

				// spaces
				case ' ':
					if(self::isAlphaNumeric($this->b))
						echo $this->a;

					$this->saveString();
					break;

				default:
					switch($this->b)
					{
						case "\n":
							if(strpos('}])+-"\'', $this->a) !== false)
							{
								echo $this->a;
								$this->saveString();
								break;
							}else{
								if(self::isAlphaNumeric($this->a))
								{
									echo $this->a;
									$this->saveString();
								}
							}
							break;

						case ' ':
							if(!self::isAlphaNumeric($this->a))
								break;

						default:
							echo $this->a;
							$this->saveString();
							break;
					}
			}

			// do reg check of doom
			$this->b = $this->getReal();

			if($this->b == '/' && strpos('(,=:[!&|?', $this->a) !== false)
			{

				echo $this->a . $this->b;

				while(($this->a = $this->getChar()) !== false)
				{
					if($this->a == '/')
						break;

					if($this->a == '\\')
					{
						echo $this->a;
						$this->a = $this->getChar();
					}

					if($this->a == "\n")
						throw new JShrinkException('Stray regex pattern. ' . $this->index);

					echo $this->a;
				}
				$this->b = $this->getReal();
			}
		}
	}

	protected function getChar()
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

	protected function getReal()
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

	protected function getNext($string)
	{
		$pos = strpos($this->input, $string, $this->index);

		if($pos === false)
			return false;

		$this->index = $pos ;
		return $this->input[$this->index];
	}

	protected function saveString()
	{
		$this->a = $this->b;
		if($this->a == '\'' || $this->a == '"')
		{
			// save literal string
			$stringType = $this->a;

			while(1)
			{
				echo $this->a;
				$this->a = $this->getChar();

				switch($this->a)
				{
					case $stringType:
						break 2;

					case "\n":
						throw new JShrinkException('Unclosed string. ' . $this->index);
						break;

					case '\\':
						echo $this->a;
						$this->a = $this->getChar();
				}
			}
		}
	}

	static protected function isAlphaNumeric($char)
	{
		return preg_match('/^[\w\$]$/', $char) === 1;
	}

}

// Adding a custom exception handler for your own projects just means changing this line
class JShrinkException extends Exception {}
?>
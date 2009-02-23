<?php

class Query
{
	static protected $input;

	static function getQuery()
	{
		if(!self::$input)
		{

			if(defined('STDIN'))
			{
				$input = Argv::getArray();
			}else{
				$input = Get::getArray();
			}


			self::$input = $input;
		}

		$output = new FilteredArray(self::$input);

		return $output;
	}
}

?>
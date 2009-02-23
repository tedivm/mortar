<?php

class Input
{

	static function getInput()
	{

		if($_SERVER['REQUEST_METHOD'] == 'PUT')
		{

			parse_str(file_get_contents("php://input"), $inputArray);

		}elseif(isset($_POST)){

			$inputArray = ((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc())
								|| (ini_get('magic_quotes_sybase')
										&& (strtolower(ini_get('magic_quotes_sybase'))!="off")))
					 ? stripslashes_deep($_POST)
					 : $_POST;

		}

		$arrayObject = new FilteredArray($inputArray);
		return $arrayObject;
	}
}

?>
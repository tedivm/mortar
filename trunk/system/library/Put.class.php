<?php

class Put extends Post
{
	public function __construct()
	{
		if($_SERVER['REQUEST_METHOD'] == 'PUT')
		{
			parse_str(file_get_contents("php://input"), $putArray);

			$this->variables = ((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) || (ini_get('magic_quotes_sybase') && (strtolower(ini_get('magic_quotes_sybase'))!="off")))
				 ? stripslashes_deep($putArray)
				 : $putArray;
		}else{
			$this->variables = array();
		}
	}
}

?>
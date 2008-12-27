<?php

interface ActionInterface
{
	public function __construct($identifier);

	public function checkAuth($action = NULL);




}

?>
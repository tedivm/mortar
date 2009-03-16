<?php

Interface Model extends ArrayAccess
{

	public function __construct($id = null);

	public function delete();
	public function save();

	public function checkAuth($action, $user = null);

	public function getAction($actionName);
	public function getAttributes();
	public function getId();
	public function getLocation();
	public function getProperties();
	public function getType();



	// class properties define content, the actual substance of the class
	public function __get($name);
	public function __set($name, $value);
	public function __isset($name);
	public function __unset($name);

	public function __toArray();
}


?>
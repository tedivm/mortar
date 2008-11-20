<?php

interface IntModule
{
	public function __construct($id);
	public function check_auth();
	public function run();
	public function get_content();
}

interface IntModuleAdmin extends IntModule 
{
	//public function get_title();
}

interface IntModuleWeb extends IntModule
{
	public function get_title();
	public function get_misc();
	public function get_meta();
}

interface IntModuleInstall
{
	public function __construct();
	public function form();
	public function install_from_form();
	public function quick_install($name);
}

interface IntModuleInfo
{
	public function __construct($module_id = '');
	public function get_location();
	public function action_auth($engine, $action);
//	public function get_internal_id($id);
}

// I am going to need to revisit global modules soon
interface WidgetInterface
{
	public function __construct($id, $name, $location_id);
	public function start();
	public function stop();
}






?>
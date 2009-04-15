<?php

class BentoBasePluginAdminNav
{
	protected $links = array('Universal' => array());

	public function __construct()
	{
		$url = new Url();
		$url->module = 'BentoBase';
		$url->action = 'LogIn';

		$this->addLink('login', 'Log In', $url, 'Main', 'Pony');
		$this->addLink('login', 'Log Out', $url, 'Main');
	}

	public function getTabs()
	{
		$keys = array_keys($this->links);
		unset($keys['Universal']);
		return $keys;
	}

	public function getNav($activeTab)
	{
		$tabItems = (isset($this->links[$activeTab])) ? $this->links[$activeTab] : array();
		return array_merge_recursive($tabItems, $this->links['Universal']);
	}


	protected function addLink($name, $label, Url $url, $tab, $category = 'StandAlone')
	{
		$action = array();
		$action['name'] = $name;
		$action['label'] = $label;
		$action['url'] = $url;
		$this->links[$tab][$category][] = $action;
	}



}

?>
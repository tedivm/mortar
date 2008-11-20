<?php

class BentoBaseActionTest extends PackageAction  
{
	//static $requiredPermission = 'Read';
	
	public $AdminSettings = array('linkLabel' => 'Test',
									'linkTab' => 'Main',
									'headerTitle' => 'Test',
									'linkContainer' => 'Test');
	
	protected function logic()
	{
		
	}
	
	public function viewHtml()
	{
		$config = Config::getInstance();
		
		if($config['id'] == 'admin')
		{
			$adminUrl = new Url();
			$adminUrl->engine = 'Admin';
			
			//return $adminUrl;
			
			header('Location: ' . $adminUrl);
		}		
		
		$page = ActivePage::get_instance();
		$page['title'] = 'Test Title';
		return 'This is the main page';
	}
	
	public function viewAdmin()
	{
		

		$form = new Form('test');
		$form->enableCache(array('module', 'id', 'thing'));
		$form->changeSection('readonlyDatabase')->
				setLegend('Read Only Database Connection')->
				setSectionIntro('This is the read only database connection, which all of the select statements use. If you do not have a seperate user for this you may leave it blank.')->
						
				createInput('DBROname')->
					setLabel('Database')->
				getForm()->
					
				createInput('DBROusername')->
					setLabel('User')->
				getForm()->
					
				createInput('DBROpassword')->
					setLabel('Password')->
				getForm()->
					
				createInput('DBROhost')->
					setLabel('Host')->
					property('value', 'localhost');				
		
					
			$form2 = new Form('test2');
			$form2->changeSection('mainDatabase')->
				setLegend('Main Database Connection')->
				setSectionIntro('This is the primary database connection. This user needs to have full access to the database.')->
					
				createInput('DBname')->
					setLabel('Database')->
					addRule('required')->
					
				getForm()->
					
				createInput('DBusername')->
					setLabel('User')->
					addRule('required')->
				getForm()->
					
				createInput('DBpassword')->
					setLabel('Password')->
					addRule('required')->
				getForm()->
					
				createInput('DBhost')->
					setLabel('Host')->
					property('value', 'localhost')->
					addRule('required');					

		$form->merge($form2);	
		
		return $form->makeDisplay();
		return 'This is the main page';
	}
	
	
}



?>
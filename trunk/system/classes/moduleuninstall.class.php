<?php
//Notes- forget this abstract class thing and turn this into a class on its own right
// The uninstaller itself should then check to see if its been extended on by the package

class ModuleUnInstaller
{
	
	public $id;
	public $location_id;
	public $package;
	
	public function __construct($module_id)
	{
		$this->id = $module_id;
		
		$module = new ObjectRelationshipMapper('modules');
		$module->mod_id = $this->id;
		$module->select();
		
		if($module->num_rows != 1)
		{
			throw new Exception('Module not found');
		}
		
		$this->id = $module->mod_id;
		$this->location_id = $module->location_id;
		$this->package = $module->mod_package;
		
		
	}
	
	public function uninstall()
	{
		$db = db_connect('default');
		$db->autocommit(false);
		
		try
		{
		
			$this->custom();
			// module config
			$this->remove_config();
			// module_has_regions
			$this->remove_module_has_regions();
			// module
			$this->remove_module();
			// locations
			$this->remove_locations();
			
			$db->commit();
		}catch (Exception $e){
			$db->rollback();
			$db->autocommit(true);
			return false;
		}
		
		return true;
	}

	protected function custom()
	{
		
	}
	
	protected function remove_config()
	{
		$config = new ObjectRelationshipMapper('mod_config');
		$config->mod_id = $this->id;
		$config->delete(0);
	}
	
	protected function remove_module_has_regions()
	{
		$has_regions = new ObjectRelationshipMapper('module_has_regions');
		$has_regions->mod_id = $this->id;
		$has_regions->delete(0);
	}

	protected function remove_module()
	{
		$module = new ObjectRelationshipMapper('modules');
		$module->mod_id = $this->id;
		$module->delete(1);
	}
	
	protected function remove_locations($id = 0)
	{
		if($id == 0)
			$id = $this->location_id;
		
		$location = new ObjectRelationshipMapper('locations');
		$location->location_id = $id;
		
		$children_locations = new ObjectRelationshipMapper('locations');
		$children_locations->location_parent = $id;
		$children_locations->select();
		
		if($children_locations->num_rows > 0)
		{
			do {
				$this->remove_locations($children_locations->location_id); // yeah, i know recursion probably isn't the best way to do this, but this is only going to happen occasionally
			}while($children_locations->next());
		}
		
		$location->delete(1);
		
		return true;
	}
	
}

?>
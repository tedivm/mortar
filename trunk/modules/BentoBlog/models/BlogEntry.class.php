<?php
//AutoLoader::import('BentoCMS');
//if(!class_exists('BentoCMSModelPage', false))
//{
//	$cmsPackage = new PackageInfo('BentoCMS');
//	$models = $cmsPackage->getModels();
//	include($models['Page']['path']);

//	if(!class_exists('BentoCMSModelPage', false))
//		throw new BentoError('Unable to include dependency BentoCMSModelPage.');
//}

class BentoBlogModelBlogEntry extends BentoCMSModelPage
{
	static public $type = 'BlogEntry';
	public $allowedChildrenTypes = array();
}

?>
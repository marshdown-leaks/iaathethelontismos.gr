<?php
/**
 * @version		1.2.8
 * @package		GalleryChamp (plugin)
 * @author    		JoomlaChamp - http://www.joomlachamp.com
 * @copyright		Copyright (c) 2012 - 2013 Redpanda OE (redpanda.gr).
 * @license		GPLv2 or later - Commercial
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// Load the K2 plugin API
JLoader::register('K2Plugin', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_k2'.DS.'lib'.DS.'k2plugin.php');

class plgK2GalleryChamp extends K2Plugin {
	
	var $pluginName = 'galleryChamp';
	var $pluginNameHumanReadable = 'Gallery Plugin for K2';

	function plgK2GalleryChamp(&$subject, $params) {
		parent::__construct($subject, $params);
	}

	function onAfterK2Save(&$article, $isNew)
	{
		
		$db = &JFactory::getDBO();
		if($isNew)
		{
		
			$rootPath 	= JPATH_ROOT . DS . 'media' .DS . 'k2' . DS . 'gallerychamp';
			$livePath 	= "/media/k2/gallerychamp";
			
			if($gallerychamp_tmpid = JRequest::getVar("gallerychamp_tmpid",null))
			{
			
				$folder 	= $rootPath.DS."item".$gallerychamp_tmpid.DS;
				$newfolder 	= $rootPath.DS."item".$article->id.DS;
				$files 		= JFolder::files($folder."main".DS);
				
				if(JFolder::exists($folder) && count($files))
				{
					
					JFolder::move($folder,$newfolder);
					
					$query = "UPDATE #__gallerychamp SET `itemid`=".$article->id." WHERE `itemid`=".$db->Quote($gallerychamp_tmpid);
					$db->setQuery($query);
					$db->query();
					
					$query = "UPDATE #__k2_items SET `gallery`='{gallery}".$article->id."{/gallery}' WHERE `id`=".$article->id;
					$db->setQuery($query);
					$db->query();
					
				} elseif(!count($files)) {
					JFolder::delete($folder);
				}
			
			}
			
		} else {
			
			$query = "UPDATE #__k2_items SET `gallery`='{gallery}".$article->id."{/gallery}' WHERE `id`=".$article->id." AND `gallery`!=NULL";
			$db->setQuery($query);
			$db->query();
			
		}
		
		if (!JFolder::exists(JPATH_SITE.DS.'media'.DS.'k2'.DS.'galleries'.DS.$article->id))
			JFolder::create(JPATH_SITE.DS.'media'.DS.'k2'.DS.'galleries'.DS.$article->id);
		
		
	}

} // End class

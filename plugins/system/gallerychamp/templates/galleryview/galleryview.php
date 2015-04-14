<?php
/**
 * @version		1.1.3
 * @package		GalleryChamp (plugin)
 * @author    		JoomlaChamp - http://www.joomlachamp.com
 * @copyright		Copyright (c) 2012 - 2013 Redpanda OE (redpanda.gr). All rights reserved.
 * @license		GPLv2 or later
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

$tmpImages = array();
if(count($gallery))
{
	
	foreach($gallery as $galImg)
	{
		$tmpImages[] = "<li><img data-frame=\"".$galImg['thumb']."\" src=\"".$galImg['modal']."\" alt=\"".$galImg['label']."\" title=\"".basename($galImg['img'],'.jpg')."\" data-description=\"".($galImg['label']?$galImg['label']:'')."\" /></li>";
	}
	
}

$js = null;
$css = null;

if($definedTmpl[$config->template]==1) {
	$definedTmpl[$config->template]=2;
	$css = '<link rel="stylesheet" href="'.JURI::root(true).'/plugins/system/gallerychamp/templates/galleryview/css/jquery.galleryview-3.0-dev.css" type="text/css" />';
	//$js = '<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js" type="text/javascript"></script>';
	$js.= '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/templates/galleryview/js/jquery.timers-1.2.js" type="text/javascript"></script>';
	//$js.= '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/templates/galleryview/js/jquery.easing.1.3.js" type="text/javascript"></script>';
	$js.= '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/templates/galleryview/js/jquery.galleryview-3.0-dev.js" type="text/javascript"></script>';
	$js.= '<script>
		$K2(document).ready(function(){
			$K2(function(){
				$K2(".galleryview").galleryView({
					panel_width: '.($config->width-10).', 				//INT - width of gallery panel (in pixels)
					enable_overlays: true,
					show_infobar: true,				//BOOLEAN - flag to show or hide infobar
					infobar_opacity: 1				//FLOAT - transparency for info bar
				
				});
			});
		});
	</script>';
}

$return = '<ul class="galleryview gch'.$id.'">'.implode("",$tmpImages).'</ul>';



?>
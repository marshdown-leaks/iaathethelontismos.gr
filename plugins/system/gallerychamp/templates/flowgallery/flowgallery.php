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
		$tmpImages[] = "<li><img src=\"".$galImg['modal']."\" alt=\"".$galImg['label']."\" title=\"".$galImg['label']."\" /></li>";
	}
	
}

$js = null;
$css = null;

if($definedTmpl[$config->template]==1) {
	$definedTmpl[$config->template]=2;
	$css = '<link rel="stylesheet" href="'.JURI::root(true).'/plugins/system/gallerychamp/templates/flowgallery/css/skin.css" type="text/css" />';
	$js = '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/templates/flowgallery/js/jquery.easing.1.3.js" type="text/javascript"></script>';
	$js.= '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/templates/flowgallery/js/jquery.flowgallery.min.js" type="text/javascript"></script>';
	$js.= '<script>
		$K2(document).ready(function(){
			$K2("ul.flowgallery").flowGallery({
				easing: "easeOutCubic",
				imagePadding: 6
			});
		});
	</script>';
}

$return = '<ul class="flowgallery gal gch'.$id.'">'.implode("",$tmpImages).'</ul>';



?>
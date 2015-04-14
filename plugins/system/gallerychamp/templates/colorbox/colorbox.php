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

$js = null;
$css = null;

if($definedTmpl[$config->template]==1) {
	$definedTmpl[$config->template]=2;
	$css = '<link rel="stylesheet" href="'.JURI::root(true).'/plugins/system/gallerychamp/templates/colorbox/css/colorbox.css" type="text/css" />';
	$js = '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/templates/colorbox/js/jquery.colorbox-min.js" type="text/javascript"></script>';
}

$js.= '<script>
		$K2(document).ready(function(){
			$K2(".group'.$id.'").colorbox({rel:"group'.$id.'"});
		});
	</script>';

$tmpImages = array();
if(count($gallery))
{
	
	foreach($gallery as $galImg)
	{
		$tmpImages[] = "<a class=\"group".$galImg['id']."\" href=\"".$galImg['modal']."\" title=\"".$galImg['label']."\"><img src=\"".$galImg['thumb']."\" alt=\"".$galImg['label']."\" /></a>";
	}
	
}


$return = '<div class="gallerychamp-container" class="gch'.$id.'">'.implode("",$tmpImages).'<br class="clr"/></div>';



?>
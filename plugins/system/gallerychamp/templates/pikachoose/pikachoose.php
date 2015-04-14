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
	$css = '<link rel="stylesheet" href="'.JURI::root(true).'/plugins/system/gallerychamp/templates/pikachoose/css/bottom.css" type="text/css" />';
	$js = '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/templates/pikachoose/js/jquery.jcarousel.min.js" type="text/javascript"></script>';
	$js.= '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/templates/pikachoose/js/jquery.pikachoose.js" type="text/javascript"></script>';
}

$js.= '<script>
		$K2(document).ready(function(){
			$K2("#pikame").PikaChoose({carousel:true,carouselOptions:{wrap:"circular"}});
		});
	</script>';

$tmpImages = array();
if(count($gallery))
{
	
	foreach($gallery as $galImg)
	{
		$tmpImages[] = "<li><img src=\"".$galImg['thumb']."\" ref=\"".$galImg['modal']."\" alt=\"".$galImg['label']."\" /><span>".$galImg['label']."</span></li>";
	}
	
}


$return = '<div id="pikachoose" class="pikachoose gch'.$id.'"><ul id="pikame" class="jcarousel-skin-pika">'.implode("",$tmpImages).'</ul></div>';



?>
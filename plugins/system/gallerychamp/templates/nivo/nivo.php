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
	$css = '<link rel="stylesheet" href="'.JURI::root(true).'/plugins/system/gallerychamp/templates/nivo/themes/default/default.css" type="text/css" media="screen" />';
	$css .= '<link rel="stylesheet" href="'.JURI::root(true).'/plugins/system/gallerychamp/templates/nivo/css/nivo-slider.css" type="text/css" media="screen" />';
	$js = '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/templates/nivo/js/jquery.nivo.slider.pack.js" type="text/javascript"></script>';
}

$js.= '<script>
		$K2(document).ready(function(){
			$K2("#nivoblock").nivoSlider();
		});
	</script>';

$tmpImages = array();
if(count($gallery))
{
	$tmpImages[] = "<div class=\"slider-wrapper theme-default\">";
	$tmpImages[] = "<div id=\"nivoblock\" class=\"nivoSlider\">";
	foreach($gallery as $galImg)
	{
		$tmpImages[] = "<img src=\"".$galImg['modal']."\" data-thumb=\"".$galImg['thumb']."\" alt=\"".$galImg['label']."\" title=\"".$galImg['label']."\" />";
	}
	$tmpImages[] = "</div>";
	$tmpImages[] = "</div>";
	
}


$return = implode("",$tmpImages);



?>
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
$conWidth = null;
$conHeight = null;
$tmpImages = array();
if(count($gallery))
{
	
	foreach($gallery as $galImg)
	{
		$tmpImages[] = "<li><img src=\"".$galImg['modal']."\" alt=\"".$galImg['label']."\" /></li>";
	}
	
}

if($config->width) $conWidth = 'width:'.$config->width.'px;';
if($config->height) $conHeight = 'height:'.$config->height.'px;';

$js = null;
$css = null;

if($definedTmpl[$config->template]==1) {
	$definedTmpl[$config->template]=2;
	$css = '<link rel="stylesheet" href="'.JURI::root(true).'/plugins/system/gallerychamp/templates/roundabout/css/roundabout.css" type="text/css" />';
	$js = '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/templates/roundabout/js/jquery.roundabout.min.js" type="text/javascript"></script>';
	$js.= '<script>
		$K2(document).ready(function(){
			$K2("ul.roundabout").roundabout();
		});
	</script>';
}

$css.= '<style type="text/css">ul.roundabout{width:80%;'.$conHeight.'}ul.roundabout li {'.$conWidth.$conHeight.'}</style>';

$return = '<ul class="roundabout gch'.$id.'">'.implode("",$tmpImages).'</ul><br class=\"clr\"/>';



?>
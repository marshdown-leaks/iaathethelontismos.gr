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
$tmpImagesSrc = array();
if(count($gallery))
{
	
	foreach($gallery as $galImg)
	{
            $tmpImagesSrc[] = "'".$galImg['modal']."'";
	    $tmpImages[] = "<a class=\"papermashup-image\" href=\"#\" rel=\"".$galImg['modal']."\" title=\"".$galImg['label']."\"><img src=\"".$galImg['thumb']."\" alt=\"".$galImg['label']."\" /></a>";
	}
	
}

$js = null;
$css = null;

if($definedTmpl[$config->template]==1) {
	$definedTmpl[$config->template]=2;
	$css = '<link rel="stylesheet" href="'.JURI::root(true).'/plugins/system/gallerychamp/templates/papermashup/css/style.css" type="text/css" />';
	$js = '<script>
			$K2(document).ready(function(){
			
				$K2.fn.preload = function() {
					this.each(function(){
					    $K2("<img/>")[0].src = this;
					});
				}
			
				$K2(".papermashup-image").click(function() {
					var $this = $K2(this);
					var image = $this.attr("rel");
					var label = $this.attr("title");
					var paper = $this.parent().prev();
					$K2(paper).hide();
					$K2(paper).fadeIn(\'quick\');
					$K2(paper).find(\' div.papermashup-image\').html("").append(\'<img src="\'+image+\'"/>\');
					if(label.length>0) $K2(paper).find(\' div.papermashup-image\').append(\'<div class="papermashup-label">\'+label+\'</div>\');
					return false;
				});
			});
		    </script>';
}

$js .= '<script>
		$K2(document).ready(function(){
		    $K2(['.implode(",",$tmpImagesSrc).']).preload();
		});
	    </script>';

$firstLabel = ($gallery[0]['label'])?'<div class="papermashup-label">'.$gallery[0]['label'].'</div>':'';
$return = '<div class="papermashup-container">
                <div class="papermashup-image">
                    <img src="'.$gallery[0]['modal'].'"/>
                    '.$firstLabel.'
                </div>
            </div>
            <div class="papermashup-nav">'.implode("",$tmpImages).'</div><div class="clr"></div>';



?>
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

jimport('joomla.plugin.plugin');

class plgSystemGallerychamp extends JPlugin
{

	function plgSystemGallerychamp(&$subject, $config)
	{

		parent::__construct($subject, $config);
	}
	
	function onAfterRoute()
	{
		
		$mainframe = JFactory::getApplication();
		
		$db 	= JFactory::getDBO();
		$user 	= JFactory::getUser();
		
		/* CHECK AND CREATE TABLE (why like this? cause of the way the plugins install) */
		if($this->params->get("set_table",0)==0)
		{
			
			$query = "CREATE TABLE IF NOT EXISTS `#__gallerychamp` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`itemid` varchar(10) NOT NULL,
				`image` varchar(225) NOT NULL,
				`display` int(1) NOT NULL,
				`label` tinytext NOT NULL,
				PRIMARY KEY (`id`),
				KEY `id` (`id`)
			) ENGINE=MyISAM;";
			$db->setQuery($query);
			if($db->query())
			{
				$newParams = '{"set_table":"1"}';
				$query = "UPDATE `#__extensions` SET `params` =".$db->Quote($newParams)." WHERE `element`='gallerychamp' AND `folder`='system'";
				$db->setQuery($query);
				if(!$db->query()) return;
			} else return;
			
		}
		
		
		/* CHECK IF ALL PLUGINS ARE INSTALLED */
		if (!JPluginHelper::isEnabled('k2', 'galleryChamp')) return;
		
		/* LOAD CSS */
		if (JRequest::getCmd('option') == 'com_k2' && JRequest::getCmd('view') == 'item')
		{
			$document = &JFactory::getDocument();
			$document->addStyleSheet(JURI::root(true).'/plugins/system/gallerychamp/css/gallerychamp.css');
		}
		
		/* UPLOADING IMAGES */
		if (JRequest::getCmd('fileuploader') == '1' && $this->checkUser(JRequest::getVar("id",null),JRequest::getInt("catid",0)))
		{
			
			$rootPath = JPATH_ROOT . DS . 'media' .DS . 'k2' . DS . 'gallerychamp';
			$livePath = JURI::root(true)."/media/k2/gallerychamp";
			$id = JRequest::getVar("id",null);
			
			if($id) {
			
				$allowedExtensions = array("jpeg","jpg","gif","png");
				$sizeLimit = 10 * 1024 * 1024;
				
				$this->initQqUploader($allowedExtensions, $sizeLimit);
				$result = $this->handleUpload($rootPath.DS.'item'.$id.DS.'main'.DS);
				$fullname = mb_strtolower($result["file"]);
				
				// lets resize!
				$config = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$id.DS.'config.json'));
				$config->changedMain = true;
				$config->changedThumb = true;
				$this->resizeImage($id,$fullname,$rootPath,$config);
				
				// and now lets insert them into db
				$query = "INSERT INTO #__gallerychamp VALUES ( NULL, ".$db->Quote($id).", ".$db->Quote($fullname).", 1, '' )";
				$db->setQuery($query);
				$db->query();
				
				// and update the ordering.json
				$ordering = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$id.DS.'ordering.json'));
				$ordering->ordering[] = $fullname;
				JFile::write(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$id.DS.'ordering.json',json_encode($ordering));
				
				// and update k2 items table
				$query = "UPDATE #__k2_items SET `gallery`=".$db->Quote("{gallery}".$id."{/gallery}")." WHERE `id`=".intval($id);
				$db->setQuery($query);
				$db->query();
				
				$result = array_merge($result, array('fileId'=>$db->insertid(), 'fileName'=>$fullname));
				echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
				
				$mainframe->close();
			
			}
		}
		
		// BROWSE IMAGE
		if (JRequest::getCmd('filebrowse') == '1' && $this->checkUser(JRequest::getVar("id",null),JRequest::getInt("catid",0)))
		{
			
			$rootPath 	= JPATH_ROOT . DS . 'media' .DS . 'k2' . DS . 'gallerychamp';
			$livePath 	= JURI::root(true)."/media/k2/gallerychamp";
			$id 		= JRequest::getVar("id",null);
			$imgpath 	= JRequest::getVar("imgpath",null);
			
			$fullPath 	= JPATH_ROOT . str_replace("/",DS,$imgpath);
			$path_parts 	= pathinfo($fullPath);
			
			if($id) {
			
				$allowedExtensions = array("jpeg","jpg","gif","png");
				if(!in_array(strtolower($path_parts['extension']),$allowedExtensions))
				{
					$result = array("success"=>false);
					echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
					$mainframe->close();
				}
				
				// lets resize!
				$config = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$id.DS.'config.json'));
				$config->changedMain = true;
				$config->changedThumb = true;
				$this->resizeImage($id,$imgpath,$rootPath,$config,true);
				
				// and now lets insert them into db
				$query = "INSERT INTO #__gallerychamp VALUES ( NULL, ".$db->Quote($id).", ".$db->Quote($path_parts['basename']).", 1, '' )";
				$db->setQuery($query);
				$db->query();
				
				// and update the ordering.json
				$ordering = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$id.DS.'ordering.json'));
				$ordering->ordering[] = $path_parts['basename'];
				JFile::write(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$id.DS.'ordering.json',json_encode($ordering));
				
				// and update k2 items table
				$query = "UPDATE #__k2_items SET `gallery`=".$db->Quote("{gallery}".$id."{/gallery}")." WHERE `id`=".intval($id);
				$db->setQuery($query);
				$db->query();
				
				$result = array();
				$result = array_merge($result, array('fileId'=>$db->insertid(), 'fileName'=>$path_parts['basename']));
				echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
				
				$mainframe->close();
			
			}
		}
		
		if(JRequest::getCmd("gallerychamp")=="getimages" && ($id=JRequest::getVar("id")) && $this->checkUser(JRequest::getVar("id",null),JRequest::getInt("catid",0)))
		{
			
			echo $this->getImages($id);
			$mainframe->close();
			
		}
		
		if(JRequest::getCmd("gallerychamp")=="getimageinfo" && ($id=JRequest::getInt("id",0)) && $this->checkUser(JRequest::getVar("iid",null),JRequest::getInt("catid",0)))
		{
			
			echo $this->getImageInfo($id);
			$mainframe->close();
			
		}
		
		if(JRequest::getCmd("gallerychamp")=="setorder" && ($id=JRequest::getVar("id")) && $this->checkUser(JRequest::getVar("id",null),JRequest::getInt("catid",0)))
		{
			
			$imgs = JRequest::getVar("imgs");
			echo $this->setOrder($imgs,$id);
			$mainframe->close();
			
		}
		
		if(JRequest::getCmd("gallerychamp")=="saveconf" && $this->checkUser(JRequest::getVar("id",null),JRequest::getInt("catid",0)))
		{
			
			echo $this->saveConf();
			$mainframe->close();
			
		}
		
		if(JRequest::getCmd("gallerychamp")=="delimage" && $this->checkUser(JRequest::getVar("iid",null),JRequest::getInt("catid",0)))
		{
			
			echo $this->delImage();
			$mainframe->close();
			
		}
		
		if(JRequest::getCmd("gallerychamp")=="pubimage" && $this->checkUser(JRequest::getVar("iid",null),JRequest::getInt("catid",0)))
		{
			
			echo $this->displayImage();
			$mainframe->close();
			
		}
		
		if(JRequest::getCmd("gallerychamp")=="savelabel" && $this->checkUser(JRequest::getVar("iid",null),JRequest::getInt("catid",0)))
		{
			
			echo $this->saveLabel();
			$mainframe->close();
			
		}
		
		if(JRequest::getCmd("gallerychamp")=="copysig" && $this->checkUser(JRequest::getVar("id",null),JRequest::getInt("catid",0)))
		{
			echo $this->showCopyScreen();
			$mainframe->close();
		}
		
		if(JRequest::getCmd("gallerychamp")=="callmigr" && $this->checkUser(JRequest::getVar("id",null),JRequest::getInt("catid",0)))
		{ 
			echo $this->callMigr();
			$mainframe->close();
		}
		
		
	}
	
	function onAfterRender()
	{

		$mainframe = JFactory::getApplication();
		$basepath = ($mainframe->isSite()) ? JPATH_SITE : JPATH_ADMINISTRATOR;
		
		$subPath = (!$mainframe->isSite())?"administrator/":"";
		
		JPlugin::loadLanguage('plg_system_gallerychamp', JPATH_ADMINISTRATOR);

		if (JRequest::getCmd('option') == 'com_k2' && JRequest::getCmd('view') == 'item' && ( !$mainframe->isSite() || (JRequest::getCmd('task') == 'edit' || JRequest::getCmd('task') == 'add')))
		{
			
			if($this->params->get("set_table",0)==0) return;
			if (!JPluginHelper::isEnabled('k2', 'galleryChamp')) return;
			
			// get the ID and check if exist image folder
			$id = JRequest::getInt("cid",0);
			$tmpId = $this->checkGalleryIntegrity($id);
			if($tmpId===false) return;
			if(is_string($tmpId)) $id=$tmpId;
			
			// lets read the config file
			$config = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$id.DS.'config.json'));
			
			// get existed images and create the container
			$js = '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/js/fileuploader/fileuploader.js" type="text/javascript"></script>';
			$js.= '<script src="'.JURI::root(true).'/plugins/system/gallerychamp/js/dragsort-0.5.1/jquery.dragsort-0.5.1.min.js" type="text/javascript"></script>';
			
			$js.= '<script type="text/javascript">
			//<![CDATA[
			$K2(document).ready(function(){
				$K2("#gallerychamp-wrapper").after("<input type=\"hidden\" name=\"gallerychamp_tmpid\" value=\"'.((intval($id)>0)?"":$id).'\" />");
				
				var imgList = function(id) {
					$K2.getJSON("'.JURI::root(true).'/'.$subPath.'index.php?gallerychamp=getimages&id="+id+"&catid="+$K2("#catid").val(), function(list) {
						$K2("#gallery-container").html("");
						if(list!=null)
						{
							$K2.each(list,function(i,img){
								if(img.display==1) { var displaytext = "'.JText::_("GCH_IS_PUBLISHED_CLICK_TO_UNPUBLISH").'"; var displaychange = 0; var displayred=""; }
								else { var displaytext = "'.JText::_("GCH_IS_UNPUBLISHED_CLICK_TO_PUBLISH").'"; var displaychange = 1; var displayred="display-red"; }
								var li = "<li class=\"gallery-displayed "+displayred+"\" title=\""+img.image+"\" data-id=\""+img.id+"\" data-image=\""+img.image+"\">";
								li += "<img src=\"'.JURI::root(true).'/media/k2/gallerychamp/item"+img.itemid+"/thumbs/"+img.image+"?rand="+(Math.floor(Math.random() * (9000 - 1000 + 1)) + 1000)+"\" />";
								li += "<div class=\"panel\">";
								li += "<a title=\""+displaytext+"\" class=\"gc_displayimage\" data-display=\""+displaychange+"\" href=\"#\">";
								li += displaytext;
								li += "</a>";
								li += "<a title=\"'.JText::_("GCH_CLICK_TO_DELETE").'\" class=\"gc_delimage\" href=\"'.JURI::root(true).'/'.$subPath.'index.php?gallerychamp=delimage&id="+img.id+"&iid='.$id.'&catid="+$K2("#catid").val()+"\">";
								li += "delete";
								li += "</a>";
								li += "</div>";
								li += "</li>";
								$K2("#gallery-container").append(li);
							});
						}
						$K2("#gallery-container").dragsort("destroy");
						$K2("#gallery-container").dragsort({ dragSelector: "li", dragSelectorExclude: ".panel", dragEnd: function() { execReordering(); }, dragBetween: false, placeHolderTemplate: "<li class=\"placeholder\"></li>" });
					});
				}
				
				var gcid = "'.$id.'";
				imgList(gcid);
				
				var uploader = new qq.FileUploader({
					element: document.getElementById("file-uploader"),
					action: "'.JURI::root(true).'/'.$subPath.'index.php?fileuploader=1&id='.$id.'&catid="+$K2("#catid").val(),
					allowedExtensions: ["jpg", "jpeg", "png", "gif"],
					onComplete: function(id, fileName, responseJSON){
						var gcid = "'.$id.'";
						imgList(gcid);
					},
					debug: true,
					template: "<div class=\"qq-uploader\">"+ 
							"<div class=\"qq-upload-drop-area\"><span>'.JText::_("GCH_UPLOAD_AREA").'</span></div>"+
							"<div class=\"qq-upload-button\">'.JText::_("GCH_UPLOAD_BUTTON").'</div>"+
							"<ul class=\"qq-upload-list\"></ul>"+ 
						     "</div>",
					fileTemplate: "<li>"+
								"<span class=\"qq-upload-file\"></span>"+
								"<span class=\"qq-upload-spinner\"></span>"+
								"<span class=\"qq-upload-size\"></span>"+
								"<a class=\"qq-upload-cancel\" href=\"#\">'.JText::_("GCH_UPLOAD_CANCEL").'</a>"+
								"<span class=\"qq-upload-failed-text\">'.JText::_("GCH_UPLOAD_FAILED").'</span>"+
							"</li>"
				});
				
				$K2("#gallery-container li").live("mouseenter", function(){ $K2(this).find(".panel").show(); }).live("mouseleave", function(){ $K2(this).find(".panel").hide(); });
				$K2("#gallery-container li").live("click", function(){ setImgInfo($K2(this).data("id")); });
				$K2("#gallery-container li .panel a.gc_delimage").live("click", function(ev){ 
					ev.preventDefault();
					if (confirm("'.JText::_("GCH_ARE_YOU_SURE").'")) {
						var $this = $K2(this);
						$K2.post($this.attr("href"), function(data) {
							if(data=="ok") $this.parent().parent().remove();
							$K2("#info-container").html("");
							$K2("#gallery-container").dragsort("destroy");
							$K2("#gallery-container").dragsort({ dragSelector: "li", dragSelectorExclude: ".panel", dragEnd: function() { execReordering(); }, dragBetween: false, placeHolderTemplate: "<li class=\"placeholder\"></li>" });
						});
					}
					$K2("#img-info").addClass("hide");
				});
				$K2("#gallery-container li .panel a.gc_displayimage").live("click", function(ev){ 
					ev.preventDefault();
					var $this = $K2(this);
					var display = $this.data("display");
					var id = $this.parent().parent().data("id");
					$K2.post("'.JURI::root(true).'/'.$subPath.'index.php?gallerychamp=pubimage&displaychange="+display+"&id="+id+"&iid='.$id.'&catid="+$K2("#catid").val(), function(data) {
						if(data=="display-red")
						{
							$this.data("display",1).attr("title","'.JText::_("GCH_IS_UNPUBLISHED_CLICK_TO_PUBLISH").'");
							$this.parent().parent().addClass(data);
						} else {
							$this.data("display",0).attr("title","'.JText::_("GCH_IS_PUBLISHED_CLICK_TO_UNPUBLISH").'");
							$this.parent().parent().removeClass("display-red");
						}
					});
				});
				$K2("#gc_conf_save").live("click", function(ev){
					ev.preventDefault();
					var width = $K2("#gc_width").val();
					var height = $K2("#gc_height").val();
					var thumb_width = $K2("#gc_thumb_width").val();
					var thumb_height = $K2("#gc_thumb_height").val();
					var template = $K2("#gc_template").val();
					$K2("#gc_conf_result").html("<img src=\"'.JURI::root(true).'/plugins/system/gallerychamp/css/loading.gif\" />");
					$K2.post("'.JURI::root(true).'/'.$subPath.'index.php?gallerychamp=saveconf&w="+width+"&h="+height+"&tw="+thumb_width+"&th="+thumb_height+"&template="+template+"&id='.$id.'&catid="+$K2("#catid").val(), function(data) {
						$K2("#gc_conf_result").html("saved...");
						var gc_conf_result_timeout = setTimeout(function(){
							$K2("#gc_conf_result").html(""); 
						}, 2000);
						var gcid = "'.$id.'";
						imgList(gcid);
					});
				});
				$K2("#label-save").live("click", function(ev){
					ev.preventDefault();
					var label = $K2(this).parent().next().val();
					$K2(this).text("saving");
					$K2.post("'.JURI::root(true).'/'.$subPath.'index.php",{gallerychamp:"savelabel", id:$K2(this).data("id"), text:label, catid:$K2("#catid").val(), iid:gcid}, function(respond) {
						
						if(respond=="ok") {
							$K2("#label-save").text("saved");
						} else {
							$K2("#label-save").text("please retry");
						}
						var gc_conf_result_timeout = setTimeout(function(){
							$K2("#label-save").text("save"); 
						}, 1000);
					});
				});
				
				$K2("#k2ImageBrowseServer4GalleryChamp").live("click", function(event){
					event.preventDefault();
					SqueezeBox.initialize();
					SqueezeBox.open("'.JURI::root(true).'/'.$subPath.'index.php?option=com_k2&view=media&type=image&tmpl=component&fieldID=gallerychamp_browse", {
						handler: "iframe", 
						size: { x: 800, y: 434 },
						onClose: function() {
							var gcimput = $K2("#gallerychamp_browse");
							if(gcimput.val()!="") {
								var link2post = "'.JURI::root(true).'/'.$subPath.'index.php?filebrowse=1&id='.$id.'&catid="+$K2("#catid").val();
								$K2.post(link2post, {imgpath:gcimput.val()},
									function(data) {
										var gcid = "'.$id.'";
										imgList(gcid);
										gcimput.val("");
									}
								);
							}
						}
					});
				});
				
				var copyIcon = function() {
					var toolbar = $K2("div#toolbar > ul");
					var copyIcon = $K2("<li id=\"toolbar-move\" class=\"button\"><a class=\"toolbar\" id=\"copysigpro\" href=\"#\"><span class=\"icon-32-copy gallerychamp-copy\"></span>Copy SIG Pro</a></li><li class=\"divider\"></li>");
					toolbar.prepend(copyIcon);
				}
				copyIcon();
				
				$K2("#copysigpro").live("click", function(event){
					event.preventDefault();
					SqueezeBox.initialize();
					SqueezeBox.open("'.JURI::root(true).'/'.$subPath.'index.php?gallerychamp=copysig&id='.$id.'&catid="+$K2("#catid").val(), {
						handler: "iframe", 
						size: { x: 800, y: 600 }
					});
				});
				
				$K2("#img-info-close").live("click", function(event){
					event.preventDefault();
					$K2("#img-info").addClass("hide");
				});
				
			});
			function execReordering()
			{
				
				var data = [];
				$K2("#gallery-container li").each( function( index ) {
					data.push($K2(this).data("image"));  
				});
				$K2.post("'.JURI::root(true).'/'.$subPath.'index.php?gallerychamp=setorder&id='.$id.'", { "imgs[]": data, catid:$K2("#catid").val() });
				
			}
			function setImgInfo(id)
			{
				$K2.getJSON("'.JURI::root(true).'/'.$subPath.'index.php?gallerychamp=getimageinfo&id="+id+"&iid='.$id.'&catid="+$K2("#catid").val(), function(info) {
					var infoHTML = "";
					infoHTML += "<div class=\"img-name\">"+info.image+"</div>";
					infoHTML += "<div class=\"img-head\">Label <a id=\"label-save\" data-id=\""+info.id+"\" href=\"#\">'.JText::_("GCH_SAVE").'</a></div>";
					infoHTML += "<textarea class=\"img-label\">"+info.label+"</textarea>";
					infoHTML += "<div class=\"img-head\">Path</div>";
					infoHTML += "<div class=\"img-paths\"><span>"+info.livepath+"</span></div>";
					infoHTML += "<div class=\"img-head\">Time</div>";
					infoHTML += "<div class=\"img-paths\"><span>"+info.time+"</span></div>";
					$K2("#img-info").removeClass("hide");
					$K2("#info-container").html("").append(infoHTML);
				});
				
			}
			//]]>
 			</script>';
			
			$container = '
			<section id="gallerychamp-wrapper" class="clearfix">
				<section class="gallerychamp-gallery">
					<h3>'.JText::_("GCH_GALLERY").'</h3>
					<ul id="gallery-container"></ul>
				</section>
				<section id="img-section">
					<section id="img-info" class="hide">
						<h3 class="clearfix">'.JText::_("GCH_IMAGE_INFO").'<a href="#" id="img-info-close" title="close box">close</a></h3>
						<div id="info-container"></div>
					</section>
					<section>
						<div>
							<h3>'.JText::_("GCH_BROWSE").'</h3>
							<div id="file-browse">
								<a href="#" id="k2ImageBrowseServer4GalleryChamp">'.JText::_('GCH_BROWSE_A_FILE').'</a>
								<input type="text" name="gallerychamp_browse" id="gallerychamp_browse" value="" />
							</div>
						</div>
						<div>
							<h3>'.JText::_("GCH_UPLOAD").'</h3>
							<div id="file-uploader">       
								<noscript>          
								    <p>Please enable JavaScript to use file uploader.</p>
								    <!-- or put a simple form for upload here -->
								</noscript>         
							</div>
						</div>
					</section>
				</section>
				<section id="img-config">
					<h3>'.JText::_("GCH_CONFIGURATION").'</h3>
					<p>'.JText::_("GCH_CONFIG_TEXT1").'</p>
					<p>'.JText::_("GCH_CONFIG_TEXT2").'</p>
					<p>'.JText::_("GCH_CONFIG_TEXT3").'</p>
					<label><div>'.JText::_("GCH_CONFIG_WIDTH").':</div><input type="text" name="gc_width" id="gc_width" value="'.$config->width.'" /></label>
					<label><div>'.JText::_("GCH_CONFIG_HEIGHT").':</div><input type="text" name="gc_height" id="gc_height" value="'.($config->height?$config->height:'').'" /></label>
					<label><div>'.JText::_("GCH_CONFIG_THUMB_WIDTH").':</div><input type="text" name="gc_thumb_width" id="gc_thumb_width" value="'.$config->thumb_width.'" /></label>
					<label><div>'.JText::_("GCH_CONFIG_THUMB_HEIGHT").':</div><input type="text" name="gc_thumb_height" id="gc_thumb_height" value="'.($config->thumb_height?$config->thumb_height:'').'" /></label>
					<label><div>'.JText::_("GCH_CONFIG_TEMPLATE").':</div>'.$this->getTempaltes($config->template).'</label>
					<label><div id="gc_conf_result"></div><input type="submit" id="gc_conf_save" name="gc_conf_save" value="'.JText::_("GCH_SAVE").'" /></label>
				</section>
			</section>
			<div class="gallerychamp_copyrights">
				<a href="http://www.joomlachamp.com/gallerychamp.html" target="_blank">GalleryChamp</a> v1.2.8 | Copyright © 20011-'.date("Y").' <a href="http://www.joomlachamp.com/" target="_blank">JoomlaChamp.com</a> (unit of <a href="http://www.redpanda.gr/" target="_blank">Redpanda.gr</a>)
			</div>
			';

			$buffer = JResponse::getBody();
			$buffer = str_replace ('<div class="simpleTabsContent" id="k2Tab3">', '<div class="simpleTabsContent" id="k2Tab3">'.$container, $buffer);
			$buffer = str_replace ('</body>', $js.'</body>', $buffer);
			JResponse::setBody($buffer);

		}
		
		// FRONTPAGE
		/* LETS LOAD MODAL TEMPLATE*/
		$option = JRequest::getCmd('option'); $view = JRequest::getCmd('view'); $task = JRequest::getCmd('task');
		if ($mainframe->isSite()
		    && (
				( $option == 'com_k2' && $view == 'item' )
				|| ( $option == 'com_k2' && $view == 'itemlist' && ($task == '' || $task == 'category' || $task == 'tag') )
			)
		    )
		{
			
			$curId = JRequest::getInt("id",0);
			
			if($view=="item") $ids = (array) $curId;
			else {
				if($task=="tag")
					$ids = $this->getItemsFromTag(JRequest::getString("tag"));
				else
					$ids = $this->getItemsFromCategory($curId);
			}
			
			$buffer = JResponse::getBody();
			$definedTmpl = array();
			if(count($ids))
			{
				foreach($ids as $id)
				{
					$gallery = $this->getGallery($id);
					if(JFile::exists(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$id.DS.'config.json'))
						$config = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$id.DS.'config.json'));
					else continue;
					
					// load template
					if(!isset($definedTmpl[$config->template])) $definedTmpl[$config->template] = 1; 
					if(JFolder::exists(JPATH_ROOT.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'plg_gallerychamp'.DS.$config->template.DS))
					{
						
						if(JFile::exists(JPATH_ROOT.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'plg_gallerychamp'.DS.$config->template.DS.$config->template.'.php'))
							include(JPATH_ROOT.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.'plg_gallerychamp'.DS.$config->template.DS.$config->template.'.php');
						
					} else {
					
						if(JFile::exists(JPATH_ROOT.DS.'plugins'.DS.'system'.DS.'gallerychamp'.DS.'templates'.DS.$config->template.DS.$config->template.'.php'))
							include(JPATH_ROOT.DS.'plugins'.DS.'system'.DS.'gallerychamp'.DS.'templates'.DS.$config->template.DS.$config->template.'.php');
						
					}
					
					$existGallery = strpos($buffer, '{gallery}'.$id.'{/gallery}');
					if($existGallery !== false) {
						$buffer = str_replace ('</head>', $css.'</head>', $buffer);
						$buffer = str_replace ('{gallery}'.$id.'{/gallery}', '<!-- GalleryChamp, template: '.$config->template.' -->'.$return, $buffer);
						$buffer = str_replace ('</body>', $js.'</body>', $buffer);
					}
				}
			}
			
			JResponse::setBody($buffer);
			
		}


	}
	
	function checkUser($id=null,$catid=0)
	{
		JRequest::setVar("option","com_k2");
		JRequest::setVar("view","item");
		
		$mainframe = JFactory::getApplication();
		$user = JFactory::getUser();
		$aid = (int) $user->get('aid');
		
		if(!$id) return false;
		
		if($mainframe->isAdmin() && $user->id) {
			
			JLoader::register('K2HelperPermissions', JPATH_SITE.DS.'components'.DS.'com_k2'.DS.'helpers'.DS.'permissions.j16.php');
			K2HelperPermissions::checkPermissions();
			
			return true;
			
		}
		if($mainframe->isSite()) {
			
			JTable::addIncludePath(JPATH_SITE.DS.'administrator'.DS.'components'.DS.'com_k2'.DS.'tables');
			JLoader::register('K2HelperPermissions', JPATH_SITE.DS.'components'.DS.'com_k2'.DS.'helpers'.DS.'permissions.php');
			JLoader::register('K2HelperUtilities', JPATH_SITE.DS.'components'.DS.'com_k2'.DS.'helpers'.DS.'utilities.php');
			K2HelperPermissions::setPermissions();
			//K2HelperPermissions::checkPermissions();
			
			if(intval($id)==0) if (K2HelperPermissions::canAddItem($catid)) return true; else return false;
			
			$item = &JTable::getInstance('K2Item', 'Table');
			$item->load($id);
			
			if (K2HelperPermissions::canAddItem($catid) && K2HelperPermissions::canEditItem($item->created_by, $item->catid))
			{
				return true;
			}
			
			return false;
			
		}
		
		return false;
		
	}
	
	function resizeImage($id,$fullname,$rootPath,$config,$fromBrowse = false)
	{
		require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_k2'.DS.'lib'.DS.'class.upload.php');
		$basePath = $rootPath.DS."item".$id.DS."main".DS.$fullname;
		
		if($fromBrowse) {
			$fullname = str_replace("/",DS,$fullname);
			$basePath = JPATH_ROOT . $fullname;
		}
		
		$handle = new Upload($basePath);
		if ($handle->uploaded)
		{
			
			if($fromBrowse)
			{
				$handle->image_resize = false;
				$handle->jpeg_quality = 80;
				$handle->file_auto_rename = false;
				$handle->file_overwrite = true;
				$handle->Process($rootPath.DS."item".$id.DS."main");	
			}
			
			if(isset($config->changedMain) && $config->changedMain==true) {
				$handle->image_resize = true;
				if($config->height>0) {
					$handle->image_ratio_y = false;
					$handle->image_y = $config->height;
				} else {
					$handle->image_ratio_y = true;
				}
				$handle->image_x = $config->width;
				$handle->jpeg_quality = 80;
				$handle->file_auto_rename = false;
				$handle->file_overwrite = true;
				$handle->Process($rootPath.DS."item".$id.DS."modal");
			}
			
			if(isset($config->changedThumb) && $config->changedThumb==true) {
				$handle->image_resize          	= true;
				$handle->image_ratio_crop      	= true;
				$handle->image_y               	= $config->thumb_height;
				$handle->image_x               	= $config->thumb_width;
				$handle->file_auto_rename 	= false;
				$handle->file_overwrite 	= true;
				$handle->Process($rootPath.DS."item".$id.DS."thumbs");
			}
			
		}
	}
	
	function getItemsFromCategory($id=0)
	{
		if(!$id) return null;
		require_once(JPATH_ROOT.DS.'components'.DS.'com_k2'.DS.'models'.DS.'itemlist.php');
		
		$mainframe = JFactory::getApplication();
		$user = JFactory::getUser();
		$aid = (int) $user->get('aid');
		$id = (int) $id;
		$db = JFactory::getDBO();

		$jnow = JFactory::getDate();
		$now = $jnow->toSQL();
		$nullDate = $db->getNullDate();

		$categories = K2ModelItemlist::getCategoryTree($id);
		$query = "SELECT id FROM #__k2_items WHERE catid IN (".implode(',', $categories).") AND published=1 AND trash=0";

		$query .= " AND access IN(".implode(',', $user->getAuthorisedViewLevels()).") ";
		if($mainframe->getLanguageFilter()) {
			$query.= " AND language IN(".$db->Quote(JFactory::getLanguage()->getTag()).", ".$db->Quote('*').")";
		}
		
		$query .= " AND ( publish_up = ".$db->Quote($nullDate)." OR publish_up <= ".$db->Quote($now)." )";
		$query .= " AND ( publish_down = ".$db->Quote($nullDate)." OR publish_down >= ".$db->Quote($now)." )";
		$db->setQuery($query);
		return $db->loadColumn();
		
	}
	
	function getItemsFromTag($tag=null)
	{
		if(!$tag) return null;
		require_once(JPATH_ROOT.DS.'components'.DS.'com_k2'.DS.'models'.DS.'itemlist.php');
		
		$mainframe = JFactory::getApplication();
		$user = JFactory::getUser();
		$aid = (int) $user->get('aid');
		$db = JFactory::getDBO();

		$jnow = JFactory::getDate();
		$now = $jnow->toSQL();
		$nullDate = $db->getNullDate();
		
		if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_joomfish'.DS.'joomfish.php')) {

			$sql = " SELECT reference_id FROM #__jf_content as jfc LEFT JOIN #__languages as jfl ON jfc.language_id = jfl.lang_id";
			$sql .= " WHERE jfc.value = ".$db->Quote($tag);
			$sql .= " AND jfc.reference_table = 'k2_tags'";
			$sql .= " AND jfc.reference_field = 'name' AND jfc.published=1";

			$db->setQuery($sql, 0, 1);
			$result = $db->loadResult();

		}
		
		if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_falang'.DS.'falang.php')) {

			$sql = " SELECT reference_id FROM #__falang_content as fc LEFT JOIN #__languages as fl ON fc.language_id = fl.lang_id";
			$sql .= " WHERE fc.value = ".$db->Quote($tag);
			$sql .= " AND fc.reference_table = 'k2_tags'";
			$sql .= " AND fc.reference_field = 'name' AND fc.published=1";

			$db->setQuery($sql, 0, 1);
			$result = $db->loadResult();

		}
		
		if (!isset($result) || $result < 1) {
			$sql = "SELECT id FROM #__k2_tags WHERE name=".$db->Quote($tag);
			$db->setQuery($sql, 0, 1);
			$result = $db->loadResult();
		}
		
		$tagQuery = ($result)?" AND id IN (SELECT itemID FROM #__k2_tags_xref WHERE tagID=".(int)$result.")":"";
		
		
		$query = "SELECT id FROM #__k2_items WHERE published=1 AND trash=0";

		$query .= " AND access IN(".implode(',', $user->getAuthorisedViewLevels()).") ";
		if($mainframe->getLanguageFilter()) {
			$query.= " AND language IN(".$db->Quote(JFactory::getLanguage()->getTag()).", ".$db->Quote('*').")";
		}
		
		$query .= " AND ( publish_up = ".$db->Quote($nullDate)." OR publish_up <= ".$db->Quote($now)." )";
		$query .= " AND ( publish_down = ".$db->Quote($nullDate)." OR publish_down >= ".$db->Quote($now)." )";
		
		$query .= $tagQuery;
		
		$db->setQuery($query);
		return $db->loadColumn();
		
		
	}
	
	function getTempaltes($current=null)
	{
		
		$select = '<select name="gc_template" class="inputbox" id="gc_template">';
		
		$templates = JFolder::folders(JPATH_ROOT.DS.'plugins'.DS.'system'.DS.'gallerychamp'.DS.'templates');
		foreach($templates as $template) {
			$selected = ($template==$current)?"selected":"";
			$select.= '<option value="'.$template.'" '.$selected.'>'.$template.'</option>';
		}
		
		$select.= '</select>';
		
		return $select;
		
	}
	
	function checkGalleryIntegrity($id=0)
	{
		$tmpId = null;
		if(!$id) {
			
			$arr = str_split('abcdefghijklmnopqrstuvwxyz'); // get all the characters into an array
			shuffle($arr); // randomize the array
			$arr = array_slice($arr, 0, 5); // get the first six (random) characters out
			$id = $tmpId = implode('', $arr); // smush them back into a string
			
		}
		
		$rootPath = JPATH_ROOT . DS . 'media' .DS . 'k2' . DS . 'gallerychamp';
		
		// check if we have already put the emty jw_sig file in the right place
		if(!JFolder::exists(JPATH_ROOT . DS . 'plugins' . DS . 'content' . DS . 'jw_sig' ))
		{
			JFolder::create(JPATH_ROOT . DS . 'plugins' . DS . 'content' . DS . 'jw_sig');
			
			if(!JFile::exists(JPATH_ROOT . DS . 'plugins' . DS . 'content' . DS . 'jw_sig' . DS . 'jw_sig.php' ))
			{
				$e = "<?php defined('_JEXEC') or die ; ?>";
				JFile::write(JPATH_ROOT . DS . 'plugins' . DS . 'content' . DS . 'jw_sig' . DS . 'jw_sig.php', $e);
			}
		}
		
		if (!JFolder::exists(JPATH_SITE.DS.'media'.DS.'k2'.DS.'galleries'.DS.$id))
			JFolder::create(JPATH_SITE.DS.'media'.DS.'k2'.DS.'galleries'.DS.$id);
		
		if (!JFolder::exists($rootPath)) {
			JFolder::create($rootPath);
		}
		
		if (!JFolder::exists($rootPath . DS . 'item' . $id)) {
			JFolder::create($rootPath . DS . 'item' . $id);
		}
		if (!JFolder::exists($rootPath . DS . 'item' . $id . DS . 'main')) {
			JFolder::create($rootPath . DS . 'item' . $id . DS . 'main');
		}
		if (!JFolder::exists($rootPath . DS . 'item' . $id . DS . 'modal')) {
			JFolder::create($rootPath . DS . 'item' . $id . DS . 'modal');
		}
		if (!JFolder::exists($rootPath . DS . 'item' . $id . DS . 'thumbs')) {
			JFolder::create($rootPath . DS . 'item' . $id . DS . 'thumbs');
		}
		if(!JFile::exists($rootPath.DS.'item'.$id.DS.'ordering.json'))
		{
			$default["ordering"] = array(); 
			JFile::write($rootPath.DS.'item'.$id.DS.'ordering.json', json_encode($default));
		}
		if(!JFile::exists($rootPath.DS.'item'.$id.DS.'config.json'))
		{
			$defaultConf = array("width"=>"800","height"=>"600","thumb_width"=>"80","thumb_height"=>"60","template"=>"colorbox"); 
			JFile::write($rootPath.DS.'item'.$id.DS.'config.json', json_encode($defaultConf));
		}
		
		if(
			!JFolder::exists($rootPath) ||
			!JFolder::exists($rootPath . DS . 'item' . $id) ||
			!JFolder::exists($rootPath . DS . 'item' . $id . DS . 'main') ||
			!JFolder::exists($rootPath . DS . 'item' . $id . DS . 'modal') ||
			!JFolder::exists($rootPath . DS . 'item' . $id . DS . 'thumbs') ||
			!JFile::exists($rootPath.DS.'item'.$id.DS.'ordering.json') ||
			!JFile::exists($rootPath.DS.'item'.$id.DS.'config.json')
		) return false;
		
		if($tmpId) return $tmpId; else return true;
	}
	
	function getImages($id=null)
	{
		$db = JFactory::getDBO();
		
		$rootPath 	= JPATH_ROOT . DS . 'media' .DS . 'k2' . DS . 'gallerychamp';
		$livePath 	= JURI::root(true)."/media/k2/gallerychamp";
		$json 		= array();
		$ordering 	= json_decode(JFile::read($rootPath.DS.'item'.$id.DS.'ordering.json'),true);
		
		$query = "SELECT * FROM #__gallerychamp WHERE itemid=".$db->Quote($id);
                $db->setQuery($query);
                $images = $db->loadObjectList();
		
		if(count($images))
		{
			
			foreach($images as $image) {
				$helpImageArray[$image->image] = $image;
			}
			
			$json[] = "{";
			
			if(count($ordering['ordering']))
			{
				foreach($ordering['ordering'] as $img) {
					if($imgObj = $helpImageArray[$img]) {
						$json_files[] = "\"".$img."\":{\"id\":\"".$imgObj->id."\",\"image\":\"".$img."\",\"itemid\":\"".$imgObj->itemid."\",\"label\":".htmlspecialchars(json_encode($imgObj->label), ENT_NOQUOTES).",\"display\":\"".$imgObj->display."\"}";
						unset($helpImageArray[$img]);
					}
				}
				foreach($helpImageArray as $image) {
					$json_files[] = "\"".$image->image."\":{\"id\":\"".$image->id."\",\"image\":\"".$image->image."\",\"itemid\":\"".$image->itemid."\",\"label\":".htmlspecialchars(json_encode($image->label), ENT_NOQUOTES).",\"display\":\"".$image->display."\"}";
				}
			} else {
				foreach($images as $image) {
					$json_files[] = "\"".$image->image."\":{\"id\":\"".$image->id."\",\"image\":\"".$image->image."\",\"itemid\":\"".$image->itemid."\",\"label\":".htmlspecialchars(json_encode($image->label), ENT_NOQUOTES).",\"display\":\"".$image->display."\"}";
				}
			}
			$json[] = implode(",",$json_files);
			
			$json[] = "}";
			
		}
		
		return implode("\n",$json);
	}
	
	function getImageInfo($id=null)
	{
		$db = JFactory::getDBO();
		
		$rootPath 	= JPATH_ROOT . DS . 'media' .DS . 'k2' . DS . 'gallerychamp';
		$livePath 	= "/media/k2/gallerychamp";
		
		$query = "SELECT * FROM #__gallerychamp WHERE id=".$id." LIMIT 0,1";
                $db->setQuery($query);
                $info = $db->loadObject();
		
		$info->livepath = $livePath."/item".$info->itemid."/(main-modal-thumbs)/".$info->image;
		
		$info->time =  date("F d Y H:i:s", filemtime($rootPath.DS."item".$info->itemid.DS."main".DS.$info->image));
		
		return json_encode($info);
		
	}
	
	function delImage()
	{
		$db = JFactory::getDBO();
		$id = JRequest::getInt("id",null);
		$itemid = JRequest::getInt("iid",null);
		if(is_null($id) || is_null($itemid)) return;
		
		$rootPath 	= JPATH_ROOT . DS . 'media' .DS . 'k2' . DS . 'gallerychamp';
		$livePath 	= "/media/k2/gallerychamp";
		
		$query = "SELECT * FROM #__gallerychamp WHERE id=".$id." LIMIT 0,1";
                $db->setQuery($query);
                $image = $db->loadObject();
		
		if($image->id)
		{
			$query = "DELETE FROM #__gallerychamp WHERE id=".$image->id;
			$db->setQuery($query);
			$db->query();
			
			JFile::delete($rootPath.DS."item".$image->itemid.DS."main".DS.$image->image);
			JFile::delete($rootPath.DS."item".$image->itemid.DS."modal".DS.$image->image);
			JFile::delete($rootPath.DS."item".$image->itemid.DS."thumbs".DS.$image->image);
			
			// and update the ordering.json
			$ordering = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$image->itemid.DS.'ordering.json'));
			$ordering->ordering = array_diff($ordering->ordering, array($image->image));
			$ordering->ordering = array_values($ordering->ordering);
			JFile::write(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$image->itemid.DS.'ordering.json',json_encode($ordering));
			
			$query = "SELECT count(*) as c FROM #__gallerychamp WHERE itemid=".$itemid." AND display=1";
			$db->setQuery($query);
			$count = $db->loadResult();
			
			if(!$count) { // no published image -> delete gallery
				$query = "UPDATE #__k2_items SET `gallery`=NULL WHERE `id`=".$itemid;
				$db->setQuery($query);
				$db->query();
			}
			
			return "ok";
		
		}
		
		return;
		
	}
	
	function displayImage()
	{
		
		$db = JFactory::getDBO();
		$id = JRequest::getInt("id",null);
		$itemid = JRequest::getInt("iid",null);
		if(is_null($id) || is_null($itemid)) return;
		$display = JRequest::getInt("displaychange",1);
		
		$query = "UPDATE #__gallerychamp SET display=".$display." WHERE id=".$id;
		$db->setQuery($query);
		if($db->query())
		{
			
			$query = "SELECT count(*) as c FROM #__gallerychamp WHERE itemid=".$itemid." AND display=1";
			$db->setQuery($query);
			$count = $db->loadResult();
			if(!$count) { // no published image -> delete gallery
				$query = "UPDATE #__k2_items SET `gallery`=NULL WHERE `id`=".$itemid;
				$db->setQuery($query);
				$db->query();
			} else {
				$query = "UPDATE #__k2_items SET `gallery`=".$db->Quote("{gallery}".$itemid."{/gallery}")." WHERE `id`=".$itemid;
				$db->setQuery($query);
				$db->query();
			}
			
			return ($display==1)?"display-green":"display-red";
			
		} else return;
		
	}
	
	function saveLabel()
	{
		$db = JFactory::getDBO();
		$id = JRequest::getInt("id",null);
		if(is_null($id)) return;
		$text = JRequest::getVar("text",null);
		
		$query = "UPDATE #__gallerychamp SET label=".$db->Quote($text)." WHERE id=".$id;
		$db->setQuery($query);
		if($db->query())
			return "ok";
		else
			return null;
	}
	
	function setOrder($imgs=null,$id=null)
	{
		$rootPath 	= JPATH_ROOT . DS . 'media' .DS . 'k2' . DS . 'gallerychamp';
		$livePath 	= JURI::root(true)."/media/k2/gallerychamp";
		
		if(count($imgs))
		{
			$jsonArray["ordering"] = $imgs;
			
		} else return;
		
		if (!JFile::write($rootPath.DS.'item'.$id.DS.'ordering.json', json_encode($jsonArray))) { return "error"; }
		
		return;
	}
	
	function saveConf()
	{
		$rootPath 	= JPATH_ROOT . DS . 'media' .DS . 'k2' . DS . 'gallerychamp';
		
		$id = JRequest::getVar("id",null); if(is_null($id)) return "null id";
		$width = JRequest::getInt("w","800");
		$height = JRequest::getInt("h","");
		$thumb_width = JRequest::getInt("tw","80");
		$thumb_height = JRequest::getInt("th","60");
		$template = JRequest::getVar("template",null);
		
		$jsonArray = array("width"=>"$width","height"=>"$height","thumb_width"=>"$thumb_width","thumb_height"=>"$thumb_height","template"=>"$template");
		
		// get old config
		$oldConfig = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$id.DS.'config.json'));
		
		// write new config
		if (!JFile::write($rootPath.DS.'item'.$id.DS.'config.json', json_encode($jsonArray))) { return "error"; }
		$config = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$id.DS.'config.json'));
		
		// lets resize the files if config is changed
		if($oldConfig->width!=$width || $oldConfig->height!=$height) $config->changedMain = true;
		if($oldConfig->thumb_width!=$thumb_width || $oldConfig->thumb_height!=$thumb_height) $config->changedThumb = true;
		
		$files = JFolder::files($rootPath.DS.'item'.$id.DS.'modal'.DS, '\.jpg$');
		foreach($files as $file)
		{
			$this->resizeImage($id,$file,$rootPath,$config);
		}
		
		return "ok";
	}
	
	function showCopyScreen()
	{
		
		// lets create the table, if not exist
		$mainframe = JFactory::getApplication();
		
		$subPath = (!$mainframe->isSite())?"administrator/":"";
		
		$db 	= JFactory::getDBO();
		$user 	= JFactory::getUser();
		$output = array();
		
		$id 	= JRequest::getInt("id",0);
		$catid	= JRequest::getInt("catid",0);
		
		$query = "CREATE TABLE IF NOT EXISTS `#__gallerychamp_copylog` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`image` varchar(225) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `id` (`id`)
		) ENGINE=MyISAM;";
		$db->setQuery($query);
		$db->query();
		
		
		$query = "SELECT id, title FROM #__k2_items WHERE `gallery`!=''";
		$db->setQuery($query);
		$list = $db->loadObjectList();
		if(count($list)<1) return;
		
		$query = "SELECT image FROM #__gallerychamp_copylog";
		$db->setQuery($query);
		$copylog = $db->loadColumn();
		
		// lets get the images from the galleries
		$images = array();
		foreach($list as $gal)
		{
			$imgs = JFolder::files(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'galleries'.DS.$gal->id.DS, '\.jpg$');
			if(count($imgs)) {
				foreach($imgs as $img) {
					$imgObj = new JObject();
					$imgObj->id = $gal->id;
					$imgObj->title = $gal->title;
					$imgObj->name= $img;
					$imgObj->status = (in_array($gal->id."_".$this->sanitizeFileName($img),$copylog))?true:false;
					$images[] = $imgObj;
					unset($imgObj);
				}
			}
			
		}
		
		$output[] = "<!DOCTYPE html>";
		$output[] = "<html>";
		$output[] = "\t<head>";
		$output[] = "\t\t<meta charset=\"UTF-8\">";
		$output[] = "\t\t<title>Copy from SIG Pro</title>";
		$output[] = "\t\t<link rel=\"stylesheet\" href=\"".JURI::root(true)."/plugins/system/gallerychamp/css/gallerychamp.css\" type=\"text/css\">";
		$output[] = "\t</head>";
		$output[] = "\t<body id=\"copyscreen\">";
		
		$output[] = "\t\t<div class=\"logo\"></div>";
		
		$output[] = "\t\t<div class=\"info\"><span>Below there is a list of images from galleries found on k2 items. By pressing \"start\" all images will be imported and resized according to your gallerychamp specifications. The procedure is done, one image at a time so you can stop it whenever you want, just by pressing the \"stop\" button.</span><a href='#' id='copylink'>Start Copy</a><br class=\"clr\" /></div>";
		
		$output[] = "\t\t<div class=\"config\">";
		$output[] = "\t\t\t<label><div>Modal width</div><input type=\"text\" id=\"mwidth\" value=\"800\" /></label>";
		$output[] = "\t\t\t<label><div>Modal height</div><input type=\"text\" id=\"mheight\" value=\"\" /></label>";
		$output[] = "\t\t\t<label><div>Thumb width</div><input type=\"text\" id=\"twidth\" value=\"80\" /></label>";
		$output[] = "\t\t\t<label><div>Thumb height</div><input type=\"text\" id=\"theight\" value=\"60\" /></label>";
		$output[] = "\t\t\t<label><div>Template</div>".$this->getTempaltes()."</label>";
		$output[] = "\t\t<br class=\"clr\" /></div>";
		
		$output[] = "\t\t<table>";
		$output[] = "\t\t\t<tr>";
		$output[] = "\t\t\t\t<th>ID</th><th>Article</th><th>Image</th><th>Status</th>";
		$output[] = "\t\t\t</tr>";
		foreach($images as $image) {
			$output[] = "\t\t\t<tr>";
			$output[] = "\t\t\t\t<td class=\"center iid\">".$image->id."</td><td>".$image->title."</td><td class=\"img\">".$image->name."</td><td class=\"center status".(($image->status)?"true":"false")."\"></td>";
			$output[] = "\t\t\t</tr>";
		}
		$output[] = "\t\t</table>";
		$output[] = "\t\t<script src=\"//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js\" type=\"text/javascript\"></script>";
		$output[] = "\t\t<script type=\"text/javascript\">";
		$output[] = "\t\t\t//<![CDATA[";
		$output[] = "\t\t\t$(document).ready(function(){";
		$output[] = "\t\t\t\t$(\"#copylink\").live(\"click\", function(ev){";
		
		$output[] = "\t\t\t\t\tev.preventDefault();";
		$output[] = "\t\t\t\t\tif($(this).data(\"status\")==\"isrunning\") { $(\"tr.cur\").next().addClass(\"stop\"); $(this).text(\"Start Copy\"); return; } else { $(this).data(\"status\",\"isrunning\"); $(this).text(\"Stop Copy\"); } ";
		
		$output[] = "\t\t\t\t\tif($(\"tr.stop\").length>0) { var line = $(\"tr.stop\"); line.removeClass(\"stop\"); } else var line = $(\"table tr:first\").next(); ";
		$output[] = "\t\t\t\t\tvar result = checkLine(line);";
		
		$output[] = "\t\t\t\t});";
		$output[] = "\t\t\t});";
		
		$output[] = "\t\t\tfunction checkLine(line) {";
		$output[] = "\t\t\t\tline.prev().removeClass(\"cur\");";
		$output[] = "\t\t\t\tline.addClass(\"cur\");";
		$output[] = "\t\t\t\tif(line.hasClass(\"stop\")) { $(\"#copylink\").data(\"status\",\"isstopped\"); return; }";
		$output[] = "\t\t\t\tif(line.find(\"td.statustrue\").length) checkLine(line.next());";
		$output[] = "\t\t\t\telse if(line.find(\"td.statusfalse\").length) { line.find(\"td:last\").addClass(\"checking\"); callMigr(line); }";
		$output[] = "\t\t\t}";
		
		$output[] = "\t\t\tfunction callMigr(line) {";
		$output[] = "\t\t\t\tvar nextLine = line.next();";
		$output[] = "\t\t\t\tvar id = line.find(\"td.iid\").text();";
		$output[] = "\t\t\t\tvar img = line.find(\"td.img\").text();";
		$output[] = "\t\t\t\tvar link = \"".JURI::root(true)."/".$subPath."index.php?gallerychamp=callmigr&id=".$id."&catid=".$catid.";\"";
		$output[] = "\t\t\t\tvar jqxhr = $.post(link, { \"k2i\": id, \"img\": img, \"mw\": \$(\"#mwidth\").val(), \"mh\": \$(\"#mheight\").val(), \"tw\": \$(\"#twidth\").val(), \"th\": \$(\"#theight\").val(), \"template\": \$(\"#gc_template\").val(), }, function(data) {";
		$output[] = "\t\t\t\tline.find(\"td:last\").removeClass(\"checking\");";
		$output[] = "\t\t\t\tif(data==1) line.find(\"td:last\").removeClass(\"noimagefound notok error\").addClass(\"statustrue\");";
		$output[] = "\t\t\t\telse if(data==2) line.find(\"td:last\").addClass(\"noimagefound\");";
		$output[] = "\t\t\t\telse line.find(\"td:last\").addClass(\"notok\");";
		$output[] = "\t\t\t\tif(nextLine.length) checkLine(nextLine);";
		$output[] = "\t\t\t\t})";
		$output[] = "\t\t\t\t.error(function() { line.find(\"td:last\").addClass(\"error\"); if(nextLine.length) checkLine(nextLine); })";
		
		$output[] = "\t\t\t\t";
		$output[] = "\t\t\t\t";
		$output[] = "\t\t\t\t";
		$output[] = "\t\t\t}";
		
		$output[] = "\t\t\t//]]>";
		$output[] = "\t\t</script>";
		$output[] = "\t</body>";
		$output[] = "</html>";
		
		return implode("\n",$output);
	}
	
	function sanitizeFileName($f) {
		// a combination of various methods
		// we don't want to convert html entities, or do any url encoding
		// we want to retain the "essence" of the original file name, if possible
		// char replace table found at:
		// http://www.php.net/manual/en/function.strtr.php#98669
		$replace_chars = array(
		    'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
		    'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
		    'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
		    'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
		    'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
		    'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
		    'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
		);
		$f = strtr($f, $replace_chars);
		// convert & to "and", @ to "at", and # to "number"
		$f = preg_replace(array('/[\&]/', '/[\@]/', '/[\#]/'), array('-and-', '-at-', '-number-'), $f);
		$f = preg_replace('/[^(\x20-\x7F)]*/','', $f); // removes any special chars we missed
		$f = str_replace(' ', '-', $f); // convert space to hyphen
		$f = str_replace('\'', '', $f); // removes apostrophes
		$f = preg_replace('/[^\w\-\.]+/', '', $f); // remove non-word chars (leaving hyphens and periods)
		$f = preg_replace('/[\-]+/', '-', $f); // converts groups of hyphens into one
		return strtolower($f);
	}

	
	function callMigr()
	{
		header('Content-type: text/html; charset=utf-8');
		
		// lets create the table, if not exist
		$mainframe = &JFactory::getApplication();
		if ($mainframe->isSite()) return 0;
		jimport('joomla.filesystem.file');
		
		$rootPath 	= JPATH_ROOT . DS . 'media' .DS . 'k2' . DS . 'gallerychamp';
		$subPath 	= (!$mainframe->isSite())?"administrator/":"";
		
		$db 	= JFactory::getDBO();
		$user 	= JFactory::getUser();
		
		$id 	= JRequest::getInt("id",0);
		$catid	= JRequest::getInt("catid",0);
		$k2i	= JRequest::getInt("k2i",0);
		$img	= JRequest::getVar("img","");
		$img	= JFile::makeSafe($img);
		
		$width = JRequest::getInt("mw",800);
		$height = JRequest::getInt("mh","");
		$thumb_width = JRequest::getInt("tw",80);
		$thumb_height = JRequest::getInt("th",60);
		$template = JRequest::getVar("template","colorbox");
		
		// first check if the img exists
		if(!JFile::exists(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'galleries'.DS.$k2i.DS.$img)) return 2;
		
		$allowedExtensions = array("jpeg","jpg","gif","png");
		if(!in_array(strtolower(JFile::getExt($img)),$allowedExtensions)) return 0;
		
		// set the folders and the main files
		$tmpId = $this->checkGalleryIntegrity($k2i);
		
		// then copy img
		$sanitizedImg = $this->sanitizeFileName($img);
		if(!JFile::copy(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'galleries'.DS.$k2i.DS.$img, JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS."item".$k2i.DS."main".DS.$sanitizedImg)) return 0;
		
		// save conf and resize imgs
		$jsonArray = array("width"=>"$width","height"=>"$height","thumb_width"=>"$thumb_width","thumb_height"=>"$thumb_height","template"=>"$template");
		$oldConfig = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$k2i.DS.'config.json'));
		
		// write new config
		if (!JFile::write($rootPath.DS.'item'.$k2i.DS.'config.json', json_encode($jsonArray))) { return 0; }
		$config = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$k2i.DS.'config.json'));
		
		$config->changedMain = true; $config->changedThumb = true;
		
		$this->resizeImage($k2i,$sanitizedImg,$rootPath,$config);
		
		// and now lets insert them into db
		$query = "INSERT INTO #__gallerychamp VALUES ( NULL, ".$db->Quote($k2i).", ".$db->Quote($sanitizedImg).", 1, '' )";
		$db->setQuery($query);
		$db->query();
		
		// and update the ordering.json
		$ordering = json_decode(JFile::read(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$k2i.DS.'ordering.json'));
		$ordering->ordering[] = $sanitizedImg;
		JFile::write(JPATH_ROOT.DS.'media'.DS.'k2'.DS.'gallerychamp'.DS.'item'.$k2i.DS.'ordering.json',json_encode($ordering));
		
		// and update k2 items table
		$query = "UPDATE #__k2_items SET `gallery`=".$db->Quote("{gallery}".$k2i."{/gallery}")." WHERE `id`=".intval($k2i);
		$db->setQuery($query);
		$db->query();
		
		// and update gallerychamp log
		$query = "INSERT INTO #__gallerychamp_copylog (`id`, `image`) VALUES (NULL, ".$db->Quote($k2i."_".$sanitizedImg)." )";
		$db->setQuery($query);
		$db->query();
		
		return 1;
		
	}
	
	function initQqUploader(array $allowedExtensions = array(), $sizeLimit = 10485760){        
		$allowedExtensions = array_map("strtolower", $allowedExtensions);
		
		$this->allowedExtensions = $allowedExtensions;        
		$this->sizeLimit = $sizeLimit;
		
		// we have to review this check, issues with varius hosts
		//$this->checkServerSettings();       
	
		if (isset($_GET['qqfile'])) {
			$this->file = new qqUploadedFileXhr();
		} elseif (isset($_FILES['qqfile'])) {
			$this->file = new qqUploadedFileForm();
		} else {
			$this->file = false; 
		}
	}
	
	private function checkServerSettings(){        
		$postSize = $this->toBytes(ini_get('post_max_size'));
		$uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
		
		if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
			$size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
			die("{'error':'increase post_max_size and upload_max_filesize to $size'}");    
		}        
	}
	
	private function toBytes($str){
		$val = trim($str);
		$last = strtolower($str[strlen($str)-1]);
		switch($last) {
			case 'g': $val *= 1024;
			case 'm': $val *= 1024;
			case 'k': $val *= 1024;        
		}
		return $val;
	}
	
	/**
	 * Returns array('success'=>true) or array('error'=>'error message')
	 */
	function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
		if (!is_writable($uploadDirectory)){
			return array('error' => "Server error. Upload directory isn't writable.");
		}
		
		if (!$this->file){
			return array('error' => 'No files were uploaded.');
		}
		
		$size = $this->file->getSize();
		
		if ($size == 0) {
			return array('error' => 'File is empty');
		}
		
		if ($size > $this->sizeLimit) {
			return array('error' => 'File is too large');
		}
		
		$pathinfo = pathinfo($this->file->getName());
		$filename = $pathinfo['filename'];
		$filename = mb_strtolower($filename);
		//$filename = md5(uniqid());
		$ext = $pathinfo['extension'];
		$ext = mb_strtolower($ext);
	
		if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
			$these = implode(', ', $this->allowedExtensions);
			return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
		}
		
		$name_existed = false;
		if(!$replaceOldFile){
			/// don't overwrite previous files that were uploaded
			while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
				$filename .= rand(10, 99);
				$name_existed = true;
			}
		}
		
		if ($this->file->save($uploadDirectory . $filename . '.' . $ext)){
			return array('success'=>true,'name_existed'=>$name_existed,'file'=>$filename.'.'.$ext);
		} else {
			return array('error'=> 'Could not save uploaded file.' .
				'The upload was cancelled, or server error encountered');
		}
	    
	}
	
	function getGallery($id=0)
	{
		
		if(!$id) return array();
		
		$rootPath = JPATH_ROOT . DS . 'media' .DS . 'k2' . DS . 'gallerychamp';
		$livePath = JURI::root(true)."/media/k2/gallerychamp";
		
		if(JFile::exists($rootPath.DS.'item'.$id.DS.'ordering.json'))
			$ordering = json_decode(JFile::read($rootPath.DS.'item'.$id.DS.'ordering.json'),true);
		else return array();
		
		$db = &JFactory::getDBO();
		$query = "SELECT * FROM #__gallerychamp WHERE `itemid`=".$id." AND display=1";
		$db->setQuery($query);
		$list = $db->loadObjectList(); 
		$return = array();
		
		if(count($list))
		{
			
			foreach($list as $image) {
				$helpImageArray[$image->image] = $image;
			}
			
			if(count($ordering['ordering']))
			{
				foreach($ordering['ordering'] as $img) {
					if($imgObj = $helpImageArray[$img]) {
						$main = $livePath."/item".$imgObj->itemid."/main/".$imgObj->image;
						$modal = $livePath."/item".$imgObj->itemid."/modal/".$imgObj->image;
						$thumb = $livePath."/item".$imgObj->itemid."/thumbs/".$imgObj->image;
						$return[] = array(
									"id"=>$imgObj->itemid,
									"main"=>$main,
									"modal"=>$modal,
									"thumb"=>$thumb,
									"img"=>$imgObj->image,
									"label"=>htmlspecialchars($imgObj->label)
								);
						unset($helpImageArray[$img]);
					}
				}
				foreach($helpImageArray as $image) {
					$main = $livePath."/item".$image->itemid."/main/".$image->image;
					$modal = $livePath."/item".$image->itemid."/modal/".$image->image;
					$thumb = $livePath."/item".$image->itemid."/thumbs/".$image->image;
					$return[] = array(
									"id"=>$image->itemid,
									"main"=>$main,
									"modal"=>$modal,
									"thumb"=>$thumb,
									"img"=>$image->image,
									"label"=>htmlspecialchars($image->label)
								);
				}
			} else {
				foreach($list as $image) {
					$main = $livePath."/item".$image->itemid."/main/".$image->image;
					$modal = $livePath."/item".$image->itemid."/modal/".$image->image;
					$thumb = $livePath."/item".$image->itemid."/thumbs/".$image->image;
					$return[] = array(
									"id"=>$image->itemid,
									"main"=>$main,
									"modal"=>$modal,
									"thumb"=>$thumb,
									"img"=>$image->image,
									"label"=>htmlspecialchars($image->label)
								);
				}
			}
			
		}
		
		return $return;
		
	}


}

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
	/**
	 * Save the file to the specified path
	 * @return boolean TRUE on success
	 */
	function save($path) {    
		$input = fopen("php://input", "r");
		$temp = tmpfile();
		$realSize = stream_copy_to_stream($input, $temp);
		fclose($input);
		
		if ($realSize != $this->getSize()){            
		    return false;
		}
		
		$target = fopen($path, "w");        
		fseek($temp, 0, SEEK_SET);
		stream_copy_to_stream($temp, $target);
		fclose($target);
		
		return true;
	}
	function getName() {
		return $_GET['qqfile'];
	}
	function getSize() {
		if (isset($_SERVER["CONTENT_LENGTH"])){
		    return (int)$_SERVER["CONTENT_LENGTH"];            
		} else {
		    throw new Exception('Getting content length is not supported.');
		}      
	}   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
	/**
	 * Save the file to the specified path
	 * @return boolean TRUE on success
	 */
	function save($path) {
		if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
		    return false;
		}
		return true;
	}
	function getName() {
		return $_FILES['qqfile']['name'];
	}
	function getSize() {
		return $_FILES['qqfile']['size'];
	}
}
<?php
/**
 * @version 1.0.1
 * @package Additional Categories for K2 (plugin)
 * @author Thodoris Bgenopoulos <teobgeno@netpin.gr>
 * @link http://www.netpin.gr
 * @copyright Copyright (c) 2012 netpin.gr
 * @license GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */


// no direct access
defined('_JEXEC') or die ('Restricted access');


// Load the K2 Plugin API
JLoader::register('K2Plugin', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_k2'.DS.'lib'.DS.'k2plugin.php');

// Initiate class to hold plugin events
class plgK2k2additonalcategories extends K2Plugin {


	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param object $params  The object that holds the plugin parameters
	 * @since 1.5
	 */
	function plgK2k2additonalcategories( & $subject, $params) {
		parent::__construct($subject, $params);
	}
	
	
	/**
	 * Modify the k2 query(items) before being executed in order to count or return additional items. 
	 * 
	 *
	 * @param 	string		The query.
	 * @return	string
	 */
	function onK2BeforeSetQuery(&$query)
	{
	  
	  $mainframe = &JFactory::getApplication();
	  $query_parts=explode('WHERE',$query);
	  
	  //admin backend area
	  if($mainframe->isAdmin())
	  {
	      //filter by category
		  if (preg_match("/i.catid IN \([\d,]+\)/",$query_parts[1], $matches, PREG_OFFSET_CAPTURE) > 0)
		  {
		    $catids_in=$matches[0][0];
			$add_catids_in=str_replace('i.catid IN','ca.`catid` IN',$catids_in);
			$all_catids='('. $catids_in .' OR '. $add_catids_in .')';
			$query_parts[1]=str_replace($catids_in,$all_catids,$query_parts[1]);
			
			$query_parts[0].=" LEFT JOIN  #__k2_additional_categories AS ca ON ca.`itemID`  = i.`id`";
			$query_parts[0]=str_replace('i.*','DISTINCT i.`id`,i.*',$query_parts[0]);
			
			$query=implode(' WHERE ',$query_parts);
			
		  }
		  //filter by category count results
		   if (preg_match("/ catid IN \([\d,]+\)/",$query_parts[1], $matches, PREG_OFFSET_CAPTURE) > 0)
		  {
		    
			 $catids_in=trim($matches[0][0]);
			 $add_catids_in=str_replace('catid IN','ca.`catid` IN',$catids_in);
			 $catids_in_add_pref=str_replace('catid IN','i.`catid` IN',$catids_in);
			 $all_catids='('. $catids_in_add_pref .' OR '. $add_catids_in .')';
			
			 $query_parts[1]=str_replace($catids_in,$all_catids,$query_parts[1]);
			 $query_parts[1]=str_replace('trash','i.`trash`',$query_parts[1]);
			 
			 $query_parts[0].=" LEFT JOIN  #__k2_additional_categories AS ca ON ca.`itemID`  = i.`id`";
			
			 $query_parts[0]=str_replace('COUNT(*)','COUNT(DISTINCT i.`id`)',$query_parts[0]);
			 $query=implode(' WHERE ',$query_parts);
			 
		  }
		   
	  }
	 
	 
	  //frontend area
	  if($mainframe->isSite())
	  {
		  
		   $task = JRequest::getCmd('task');
	       $view= JRequest::getCmd('view');
	       $layout= JRequest::getCmd('layout');
		   $category_id= JRequest::getInt('id');
		   
		  //user blog layout
		  if($task=='user' && $view=='itemlist')
		  {
			
			  if (preg_match("/i.catid IN\([\d,]+\)/",$query_parts[1], $matches, PREG_OFFSET_CAPTURE) > 0)
			  {
				$catids_in=$matches[0][0];
				$add_catids_in=str_replace('i.catid IN','ca.`catid` IN',$catids_in);
				$all_catids='('. $catids_in .' OR '. $add_catids_in .')';
				$query_parts[1]=str_replace($catids_in,$all_catids,$query_parts[1]);
				
				$query_parts[0].=" LEFT JOIN  #__k2_additional_categories AS ca ON ca.`itemID`  = i.`id`";
				$query_parts[0]=str_replace('i.*','DISTINCT i.`id`,i.*',$query_parts[0]);
				
				//case count
				$query_parts[0]=str_replace('COUNT(*)','COUNT(DISTINCT i.`id`)',$query_parts[0]);

				$query=implode(' WHERE ',$query_parts);
				 
			  }
			  
			  //k2 2.6.3 +
			  if (preg_match("/c.id IN\([\d,]+\)/",$query_parts[1], $matches, PREG_OFFSET_CAPTURE) > 0)
			  {
				
				$catids_in=$matches[0][0];
				$add_catids_in=str_replace('c.id IN','ca.`catid` IN',$catids_in);
				$all_catids='('. $catids_in .' OR '. $add_catids_in .')';
				$query_parts[1]=str_replace($catids_in,$all_catids,$query_parts[1]);
				
				$query_parts[0].=" LEFT JOIN  #__k2_additional_categories AS ca ON ca.`itemID`  = i.`id`";
				$query_parts[0]=str_replace('i.*','DISTINCT i.`id`,i.*',$query_parts[0]);
				
				//case count
				$query_parts[0]=str_replace('COUNT(*)','COUNT(DISTINCT i.`id`)',$query_parts[0]);

				$query=implode(' WHERE ',$query_parts);

				
			  }		  
		  
		  }
		  //one category id
		  if($task=='category' && $view=='itemlist' && $category_id > 0)
		  {
			  if (preg_match("/c.id IN \([\d,]+\)/",$query_parts[1], $matches, PREG_OFFSET_CAPTURE) > 0)
			  {
				$catids_in=$matches[0][0];
				$add_catids_in=str_replace('c.id IN','ca.`catid` IN',$catids_in);
				$all_catids='('. $catids_in .' OR '. $add_catids_in .')';
				$query_parts[1]=str_replace($catids_in,$all_catids,$query_parts[1]);
				
				$query_parts[0].=" LEFT JOIN  #__k2_additional_categories AS ca ON ca.`itemID`  = i.`id`";
				$query_parts[0]=str_replace('i.*','DISTINCT i.`id`,i.*',$query_parts[0]);
				
				//case count
				$query_parts[0]=str_replace('COUNT(*)','COUNT(DISTINCT i.`id`)',$query_parts[0]);

				$query=implode(' WHERE ',$query_parts);
				
				
			  }
			  
			  //catalog mode
			  if (preg_match("/c.id\=[\d]+/",$query_parts[1], $matches, PREG_OFFSET_CAPTURE) > 0)
			  {
				
				$catids_in=$matches[0][0];
				$add_catids_in=str_replace('c.id','ca.`catid`',$catids_in);
				$all_catids='('. $catids_in .' OR '. $add_catids_in .')';
				$query_parts[1]=str_replace($catids_in,$all_catids,$query_parts[1]);
				
				$query_parts[0].=" LEFT JOIN  #__k2_additional_categories AS ca ON ca.`itemID`  = i.`id`";
				$query_parts[0]=str_replace('i.*','DISTINCT i.`id`,i.*',$query_parts[0]);
				
				//case count
				$query_parts[0]=str_replace('COUNT(*)','COUNT(DISTINCT i.`id`)',$query_parts[0]);

				$query=implode(' WHERE ',$query_parts);
				 
			  }
			  
		  }
		  
		  //multiple category ids
		  if($layout=='category' && $view=='itemlist' && $category_id==0)
		  {
			 if (preg_match("/i.catid IN \([\d,]+\)/",$query_parts[1], $matches, PREG_OFFSET_CAPTURE) > 0)
			  {
				$catids_in=$matches[0][0];
				$add_catids_in=str_replace('i.catid IN','ca.`catid` IN',$catids_in);
				$all_catids='('. $catids_in .' OR '. $add_catids_in .')';
				$query_parts[1]=str_replace($catids_in,$all_catids,$query_parts[1]);
				
				$query_parts[0].=" LEFT JOIN  #__k2_additional_categories AS ca ON ca.`itemID`  = i.`id`";
				$query_parts[0]=str_replace('i.*','DISTINCT i.`id`,i.*',$query_parts[0]);
				
				//case count
				$query_parts[0]=str_replace('COUNT(*)','COUNT(DISTINCT i.`id`)',$query_parts[0]);

				$query=implode(' WHERE ',$query_parts);
				 
			  }
			  
			  //k2 2.6.3 +
			  if (preg_match("/c.id IN \([\d,]+\)/",$query_parts[1], $matches, PREG_OFFSET_CAPTURE) > 0)
			  {
				
				$catids_in=$matches[0][0];
				$add_catids_in=str_replace('c.id IN','ca.`catid` IN',$catids_in);
				$all_catids='('. $catids_in .' OR '. $add_catids_in .')';
				$query_parts[1]=str_replace($catids_in,$all_catids,$query_parts[1]);
				
				$query_parts[0].=" LEFT JOIN  #__k2_additional_categories AS ca ON ca.`itemID`  = i.`id`";
				$query_parts[0]=str_replace('i.*','DISTINCT i.`id`,i.*',$query_parts[0]);
				
				//case count
				$query_parts[0]=str_replace('COUNT(*)','COUNT(DISTINCT i.`id`)',$query_parts[0]);

				$query=implode(' WHERE ',$query_parts);
				
				
				 
			  }
		  
		  }
	  
	  }
	  
   }
   
   
	/**
	 * Brings the GUI of plugin in the content tab of the k2 item. 
	 * 
	 * @param 	object		The k2 item.
	 * @param 	string		The view.
	 * @param 	string		The tab for assign the plugin.
	 * @return	object
	 */
	function onRenderAdminForm(&$item, $type, $tab='')
	{
	   if($type=='item' && $tab=='content')
	   {
	   
		 $plugin = new JObject;
		 $language = JFactory::getLanguage();
         $language->load('com_k2additionalcategories');
		 $plugin->set('name', JText::_( 'COM_K2ADDITIONALCATEGORIES_LBL' ));
		 $db = &JFactory::getDBO();
		 $query = 'SELECT m.* FROM #__k2_categories AS m WHERE m.`trash` = 0 ORDER BY `parent`, `ordering`';
		 $db->setQuery( $query );
		 $mitems = $db->loadObjectList();
		 $children = array();
		 if ($mitems)
		 {
			foreach ( $mitems as $v )
			{
				if(K2_JVERSION!='15')
				{
					$v->title = $v->name;
					$v->parent_id = $v->parent;
				}
				$pt = $v->parent;
				$list = @$children[$pt] ? $children[$pt] : array();
				array_push( $list, $v );
				$children[$pt] = $list;
			}
		 }
		 $list = JHTML::_('menu.treerecurse', 0, '', array(), $children, 9999, 0, 0 );
		 $mitems = array();

		 foreach ( $list as $i ) 
		 {
			$i->treename = JString::str_ireplace('&#160;', '- ', $i->treename);
			$mitems[] = JHTML::_('select.option',  $i->id, '   '.$i->treename );
		 }
		 $sel_value=array();
		 $query = "SELECT `catid` FROM #__k2_additional_categories WHERE `itemID` = {intval($item->id)}";
		 $db->setQuery( $query );
		 if (version_compare(JVERSION, '1.6.0', '<'))
		 {
			$sel_value = $db->loadResultArray();
		 }
		 else
		 {
			$sel_value = $db->loadColumn();
		 }
		 
		
		 $fields=JHTML::_('select.genericlist',  $mitems,'itm_add_catids[]', 'class="inputbox" style="width:100%;" multiple="multiple" size="10"', 'value', 'text', $sel_value );
		
		 $plugin->set('fields', $fields);
		 return $plugin;
		
	   }
	   
	}
	
	
	/**
	 * Saves data in db after apply or save. 
	 * 
	 * @param 	object		The updated or new k2 item.
	 * @param 	boolean		Flag setting if the item is new or not.
	 * @return	void
	 */
	function onAfterK2Save(&$row, $isNew) 
	{
	
	   $add_catids = JRequest::getVar('itm_add_catids','',array());
	   $db = &JFactory::getDBO();
	   $query ="DELETE FROM #__k2_additional_categories WHERE `itemID`= {intval($row->id)}";
	   $db->setQuery($query);
	   $db->query();
	   if(is_array($add_catids) && count($add_catids) > 0)
	   {
		   foreach($add_catids as $k=>$v)
		   {
			 
			  $v=intval($v);
			  if($v > 0)
			  {
				  $query = "INSERT INTO #__k2_additional_categories (`id`, `catid`, `itemID`) VALUES(NULL, '$v', {intval($row->id)})";
				  $db->setQuery($query);
				  $db->query();
			  }
		   
		   }
	   
	   }
	   
	}
	
	
	/**
	 * Custom trigger (not k2 core). Count the active or trash item the category has.Category list on backend. 
	 *
	 * @param 	string		The k2 query.
	 * @return	string
	 *
	 * @deprecated
	 */
	function onK2BeforeCountCategoryItemsQuery(&$query)
	{
	
	   $query_parts=explode('WHERE',$query);
	   if (preg_match("/catid\=[\d]+/",$query_parts[1], $matches, PREG_OFFSET_CAPTURE) > 0)
	   {
	      $catids_in=$matches[0][0];
		  $add_catids_in=str_replace('catid','ca.`catid`',$catids_in);
		  $catids_in_add_pref=str_replace('catid','i.`catid`',$catids_in);
		  $all_catids='('. $catids_in_add_pref .' OR '. $add_catids_in .')';
		  
		  $query_parts[1]=str_replace($catids_in,$all_catids,$query_parts[1]);
		  $query_parts[1]=str_replace('trash','i.`trash`',$query_parts[1]);
		  $query_parts[0].=" LEFT JOIN  #__k2_additional_categories AS ca ON ca.`itemID`  = i.`id`";
			
		  $query_parts[0]=str_replace('COUNT(*)','COUNT(DISTINCT i.`id`)',$query_parts[0]);
		  $query_parts[0]=str_replace('FROM #__k2_items','FROM #__k2_items AS i',$query_parts[0]);
		  $query=implode(' WHERE ',$query_parts);
	   }
	   
	}
	
	
	/**
	 * Custom trigger (not k2 core). Count the active or trash item the category has.Category list on backend. 
	 *
	 * @param 	string		The k2 query.
	 * @return	string
	 *
	 * @since  1.0.1
	 */
	function onK2BeforeCountCategoryItemsAdmin(&$result,$catid,$trash)
	{
	    
		$db = &JFactory::getDBO();
		$query = "SELECT COUNT(ca.`itemID`) FROM #__k2_items AS i,#__k2_additional_categories AS ca WHERE ca.`catid`={intval($catid)} AND i.`trash`={intval($trash)} AND i.`id`=ca.`itemID`";
		
		$db->setQuery($query);
        $total_additional = $db->loadResult();
		$result=$result+$total_additional;
	   
	}
	
	
	/**
	 * Custom trigger (not k2 core). Delete the k2 item entry from #__k2_additional_categories when 
	 * item removed from k2. 
	 *
	 * @param 	object		The k2 item.
	 * @return	void
	 */
	function onK2AfterDeleteItem($row)
	{
	   $db = &JFactory::getDBO();
	   $query ="DELETE FROM #__k2_additional_categories WHERE `itemID`= {intval($row->id)}";
	   $db->setQuery($query);
	   $db->query();
	}
	
	
	
	
    // Events to display (in the frontend)
	
	
	
	
	/**
	 * Custom trigger (not k2 core). Generate link and title of additional catecories if exists. 
	 * 
	 * @param 	int		The id of k2 item.
	 * @return	array
	 */
	function onK2AfterLinkCategoryPublish($itemID) {
	   
		$itemID=intval($itemID);
		$output='';
		if($itemID > 0)
		{
			
			$db = &JFactory::getDBO();
			$query = "SELECT c.`id`,c.`name`,c.`alias` FROM #__k2_additional_categories AS ca, #__k2_categories AS c WHERE ca.`itemID` ='$itemID' AND c.`id`=ca.`catid`";
			$db->setQuery( $query );
			$sel_value = $db->loadObjectList();
			
			foreach($sel_value as $k=>$v)
			{
				$link= urldecode(JRoute::_(K2HelperRoute::getCategoryRoute($v->id.':'.urlencode($v->alias))));
				$output.=', <span class="add_cat_link"><a href="'.$link.'">'.$v->name.'</a></span>';
			}
		}
		return $output;
	}
	
	
	/**
	 * Custom trigger (not k2 core). Add additional category items to the count of current category subcategories. 
	 * 
	 * @param 	int		The count of items that subcategory have.
	 * @param 	int		The subcategory id.
	 * @param 	object	The subcategories data.
	 * @return	int
	 */
	function onK2BeforeSetCountCategoryQuery(&$total, &$id, &$categories)
	{
	  
	   $mainframe = &JFactory::getApplication();
	   $user = &JFactory::getUser();
	   $aid = (int) $user->get('aid');
	   $id = (int) $id;
	   $db = &JFactory::getDBO();

	   $jnow = &JFactory::getDate();
	   $now = $jnow->toMySQL();
	   $nullDate = $db->getNullDate();
	   
	   $query = "SELECT COUNT(ca.`itemID`) FROM #__k2_items AS i,#__k2_additional_categories AS ca WHERE ca.`catid` IN (".implode(',', $categories).") AND i.`published`=1 AND i.`trash`=0 AND i.`id`=ca.`itemID`";

	   if(K2_JVERSION!='15')
	   {
		  $query .= " AND i.access IN(".implode(',', $user->authorisedLevels()).") ";
		  if($mainframe->getLanguageFilter()) 
		  {
			  $query.= " AND i.`language` IN(".$db->Quote(JFactory::getLanguage()->getTag()).", ".$db->Quote('*').")";
		  }
	   }
	   else 
	   {
			$query .=" AND i.`access`<=".$aid;
	   }
				
		 $query .= " AND ( i.`publish_up` = ".$db->Quote($nullDate)." OR i.`publish_up` <= ".$db->Quote($now)." )";
		 $query .= " AND ( i.`publish_down` = ".$db->Quote($nullDate)." OR i.`publish_down` >= ".$db->Quote($now)." )";
		 $db->setQuery($query);
		 $total_additional = $db->loadResult();
		 $total=$total+$total_additional;
	}
	
}
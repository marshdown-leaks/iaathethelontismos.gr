<?php
/**
 * @version 1.0.1
 * @package Additional Categories for K2
 * @author Thodoris Bgenopoulos <teobgeno@netpin.gr>
 * @link http://www.netpin.gr
 * @copyright Copyright (c) 2012 netpin.gr
 * @license GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

class com_k2additionalcategoriesInstallerScript
{

    public function postflight($type, $parent)
    {
        $db = JFactory::getDBO();
        $status = new stdClass;
        $status->plugins = array();
        $src = $parent->getParent()->getPath('source');
        $manifest = $parent->getParent()->manifest;
        $plugins = $manifest->xpath('plugins/plugin');
		
        foreach ($plugins as $plugin)
        {
            $name = (string)$plugin->attributes()->plugin;
            $group = (string)$plugin->attributes()->group;
            $path = $src.'/plugins/'.$group;
            if (JFolder::exists($src.'/plugins/'.$group.'/'.$name))
            {
                $path = $src.'/plugins/'.$group.'/'.$name;
            }
            $installer = new JInstaller;
            $result = $installer->install($path);
			if (JFile::exists(JPATH_SITE.'/plugins/'.$group.'/'.$name.'/'.$name.'.xml'))
            {
				JFile::delete(JPATH_SITE.'/plugins/'.$group.'/'.$name.'/'.$name.'.xml');
            }
                JFile::move(JPATH_SITE.'/plugins/'.$group.'/'.$name.'/'.$name.'.j25.xml', JPATH_SITE.'/plugins/'.$group.'/'.$name.'/'.$name.'.xml');
			
            $query = "UPDATE #__extensions SET enabled=1 WHERE type='plugin' AND element=".$db->Quote($name)." AND folder=".$db->Quote($group);
            $db->setQuery($query);
            $db->query();
            $status->plugins[] = array('name' => $name, 'group' => $group, 'result' => $result);
        }	
      
        $this->installationResults($status);
       
    }

    public function uninstall($parent)
    {
        $db = JFactory::getDBO();
        $status = new stdClass;
        $status->plugins = array();
        $manifest = $parent->getParent()->manifest;
        $plugins = $manifest->xpath('plugins/plugin');
        foreach ($plugins as $plugin)
        {
            $name = (string)$plugin->attributes()->plugin;
            $group = (string)$plugin->attributes()->group;
            $query = "SELECT `extension_id` FROM #__extensions WHERE `type`='plugin' AND element = ".$db->Quote($name)." AND folder = ".$db->Quote($group);
            $db->setQuery($query);
            $extensions = $db->loadColumn();
            if (count($extensions))
            {
                foreach ($extensions as $id)
                {
                    $installer = new JInstaller;
                    $result = $installer->uninstall('plugin', $id);
                }
                $status->plugins[] = array('name' => $name, 'group' => $group, 'result' => $result);
            }
            
        }
        
        $this->uninstallationResults($status);
    }

    public function update($type)
    {
        
    }
    private function installationResults($status)
    {
        ?>
		<h2>
			<a target='_blank' href='http://www.netpin.gr/'>Additional Categories for K2 Plugin v1.0.1</a>
		</h2>
		<a target='_blank' href='http://www.netpin.gr/'>
		   <img style='float:left;background:#fff;padding:2px;margin:0 0 8px 0px;' src='../media/k2additonalcategories/addcat_logo_219x125_24.png' border='0' alt='Additional Categories for K2'/>
		</a>
		<b>Additional Categories for K2</b> allows you to assign a K2 item to more than one K2 category.
		<br/><br/>
		You may also want to check out the following resources:
		<ul>
			<li>
				<a target='_blank' href='http://www.netpin.gr/documentation/item/2-k2-additional-categories'>Additional Categories for K2 documentation
				</a>.
		  </li>
		</ul>
		 <b>Additional Categories for K2</b> is a <a target='_blank' href='http://getk2.org/'>K2</a> plugin developed by <a target='_blank' title='netpin.gr' href='http://www.netpin.gr'>Thodoris Bgenopoulos</a>, released under the <a target='_blank' title='GNU General Public License' href='http://www.gnu.org/copyleft/gpl.html'>GNU General Public License</a>.<br/><br/>Copyright &copy; 2012 netpin.gr. All rights reserved.<br/><br/><i>(Last update: April 5th, 2013 - Version 1.0.1)</i>
		 <br />
		 <br />
    <?php
    }
    private function uninstallationResults($status)
    {
    
    }
}
?>
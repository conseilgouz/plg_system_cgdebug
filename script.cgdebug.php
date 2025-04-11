<?php
/**
 * @package		CGDebug system plugin
 * @author		ConseilGouz
 * @copyright	Copyright (C) 2025 ConseilGouz. All rights reserved.
 * license      https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 **/
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class plgSystemcgdebugInstallerScript
{
    public function postflight($type, $parent)
    {
        if (($type != 'install') && ($type != 'update')) {
            return true;
        }

        // remove obsolete directory
		$this->delete([
			JPATH_SITE . '/media/plg_system_cgdebug',
		]);

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true);
        $query->select(array(
            'e.manifest_cache',
            'e.params',
            'e.extension_id'
        ));
        $query->from('#__extensions AS e');
        $query->where('e.element = ' . $db->quote('cgdebug'));
        $query->where('e.type = ' . $db->quote('plugin'));
        $query->where('e.folder = ' . $db->quote('system'));
        $db->setQuery($query);

        $schema = $db->loadObject();

        // Enable plugin
        $query = $db->getQuery(true);
        $db->setQuery('UPDATE #__extensions SET enabled = 1 WHERE extension_id = ' . $schema->extension_id);
        if (!$db->execute()) {
            Factory::getApplication()->enqueueMessage('unable to enable CG AVIF', 'error');
            return false;
        }
        return true;
    }
    public function delete($files = [])
    {
        foreach ($files as $file) {
            if (is_dir($file)) {
                Folder::delete($file);
            }

            if (is_file($file)) {
                File::delete($file);
            }
        }
    }
    
}

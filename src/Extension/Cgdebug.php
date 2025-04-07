<?php
/**
* CG Debug  - Joomla 4.x/5.x Plugin
* @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
* @copyright (c) 2025 ConseilGouz. All Rights Reserved.
* @author ConseilGouz
**/

namespace ConseilGouz\Plugin\System\CGDebug\Extension;

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\Utilities\ArrayHelper;

final class Cgdebug extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    protected $debug = JDEBUG;
    protected $error_reporting;
    public $myname = 'Cgdebug';
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterDispatch' => 'onAfterDispatch',
        ];
    }

    public function __construct(DispatcherInterface $dispatcher, array $config, CMSApplicationInterface $app, DatabaseInterface $db)
    {
        parent::__construct($dispatcher, $config);

        $this->setApplication($app);
        $this->setDatabase($db);

    }

    public function onAfterDispatch()
    {
        $mainframe 	= Factory::getApplication();
        $godebug = isset($_GET['godebug']) ? $_GET['godebug'] : null ;
        $stopdebug =  isset($_GET['stopdebug']) ? $_GET['stopdebug'] : null;
        if (!$godebug && !$stopdebug) {
            return;
        }
        $type = $this->params->get('passwordtype');
        if ($type == 'none') {
            return;
        } elseif ($type == 'value') {
            $pwd = $this->params->get('valuepassword', '');
        }

        if (isset($pwd) && ($godebug == $pwd)) {
            $this->debug = true;
            $this->error_reporting = 'maximum';
        } elseif (isset($pwd) && ($stopdebug == $pwd)) {
            $this->debug = false;
            $this->error_reporting = 'none';
        } else {
            return;
        }

        $config = new \JConfig();
        $data   = ArrayHelper::fromObject($config);

        $data['debug'] = $this->debug;
        $data['error_reporting'] = $this->error_reporting;
        $config = new Registry($data);
        $this->writeConfigFile($config);
        $mainframe->redirect(URI::root());

    }
    /**
     * Method to write the configuration to a file.
    *  from administrator/components/com_config/src/Model/ApplicationModel.php
     *
     * @param   Registry  $config  A Registry object containing all global config data.
     *
     * @return  boolean  True on success, false on failure.
     *
     * @throws  \RuntimeException
     */
    private function writeConfigFile(Registry $config)
    {
        // Set the configuration file path.
        $file = JPATH_CONFIGURATION . '/configuration.php';

        $app = Factory::getApplication();

        // Attempt to make the file writeable.
        if (Path::isOwner($file) && !Path::setPermissions($file, '0644')) {
            $app->enqueueMessage(Text::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTWRITABLE'), 'notice');
        }

        // Attempt to write the configuration file as a PHP class named JConfig.
        $configuration = $config->toString('PHP', ['class' => 'JConfig', 'closingtag' => false]);

        if (!File::write($file, $configuration)) {
            throw new \RuntimeException(Text::_('COM_CONFIG_ERROR_WRITE_FAILED'));
        }

        // Attempt to make the file unwriteable.
        if (Path::isOwner($file) && !Path::setPermissions($file, '0444')) {
            $app->enqueueMessage(Text::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTUNWRITABLE'), 'notice');
        }

        return true;
    }
}

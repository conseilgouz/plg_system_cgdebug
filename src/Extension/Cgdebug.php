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
use Joomla\CMS\Event\MultiFactor\Validate;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Component\Users\Administrator\Model\CaptiveModel;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\Utilities\ArrayHelper;

final class Cgdebug extends CMSPlugin
{
    use UserFactoryAwareTrait;
    protected $debug = JDEBUG;
    protected $error_reporting;
    public $myname = 'Cgdebug';
    protected $autoloadLanguage = true;

    public function __construct(DispatcherInterface $dispatcher, array $config, CMSApplicationInterface $app, $userFactory)
    {
        parent::__construct($dispatcher, $config);
        $this->setApplication($app);
        $this->setUserFactory($userFactory);
        $goval      = $this->params->get('gotag', 'godebug');
        $stopval      = $this->params->get('stoptag', 'stopdebug');
        $godebug = isset($_GET[$goval]) ? $_GET[$goval] : null ;
        $stopdebug =  isset($_GET[$stopval]) ? $_GET[$stopval] : null;
         if (is_null($godebug) && is_null($stopdebug)) {
            return;
        }
        $type = $this->params->get('passwordtype');
        if ($type == 'none') {
            $pwd = '';
        } elseif ($type == 'value') {
            $pwd = $this->params->get('valuepassword', '');
        }

        if ($type == 'totp') {
            $userId = $this->params->get('user', 0);
            $check = $godebug;
            $go = true; // assume godebug
            if (!isset($check)) {
                $check = $stopdebug;
                $go = false; // we are in stop debug mode
            }
            if (!$check) { // nothing to check : exit
                return;
            }
            $valid = $this->getOTP($userId, $check);
            if (!$valid) {
                return;
            }
            if ($go) {
                $this->debug = true;
            } else {
                $this->debug = false;
            }
        } elseif (!is_null($godebug) && ($godebug === $pwd)) { {
            $this->debug = true;
        } elseif (!is_null($stopdebug) && ($stopdebug === $pwd)) {
            $this->debug = false;
        } else {
            return;
        }

        $config = new \JConfig();
        $data   = ArrayHelper::fromObject($config);
        if ($this->debug) { // save error_reproting value
            $data['prev_error_reporting']   = $data['error_reporting'];
            $data['error_reporting']        = "maximum";
        } else {
            if (isset($data['prev_error_reporting'])) {
                $data['error_reporting'] =  $data['prev_error_reporting'];
                unset($data['prev_error_reporting']);
            }
        }
        $data['debug'] = $this->debug;
        $config = new Registry($data);
        $this->writeConfigFile($config);
        $app->redirect(URI::root());
    }
    private function getOTP($userId, $code)
    {
        $recordId = 0;

        $user = $this->getUserFactory()->loadUserById($userId);
        $model = new CaptiveModel(array('ignore_request' => true));
        $records = $model->getRecords($user);
        foreach ($records as $one) {
            if ($one->method == 'totp') {
                $recordId = $one->id;
            }
        }
        if (!$recordId) {
            return false;
        }
        $model->setState('record_id', $recordId);
        $record = $model->getRecord($user);

        $event   = new Validate($record, $user, $code);
        $results = $this->getApplication()
            ->getDispatcher()
            ->dispatch($event->getName(), $event)
            ->getArgument('result', []);

        $isValidCode = false;
        $isValidCode = array_reduce(
            $results,
            function (bool $carry, $result) {
                return $carry || \boolval($result);
            },
            false
        );
        return $isValidCode;
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

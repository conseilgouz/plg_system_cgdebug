<?php
/**
* CG Debug  - Joomla 4.x/5.x Plugin
* @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
* @copyright (c) 2025 ConseilGouz. All Rights Reserved.
* @author ConseilGouz
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use ConseilGouz\Plugin\System\CGDebug\Extension\Cgdebug;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.2.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                return new Cgdebug(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'cgdebug'),
                    Factory::getApplication(),
                    $container->get(UserFactoryInterface::class)
                );
                return $plugin;
            }
        );
    }
};

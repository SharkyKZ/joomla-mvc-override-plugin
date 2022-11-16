<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */
defined('_JEXEC') || exit;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Sharky\Joomla\Plugin\System\MvcOverride\Plugin;

/**
 * The service provider
 *
 * @since  1.0.0
 */
return new class implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			static function (Container $container)
			{
				return new Plugin(
					$container->get(DispatcherInterface::class),
					new Registry(PluginHelper::getPlugin('system', 'mvcoverride')->params ?? null)
				);
			}
		);
	}
};

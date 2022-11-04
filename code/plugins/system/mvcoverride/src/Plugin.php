<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */
declare(strict_types=1);

namespace SharkyKZ\Joomla\Plugin\System\MvcOverride;

\defined('_JEXEC') or exit;

use Joomla\CMS\MVC\Factory\MVCFactory as CoreFactory;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\Event\EventInterface;
use Joomla\DI\Container;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

/**
 * MVC override plugin class
 *
 * @since  1.0.0
 */
final class Plugin implements PluginInterface
{
	/**
	 * Event dispatcher instance.
	 *
	 * @var    DispatcherInterface
	 * @since  1.0.0
	 */
	private $dispatcher;

	/**
	 * Plugin parameters.
	 *
	 * @var    Registry
	 * @since  1.0.0
	 */
	private $params;

	/**
	 * Class constructor.
	 *
	 * @param   DispatcherInterface  $dispatcher
	 * @param   Registry             $params
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function __construct(DispatcherInterface $dispatcher, Registry $params)
	{
		$this->dispatcher = $dispatcher;
		$this->params = $params;
	}

	/**
	 * Method to register listeners with the event dispatcher.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function registerListeners(): void
	{
		$this->dispatcher->addListener('onAfterExtensionBoot', [$this, 'onAfterExtensionBoot']);
	}

	/**
	 * Set the dispatcher to use.
	 *
	 * @param   DispatcherInterface  $dispatcher  The dispatcher to use.
	 *
	 * @return  $this
	 *
	 * @since   1.0.0
	 */
	public function setDispatcher(DispatcherInterface $dispatcher)
	{
		$this->dispatcher = $dispatcher;

		return $this;
	}

	/**
	 * Plugin event fired after an extension has been booted.
	 *
	 * @param   EventInterface  $event
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterExtensionBoot(EventInterface $event): void
	{
		// Test that this is a component.
		if ($event->getArgument('type') !== 'Joomla\\CMS\\Extension\\ComponentInterface')
		{
			return;
		}

		$overrides = $this->getOverridesForComponent('com_' . $event->getArgument('extensionName'));

		// Test that we have overrides for this component.
		if (!$overrides)
		{
			return;
		}

		// Get the container.
		$container = $event->getArgument('container');

		if (!($container instanceof Container))
		{
			return;
		}

		// Service key to override.
		$interfaceClass = 'Joomla\\CMS\\MVC\\Factory\\MVCFactoryInterface';

		// Service key not found or can't be overridden.
		if (!$container->has($interfaceClass) || $container->isProtected($interfaceClass))
		{
			return;
		}

		// Get current MVC factory.
		$currentFactory = $container->get($interfaceClass);

		// To be safe we only handle default core MVC factory.
		if (\get_class($currentFactory) !== 'Joomla\\CMS\\MVC\\Factory\\MVCFactory')
		{
			return;
		}

		// Register our custom MVC factory.
		$namespace = $this->getNamespaceFromFactory($currentFactory);

		if ($namespace === '')
		{
			return;
		}

		$container->set($interfaceClass, new MvcFactory($namespace, $overrides));
	}

	/**
	 * Gets class override configuration for a given component
	 *
	 * @param   string  $component  Component name
	 *
	 * @return  array
	 *
	 * @since  1.0.0
	 */
	private function getOverridesForComponent(string $component): array
	{
		$overrides = [];

		foreach ($this->params->get('overrides', []) as $override)
		{
			if ($override->component === $component)
			{
				$overrides[] = $override;
			}
		}

		return $overrides;
	}

	/**
	 * Gets namespace from MVC factory instance
	 *
	 * @param   CoreFactory  $factory  MVC factory instance
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	private function getNamespaceFromFactory(CoreFactory $factory): string
	{
		$closure = function ()
		{
			return $this->namespace ?? '';
		};

		$function = $closure->bindTo($factory, $factory);

		return $function();
	}
}

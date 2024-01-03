<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */
namespace Sharky\Joomla\Plugin\System\MvcOverride;

\defined('_JEXEC') || exit;

use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\MVC\Factory\MVCFactory as CoreFactory;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
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
		if ($event->getArgument('type') !== ComponentInterface::class)
		{
			return;
		}

		// Test that we have overrides for this component.
		if (!$overrides = $this->getOverridesForComponent('com_' . $event->getArgument('extensionName')))
		{
			return;
		}

		// Get the container.
		if (!($container = $event->getArgument('container')) instanceof Container)
		{
			return;
		}

		// MVC factory not found or can't be overridden.
		if (!$container->has(MVCFactoryInterface::class) || $container->isProtected(MVCFactoryInterface::class))
		{
			return;
		}

		// Override the service.
		$container->extend(
			MVCFactoryInterface::class,
			function ($currentFactory) use ($overrides)
			{
				if (!$currentFactory instanceof CoreFactory)
				{
					return $currentFactory;
				}

				if (($namespace = $this->getPropertyFromFactory('namespace', $currentFactory)) === null)
				{
					return $currentFactory;
				}

				return new MvcFactory(
					$currentFactory,
					$overrides,
					$namespace,
					$this->getPropertyFromFactory('logger', $currentFactory)
				);
			}
		);
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
	 * Gets a private property from MVC factory instance
	 *
	 * @param   CoreFactory  $factory  MVC factory instance
	 *
	 * @return  mixed
	 *
	 * @since  1.0.0
	 */
	private function getPropertyFromFactory(string $property, CoreFactory $factory)
	{
		$closure = function ($property)
		{
			return $this->$property ?? null;
		};

		$function = $closure->bindTo($factory, $factory);

		return $function($property);
	}
}

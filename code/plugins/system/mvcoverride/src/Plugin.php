<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */
declare(strict_types=1);

namespace SharkyKZ\Joomla\Plugin\System\MvcOverride;

\defined('_JEXEC') or exit;

use Joomla\CMS\MVC\Factory\MVCFactory as CoreFactory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\EventInterface;
use Joomla\DI\Container;

/**
 * MVC override plugin class
 *
 * @since  1.0.0
 */
final class Plugin extends CMSPlugin
{
	/**
	 * Plugin event fired after an extension has been booted
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
		$container->set($interfaceClass, new MvcFactory($this->getNamespaceFromFactory($currentFactory), $overrides));
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
		$reflection = new \ReflectionClass($factory);
		$namespaceProperty = $reflection->getProperty('namespace');
		$namespaceProperty->setAccessible(true);

		return $namespaceProperty->getValue($factory);
	}
}

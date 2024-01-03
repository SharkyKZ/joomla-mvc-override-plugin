<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */
namespace Sharky\Joomla\Plugin\System\MvcOverride;

\defined('_JEXEC') || exit;

use Joomla\CMS\MVC\Factory\MVCFactory as CoreFactory;
use Joomla\Database\Exception\DatabaseNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Custom MVC factory class
 *
 * @since  1.0.0
 */
final class MvcFactory extends CoreFactory
{
	/**
	 * Array of class override configurations
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	private $overrides = [];

	/**
	 * Class constructor
	 *
	 * @param   string  $namespace  The base namespace
	 * @param   array   $overrides  Array of class override configurations
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function __construct(CoreFactory $factory, array $overrides, string $namespace, ?LoggerInterface $logger = null)
	{
		$this->overrides = $overrides;
		$this->acquireDependencies($factory);

		parent::__construct($namespace, $logger);
	}

	/**
	 * Returns a standard classname, if the class doesn't exist null is returned.
	 *
	 * @param   string  $suffix  The suffix
	 * @param   string  $prefix  The prefix
	 *
	 * @return  string|null  The class name
	 *
	 * @since   1.0.0
	 */
	protected function getClassName(string $suffix, string $prefix): ?string
	{
		// Build original class name first.
		$className = parent::getClassName($suffix, $prefix);

		// Original class doesn't exist.
		if ($className === null)
		{
			return null;
		}

		foreach ($this->overrides as $override)
		{
			if ($override->class === $className)
			{
				// If class is not found, try to load it.
				if ($override->newFile !== '' && !class_exists($override->newClass))
				{
					\JLoader::register($override->newClass, \JPATH_ROOT . '/' . $override->newFile);
				}

				if (class_exists($override->newClass))
				{
					return $override->newClass;
				}
			}
		}

		// Class override not found, use original class.
		return $className;
	}

	/**
	 * Injects dependencies from original factory to current factory instance
	 *
	 * @param   CoreFactory  $factory  Core MVC factory instance
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function acquireDependencies(CoreFactory $factory): void
	{
		try
		{
			$this->setFormFactory($factory->getFormFactory());
		}
		catch (\UnexpectedValueException $e)
		{
		}

		try
		{
			$this->setDispatcher($factory->getDispatcher());
		}
		catch (\UnexpectedValueException $e)
		{
		}

		try
		{
			$this->setSiteRouter($factory->getSiteRouter());
		}
		catch (\UnexpectedValueException $e)
		{
		}

		$this->setCacheControllerFactory($factory->getCacheControllerFactory());

		try
		{
			$this->setUserFactory($factory->getUserFactory());
		}
		catch (\UnexpectedValueException $e)
		{
		}

		try
		{
			$this->setMailerFactory($factory->getMailerFactory());
		}
		catch (\UnexpectedValueException $e)
		{
		}

		try
		{
			$this->setDatabase($factory->getDatabase());
		}
		catch (DatabaseNotFoundException $e)
		{
		}
	}
}

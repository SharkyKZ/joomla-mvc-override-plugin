<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */
namespace SharkyKZ\Joomla\Plugin\System\MvcOverride;

\defined('_JEXEC') or exit;

use Joomla\CMS\MVC\Factory\MVCFactory as CoreFactory;

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
	public function __construct(string $namespace, array $overrides = [])
	{
		$this->overrides = $overrides;

		parent::__construct($namespace);
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
}

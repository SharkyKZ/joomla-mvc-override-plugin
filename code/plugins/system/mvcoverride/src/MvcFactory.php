<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */
declare(strict_types=1);

namespace SharkyKZ\Joomla\Plugin\System\MvcOverride;

\defined('_JEXEC') or exit;

use Joomla\CMS\MVC\Factory\MVCFactory as CoreFactory;

/**
 * Custom MVC factory class.
 */
final class MvcFactory extends CoreFactory
{
	private $overrides = [];

	public function __construct(string $namespace, array $overrides = [])
	{
		$this->overrides = $overrides;

		parent::__construct($namespace);
	}

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

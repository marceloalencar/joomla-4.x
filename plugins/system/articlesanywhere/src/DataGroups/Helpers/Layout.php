<?php
/**
 * @package         Articles Anywhere
 * @version         12.3.1
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Layout\FileLayout as JFileLayout;

class Layout
{
	public static function getId($layout, $default = '', $prefix = '')
	{
		$prefix = $prefix ? $prefix . '.' : '';

		if ( ! $layout || $layout === true || $layout === 'default' || $layout === $default)
		{
			return $prefix . $default;
		}

		$layout = self::getDottedPath($layout, $prefix);

		return $layout;
	}

	private static function getDottedPath($path, $prefix = '')
	{
		$prefix = $prefix ? $prefix . '.' : '';

		$path = str_replace('.php', '', $path);
		$path = str_replace('/', '.', trim($path, '/'));

		if (strpos($path, '.') === false)
		{
			$path = $prefix . $path;
		}

		return $path;
	}

	public static function render($layoutId, $displayData = [], $options = [])
	{
		$layout = new JFileLayout(
			$layoutId,
			null,
			$options
		);

		return $layout->addIncludePath(JPATH_SITE)->render($displayData);
	}
}

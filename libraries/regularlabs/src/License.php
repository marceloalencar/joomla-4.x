<?php
/**
 * @package         Regular Labs Library
 * @version         22.5.9993
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Library;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text as JText;

class License
{
	/**
	 * Render the license message for Free versions
	 *
	 * @param string $name
	 * @param bool   $check_pro
	 *
	 * @return string
	 */
	public static function getMessage($name, $check_pro = false)
	{
		if ( ! $name)
		{
			return '';
		}

		$alias = Extension::getAliasByName($name);
		$name  = Extension::getNameByAlias($name);

		if ($check_pro && self::isPro($alias))
		{
			return '';
		}

		return '<div class="rl-license rl-alert alert alert-warning rl-alert-light">' .
			'<div>' . JText::sprintf('RL_IS_FREE_VERSION', $name) . '</div>'
			. '<div>' . JText::_('RL_FOR_MORE_GO_PRO') . '</div>'
			. '<div>'
			. '<a href="https://regularlabs.com/purchase/cart/add/' . $alias . '" target="_blank" class="btn btn-sm btn-primary">'
			. '<span class="icon-basket"></span>&nbsp;&nbsp;'
			. StringHelper::html_entity_decoder(JText::_('RL_GO_PRO'))
			. '</a>'
			. '</div>'
			. '</div>';
	}

	/**
	 * Check if the installed version of the extension is a Pro version
	 *
	 * @param string $element_name
	 *
	 * @return bool
	 */
	private static function isPro($element_name)
	{
		if ( ! $version = Extension::getXMLValue('version', $element_name))
		{
			return false;
		}

		return (stripos($version, 'PRO') !== false);
	}
}

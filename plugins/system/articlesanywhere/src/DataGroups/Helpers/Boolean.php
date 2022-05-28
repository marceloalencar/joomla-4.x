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

use Joomla\CMS\Language\Text as JText;

class Boolean
{
	public static function process($string, $key, $attributes)
	{
		if ( ! is_bool($string))
		{
			return $string;
		}


		return $string ? JText::_('JYES') : JText::_('JNO');
	}
}

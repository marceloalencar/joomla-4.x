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

use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;

class Calculation
{
	/**
	 * @param Text $string
	 */
	public static function process($string, $key, $attributes)
	{
		if (empty($attributes->calc) || ! is_numeric($string))
		{
			return $string;
		}

		return DataHelper::calculate($string, $attributes->calc);
	}
}

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

use Joomla\CMS\Date\Date as JDate;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\Date as RL_Date;
use RegularLabs\Library\RegEx as RL_RegEx;

class Date
{
	private static $no_date_keys = [
		'text', 'introtext', 'fulltext',
		'title', 'description',
		'text', 'textarea', 'editor',
		'category-title', 'category-description',
		'metakey', 'metadesc',
		'id', 'title', 'alias',
		'category-id', 'category-title', 'category-alias', 'category-description',
		'author-id', 'author-name', 'author-username',
		'modifier-id', 'modifier-name', 'modifier-username',
	];

	/**
	 * @param Text $string
	 */
	public static function process($string, $key, $attributes)
	{
		if ( ! empty($attributes->output)
			&& in_array($attributes->output, ['value', 'values', 'raw'], true))
		{
			return $string;
		}

		if ( ! self::isDate($key, $string))
		{
			return $string;
		}

		return self::toString($string, $attributes);
	}

	public static function isDate($key, $value)
	{
		if ( ! self::keyIsPotentialDate($key))
		{
			return false;
		}

		return self::valueIsDate($value);
	}

	public static function toString($value, $attributes)
	{
		$showtime        = $attributes->showtime ?? true;
		$format          = $attributes->format ?? '';
		$is_custom_field = $attributes->is_custom_field ?? false;

		if (empty($format))
		{
			$format = $showtime ? JText::_('DATE_FORMAT_LC2') : JText::_('DATE_FORMAT_LC1');
		}

		if (strpos($format, '%') !== false)
		{
			$format = RL_Date::strftimeToDateFormat($format);
		}

		// Don't pass custom fields through JHtml, as it will double the offset
		if ($is_custom_field)
		{
			return (new JDate($value))->format($format);
		}

		return JHtml::_('date', $value, $format);
	}

	public static function keyIsPotentialDate($key)
	{
		return ! in_array($key, self::$no_date_keys, true);
	}

	public static function valueIsDate($value)
	{
		// Check if string could be a date
		if ( ! is_string($value))
		{
			return false;
		}

		if (strpos($value, ' to ') !== false)
		{
			[$from, $to] = explode(' to ', $value, 2);
			$from = RL_RegEx::replace('^from ', '', $from);

			return self::valueIsDate($from) && self::valueIsDate($to);
		}

		if (
			// Dates must contain a '-' and not letters
			(strpos($value, '-') === false)
			|| RL_RegEx::match('^[a-z]', $value)
			// Start with Y-m-d format
			|| ! RL_RegEx::match('^[0-9]{4}-[0-9]{2}-[0-9]{2}', $value)
			// Check string it passes a simple strtotime
			|| ! strtotime($value)
		)
		{
			return false;
		}

		return true;
	}
}

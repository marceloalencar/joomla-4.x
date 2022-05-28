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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Date\Date as JDate;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Filesystem\Folder as JFolder;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\Filters\ValuesObject;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;

class Data
{
	private static $plain_keys_to_types = [];
	private static $regex_keys_to_types = [];

	/**
	 * @param int|float $source
	 * @param string    $calculation
	 *
	 * @return int|float|array
	 */
	public static function calculate($value, $calculation)
	{
		$array = RL_Array::applyMethodToValues([$value, $calculation]);

		if (is_array($value))
		{
			return $array;
		}

		if ( ! is_numeric($value))
		{
			return $value;
		}

		if (RL_String::strlen($calculation) < 2)
		{
			return $value;
		}

		$modifier = $calculation[0];
		$value2   = (int) RL_String::substr($calculation, 1);

		switch ($modifier)
		{
			case '+':
				return $value + $value2;
			case '-':
				return $value - $value2;
			case '/':
				return $value / $value2;
			case '*':
				return $value * $value2;
			case '%':
				return $value % $value2;
			default:
				return $value;
		}
	}

	public static function getDataGroupPrefixes()
	{
		$prefixes = [];

		$class_names = self::getDataGroupNames();

		foreach ($class_names as $class_name)
		{
			/* @var DataGroup $data_group */
			$data_group = 'RegularLabs\\Plugin\\System\\ArticlesAnywhere\\DataGroups\\' . $class_name;

			if ( ! method_exists($data_group, 'getPossibleKeys'))
			{
				continue;
			}

			$prefixes[] = $data_group::getPrefix();
		}

		return RL_Array::clean($prefixes);
	}

	public static function getDataGroupNames()
	{
		$files = JFolder::files(dirname(__DIR__) . '/DataGroups');

		$class_names = RL_Array::removePostfixFromValues($files, '.php');

		// Remove classes we don't want to include
		$class_names = array_diff($class_names, ['DataGroup', 'RowValue', 'Tag']);

		// Force basic stuff to the front in specific order
		$class_names = array_unique(array_merge(
			[
				'Article',
				'Category',
				'Author',
				'Modifier',
			],
			$class_names
		));

		// Place Field classes at the end, as it is the heaviest
		if (in_array('Field', $class_names))
		{
			$class_names   = array_diff($class_names, ['Field']);
			$class_names[] = 'Field';
		}

		if (in_array('FieldGroup', $class_names))
		{
			$class_names   = array_diff($class_names, ['FieldGroup']);
			$class_names[] = 'FieldGroup';
		}

		return $class_names;
	}

	public static function getDateObject($string)
	{
		$operator = DB::getOperator($string);
		$value    = DB::removeOperator($string);

		$value = DataHelper::placeholderToDate($value);

		if ($value instanceof ValuesObject)
		{
			return $value;
		}

		if ( ! self::isDate($value) || self::isFullDateString($value))
		{
			return $operator . $value;
		}

		[$from, $to] = self::getFromAndToDates($value);

		if ( ! $to)
		{
			return $operator . $from;
		}

		switch ($operator)
		{
			case '<':
				return '<' . $from;

			case '>':
				return '>=' . $to;

			case '<=':
				return '<' . $to;

			case '>=':
				return '>=' . $from;

			case '!':
			case '!=':
				return new ValuesObject([
					'<' . $from,
					'>' . $to,
				]);

			default:
				return new ValuesObject([
					'>=' . $from,
					'<' . $to,
				]);
		}
	}

	public static function placeholderToDate($value, $apply_offset = true)
	{
		if (in_array($value, [
			'NOW',
			'now()',
			'JFactory::getDate()',
		], true))
		{
			if ( ! $apply_offset)
			{
				return date('Y-m-d H:i:s', strtotime('now'));
			}

			$date = new JDate('now', JFactory::getApplication()->get('offset', 'UTC'));

			return $date->format('Y-m-d H:i:s');
		}

		if (strpos($value, ' to ') !== false)
		{
			$range    = explode(' to ', $value, 2);
			$range[0] = RL_RegEx::replace('^from ', '', $range[0]);

			$from = self::placeholderToDate($range[0], $apply_offset) ?: $range[0];
			$to   = self::placeholderToDate($range[1], $apply_offset) ?: $range[1];

			if ( ! $from || ! $to)
			{
				return $value;
			}

			[$from_start, $from_end] = self::getFromAndToDates($from);
			[$to_start, $to_end] = self::getFromAndToDates($to);

			return new ValuesObject([
				'>=' . $from_start,
				'<=' . ($to_end ?: $to_start ?: $from_end),
			]);
		}

		$regex = '^date\(\s*'
			. '(?:\'(?<datetime>.*?)\')?'
			. '(?:\\\\?,\s*\'(?<format>.*?)\')?'
			. '\s*\)$';

		if ( ! RL_RegEx::match($regex, $value, $match))
		{
			return $value;
		}

		$datetime = ($match['datetime'] ?? null) ?: 'now';
		$format   = $match['format'] ?? '';

		if (empty($format))
		{
			$time   = date('His', strtotime($datetime));
			$format = (int) $time ? 'Y-m-d H:i:s' : 'Y-m-d';
		}

		if ( ! $apply_offset)
		{
			return date($format, strtotime($datetime));
		}

		$date = new JDate($datetime, JFactory::getApplication()->get('offset', 'UTC'));

		return $date->format($format);
	}

	private static function isDate($string)
	{
		return (
			// Must contain a '-'
			strpos($string, '-') !== false
			// Cannot contain letters
			&& ! RL_RegEx::match('^[a-z]', $string)
			// Must start with Y-m-d format
			&& RL_RegEx::match('^[0-9]{4}-[0-9]{2}-[0-9]{2}', $string)
			// Must be able to pass through a simple strtotime
			&& strtotime($string)
		);
	}

	private static function isFullDateString($string)
	{
		return RL_RegEx::match('^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}', $string);
	}

	private static function getFromAndToDates($string)
	{
		if ( ! self::isDate($string) || self::isFullDateString($string))
		{
			return [$string, ''];
		}

		[$interval, $format] = self::getIntervalAndFormatFromDate($string);

		$date = new JDate($string, JFactory::getApplication()->get('offset', 'UTC'));

		$from = $date->format($format);
		$to   = $interval ? $date->modify('1' . $interval)->format($format) : '';

		return [$from, $to];
	}

	private static function getIntervalAndFormatFromDate($value)
	{
		if ( ! RL_RegEx::match(
			'^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}(?<hours> [0-9]{1,2}(?<minutes>:[0-9]{1,2}(?<seconds>\:[0-9]{1,2})?)?)?$',
			$value, $datetime_parts)
		)
		{
			return [false, 'Y-m-d H:i:s'];
		}

		switch (true)
		{
			case (isset($datetime_parts['seconds'])):
				return ['seconds', 'Y-m-d H:i:s'];

			case (isset($datetime_parts['minutes'])):
				return ['minutes', 'Y-m-d H:i:00'];

			case (isset($datetime_parts['hours'])):
				return ['hours', 'Y-m-d H:00:00'];

			default:
				return ['days', 'Y-m-d H:00:00'];
		}
	}

	public static function getRangeObject($string)
	{
		$operator = DB::getOperator($string);
		$string   = DB::removeOperator($string);

		$range = self::isRange($string);

		if ( ! $range)
		{
			return $operator . $string;
		}

		return new ValuesObject([
			'>=' . $range['from'],
			'<=' . $range['to'],
		]);
	}

	public static function isRange($string)
	{
		if (strpos($string, 'range(') !== 0)
		{
			return false;
		}

		if ( ! RL_RegEx::match('^range\(\s*(?<from>[0-9]+)\s*,\s*(?<to>[0-9]+)\s*\)$', $string, $match))
		{
			return false;
		}

		return $match;
	}

	public static function getValue($key, $attributes = [])
	{
		// Value is numeric, so return the number
		if (is_null($key) || strtoupper($key) === 'NULL')
		{
			return null;
		}

		// Value is CURRENT
		if (strtoupper($key) === 'CURRENT')
		{
			return '[:current:]';
		}

		// Value is TRUE
		if (strtoupper($key) === 'TRUE')
		{
			return true;
		}

		// Value is FALSE
		if (strtoupper($key) === 'FALSE')
		{
			return false;
		}

		// Value is numeric, so return the number
		if (is_numeric($key))
		{
			return $key + 0;
		}

		// Value is surrounded by quotes, so return string
		if (RL_RegEx::match('^(?<quotes>[\'"])(?<value>.*?)(?P=quotes)$', $key, $match))
		{
			return $match['value'];
		}

		// Value has an array syntax, so convert to an array
		if (RL_RegEx::match('^\[(?<value>.*?)\]$', $key, $match))
		{
			$parts = RL_Array::toArray($match['value']);

			foreach ($parts as &$part)
			{
				$part = self::getValue($part);
			}

			return $parts;
		}

		$date = DataHelper::placeholderToDate($key);

		if (is_string($date) && $date !== $key)
		{
			return $date;
		}

		//	- (...): deal with sums, like (2*5) and (myvalue+1)  ??????
		$data_group = self::getDataGroup($key, $attributes);

		return $data_group ?? null;
	}

	/**
	 * @param string|object $selectors
	 * @param array         $attributes
	 * @param string        $data_group
	 *
	 * @return DataGroup|false
	 */
	public static function getDataGroup($selectors, $attributes = [], $data_group = '')
	{
		if (is_string($selectors))
		{
			$selectors = self::getSelectors($selectors);
		}

		if (empty($selectors) || ! isset($selectors->full_key))
		{
			return false;
		}

		$class = $data_group
			? 'RegularLabs\\Plugin\\System\\ArticlesAnywhere\\DataGroups\\' . $data_group
			: self::getDataGroupClassName($selectors->full_key);

		if ( ! class_exists($class))
		{
			return false;
		}

		$data_subkey = $selectors->data_subkey ?? '';

		if ($class != 'RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Input')
		{
			$data_subkey = RL_String::toDashCase($data_subkey);
		}

		return new $class(
			RL_String::toDashCase($selectors->data_key),
			$data_subkey,
			$attributes,
			$selectors->article_selector ?? ''
		);
	}

	private static function getSelectors($string)
	{
		$regex = Params::getDataSelectorsRegex();

		RL_RegEx::match('^' . $regex . '$', $string, $selectors);

		return (object) $selectors ?: [];
	}

	private static function getDataGroupClassName($key)
	{
		$key = RL_String::toDashCase($key);

		$class = self::findClassNameInKeysToTypes($key);

		if ($class)
		{
			return class_exists($class) ? $class : null;
		}

		$class_names = self::getDataGroupNames();

		foreach ($class_names as $class_name)
		{
			$class = 'RegularLabs\\Plugin\\System\\ArticlesAnywhere\\DataGroups\\' . $class_name;

			if ( ! method_exists($class, 'getPossibleKeys'))
			{
				continue;
			}

			if ( ! self::isClassFoundAddedToCache($class))
			{
				$possible_keys = $class::getPossibleKeys();

				self::addPossiblePlainKeysToCache($possible_keys->plain, $class);
				self::addPossibleRegexKeysToCache($possible_keys->regex, $class);
			}

			$class = self::findClassNameInKeysToTypes($key);

			if ( ! $class)
			{
				continue;
			}

			return $class;
		}

		return false;
	}

	public static function findClassNameInKeysToTypes($key)
	{
		$key = RL_String::toDashCase($key);

		if (isset(self::$plain_keys_to_types[$key]))
		{
			return self::$plain_keys_to_types[$key];
		}

		if (empty(self::$regex_keys_to_types))
		{
			return false;
		}

		foreach (self::$regex_keys_to_types as $regex => $class_name)
		{
			if (RL_RegEx::match($regex, $key))
			{
				// add the matched key to the plain keys for faster lookup next time
				self::addPossiblePlainKeysToCache([$key], $class_name);

				return $class_name;
			}
		}

		return false;
	}

	public static function isClassFoundAddedToCache($class)
	{
		return in_array($class, self::$plain_keys_to_types, true)
			|| in_array($class, self::$regex_keys_to_types, true);
	}

	public static function addPossiblePlainKeysToCache($keys, $class_name)
	{
		if (empty($keys))
		{
			return;
		}

		self::$plain_keys_to_types = array_merge(
			array_fill_keys($keys, $class_name),
			self::$plain_keys_to_types
		);
	}

	public static function addPossibleRegexKeysToCache($keys, $class_name)
	{
		if (empty($keys))
		{
			return;
		}

		self::$regex_keys_to_types = array_merge(
			array_fill_keys($keys, $class_name),
			self::$regex_keys_to_types
		);
	}

	public static function isJsonKeys($key)
	{
		$key = RL_RegEx::replace('^article_', '', $key);

		return in_array($key, self::getJsonKeys(), true);
	}

	private static function getJsonKeys()
	{
		return [
			'images',
			'urls',
			'attribs',
			'metadata',
		];
	}

	/**
	 * @param array $values
	 *
	 * @return array
	 */
	public static function valuesToSimpleArray($values)
	{
		$array = [];

		foreach ($values as $value)
		{
			if ($value instanceof ValuesObject)
			{
				$value = $value->getValues();
			}

			if (is_array($value))
			{
				$array = array_merge($array, $value);
				continue;
			}

			$array[] = $value;
		}

		return $array;
	}
}

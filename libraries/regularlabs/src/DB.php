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

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Uri\Uri as JUri;
use Joomla\Database\DatabaseDriver as JDatabaseDriver;
use Joomla\Database\DatabaseQuery as JDatabaseQuery;
use Joomla\Database\QueryInterface as JQueryInterface;

class DB
{
	static $tables = [];

	/**
	 * @param JQueryInterface $query
	 * @param string          $prefix
	 */
	public static function addArticleIsPublishedFilters(&$query, $prefix = 'a')
	{
		$filters = self::getArticleIsPublishedFilters($prefix);

		$query->where($filters);
	}

	/**
	 * @param string $prefix
	 *
	 * @return  string
	 */
	public static function getArticleIsPublishedFilters($prefix = 'a')
	{
		$nowDate  = self::getNowDate();
		$nullDate = self::getNullDate();

		$wheres = [];

		$wheres[] = self::is($prefix . '.state', 1);

		$wheres[] = self::combine([
			self::is($prefix . '.publish_up', 'NULL'),
			self::is($prefix . '.publish_up', '<=' . $nowDate),
		], 'OR');

		$wheres[] = self::combine([
			self::is($prefix . '.publish_down', 'NULL'),
			self::is($prefix . '.publish_down', $nullDate),
			self::is($prefix . '.publish_down', '>' . $nowDate),
		], 'OR');

		return self::combine($wheres, 'AND');
	}

	/**
	 * @return  string
	 */
	public static function getNowDate()
	{
		return JFactory::getDate()->toSql();
	}

	/**
	 * @return  string
	 */
	public static function getNullDate()
	{
		return JFactory::getDbo()->getNullDate();
	}

	/**
	 * Creates a WHERE string that handles strings and arrays and deals with wildcards (=> LIKE)
	 *
	 * @param string|array $keys
	 * @param string|array $values
	 * @param array|object $options
	 *
	 * @return string
	 */
	public static function is($keys, $values, $options = [])
	{
		$options = (object) ArrayHelper::toArray($options);

		$glue             = $options->glue ?? 'OR';
		$handle_wildcards = $options->handle_wildcards ?? true;

		if (is_array($keys) && $glue == 'OR')
		{
			$wheres = [];
			foreach ($keys as $single_key)
			{
				$wheres[] = self::is($single_key, $values, $options);
			}

			return self::combine($wheres, $glue);
		}

		if (is_array($keys) && $glue == 'AND')
		{
			$options->glue = 'OR';
			$wheres        = [];
			foreach ($values as $single_values)
			{
				$wheres[] = self::is($keys, $single_values, $options);
			}

			return self::combine($wheres, $glue);
		}

		$db_key = self::quoteName($keys);

		if (
			! is_array($values)
			&& $handle_wildcards
			&& strpos($values, '*') !== false
		)
		{
			return self::like($db_key, $values, $options);
		}

		if ( ! is_array($values))
		{
			return self::in($db_key, $values, $options);
		}

		$includes = [];
		$excludes = [];
		$wheres   = [];

		foreach ($values as $value)
		{
			if ($handle_wildcards && strpos($value, '*') !== false)
			{
				$wheres[] = self::is($keys, $value, $options);
				continue;
			}
			if (self::isExclude($value))
			{
				$excludes[] = $value;
				continue;
			}

			$includes[] = $value;
		}

		if ( ! empty($includes))
		{
			$wheres[] = self::in($db_key, $includes, $options);
		}

		if ( ! empty($excludes))
		{
			$wheres[] = self::in($db_key, $excludes, $options);
		}

		if (empty($wheres))
		{
			return '0';
		}

		if (count($wheres) == 1)
		{
			return reset($wheres);
		}

		return self::combine($wheres, $glue);
	}

	/**
	 * Concatenate conditions using AND or OR
	 *
	 * @param string $glue
	 * @param array  $conditions
	 *
	 * @return string
	 */
	public static function combine($conditions = [], $glue = 'OR')
	{
		if (empty($conditions))
		{
			return '';
		}

		if ( ! is_array($conditions))
		{
			return (string) $conditions;
		}

		if (count($conditions) < 2)
		{
			return reset($conditions);
		}

		$glue = strtoupper($glue) == 'AND' ? 'AND' : 'OR';

		return '(' . implode(' ' . $glue . ' ', $conditions) . ')';
	}

	/**
	 * @param array|string $name
	 * @param array|string $as
	 *
	 * @return  array|string
	 */
	public static function quoteName($name, $as = null)
	{
		return JFactory::getDbo()->quoteName($name, $as);
	}

	/**
	 * Create an LIKE statement
	 *
	 * @param string       $key
	 * @param string|array $value
	 * @param array|object $options
	 *
	 * @return string
	 */
	public static function like($key, $value, $options = [])
	{
		$array = ArrayHelper::applyMethodToValues([$key, $value, $options], '', '', 1);
		if ( ! is_null($array))
		{
			return $array;
		}

		$options = (object) ArrayHelper::toArray($options);

		$operator = self::getOperator($value);

		$value = self::removeOperator($value);
		$value = self::prepareValue($value, $options);
		$value = str_replace('*', '%', $value);

		$operator = $operator == '!=' ? 'NOT LIKE' : 'LIKE';

		return 'LOWER(' . $key . ') ' . $operator . ' LOWER(' . $value . ')';
	}

	/**
	 * Create an IN statement
	 * Reverts to a simple equals statement if array just has 1 value
	 *
	 * @param string|array $keys
	 * @param string|array $values
	 * @param array|object $options
	 *
	 * @return string
	 */
	public static function in($keys, $values, $options = [])
	{
		$options = (object) ArrayHelper::toArray($options);
		$glue    = $options->glue ?? 'OR';

		if (is_array($keys))
		{
			$wheres = [];
			foreach ($keys as $single_key)
			{
				$wheres[] = self::in($single_key, $values, $options);
			}

			return self::combine($wheres, $glue);
		}

		if (empty($values) && ! is_array($values))
		{
			return $keys . ' = 0';
		}

		$operator = self::getOperator($values);

		if ( ! is_array($values) || count($values) == 1)
		{
			$values = self::removeOperator($values);
			$value  = is_array($values) ? reset($values) : $values;
			$value  = self::prepareValue($value, $options);

			if ($value === 'NULL')
			{
				$operator = $operator == '!=' ? 'IS NOT' : 'IS';
			}

			return $keys . ' ' . $operator . ' ' . $value;
		}

		$values   = ArrayHelper::clean($values);
		$operator = $operator == '!=' ? 'NOT IN' : 'IN';

		if ($glue == 'OR')
		{
			$values = self::removeOperator($values);
			$values = self::prepareValue($values, $options);

			return $keys . ' ' . $operator . ' (' . implode(',', $values) . ')';
		}

		$wheres = [];

		foreach ($values as $value)
		{
			$wheres[] = self::in($keys, $value, $options);
		}

		return self::combine($wheres, $glue);
	}

	public static function isExclude($string)
	{
		return in_array(self::getOperator($string), ['!=', '<>'], true);
	}

	public static function getOperator($value, $default = '=')
	{
		if (empty($value))
		{
			return $default;
		}

		if (is_array($value))
		{
			$value = array_values($value);

			return self::getOperator(reset($value), $default);
		}

		$regex = '^' . RegEx::quote(self::getOperators(), 'operator');

		if ( ! RegEx::match($regex, $value, $parts))
		{
			return $default;
		}

		$operator = $parts['operator'];

		switch ($operator)
		{
			case '!':
			case '<>':
			case '!NOT!':
				$operator = '!=';
				break;

			case '==':
				$operator = '=';
				break;

			default:
				break;
		}

		return $operator;
	}

	public static function removeOperator($string)
	{
		$array = ArrayHelper::applyMethodToValues([$string]);
		if ( ! is_null($array))
		{
			return $array;
		}

		$regex = '^' . RegEx::quote(self::getOperators(), 'operator');

		return RegEx::replace($regex, '', $string);
	}

	public static function prepareValue($value, $options = [])
	{
		$array = ArrayHelper::applyMethodToValues([$value, $options]);
		if ( ! is_null($array))
		{
			return $array;
		}

		if ( ! is_array($value) && $value === 'NULL')
		{
			return $value;
		}

		$options = (object) ArrayHelper::toArray($options);

		$handle_now = $options->handle_now ?? true;

		$dates = ['now', 'now()', 'date()', 'jfactory::getdate()'];

		if ($handle_now && ! is_array($value) && in_array(strtolower($value), $dates, true))
		{
			return 'NOW()';
		}

		if (is_int($value) || ctype_digit($value))
		{
			return $value;
		}

		$value = self::quote($value);

		return $value;
	}

	public static function getOperators()
	{
		return ['!NOT!', '!=', '!', '<>', '<=', '<', '>=', '>', '=', '=='];
	}

	/**
	 * @param array|string $text
	 * @param boolean      $escape
	 *
	 * @return  string  The quoted input string.
	 */
	public static function quote($text, $escape = true)
	{
		$array = ArrayHelper::applyMethodToValues([$text, $escape]);
		if ( ! is_null($array))
		{
			return $array;
		}

		if (is_null($text))
		{
			return 'NULL';
		}

		return JFactory::getDbo()->quote($text, $escape);
	}

	/**
	 * Creat a query dump string
	 *
	 * @param string|JQueryInterface $query
	 * @param string                 $class_prefix
	 * @param string                 $caller_offset
	 */
	public static function dump($query, $class_prefix = '', $caller_offset = 0)
	{
		$string = "\n" . (string) $query;
		$string = str_replace('#__', JFactory::getDbo()->getPrefix(), $string);

		Protect::protectByRegex($string, ' IN \(.*?\)');
		Protect::protectByRegex($string, ' FIELD\(.*?\)');

		$string = preg_replace('#(\n[A-Z][A-Z ]+) #', "\n\\1\n       ", $string);
		$string = str_replace(' LIMIT ', "\n\nLIMIT ", $string);
		$string = str_replace(' ON ', "\n    ON ", $string);
		$string = str_replace(' OR ', "\n    OR ", $string);
		$string = str_replace(' AND ', "\n   AND ", $string);
		$string = str_replace('`,', "`,\n       ", $string);

		Protect::unprotect($string);

		echo "\n<pre>==============================================================================\n";
		echo self::getQueryComment($class_prefix, $caller_offset) . "\n";
		echo "-----------------------------------------------------------------------------------\n";
		echo trim($string);
		echo "\n===================================================================================</pre>\n";
	}

	public static function getQueryComment($class_prefix = '', $caller_offset = 0)
	{
		$callers = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $caller_offset + 5);

		for ($i = 1; $i <= ($caller_offset + 2); $i++)
		{
			array_shift($callers);
		}

		$callers = array_reverse($callers);

		$lines = [
			JUri::getInstance()->toString(),
		];

		foreach ($callers as $caller)
		{
			$lines[] = '[' . str_pad($caller['line'] ?? '', 3, ' ', STR_PAD_LEFT) . '] '
				. str_replace(
					'\\',
					'.',
					trim(substr($caller['class'] ?? '', strlen($class_prefix)), '\\')
				)
				. '.' . $caller['function'];
		}

		return implode("\n", $lines);
	}

	/**
	 * @return  JDatabaseDriver
	 */
	public static function get()
	{
		return JFactory::getDbo();
	}

	public static function getIncludesExcludes($values, $remove_exclude_operators = true)
	{
		$includes = [];
		$excludes = [];

		$values = ArrayHelper::toArray($values);

		if (empty($values))
		{
			return [$includes, $excludes];
		}

		foreach ($values as $value)
		{
			if ($value == '')
			{
				$value = '!*';
			}

			if ($value == '!')
			{
				$value = '+';
			}

			if (self::isExclude($value))
			{
				$excludes[] = $remove_exclude_operators
					? self::removeOperator($value)
					: $value;
				continue;
			}

			$includes[] = $value;
		}

		return [$includes, $excludes];
	}

	/**
	 * @return  JDatabaseQuery
	 */
	public static function getQuery()
	{
		return JFactory::getDbo()->getQuery(true);
	}

	/**
	 * @param string  $table    The name of the database table.
	 * @param boolean $typeOnly True (default) to only return field types.
	 *
	 * @return  array
	 */
	public static function getTableColumns($table, $typeOnly = true)
	{
		$cache = new Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		return $cache->set(JFactory::getDbo()->getTableColumns($table, $typeOnly));
	}

	/**
	 * Creates a WHERE string that handles strings and arrays and deals with wildcards (=> LIKE)
	 *
	 * @param string|array $key
	 * @param string|array $value
	 * @param array|object $options
	 *
	 * @return string
	 */
	public static function isNot($key, $value, $options = [])
	{
		if (is_array($key))
		{
			$wheres = [];
			foreach ($key as $single_key)
			{
				$wheres[] = self::isNot($single_key, $value, $options);
			}

			return self::combine($wheres, 'AND');
		}

		$values = $value;

		if ( ! is_array($values))
		{
			$values = [$values];
		}

		foreach ($values as $i => $value)
		{
			$operator = self::isExclude($value) ? '=' : '!=';

			$values[$i] = $operator . self::removeOperator($value);
		}

		return self::is($key, $values, $options);
	}

	/**
	 * Check if a table exists in the database
	 *
	 * @param string $table
	 *
	 * @return bool
	 */
	public static function tableExists($table)
	{
		if (isset(self::$tables[$table]))
		{
			return self::$tables[$table];
		}

		$db = JFactory::getDbo();

		if (strpos($table, '#__') === 0)
		{
			$table = $db->getPrefix() . substr($table, 3);
		}

		if (strpos($table, $db->getPrefix()) !== 0)
		{
			$table = $db->getPrefix() . $table;
		}

		$query = 'SHOW TABLES LIKE ' . $db->quote($table);
		$db->setQuery($query);
		$result = $db->loadResult();

		self::$tables[$table] = ! empty($result);

		return self::$tables[$table];
	}
}

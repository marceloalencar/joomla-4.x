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

class RegEx
{
	/**
	 * Perform a regular expression match
	 *
	 * @param string $pattern
	 * @param string $string
	 * @param null   $match
	 * @param string $options
	 * @param int    $flags
	 *
	 * @return int
	 */
	public static function match($pattern, $string, &$match = null, $options = null, $flags = 0)
	{
		if (
			( ! is_string($pattern) && ! is_numeric($pattern))
			|| $pattern === ''
			|| ( ! is_string($string) && ! is_numeric($string))
			|| $string === ''
		)
		{
			return false;
		}

		$pattern = self::preparePattern($pattern, $options, $string);

		$result = preg_match($pattern, $string, $match, $flags);

		// Remove all numeric keys except 0
		$no_numeric_values = array_filter(
			$match,
			function ($key) {
				return ! is_int($key) || $key === 0;
			},
			ARRAY_FILTER_USE_KEY);

		// If the left over array counts more than 2 (so contains named groups), replace $match
		if (count($no_numeric_values) > 1)
		{
			$match = $no_numeric_values;
		}

		return $result;
	}

	/**
	 * Make a string a valid regular expression pattern
	 *
	 * @param string $pattern
	 * @param string $options
	 * @param string $string
	 *
	 * @return string
	 */
	public static function preparePattern($pattern, $options = null, $string = '')
	{
		$array = ArrayHelper::applyMethodToValues([$pattern, $options, $string]);

		if ( ! is_null($array))
		{
			return $array;
		}

		if (substr($pattern, 0, 1) != '#')
		{
			$options = ! is_null($options) ? $options : 'si';
			$pattern = '#' . $pattern . '#' . $options;
		}

		if (StringHelper::detectUTF8($string))
		{
			// use utf-8
			return $pattern . 'u';
		}

		return $pattern;
	}

	/**
	 * Perform a global regular expression match
	 *
	 * @param string $pattern
	 * @param string $string
	 * @param null   $matches
	 * @param string $options
	 * @param int    $flags
	 *
	 * @return int
	 */
	public static function matchAll($pattern, $string, &$matches = null, $options = null, $flags = PREG_SET_ORDER)
	{
		if (
			( ! is_string($pattern) && ! is_numeric($pattern))
			|| $pattern === ''
			|| ( ! is_string($string) && ! is_numeric($string))
			|| $string === ''
		)
		{
			$matches = [];

			return false;
		}

		$pattern = self::preparePattern($pattern, $options, $string);

		$result = preg_match_all($pattern, $string, $matches, $flags);

		if ( ! $result)
		{
			return false;
		}

		if ($flags != PREG_SET_ORDER)
		{
			return $result;
		}

		foreach ($matches as &$match)
		{
			// Remove all numeric keys except 0
			$no_numeric_values = array_filter(
				$match,
				function ($key) {
					return ! is_int($key) || $key === 0;
				},
				ARRAY_FILTER_USE_KEY);

			// If the left over array counts less than 2 (so no named groups), don't continue
			if (count($no_numeric_values) < 2)
			{
				break;
			}

			$match = $no_numeric_values;
		}

		return $result;
	}

	/**
	 * preg_quote the given string or array of strings
	 *
	 * @param string|array $data
	 * @param string       $name
	 * @param string       $delimiter
	 *
	 * @return string
	 */
	public static function nameGroup($data, $name = '')
	{
		return '(?<' . $name . '>' . $data . ')';
	}

	/**
	 * preg_quote the given string or array of strings
	 *
	 * @param string|array $data
	 * @param string       $name
	 * @param string       $delimiter
	 *
	 * @return string
	 */
	public static function quote($data, $name = '', $delimiter = '#')
	{
		if (is_array($data))
		{
			if (count($data) === 1)
			{
				return self::quote(array_pop($data), $name, $delimiter);
			}

			$array = self::quoteArray($data, $delimiter);

			$prefix = '?:';

			if ( ! empty($name))
			{
				$prefix = $name ? '?<' . $name . '>' : '';
			}

			return '(' . $prefix . implode('|', $array) . ')';
		}

		if ( ! empty($name))
		{
			return '(?<' . $name . '>' . preg_quote($data, $delimiter) . ')';
		}

		return preg_quote($data, $delimiter);
	}

	/**
	 * preg_quote the given array of strings
	 *
	 * @param array  $array
	 * @param string $delimiter
	 *
	 * @return array
	 */
	public static function quoteArray($array = [], $delimiter = '#')
	{
		array_walk($array, function (&$part, $key, $delimiter) {
			$part = self::quote($part, '', $delimiter);
		}, $delimiter);

		return $array;
	}

	/**
	 * Perform a regular expression search and replace once
	 *
	 * @param string $pattern
	 * @param string $replacement
	 * @param string $string
	 * @param string $options
	 *
	 * @return string
	 */
	public static function replaceOnce($pattern, $replacement, $string, $options = null)
	{
		return self::replace($pattern, $replacement, $string, $options, 1);
	}

	/**
	 * Perform a regular expression search and replace
	 *
	 * @param string $pattern
	 * @param string $replacement
	 * @param string $string
	 * @param string $options
	 * @param int    $limit
	 * @param int    $count
	 *
	 * @return string
	 */
	public static function replace($pattern, $replacement, $string, $options = null, $limit = -1, &$count = null)
	{
		if ( ! is_string($pattern) || $pattern == '' || ! is_string($string) || $string == '')
		{
			return $string;
		}

		$pattern = self::preparePattern($pattern, $options, $string);

		return preg_replace($pattern, $replacement, $string, $limit, $count);
	}

	/**
	 * reverse preg_quote the given string
	 *
	 * @param string $string
	 * @param string $delimiter
	 *
	 * @return string
	 */
	public static function unquote($string, $delimiter = '#')
	{
		return strtr($string, [
			'\\' . $delimiter => $delimiter,
			'\\.'             => '.',
			'\\\\'            => '\\',
			'\\+'             => '+',
			'\\*'             => '*',
			'\\?'             => '?',
			'\\['             => '[',
			'\\^'             => '^',
			'\\]'             => ']',
			'\\$'             => '$',
			'\\('             => '(',
			'\\)'             => ')',
			'\\{'             => '{',
			'\\}'             => '}',
			'\\='             => '=',
			'\\!'             => '!',
			'\\<'             => '<',
			'\\>'             => '>',
			'\\|'             => '|',
			'\\:'             => ':',
			'\\-'             => '-',
		]);
	}
}

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

use Joomla\String\Normalise as JNormalise;
use Normalizer;

class StringHelper extends \Joomla\String\StringHelper
{
	/**
	 * Adds postfix to a string
	 *
	 * @param string $string
	 * @param string $postfix
	 *
	 * @return string
	 */
	public static function addPostfix($string, $postfix)
	{
		$array = ArrayHelper::applyMethodToValues([$string, $postfix]);

		if ( ! is_null($array))
		{
			return $array;
		}

		if (empty($postfix))
		{
			return $string;
		}

		if ( ! is_string($string) && ! is_numeric($string))
		{
			return $string;
		}

		return $string . $postfix;
	}

	/**
	 * Adds prefix to a string
	 *
	 * @param string $string
	 * @param string $prefix
	 * @param bool   $keep_leading_slash
	 *
	 * @return string
	 */
	public static function addPrefix($string, $prefix, $keep_leading_slash = true)
	{
		$array = ArrayHelper::applyMethodToValues([$string, $prefix, $keep_leading_slash]);

		if ( ! is_null($array))
		{
			return $array;
		}

		if (empty($prefix))
		{
			return $string;
		}

		if ( ! is_string($string) && ! is_numeric($string))
		{
			return $string;
		}

		if ($keep_leading_slash && ! empty($string) && $string[0] == '/')
		{

			return $string[0] . $prefix . substr($string, 1);
		}

		return $prefix . $string;
	}

	/**
	 * @param string $type
	 * @param string $string
	 * @param object $attributes
	 *
	 * @return string
	 */
	public static function applyConversion($type, $string, $attributes = null)
	{
		switch ($type)
		{
			case 'escape':
				return addslashes($string);

			case 'lowercase':
				return self::toLowerCase($string);

			case 'uppercase':
				return self::toUpperCase($string);

			case 'notags':
				return strip_tags($string);

			case 'nowhitespace':
				return str_replace(' ', '', strip_tags($string));

			case 'toalias':
				return Alias::get($string);

			case 'replace':
				return isset($attributes->from)
					? RegEx::replace($attributes->from, $attributes->to ?? '', $string)
					: $string;

			default:
				return $string;
		}
	}

	/**
	 * Converts a string to a lower case
	 * eg: FooBar => foobar
	 * eg: foo_bar => foo_bar
	 *
	 * @param string|array|object $string
	 *
	 * @return string|array
	 */
	public static function toLowerCase($string)
	{
		$array = ArrayHelper::applyMethodToValues([$string]);

		if ( ! is_string($string))
		{
			return $array;
		}

		return self::strtolower($string);
	}

	/**
	 * Converts a string to a lower case
	 * eg: FooBar => FOOBAR
	 * eg: foo_bar => FOO_BAR
	 *
	 * @param string|array|object $string
	 *
	 * @return string|array
	 */
	public static function toUpperCase($string)
	{
		$array = ArrayHelper::applyMethodToValues([$string]);

		if ( ! is_string($string))
		{
			return $array;
		}

		return self::strtoupper($string);
	}

	/**
	 * Converts a string to a UTF-8 encoded string
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function convertToUtf8($string = '')
	{
		$array = ArrayHelper::applyMethodToValues([$string]);

		if ( ! is_null($array))
		{
			return $array;
		}

		if (self::detectUTF8($string))
		{
			// Already UTF-8, so skip
			return $string;
		}

		if ( ! function_exists('iconv'))
		{
			// Still need to find a stable fallback
			return $string;
		}

		$utf8_string = @iconv('UTF8', 'UTF-8//IGNORE', $string);

		if (empty($utf8_string))
		{
			return $string;
		}

		return $utf8_string;
	}

	/**
	 * Check whether string is a UTF-8 encoded string
	 *
	 * @param string $string
	 *
	 * @return bool
	 */
	public static function detectUTF8($string = '')
	{
		// Try to check the string via the mb_check_encoding function
		if (function_exists('mb_check_encoding'))
		{
			return mb_check_encoding($string, 'UTF-8');
		}

		// Otherwise: Try to check the string via the iconv function
		if (function_exists('iconv'))
		{
			$converted = iconv('UTF-8', 'UTF-8//IGNORE', $string);

			return (md5($converted) == md5($string));
		}

		// As last fallback, check if the preg_match finds anything using the unicode flag
		return preg_match('#.#u', $string);
	}

	/**
	 * @param string $string
	 * @param int    $format
	 *
	 * @return array|int
	 */
	public static function countWords(string $string, int $format = 0)
	{
		$words = preg_split('#[^\p{L}\p{N}\']+#u', $string, -1, $format == 2 ? PREG_SPLIT_OFFSET_CAPTURE : null);

		switch ($format)
		{
			case 1:
				return $words;

			case 2:
				$numbered = [];
				foreach ($words as $word)
				{
					$numbered[$word[1]] = $word[0];
				}

				return $numbered;

			case 0:
			default:
				return count($words);
		}
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public static function escape($string)
	{
		return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Decode html entities in string (array of strings)
	 *
	 * @param string $string
	 * @param int    $quote_style
	 * @param string $encoding
	 *
	 * @return array|string
	 */
	public static function html_entity_decoder($string, $quote_style = ENT_QUOTES, $encoding = 'UTF-8')
	{
		$array = ArrayHelper::applyMethodToValues([$string, $quote_style, $encoding]);

		if ( ! is_null($array))
		{
			return $array;
		}

		if ( ! is_string($string))
		{
			return $string;
		}

		$string = html_entity_decode($string, $quote_style | ENT_HTML5, $encoding);
		$string = str_replace(chr(194) . chr(160), ' ', $string);

		return $string;
	}

	/**
	 * Check if string is alphanumerical
	 *
	 * @param string $string
	 *
	 * @return bool
	 */
	public static function is_alphanumeric($string)
	{
		if (function_exists('ctype_alnum'))
		{
			return (bool) ctype_alnum($string);
		}

		return (bool) RegEx::match('^[a-z0-9]+$', $string);
	}

	/**
	 * Check if string is a valid key / alias (alphanumeric with optional _ or - chars)
	 *
	 * @param string $string
	 *
	 * @return bool
	 */
	public static function is_key($string)
	{
		return RegEx::match('^[a-z][a-z0-9-_]*$', trim($string));
	}

	/**
	 * Normalizes the input provided and returns the normalized string
	 *
	 * @param string $string
	 * @param bool   $to_lowercase
	 *
	 * @return string
	 */
	public static function normalize($string, $to_lowercase = false)
	{
		$array = ArrayHelper::applyMethodToValues([$string, $to_lowercase]);

		if ( ! is_null($array))
		{
			return $array;
		}

		// Normalizer-class missing!
		if (class_exists('Normalizer', false))
		{
			$string = Normalizer::normalize($string);
		}

		return $to_lowercase ? self::toLowerCase($string) : $string;
	}

	/**
	 * Removes html tags from string
	 *
	 * @param string $string
	 * @param bool   $remove_comments
	 *
	 * @return string
	 */
	public static function removeHtml($string, $remove_comments = false)
	{
		$array = ArrayHelper::applyMethodToValues([$string, $remove_comments]);

		if ( ! is_null($array))
		{
			return $array;
		}

		return Html::removeHtmlTags($string, $remove_comments);
	}

	/**
	 * Removes the trailing part of a string if it matches the given $postfix
	 *
	 * @param string $string
	 * @param string $postfix
	 *
	 * @return string
	 */
	public static function removePostfix($string, $postfix)
	{
		$array = ArrayHelper::applyMethodToValues([$string, $postfix]);

		if ( ! is_null($array))
		{
			return $array;
		}

		if (empty($string) || empty($postfix))
		{
			return $string;
		}

		if ( ! is_string($string) && ! is_numeric($string))
		{
			return $string;
		}

		$string_length  = strlen($string);
		$postfix_length = strlen($postfix);
		$start          = $string_length - $postfix_length;

		if (substr($string, $start) !== $postfix)
		{
			return $string;
		}

		return substr($string, 0, $start);
	}

	/**
	 * Removes the first part of a string if it matches the given $prefix
	 *
	 * @param string $string
	 * @param string $prefix
	 * @param bool   $keep_leading_slash
	 *
	 * @return string
	 */
	public static function removePrefix($string, $prefix, $keep_leading_slash = true)
	{
		$array = ArrayHelper::applyMethodToValues([$string, $prefix, $keep_leading_slash]);

		if ( ! is_null($array))
		{
			return $array;
		}

		if (empty($string) || empty($prefix))
		{
			return $string;
		}

		if ( ! is_string($string) && ! is_numeric($string))
		{
			return $string;
		}

		$prefix_length = strlen($prefix);
		$start         = 0;

		if ($keep_leading_slash
			&& $prefix[0] !== '/'
			&& $string[0] == '/'
		)
		{
			$start = 1;
		}

		if (substr($string, $start, $prefix_length) !== $prefix)
		{
			return $string;
		}

		return substr($string, 0, $start)
			. substr($string, $start + $prefix_length);
	}

	/**
	 * Replace the given replace string once in the main string
	 *
	 * @param string $search
	 * @param string $replace
	 * @param string $string
	 *
	 * @return string
	 */
	public static function replaceOnce($search, $replace, $string)
	{
		if (empty($search) || empty($string))
		{
			return $string;
		}

		$pos = strpos($string, $search);

		if ($pos === false)
		{
			return $string;
		}

		return substr_replace($string, $replace, $pos, strlen($search));
	}

	/**
	 * Split a long string into parts (array)
	 *
	 * @param string $string
	 * @param array  $delimiters     Array of strings to split the string on
	 * @param int    $max_length     Maximum length of each part
	 * @param bool   $maximize_parts If true, the different parts will be made as large as possible (combining consecutive short string elements)
	 *
	 * @return array
	 */
	public static function split($string, $delimiters = [], $max_length = 10000, $maximize_parts = true)
	{
		// String is too short to split
		if (strlen($string) < $max_length)
		{
			return [$string];
		}

		// No delimiters given or found
		if (empty($delimiters) || ! self::contains($string, $delimiters))
		{
			return [$string];
		}

		// preg_quote all delimiters
		$array = preg_split('#(' . RegEx::quote($delimiters) . ')#s', $string, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		if ( ! $maximize_parts)
		{
			return $array;
		}

		$new_array = [];
		foreach ($array as $i => $part)
		{
			// First element, add to new array
			if ( ! count($new_array))
			{
				$new_array[] = $part;
				continue;
			}

			$last_part = end($new_array);
			$last_key  = key($new_array);

			// This is the delimiter so add to previous part
			if ($i % 2)
			{
				// Concatenate part to previous part
				$new_array[$last_key] .= $part;
				continue;
			}

			// If last and current parts are shorter than or same as  max_length, then add to previous part
			if (strlen($last_part) + strlen($part) <= $max_length)
			{
				$new_array[$last_key] .= $part;
				continue;
			}

			$new_array[] = $part;
		}

		return $new_array;
	}

	/**
	 * Check if any of the needles are found in any of the haystacks
	 *
	 * @param $haystacks
	 * @param $needles
	 *
	 * @return bool
	 */
	public static function contains($haystacks, $needles)
	{
		$haystacks = (array) $haystacks;
		$needles   = (array) $needles;

		foreach ($haystacks as $haystack)
		{
			foreach ($needles as $needle)
			{
				if (strpos($haystack, $needle) !== false)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Converts a string to a certain case
	 *
	 * @param string $string
	 * @param string $format ('camel',  'dash', 'dot', 'underscore')
	 * @param bool   $to_lowercase
	 *
	 * @return string
	 */
	public static function toCase($string, $format, $to_lowercase = true)
	{
		$format = strtolower(str_replace('case', '', $format));

		switch ($format)
		{
			case 'lower':
				return self::toLowerCase($string);

			case 'upper':
				return self::toUpperCase($string);

			case 'lcfirst':
			case 'lower-first':
				return self::lowerCaseFirst($string);

			case 'ucfirst':
			case 'upper-first':
				return self::upperCaseFirst($string);

			case 'title':
				return self::toTitleCase($string);

			case 'camel':
				return self::toCamelCase($string);

			case 'dash':
				return self::toDashCase($string, $to_lowercase);

			case 'dot':
				return self::toDotCase($string, $to_lowercase);

			case 'pascal':
				return self::toPascalCase($string);

			case 'underscore':
				return self::toUnderscoreCase($string, $to_lowercase);

			default:
				return $to_lowercase ? self::toLowerCase($string) : $string;
		}
	}

	/**
	 * Converts the first letter to lowercase
	 * eg: FooBar => fooBar
	 * eg: Foo bar => foo bar
	 * eg: FOO_BAR => fOO_BAR
	 *
	 * @param string|array|object $string
	 *
	 * @return string|array
	 */
	public static function lowerCaseFirst($string)
	{
		$array = ArrayHelper::applyMethodToValues([$string]);

		if ( ! is_string($string))
		{
			return $array;
		}

		return self::lcfirst($string);
	}

	/**
	 * Converts the first letter to uppercase
	 * eg: fooBar => FooBar
	 * eg: foo bar => Foo bar
	 * eg: foo_bar => Foo_bar
	 *
	 * @param string|array|object $string
	 *
	 * @return string|array
	 */
	public static function upperCaseFirst($string)
	{
		$array = ArrayHelper::applyMethodToValues([$string]);

		if ( ! is_string($string))
		{
			return $array;
		}

		return self::ucfirst($string);
	}

	/**
	 * Converts a string to a camel case
	 * eg: foo bar => Foo Bar
	 * eg: foo_bar => Foo Bar
	 * eg: foo-bar => Foo Bar
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function toTitleCase($string, $keep_duplicate_separators = true)
	{
		$array = ArrayHelper::applyMethodToValues([$string]);

		if ( ! is_null($array))
		{
			return $array;
		}

		return self::ucwords(self::toSpaceSeparated($string, $keep_duplicate_separators));
	}

	/**
	 * Converts a string to a camel case
	 * eg: foo bar => fooBar
	 * eg: foo_bar => fooBar
	 * eg: foo-bar => fooBar
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function toCamelCase($string, $keep_duplicate_separators = true)
	{
		$array = ArrayHelper::applyMethodToValues([$string]);

		if ( ! is_null($array))
		{
			return $array;
		}

		if (empty($string))
		{
			return $string;
		}

		return JNormalise::toVariable(self::toSpaceSeparated($string, $keep_duplicate_separators));
	}

	/**
	 * Converts a string to a camel case
	 * eg: FooBar => foo-bar
	 * eg: foo_bar => foo-bar
	 *
	 * @param string|array|object $string
	 * @param bool                $to_lowercase
	 *
	 * @return string|array
	 */
	public static function toDashCase($string, $to_lowercase = true, $keep_duplicate_separators = true)
	{
		$array = ArrayHelper::applyMethodToValues([$string, $to_lowercase]);

		if ( ! is_string($string))
		{
			return $array;
		}

		$string = preg_replace(self::getSeparatorRegex($keep_duplicate_separators),
			'-',
			self::toSpaceSeparated($string, $keep_duplicate_separators)
		);

		return $to_lowercase ? self::toLowerCase($string) : $string;
	}

	/**
	 * Converts a string to a camel case
	 * eg: FooBar => foo.bar
	 * eg: foo_bar => foo.bar
	 *
	 * @param string|array|object $string
	 * @param bool                $to_lowercase
	 *
	 * @return string|array
	 */
	public static function toDotCase($string, $to_lowercase = true)
	{
		$array = ArrayHelper::applyMethodToValues([$string, $to_lowercase]);

		if ( ! is_string($string))
		{
			return $array;
		}

		$string = self::toDashCase($string, $to_lowercase);

		return str_replace('-', '.', $string);
	}

	/**
	 * Converts a string to a camel case
	 * eg: foo bar => FooBar
	 * eg: foo_bar => FooBar
	 * eg: foo-bar => FooBar
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function toPascalCase($string, $keep_duplicate_separators = true)
	{
		$array = ArrayHelper::applyMethodToValues([$string]);

		if ( ! is_null($array))
		{
			return $array;
		}

		return JNormalise::toCamelCase(self::toSpaceSeparated($string, $keep_duplicate_separators));
	}

	/**
	 * Converts a string to a underscore separated string
	 * eg: FooBar => foo_bar
	 * eg: foo-bar => foo_bar
	 *
	 * @param string $string
	 * @param bool   $to_lowercase
	 *
	 * @return string
	 */
	public static function toUnderscoreCase($string, $to_lowercase = true, $keep_duplicate_separators = true)
	{
		$array = ArrayHelper::applyMethodToValues([$string, $to_lowercase]);

		if ( ! is_null($array))
		{
			return $array;
		}

		$string = preg_replace(self::getSeparatorRegex($keep_duplicate_separators),
			'_',
			self::toSpaceSeparated($string, $keep_duplicate_separators)
		);

		return $to_lowercase ? self::toLowerCase($string) : $string;
	}

	/**
	 * UTF-8 aware alternative to lcfirst
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function lcfirst($string)
	{
		switch (utf8_strlen($string))
		{
			case 0:
				return '';
			case 1:
				return utf8_strtolower($string);
			default:
				preg_match('/^(.{1})(.*)$/us', $string, $matches);

				return utf8_strtolower($matches[1]) . $matches[2];
		}
	}

	/**
	 * Converts a string into space separated form
	 * eg: FooBar => Foo Bar
	 * eg: foo-bar => foo bar
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function toSpaceSeparated($string, $keep_duplicate_separators = true)
	{
		$array = ArrayHelper::applyMethodToValues([$string]);

		if ( ! is_null($array))
		{
			return $array;
		}

		return preg_replace(self::getSeparatorRegex($keep_duplicate_separators),
			' ',
			self::fromCamelCase($string)
		);
	}

	/**
	 * @param bool $keep_duplicate_separators
	 *
	 * @return string
	 */
	private static function getSeparatorRegex($keep_duplicate_separators = true)
	{
		$regex = '[ \-_]';

		if ( ! $keep_duplicate_separators)
		{
			$regex .= '+';
		}

		return '#' . $regex . '#';
	}

	/**
	 * Converts a camelcased string to a space separated string
	 * eg: FooBar => Foo Bar
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function fromCamelCase($string)
	{
		$array = ArrayHelper::applyMethodToValues([$string]);

		if ( ! is_null($array))
		{
			return $array;
		}

		return implode(' ', JNormalise::fromCamelCase($string, true));
	}

	/**
	 * Converts an object or array to a single string
	 *
	 * @param string|array|object $string
	 *
	 * @return string
	 */
	public static function toString($string)
	{
		if (is_string($string))
		{
			return $string;
		}

		foreach ($string as &$part)
		{
			$part = self::toString($part);
		}

		return ArrayHelper::implode((array) $string);
	}

	/**
	 * @param string $string
	 * @param int    $maxlen
	 *
	 * @return string
	 */
	public static function truncate($string, $maxlen)
	{
		if (self::strlen($string) <= $maxlen)
		{
			return $string;
		}

		return self::substr($string, 0, $maxlen - 3) . '…';
	}
}

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

use Joomla\CMS\Factory as JFactory;
use Joomla\Database\DatabaseDriver as JDatabaseDriver;
use Joomla\Database\DatabaseQuery as JDatabaseQuery;
use Joomla\Database\QueryInterface;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Library\DB as RL_DB;

class DB extends RL_DB
{
	/**
	 * @var JDatabaseDriver
	 */
	private static $db;
	private static $name = '';
	private static $query_cache_time;

	/**
	 * @param string|QueryInterface $query
	 * @param string                $return_type
	 * @param bool                  $use_local_db
	 * @param bool                  $allow_caching
	 *
	 * @return mixed
	 */
	public static function getResults($query, $return_type = 'column', $use_local_db = false, $allow_caching = true)
	{
		$query_cache_id = '';
		$params         = Params::get();

		if ($allow_caching)
		{
			$force_caching = $params->use_query_cache === 2;

			$query_cache_id = [__METHOD__, self::$name, $return_type, $use_local_db, (string) $query];

			$cache = (new RL_Cache($query_cache_id))
				->useFiles(
					self::getQueryTime(),
					$force_caching
				);
		}

		if ($allow_caching && $cache->exists())
		{
			return $cache->get();
		}

		$db     = self::get([], $use_local_db);
		$method = 'load' . ucfirst($return_type);

		$use_query_log_cache = $allow_caching && $params->use_query_comments && $params->use_query_log_cache;

		if (JDEBUG || $params->use_query_comments)
		{
			$backtrace = self::getQueryComment();
		}

		if ($use_query_log_cache)
		{
			$query_cache = ''
				. "\n\n" . 'QUERY:' . "\n==========\n" . trim((string) $query)
				. "\n\n" . 'METHOD: ' . "\n==========\n" . $method
				. "\n\n" . 'BACKTRACE:' . "\n==========\n" . str_replace(' => ', "\n", $backtrace)
				. "\n\n";
		}

		if (JDEBUG || $params->use_query_comments)
		{
			$query->select(
				$db->quote($backtrace) . ' as ' . $db->quote('query_comment')
			);
		}


		$result = $db->setQuery($query)->$method();

		if ( ! $allow_caching)
		{
			return $result;
		}

		if ($use_query_log_cache)
		{
			(new RL_Cache($query_cache_id, 'regularlabs_query'))
				->useFiles(
					self::getQueryTime() * 60,
					true
				)
				->set($query_cache);
		}

		return $cache->set($result);
	}

	private static function getQueryTime()
	{
		if ( ! is_null(self::$query_cache_time))
		{
			return self::$query_cache_time;
		}

		self::$query_cache_time = (int) Params::get()->query_cache_time ?: JFactory::getApplication()->get('cachetime');

		return self::$query_cache_time;
	}

	/**
	 * @param array $options
	 * @param bool  $use_local_db
	 *
	 * @return JDatabaseDriver
	 */
	public static function get($options = [], $use_local_db = false)
	{
		if ($use_local_db)
		{
			return JFactory::getDbo();
		}

		if (is_null(self::$db))
		{
			self::set($options);
		}

		return self::$db;
	}

	/**
	 * @param array $options
	 */
	public static function set($options = [], $name = '')
	{
		$cache = new RL_Cache([__METHOD__, $name ?: $options]);

		if ($cache->exists())
		{
			self::$name = $name;
			self::$db   = $cache->get();

			return;
		}

		if (empty($options) || empty($options['user']) || empty($options['password']))
		{
			self::$db = JFactory::getDbo();

			return;
		}

		self::$name = $name;
		self::$db   = JDatabaseDriver::getInstance($options);

		$cache->set(self::$db);
	}

	/**
	 * @return  string
	 */
	public static function getNullDate()
	{
		return self::get()->getNullDate();
	}

	/**
	 * @return  JDatabaseQuery
	 */
	public static function getQuery()
	{
		return self::get()->getQuery(true);
	}

	/**
	 * @param array|string $text
	 * @param boolean      $escape
	 *
	 * @return  string  The quoted input string.
	 */
	public static function quote($text, $escape = true)
	{
		return self::get()->quote($text, $escape);
	}

	/**
	 * @param array|string $name
	 * @param array|string $as
	 *
	 * @return  array|string
	 */
	public static function quoteName($name, $as = null)
	{
		return self::get()->quoteName($name, $as);
	}

	/**
	 * @return bool
	 */
	public static function isExternal()
	{
		return ! empty(self::$name);
	}

	/**
	 * @param string $name
	 */
	public static function setByDatabaseName($name)
	{
		$database = Params::getDatabase($name);

		self::setBySettings($database);
	}

	/**
	 * @param object $settings
	 */
	public static function setBySettings($settings)
	{
		if (empty($settings)
			|| (empty($settings->database) && empty($settings->db))
			|| empty($settings->user)
			|| empty($settings->password))
		{
			self::set();

			return;
		}

		$options = [
			'driver'   => $settings->driver ?? $settings->dbtype,
			'host'     => $settings->host,
			'user'     => $settings->user,
			'password' => $settings->password,
			'database' => $settings->database ?? $settings->db,
			'prefix'   => $settings->prefix ?? $settings->dbprefix ?? '',
		];

		self::set($options);
	}

}

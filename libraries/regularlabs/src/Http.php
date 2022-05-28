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
use Joomla\CMS\Http\HttpFactory as JHttpFactory;
use Joomla\Registry\Registry;
use RuntimeException;

/**
 * Class Http
 * @package RegularLabs\Library
 */
class Http
{
	/**
	 * Get the contents of the given internal url
	 *
	 * @param string $url
	 * @param int    $timeout
	 * @param string $default
	 *
	 * @return string
	 */
	public static function get($url, $timeout = 20, $default = '')
	{
		if (Uri::isExternal($url))
		{
			return $default;
		}

		return @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => $timeout]]))
			|| self::getFromUrl($url, $timeout, $default);
	}

	/**
	 * Get the contents of the given url
	 *
	 * @param string $url
	 * @param int    $timeout
	 * @param string $default
	 *
	 * @return string
	 */
	public static function getFromUrl($url, $timeout = 20, $default = '')
	{
		$cache     = new Cache;
		$cache_ttl = JFactory::getApplication()->input->getInt('cache', 0);

		if ($cache_ttl)
		{
			$cache->useFiles($cache_ttl > 1 ? $cache_ttl : null);
		}

		if ($cache->exists())
		{
			return $cache->get();
		}

		$content = self::getContents($url, $timeout);

		if (empty($content))
		{
			return $default;
		}

		return $cache->set($content ?: $default);
	}

	/**
	 * Load the contents of the given url
	 *
	 * @param string $url
	 * @param int    $timeout
	 * @param string $default
	 *
	 * @return string
	 */
	private static function getContents($url, $timeout = 20, $default = '')
	{
		try
		{
			// Adding a valid user agent string, otherwise some feed-servers returning an error
			$options = new Registry([
				'userAgent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0',
			]);

			$response = JHttpFactory::getHttp($options)->get($url, [], $timeout);

			$content = $response->body ?? $default;
		}
		catch (RuntimeException $e)
		{
			return $default;
		}

		return $content;
	}

	/**
	 * Get the contents of the given external url from the Regular Labs server
	 *
	 * @param string $url
	 * @param int    $timeout
	 * @param string $default
	 *
	 * @return string
	 */
	public static function getFromServer($url, $timeout = 20, $default = '')
	{
		$cache     = new Cache;
		$cache_ttl = JFactory::getApplication()->input->getInt('cache', 0);

		if ($cache_ttl)
		{
			$cache->useFiles($cache_ttl > 1 ? $cache_ttl : null);
		}

		if ($cache->exists())
		{
			return $cache->get();
		}

		// only allow url calls from administrator
		if ( ! Document::isClient('administrator'))
		{
			die;
		}

		// only allow when logged in
		$user = JFactory::getApplication()->getIdentity() ?: JFactory::getUser();
		if ( ! $user->id)
		{
			die;
		}

		if (substr($url, 0, 4) != 'http')
		{
			$url = 'http://' . $url;
		}

		// only allow url calls to regularlabs.com domain
		if ( ! (RegEx::match('^https?://([^/]+\.)?regularlabs\.com/', $url)))
		{
			die;
		}

		// only allow url calls to certain files
		if (
			strpos($url, 'download.regularlabs.com/extensions.php') === false
			&& strpos($url, 'download.regularlabs.com/extensions.json') === false
			&& strpos($url, 'download.regularlabs.com/extensions.xml') === false
			&& strpos($url, 'download.regularlabs.com/check_key.php') === false
		)
		{
			die;
		}

		$content = self::getContents($url, $timeout);

		$format = (strpos($url, '.json') !== false || strpos($url, 'format=json') !== false)
			? 'application/json'
			: 'text/xml';

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-type: " . $format);

		if (empty($content))
		{
			return $default;
		}

		return $cache->set($content ?: $default);
	}

}

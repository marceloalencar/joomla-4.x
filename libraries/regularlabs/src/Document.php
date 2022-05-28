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

use Joomla\CMS\Document\Document as JDocument;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\WebAsset\WebAssetManager as JWebAssetManager;

class Document
{
	/**
	 * Enqueues an admin error
	 *
	 * @param string $message
	 */
	public static function adminError($message)
	{
		self::adminMessage($message, 'error');
	}

	/**
	 * Enqueues an admin message
	 *
	 * @param string $message
	 * @param string $type
	 */
	public static function adminMessage($message, $type = 'message')
	{
		if ( ! self::isAdmin())
		{
			return;
		}

		self::message($message, $type);
	}

	/**
	 * Check if page is an admin page
	 *
	 * @param bool $exclude_login
	 *
	 * @return bool
	 */
	public static function isAdmin($exclude_login = false)
	{
		$cache = new Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		$input = JFactory::getApplication()->input;
		$user  = JFactory::getApplication()->getIdentity() ?: JFactory::getUser();

		$is_admin = (
			self::isClient('administrator')
			&& ( ! $exclude_login || ! $user->get('guest'))
			&& $input->get('task', '') != 'preview'
			&& ! (
				$input->get('option', '') == 'com_finder'
				&& $input->get('format', '') == 'json'
			)
		);

		return $cache->set($is_admin);
	}

	/**
	 * Enqueues a message
	 *
	 * @param string $message
	 * @param string $type
	 */
	public static function message($message, $type = 'message')
	{
		Language::load('plg_system_regularlabs');

		JFactory::getApplication()->enqueueMessage($message, $type);
	}

	/**
	 * Check if page is an edit page
	 *
	 * @return bool
	 */
	public static function isClient($identifier)
	{
		$identifier = $identifier == 'admin' ? 'administrator' : $identifier;

		$cache = new Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		return $cache->set(JFactory::getApplication()->isClient($identifier));
	}

	/**
	 * Enqueues an error
	 *
	 * @param string $message
	 */
	public static function error($message)
	{
		self::message($message, 'error');
	}

	/**
	 * @return null|string
	 */
	public static function getComponentBuffer()
	{
		$buffer = self::get()->getBuffer('component');

		if (empty($buffer) || ! is_string($buffer))
		{
			return null;
		}

		$buffer = trim($buffer);

		if (empty($buffer))
		{
			return null;
		}

		return $buffer;
	}

	/**
	 * @return  JDocument  The document object
	 */
	public static function get()
	{
		$document = JFactory::getApplication()->getDocument();

		if ( ! is_null($document))
		{
			return $document;
		}

		JFactory::getApplication()->loadDocument();

		return JFactory::getApplication()->getDocument();
	}

	/**
	 * Checks if context/page is a category list
	 *
	 * @param string $context
	 *
	 * @return bool
	 */
	public static function isCategoryList($context)
	{
		$cache = new Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		$app   = JFactory::getApplication();
		$input = $app->input;

		// Return false if it is not a category page
		if ($context != 'com_content.category' || $input->get('view', '') != 'category')
		{
			return $cache->set(false);
		}

		// Return false if layout is set and it is not a list layout
		if ($input->get('layout', '') && $input->get('layout', '') != 'list')
		{
			return $cache->set(false);
		}

		// Return false if default layout is set to blog
		if ($app->getParams()->get('category_layout') == '_:blog')
		{
			return $cache->set(false);
		}

		// Return true if it IS a list layout
		return $cache->set(true);
	}

	/**
	 * Check if page is an edit page
	 *
	 * @return bool
	 */
	public static function isEditPage()
	{
		$cache = new Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		$input = JFactory::getApplication()->input;

		$option = $input->get('option', '');

		// always return false for these components
		if (in_array($option, ['com_rsevents', 'com_rseventspro'], true))
		{
			return $cache->set(false);
		}

		$task = $input->get('task', '');

		if (strpos($task, '.') !== false)
		{
			$task = explode('.', $task);
			$task = array_pop($task);
		}

		$view = $input->get('view', '');

		if (strpos($view, '.') !== false)
		{
			$view = explode('.', $view);
			$view = array_pop($view);
		}

		$is_edit_page = (
			in_array($option, ['com_config', 'com_contentsubmit', 'com_cckjseblod'], true)
			|| ($option == 'com_comprofiler' && in_array($task, ['', 'userdetails'], true))
			|| in_array($task, ['edit', 'form', 'submission'], true)
			|| in_array($view, ['edit', 'form'], true)
			|| in_array($input->get('do', ''), ['edit', 'form'], true)
			|| in_array($input->get('layout', ''), ['edit', 'form', 'write'], true)
			|| self::isAdmin()
		);

		return $cache->set($is_edit_page);
	}

	/**
	 * Checks if current page is a feed
	 *
	 * @return bool
	 */
	public static function isFeed()
	{
		$cache = new Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		$input = JFactory::getApplication()->input;

		$is_feed = (
			self::get()->getType() == 'feed'
			|| in_array($input->getWord('format'), ['feed', 'xml'], true)
			|| in_array($input->getWord('type'), ['rss', 'atom'], true)
		);

		return $cache->set($is_feed);
	}

	/**
	 * Checks if current page is a html page
	 *
	 * @return bool
	 */
	public static function isHtml()
	{
		$cache = new Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		$is_html = (self::get()->getType() == 'html');

		return $cache->set($is_html);
	}

	/**
	 * Checks if current page is a https (ssl) page
	 *
	 * @return bool
	 */
	public static function isHttps()
	{
		$cache = new Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		$is_https = (
			( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off')
			|| (isset($_SERVER['SSL_PROTOCOL']))
			|| (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
			|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https')
		);

		return $cache->set($is_https);
	}

	/**
	 * Checks if current page is a JSON format fle
	 *
	 * @return bool
	 */
	public static function isJSON()
	{
		$cache = new Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		$is_json = JFactory::getApplication()->input->get('format', '') == 'json';

		return $cache->set($is_json);
	}

	/**
	 * Check if the current setup matches the given main version number
	 *
	 * @param int    $version
	 * @param string $title
	 *
	 * @return bool
	 */
	public static function isJoomlaVersion($version, $title = '')
	{
		if ((int) JVERSION == $version)
		{
			return true;
		}

		if ($title && self::isAdmin())
		{
			Language::load('plg_system_regularlabs');

			JFactory::getApplication()->enqueueMessage(
				JText::sprintf('RL_NOT_COMPATIBLE_WITH_JOOMLA_VERSION', JText::_($title), (int) JVERSION),
				'error'
			);
		}

		return false;
	}

	/**
	 * Checks if current page is a pdf
	 *
	 * @return bool
	 */
	public static function isPDF()
	{
		$cache = new Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		$input = JFactory::getApplication()->input;

		$is_pdf = (
			self::get()->getType() == 'pdf'
			|| $input->getWord('format') == 'pdf'
			|| $input->getWord('cAction') == 'pdf'
		);

		return $cache->set($is_pdf);
	}

	/**
	 * Remove joomla script options
	 *
	 * @param string $string
	 * @param string $name
	 * @param string $alias
	 */
	public static function removeScriptsOptions(&$string, $name, $alias = '')
	{
		RegEx::match(
			'(<script type="application/json" class="joomla-script-options new">)(.*?)(</script>)',
			$string,
			$match
		);

		if (empty($match))
		{
			return;
		}

		$alias = $alias ?: Extension::getAliasByName($name);

		$scripts = json_decode($match[2]);

		if ( ! isset($scripts->{'rl_' . $alias}))
		{
			return;
		}

		unset($scripts->{'rl_' . $alias});

		$string = str_replace(
			$match[0],
			$match[1] . json_encode($scripts) . $match[3],
			$string
		);
	}

	/**
	 * Remove style/css blocks from html string
	 *
	 * @param string $string
	 * @param string $name
	 * @param string $alias
	 */
	public static function removeScriptsStyles(&$string, $name, $alias = '')
	{
		[$start, $end] = Protect::getInlineCommentTags($name, null, true);
		$alias = $alias ?: Extension::getAliasByName($name);

		$string = RegEx::replace('((?:;\s*)?)(;?)' . $start . '.*?' . $end . '\s*', '\1', $string);
		$string = RegEx::replace('\s*<link [^>]*href="[^"]*/(' . $alias . '/css|css/' . $alias . ')/[^"]*\.css[^"]*"[^>]*( /)?>', '', $string);
		$string = RegEx::replace('\s*<script [^>]*src="[^"]*/(' . $alias . '/js|js/' . $alias . ')/[^"]*\.js[^"]*"[^>]*></script>', '', $string);
	}

	/**
	 * Registers and uses a script
	 *
	 * @param string $name
	 * @param array  $attributes
	 * @param array  $dependencies
	 */
	public static function script($name, $attributes = ['defer' => true], $dependencies = [], $convert_dots = true)
	{
		$file = $name;

		if ($convert_dots)
		{
			$file = str_replace('.', '/', $file) . '.min.js';
		}

		self::getAssetManager()->registerAndUseScript(
			$name,
			$file,
			[],
			$attributes,
			$dependencies
		);
	}

	/**
	 * @return  JWebAssetManager
	 */
	public static function getAssetManager()
	{
		$document = self::get();

		if (is_null($document))
		{
			return null;
		}

		return $document->getWebAssetManager();
	}

	/**
	 * Adds a javascript declaration to the page
	 *
	 * @param string $content
	 * @param string $name
	 * @param bool   $minify
	 * @param string $position before/after
	 */
	public static function scriptDeclaration($content = '', $name = '', $minify = true, $position = 'before')
	{
		if ($minify)
		{
			$content = self::minify($content);
		}

		if ( ! empty($name))
		{
			$content = Protect::wrapScriptDeclaration($content, $name, $minify);
		}

		self::getAssetManager()->addInlineScript(
			$content,
			['position' => $position]
		);
	}

	/**
	 * Minify the given string
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function minify($string)
	{
		// place new lines around string to make regex searching easier
		$string = "\n" . $string . "\n";

		// Remove comment lines
		$string = RegEx::replace('\n\s*//.*?\n', '', $string);
		// Remove comment blocks
		$string = RegEx::replace('/\*.*?\*/', '', $string);
		// Remove enters
		$string = RegEx::replace('\n\s*', ' ', $string);

		// Remove surrounding whitespace
		$string = trim($string);

		return $string;
	}

	/**
	 * Adds extension options to the page
	 *
	 * @param array  $options
	 * @param string $name
	 */
	public static function scriptOptions($options = [], $name = '')
	{
		$key = 'rl_' . Extension::getAliasByName($name);
		JHtml::_('behavior.core');

		self::get()->addScriptOptions($key, $options);
	}

	/**
	 * @param string $buffer
	 */
	public static function setComponentBuffer($buffer = '')
	{
		self::get()->setBuffer($buffer, 'component');
	}

	/**
	 * Registers and uses a stylesheet
	 *
	 * @param string $name
	 * @param array  $attributes
	 */
	public static function style($name, $attributes = [], $convert_dots = true)
	{
		$file = $name;

		if ($convert_dots)
		{
			$file = str_replace('.', '/', $file) . '.min.css';
		}

		self::getAssetManager()->registerAndUseStyle(
			$name,
			$file,
			[],
			$attributes
		);
	}

	/**
	 * Adds a stylesheet declaration to the page
	 *
	 * @param string $content
	 * @param string $name
	 * @param bool   $minify
	 */
	public static function styleDeclaration($content = '', $name = '', $minify = true)
	{
		if ($minify)
		{
			$content = self::minify($content);
		}

		if ( ! empty($name))
		{
			$content = Protect::wrapStyleDeclaration($content, $name, $minify);
		}

		self::getAssetManager()->addInlineStyle($content);
	}

	/**
	 * Uses a registered preset
	 *
	 * @param string $name
	 */
	public static function usePreset($name)
	{
		self::getAssetManager()->usePreset($name);
	}

	/**
	 * Uses an already registered script
	 *
	 * @param string $name
	 */
	public static function useScript($name)
	{
		self::getAssetManager()->useScript($name);
	}

	/**
	 * Uses an already registered stylesheet
	 *
	 * @param string $name
	 */
	public static function useStyle($name)
	{
		self::getAssetManager()->useStyle($name);
	}
}

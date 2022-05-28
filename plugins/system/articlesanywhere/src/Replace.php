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

namespace RegularLabs\Plugin\System\ArticlesAnywhere;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Html as RL_Html;
use RegularLabs\Library\Protect as RL_Protect;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Area;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Protect;

class Replace
{
	static $message = '';

	/**
	 * @param string $string
	 * @param string $area
	 *
	 * @return string
	 */
	public static function render(&$string, $area = 'article', $context = '', $article = null): string
	{
		if ( ! is_string($string) || $string === '')
		{
			return $string;
		}

		if ( ! RL_String::contains($string, Params::getTags(true)))
		{
			return $string;
		}

		$params = Params::get();

		self::$message = '';


		// allow in component?
		if (RL_Protect::isRestrictedComponent($params->disabled_components ?? [], $area))
		{

			self::$message = JText::_('AA_OUTPUT_REMOVED_NOT_ENABLED');

			Protect::_($string);
		}

		Protect::_($string);

		switch ($area)
		{
			case 'article':
				$replace = self::prepareStringForArticles($string, $context);
				break;

			case 'component':
				$replace = self::prepareStringForComponent($string);
				break;

			default:
			case 'body':
				$replace = self::prepareStringForBody($string);
				break;
		}

		if ($replace)
		{
			$strip_html = $area === 'head' && $params->strip_html_in_head;
			self::process($string, $strip_html);
		}

		RL_Protect::unprotect($string);

		return $string;
	}

	private static function prepareStringForArticles(&$string, $context = '')
	{
		$params = Params::get();

		if (strpos($context, 'com_search.') === 0)
		{
			$limit = explode('.', $context, 2);
			$limit = (int) array_pop($limit);

			$string_check = substr($string, 0, $limit);

			if ( ! RL_String::contains($string_check, Params::getTags(true)))
			{
				return false;
			}
		}


		return true;
	}

	private static function prepareStringForComponent(&$string)
	{

		if (RL_Document::isFeed())
		{
			$s      = '(<item[^>]*>)';
			$string = RL_RegEx::replace($s, '\1<!-- START: AA_COMPONENT -->', $string);
			$string = str_replace('</item>', '<!-- END: AA_COMPONENT --></item>', $string);
		}

		if (strpos($string, '<!-- START: AA_COMPONENT -->') === false)
		{
			Area::tag($string, 'component');
		}

		return false;
	}

	private static function prepareStringForBody(&$string)
	{

		return true;
	}

	private static function process(&$full_string, $strip_html = false)
	{
		[$start_tags, $end_tags] = Params::getTags();

		[$pre_string, $string, $post_string] = RL_Html::getContentContainingSearches(
			$full_string,
			$start_tags,
			$end_tags
		);

		$tags = self::getPluginTags($string);

		if (empty($tags))
		{
			return;
		}

		$break     = 0;
		$max_loops = 10;

		while (
			$break++ < $max_loops
			&& ! empty($tags)
		)
		{
			self::replaceTagsInString($string, $tags, $strip_html);

			$tags = self::getPluginTags($string);
		}

		$full_string = $pre_string . $string . $post_string;
	}

	private static function getPluginTags($string)
	{
		$regex = Params::getRegex();

		RL_RegEx::matchAll($regex, $string, $matches);

		if (empty($matches))
		{
			return [];
		}

		$tags = [];

		foreach ($matches as $match)
		{
			$tags[] = new PluginTag($match);
		}

		return $tags;
	}

	private static function replaceTagsInString(&$string, $tags, $strip_html = false)
	{
		/** @var PluginTag $tag */
		foreach ($tags as $tag)
		{
			$output = self::$message ? Protect::getMessageCommentTag(self::$message) : $tag->render();

			if ($strip_html)
			{
				$output = RL_Html::removeHtmlTags($output, true);
			}

			$string = RL_String::replaceOnce($tag->getOriginalString(), $output, $string);
		}
	}
}

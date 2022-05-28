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
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\Parameters as RL_Parameters;
use RegularLabs\Library\PluginTag as RL_PluginTag;
use RegularLabs\Library\RegEx as RL_RegEx;

class Params
{
	public static function getAlignment()
	{
		$params = self::get();


		if ( ! $params->alignment)
		{
			$params->alignment = JFactory::getLanguage()->isRTL() ? 'right' : 'left';
		}

		return 'align_' . $params->alignment;
	}

	public static function get($overrides = null)
	{
		$cache = new RL_Cache(__METHOD__);

		if ($cache->exists())
		{
			return RL_Parameters::overrideFromObject(
				$cache->get(),
				RL_Object::changeKeyCase($overrides, 'underscore')
			);
		}

		$params = RL_Parameters::getPlugin('articlesanywhere');

		$params->article_tag = RL_PluginTag::clean($params->article_tag);

		

		[$params->tag_character_data_start, $params->tag_character_data_end]
			= RL_Array::toArray($params->tag_characters_data, '.');

		[$params->tag_character_start, $params->tag_character_end]
			= RL_Array::toArray($params->tag_characters, '.');

		// Defaults for other params
		$params->offset           = 0;
		$params->one_per_category = false;

		$cache->set($params);

		return RL_Parameters::overrideFromObject($params, $overrides);
	}

	public static function getPositioning()
	{


		return 'top';
	}

	public static function getAuthorisedViewLevels()
	{
		$cache = new RL_Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		$user = JFactory::getApplication()->getIdentity() ?: JFactory::getUser();

		$view_levels = $user->getAuthorisedViewLevels();
		$view_levels = array_unique($view_levels);

		if (empty($view_levels))
		{
			$view_levels = [0];
		}

		return $cache->set($view_levels);
	}

	public static function getDatabase($name)
	{
	}

	public static function getIfEndTag()
	{
		[$tag_start, $tag_end] = self::getTagCharacters();

		return $tag_start . '/if' . $tag_end;
	}

	public static function getTagCharacters()
	{
		$params = self::get();

		return [$params->tag_character_start, $params->tag_character_end];
	}

	public static function getRegex($type = 'tag')
	{
		$regexes = self::getRegexes();

		return $regexes->{$type} ?? $regexes->tag;
	}

	private static function getRegexes()
	{
		$cache = new RL_Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		$regexes = (object) [
			'tag'            => self::getPluginTagRegex(),
			'datatag'        => self::getDataTagRegex(),
			'ifstatement'    => self::getIfStatementRegex(),
			'iftag'          => self::getIfTagRegex(),
			'ignoretag'      => self::getIgnoreTagRegex(),
		];

		return $cache->set($regexes);
	}

	private static function getPluginTagRegex()
	{
		[$tag_start, $tag_end] = Params::getTagCharacters();

		$pre        = RL_PluginTag::getRegexSurroundingTagsPre();
		$post       = RL_PluginTag::getRegexSurroundingTagsPost();
		$inside_tag = RL_PluginTag::getRegexInsideTag($tag_start, $tag_end);
		$spaces     = RL_PluginTag::getRegexSpaces();

		$tag_start = RL_RegEx::quote($tag_start);
		$tag_end   = RL_RegEx::quote($tag_end);

		$tags   = RL_RegEx::quote(self::getTagNames(), 'tag');
		$set_id = '(?:-[a-zA-Z0-9-_]+)?';

		return '(?<opening_tags_before_open>' . $pre . ')'
			. $tag_start . $tags . '(?<set_id>' . $set_id . ')(?:' . $spaces . '(?<id>' . $inside_tag . '))?' . $tag_end
			. '(?<closing_tags_after_open>' . $post . ')'
			. '(?<content>.*?)'
			. '(?<opening_tags_before_close>' . $pre . ')'
			. $tag_start . '/\2\3' . $tag_end
			. '(?<closing_tags_after_close>' . $post . ')';
	}

	private static function getDataTagRegex()
	{
		$data_selector = self::getDataSelectorsRegex();

		return self::getDataTagRegexByDataSelector($data_selector);
	}

	private static function getIfStatementRegex()
	{
		[$tag_start, $tag_end] = self::getTagCharacters();

		$inside_tag = RL_PluginTag::getRegexInsideTag($tag_start, $tag_end);
		$spaces     = RL_PluginTag::getRegexSpaces();

		$tag_start = RL_RegEx::quote($tag_start);
		$tag_end   = RL_RegEx::quote($tag_end);

		return $tag_start . 'if' . $spaces . $inside_tag . $tag_end
			. '.*?'
			. $tag_start . '/if' . $tag_end;
	}

	private static function getIfTagRegex()
	{
		[$tag_start, $tag_end] = self::getTagCharacters();

		$inside_tag = RL_PluginTag::getRegexInsideTag($tag_start, $tag_end);
		$spaces     = RL_PluginTag::getRegexSpaces();

		$tag_start = RL_RegEx::quote($tag_start);
		$tag_end   = RL_RegEx::quote($tag_end);

		return $tag_start
			. '(?<type>if|else ?if|else)(?:' . $spaces . '(?<condition>' . $inside_tag . '))?'
			. $tag_end;
	}

	private static function getIgnoreTagRegex()
	{
		[$tag_start, $tag_end] = self::getTagCharacters();

		$tag_start = RL_RegEx::quote($tag_start);
		$tag_end   = RL_RegEx::quote($tag_end);

		return $tag_start . 'ignore' . $tag_end
			. '(?<content>.*?)'
			. $tag_start . '/ignore' . $tag_end;
	}

	private static function getForeachTagRegex()
	{
	}

	private static function getForeachDataTagRegex()
	{
	}

	public static function getTagNames()
	{
		$params = self::get();

		return
			[
				$params->article_tag,
			];
	}

	public static function getDataSelectorsRegex()
	{
		$article_selector = '(?:(?<article_selector>(?:[0-9]+|previous|next|first|last|this))\:)?';
		$data_group       = '(?:(?<data_group>(?:' . implode('|', Data::getDataGroupPrefixes()) . '))\:)?';
		$data_key         = '(?<data_key>[a-z0-9-_]+?)';
		$data_subkey      = '(?:\:(?<data_subkey>[a-z0-9-_]+))?';

		return $article_selector
			. '(?<full_key>' . $data_group . $data_key . ')'
			. $data_subkey;
	}

	private static function getDataTagRegexByDataSelector($data_selector)
	{
		[$tag_start, $tag_end] = self::getDataTagCharacters();

		$inside_tag = RL_PluginTag::getRegexInsideTag($tag_start, $tag_end);
		$spaces     = RL_PluginTag::getRegexSpaces();

		$tag_start = RL_RegEx::quote($tag_start);
		$tag_end   = RL_RegEx::quote($tag_end);

		return $tag_start . '(?<is_closing_tag>/)?'
			. $data_selector
			. '(?:' . $spaces . '(?<attributes>' . $inside_tag . '))?'
			. $tag_end;
	}

	public static function getForeachDataSelectorsRegex()
	{
	}

	public static function getDataTagCharacters()
	{
		$params = self::get();

		return [$params->tag_character_data_start, $params->tag_character_data_end];
	}

	public static function getTags($only_start_tags = false)
	{
		$params = self::get();

		[$tag_start, $tag_end] = self::getTagCharacters();

		$tags = [
			[
				$tag_start . $params->article_tag,
			],
			[
				$tag_start . '/' . $params->article_tag . $tag_end,
			],
		];

		return $only_start_tags ? $tags[0] : $tags;
	}
}

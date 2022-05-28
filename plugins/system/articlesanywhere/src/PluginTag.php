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

use Joomla\CMS\Uri\Uri as JUri;
use RegularLabs\Library\Html as RL_Html;
use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\Parameters as RL_Parameters;
use RegularLabs\Library\PluginTag as RL_PluginTag;
use RegularLabs\Library\Protect as RL_Protect;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Filters\Filters;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Protect;

class PluginTag
{
	static $use_sef = true;
	/**
	 * @var array
	 */
	public $items = [];
	/**
	 * @var array
	 */
	public $match_data = [];
	/**
	 * @var array
	 */
	private $attributes;
	/**
	 * @var object
	 */
	private $database;

	/**
	 * @param array $match
	 */
	public function __construct(array $match)
	{
		$this->match_data        = $match;
		$this->match_data['tag'] = $this->match_data['tag'] ?? Params::get()->article_tag;

		$string           = $this->getTagString();
		$this->attributes = $this->getSetAttributes($string);

		if ($this->match_data['tag'] === Params::get()->article_tag)
		{
			$this->attributes->limit = 1;
		}

		$this->params = Params::get($this->attributes);

	}

	/**
	 * @return string
	 */
	private function getTagString()
	{
		$string = RL_String::html_entity_decoder($this->match_data['id']);

		// protect comma's inside date() functions
		$string = RL_RegEx::replace(
			'(date\(\s*\'.*?\'),(\s*\'.*?\'\s*\))',
			'\1\\,\2',
			$string
		);

		return $string;
	}

	/**
	 * @param string $string
	 *
	 * @return object
	 */
	private function getSetAttributes($string = '')
	{
		if (empty($string))
		{
			return (object) [];
		}

		$known_boolean_keys = [
			'ignore_language', 'ignore_access', 'ignore_state', 'fix_html_syntax',
		];

		$key_aliases = [
			/* Articles */
			'article'                  => ['articles', 'items', 'item'],
			'id'                       => ['ids'],
			'alias'                    => ['aliases'],
			'title'                    => ['titles'],
			/* Settings */
			'ignore_state'             => ['ignore_published'],
			'fix_html_syntax'          => ['fix_html', 'html_fix', 'htmlfix', 'fixhtml'],
		];

		$attributes = RL_PluginTag::getAttributesFromString(
			$string,
			'article',
			$known_boolean_keys,
			'underscore',
			[]
		);

		return RL_Object::replaceKeys($attributes, $key_aliases);
	}

	static public function setDatabase($database)
	{
	}

	public function getOriginalString()
	{

		return $this->match_data[0];
	}

	/**
	 * @return string
	 */
	public function render()
	{
		$html = $this->match_data['content'];

		// protect ignore tags
		RL_Protect::protectByRegex($html, Params::getRegex('ignoretag'), 'content');

		$html = $this->getArticles($html)->render();

		if ( ! empty($this->database->url_domain))
		{
			$this->setDomainInUrls($html, $this->database->url_domain);
		}

		RL_Protect::unprotect($html);

		$opening_tags = RL_Html::removeEmptyTagPairs(
			$this->match_data['opening_tags_before_open']
			. $this->match_data['closing_tags_after_open']
		);

		$closing_tags = RL_Html::removeEmptyTagPairs(
			$this->match_data['opening_tags_before_close']
			. $this->match_data['closing_tags_after_close']
		);

		if (empty($html) || ! $this->params->fix_html_syntax)
		{
			return $opening_tags . $html . $closing_tags;
		}

		if (empty($opening_tags) || empty($closing_tags))
		{
			return $opening_tags
				. $this->fixBrokenHtmlTags($html)
				. $closing_tags;
		}

		return $this->fixBrokenHtmlTags($opening_tags . $html . $closing_tags);
	}

	private function protectNestedTags(&$string)
	{
	}

	private function protectCustomHtmlForArticlesField(&$string)
	{
	}

	/**
	 * @return Articles
	 */
	private function getArticles($html)
	{

		$filter_groups = $this->getFilterGroups();

		return new Articles(
			$html,
			$filter_groups,
			$this->attributes,
			$this->match_data['tag'],
			$this->database
		);
	}

	private function setDomainInUrls(&$string, $domain)
	{
		$domain = RL_String::rtrim($domain, '/');

		RL_RegEx::matchAll($this->getUrlRegex(), $string, $matches);

		foreach ($matches as $match)
		{
			$url = RL_String::ltrim($match['url'], '/');

			if (strpos($url, '://') !== false)
			{
				continue;
			}

			$string = str_replace(
				$match[0],
				$match['prefix'] . $domain . '/' . $url . $match['postfix'],
				$string
			);
		}
	}

	private function fixBrokenHtmlTags($string)
	{
		$string = RL_Html::fix($string);

		if ( ! $this->params->place_comments)
		{
			return $string;
		}

		return Protect::wrapInCommentTags($string);
	}

	/**
	 * @return Filters[]
	 */
	private function getFilterGroups()
	{
		$parts = $this->getTagStringParts();

		$filter_groups = [];

		foreach ($parts as $string)
		{
			$attributes = $this->getSetAttributes($string);

			$filter_group = $this->getFilterGroup($attributes);

			$filter_groups[] = $filter_group;
		}

		return $filter_groups;
	}

	/**
	 * Searches are replaced by:
	 * '\1http(s)://' . [cdn] . '/\3\4'
	 * \2 is used to reference the possible starting quote
	 */
	private static function getUrlRegex()
	{
		// Domain url or root path
		$roots   = [];
		$roots[] = '\/';
		$roots[] = str_replace(['http\\://', 'https\\://'], '(?:https?\:)?//', RL_RegEx::quote(JUri::root()));

		if (JUri::root(1))
		{
			$roots[] = RL_RegEx::quote(JUri::root(1) . '/');
		}

		return '(?<prefix>(?:href|src)=(?<quote>["\']))'
			. '(?:' . implode('|', $roots) . '?)'
			. '(?<url>[a-z0-9-_]+.*?)'
			. '(?<postfix>\2)';
	}

	/**
	 * @return array
	 */
	private function getTagStringParts()
	{
		$string = $this->getTagString();



		return [$string];
	}

	/**
	 * @param object $attributes
	 *
	 * @return Filters
	 */
	private function getFilterGroup(object $attributes)
	{
		$filters = [];

		foreach ($attributes as $key => $value)
		{
			if ($this->getAttributeType($key) != 'filter')
			{
				continue;
			}

			if (RL_RegEx::match('^[0-9]+\#', $value))
			{
				$value = (int) $value;
			}

			$filters[$key] = $value;
			unset($attributes->{$key});
		}

		$params = RL_Parameters::overrideFromObject($this->params, $attributes);

		return new Filters($filters, $params);
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	private function getAttributeType($key)
	{
		$params = Params::get();

		if (isset($params->{RL_String::toUnderscoreCase($key)}))
		{
			return 'param';
		}

		return 'filter';
	}
}

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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers;

defined('_JEXEC') or die;

use DOMDocument;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Plugin\PluginHelper as JPluginHelper;
use Joomla\Registry\Registry as JRegistry;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Html as RL_Html;
use RegularLabs\Library\HtmlTag as RL_HtmlTag;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class Text
{
	private static $comment_page_splitter          = '<!-- Articles Anywhere: PAGE_SPLITTER -->';
	private static $comment_pagination_placeholder = '<!-- Articles Anywhere: PAGENAVIGATION_%nr% -->';
	private static $comment_tag_splitter           = '<!-- Articles Anywhere: TAG_SPLITTER -->';
	private static $navigations                    = [];

	public static function process($string, $key, $attributes)
	{
		if ( ! is_string($string))
		{
			return $string;
		}

		$string = self::protectNavigations($string);

		if (isset($attributes->page))
		{
			$string = self::getPage($string, $attributes);
		}

		if (isset($attributes->id) || isset($attributes->element))
		{
			$string = self::getElementById($string, $attributes->id ?? $attributes->element);
		}

		if ( ! empty($attributes->paragraphs))
		{
			$string = self::limitByParagraphs($string, $attributes->paragraphs, $attributes->add_ellipsis ?? true);
		}

		if (isset($attributes->html) && ! $attributes->html)
		{
			$string = self::removeHtml($string);
		}

		if (isset($attributes->images) && ! $attributes->images)
		{
			$string = self::removeImages($string);
		}

		if (isset($attributes->offset_headings))
		{
			$string = self::offsetHeadings($string, $attributes->offset_headings);
		}

		$string = self::limit($string, $attributes);

		$string = self::unprotectNavigations($string);

		if (isset($attributes->replace))
		{
			$string = self::replace($string, $attributes->replace, $attributes->replace_case_sensitive ?? true);
		}

		if (isset($attributes->convert_case))
		{
			$string = RL_String::toCase($string, $attributes->convert_case);
		}

		if (isset($attributes->htmlentities) && $attributes->htmlentities)
		{
			$string = htmlentities($string);
		}

		return $string;
	}

	private static function protectNavigations($string)
	{
		self::$navigations = [];

		$regex = '<div [^>]*>\s*<p class="counter.*?</p><nav role="navigation".*?</nav>\s*</div>';
		if ( ! RL_RegEx::matchAll($regex, $string, $matches))
		{
			return $string;
		}

		foreach ($matches as $i => $match)
		{
			$string = str_replace(
				$match[0],
				str_replace('%nr%', $i, self::$comment_pagination_placeholder),
				$string
			);
		}

		return $string;
	}

	private static function getPage($string, $attributes)
	{
		if (empty($attributes->page))
		{
			return $string;
		}

		$pages = self::extractPages($string);

		if (empty($pages))
		{
			return $string;
		}

		if (is_numeric($attributes->page))
		{
			return $pages[$attributes->page - 1]->contents ?? '';
		}

		foreach ($pages as $page)
		{
			if ($page->title === $attributes->page)
			{
				return $page->contents;
			}
		}

		return '';
	}

	private static function getElementById($string, $id)
	{
		if ( ! class_exists('DOMDocument'))
		{
			return '';
		}

		if (strpos($string, 'id="' . $id . '"') === false)
		{
			return '';
		}

		$doc = new DOMDocument;

		$doc->validateOnParse = true;

		$string = '<html><body><div>' . $string . '</div></body></html>';
		$doc->loadHTML($string);

		$node = $doc->getElementById($id);

		if (empty($node))
		{
			return '';
		}

		return $doc->saveHTML($node);
	}

	private static function limitByParagraphs($string, $limit, $add_ellipsis = true)
	{
		if ( ! self::containsHtml($string))
		{
			return $string;
		}

		if ( ! RL_RegEx::match('^' . str_repeat('.*?</p>', $limit), $string, $match))
		{
			return $string;
		}

		// Number of paragraphs in text matches the limit
		// So no need to do anything
		if ($string === $match[0])
		{
			return $string;
		}

		$string = $match[0];

		if ($add_ellipsis)
		{
			RL_RegEx::match('(.*?)(</p>)$', $string, $match);
			self::addEllipsis($match[1]);
			$string = $match[1] . $match[2];
		}

		return RL_Html::fix($string);
	}

	private static function removeHtml($string)
	{
		return RL_String::removeHtml($string, true);
	}

	private static function removeImages($string)
	{
		return RL_RegEx::replace(
			'(<p><img\s[^>]*></p>|<img\s.*?>)',
			'',
			$string
		);
	}

	private static function offsetHeadings($string, $offset = 0)
	{
		$offset = (int) $offset;

		if ($offset === 0)
		{
			return $string;
		}

		if (strpos($string, '<h') === false && strpos($string, '<H') === false)
		{
			return $string;
		}

		if ( ! RL_RegEx::matchAll('<h(?<nr>[1-6])(?<content>[\s>].*?)</h\1>', $string, $headings))
		{
			return $string;
		}

		foreach ($headings as $heading)
		{
			$new_nr = min(max($heading['nr'] + $offset, 1), 6);

			$string = str_replace(
				$heading[0],
				'<h' . $new_nr . $heading['content'] . '</h' . $new_nr . '>',
				$string
			);
		}

		return $string;
	}

	private static function limit($string, $attributes)
	{
		if (empty($attributes->characters)
			&& empty($attributes->words)
			&& empty($attributes->letters)
		)
		{
			return $string;
		}

		if (self::containsHtml($string))
		{
			return self::limitHtml($string, $attributes);
		}

		$add_ellipsis = $attributes->add_ellipsis ?? Params::get()->use_ellipsis;

		if ( ! empty($attributes->words))
		{
			return self::limitByWords($string, (int) $attributes->words, $add_ellipsis);
		}

		if ( ! empty($attributes->letters))
		{
			return self::limitByLetters($string, (int) $attributes->letters, $add_ellipsis);
		}

		return self::limitByCharacters($string, (int) $attributes->characters, $add_ellipsis);
	}

	private static function unprotectNavigations($string)
	{
		foreach (self::$navigations as $i => $navigation)
		{
			$string = str_replace(
				str_replace('%nr%', $i, self::$comment_pagination_placeholder),
				$navigation,
				$string
			);
		}

		return $string;
	}

	private static function replace($string, $replacement_string, $casesensitive = true, $separator = '=>')
	{
		$replacements = RL_Array::toArray($replacement_string, ',', false, false);

		foreach ($replacements as $replacement)
		{
			$replacement = str_replace(htmlentities($separator), $separator, $replacement);

			if (strpos($replacement, $separator) === false)
			{
				$string = str_replace($replacement, '', $string);
				continue;
			}

			[$search, $replace] = RL_Array::toArray($replacement, '=>', false, false);

			$string = $casesensitive
				? str_replace($search, $replace, $string)
				: str_ireplace($search, $replace, $string);
		}

		return $string;
	}

	private static function extractPages($string)
	{
		// Flip order of title and class around to match latest syntax
		$string = RL_RegEx::replace(
			'<hr title="([^"]*)" class="system-pagebreak" /?>',
			'<hr class="system-pagebreak" title="\1"" />',
			$string
		);

		$regex = '<hr class="system-pagebreak" title="([^"]*)" /?>';

		RL_RegEx::matchAll($regex, $string, $page_titles, null, PREG_PATTERN_ORDER);

		if (empty($page_titles))
		{
			return [];
		}

		$string = RL_RegEx::replace(
			$regex,
			RL_RegEx::quote(self::$comment_page_splitter),
			$string
		);

		$contents = explode(self::$comment_page_splitter, $string);

		$pages = [];

		foreach ($contents as $i => $content)
		{
			$pages[] = (object) [
				'title'    => $page_titles[$i][1],
				'contents' => $content,
			];
		}

		return $pages;
	}

	private static function containsHtml($string)
	{
		return strpos($string, '<') !== false && strpos($string, '>') !== false;
	}

	private static function addEllipsis(&$string)
	{
		$string = RL_String::rtrim($string);

		$string = RL_RegEx::replace('(.)\.*((?:\s*</[a-z][^>]*>)*)$', '\1...\2', $string);
	}

	private static function limitHtml($string, $attributes)
	{
		if (empty($attributes->characters)
			&& empty($attributes->letters)
			&& empty($attributes->words)
			&& empty($attributes->paragraphs)
		)
		{
			return $string;
		}

		$add_ellipsis = $attributes->add_ellipsis ?? Params::get()->use_ellipsis;

		if ( ! empty($attributes->paragraphs))
		{
			return self::limitByParagraphs($string, (int) $attributes->paragraphs, $add_ellipsis);
		}

		if ( ! empty($attributes->words))
		{
			return self::limitHtmlByType('words', $string, (int) $attributes->words, $add_ellipsis);
		}

		if ( ! empty($attributes->letters))
		{
			return self::limitHtmlByType('letters', $string, (int) $attributes->letters, $add_ellipsis);
		}

		return self::limitHtmlByType('characters', $string, (int) $attributes->characters, $add_ellipsis);
	}

	private static function limitByWords($string, $limit, $add_ellipsis = true)
	{
		if (self::getLengthWords($string) <= $limit)
		{
			return $string;
		}

		$string = RL_String::html_entity_decoder($string);

		$words     = RL_String::countWords($string, 2);
		$positions = array_keys($words);
		$start_pos = $positions[$limit - 1];
		$end_pos   = $start_pos + RL_String::strlen($words[$start_pos]);

		// Move the end position to include the trailing period
		if ( ! $add_ellipsis && substr($string, $end_pos, 1) === '.')
		{
			$end_pos++;
		}

		$string = substr($string, 0, $end_pos);

		if ($add_ellipsis)
		{
			self::addEllipsis($string);
		}

		return $string;
	}

	private static function limitByLetters($string, $limit, $add_ellipsis)
	{
		if (self::getLengthLetters($string) <= $limit)
		{
			return $string;
		}

		$string = RL_String::html_entity_decoder($string);

		$characters         = self::getCharacters($string);
		$letter_count       = 0;
		$characters_to_keep = [];

		foreach ($characters as $character)
		{
			$characters_to_keep[] = $character;

			if (is_numeric($character) || self::isLetter($character))
			{
				$letter_count++;
			}

			if ($letter_count >= $limit)
			{
				break;
			}
		}

		$string = implode('', $characters_to_keep);

		if ($add_ellipsis)
		{
			self::addEllipsis($string);
		}

		return $string;
	}

	private static function limitByCharacters($string, $limit, $add_ellipsis)
	{
		if (self::getLengthCharacters($string) <= $limit)
		{
			return $string;
		}

		$string = RL_String::html_entity_decoder($string);

		$string = self::rtrim($string, $limit);

		if ($add_ellipsis)
		{
			self::addEllipsis($string);
		}

		return $string;
	}

	private static function limitHtmlByType($type, $string, $limit, $add_ellipsis = true)
	{
		if ( ! in_array($type, ['words', 'letters', 'characters'], true))
		{
			return $string;
		}

		$limit_class      = 'limitBy' . ucfirst($type);
		$get_length_class = 'getLength' . ucfirst($type);

		if ( ! self::containsHtml($string))
		{
			return self::$limit_class($string, $limit, $add_ellipsis);
		}

		if (self::$get_length_class($string) <= $limit)
		{
			return $string;
		}

		$string = RL_String::html_entity_decoder($string);

		$parts = self::splitByHtmlTags($string);

		$last_text_part = 0;
		$total          = 0;

		foreach ($parts as $i => $part)
		{
			// this is not a text part. So ignore it.
			if ($i % 2)
			{
				continue;
			}

			$current_count  = self::$get_length_class($part);
			$last_text_part = $i;

			$new_total = $total + $current_count;

			if ($new_total < $limit)
			{
				$total = $new_total;
				continue;
			}

			if ($new_total > $limit)
			{
				$parts[$i] = self::$limit_class(
					$part,
					$limit - $total,
					$add_ellipsis
				);

				break;
			}

			if ($add_ellipsis)
			{
				self::addEllipsis($parts[$i]);
			}

			break;
		}

		$parts_to_keep = self::getPartsToKeep($parts, $last_text_part);

		return implode('', $parts_to_keep);
	}

	private static function getLengthWords($string)
	{
		$string = RL_String::html_entity_decoder($string);

		return str_word_count($string);
	}

	private static function getLengthLetters($string)
	{
		$string = RL_String::html_entity_decoder($string);

		$letters = self::getLetters($string);

		return count($letters);
	}

	private static function getCharacters($string)
	{
		$string = RL_String::html_entity_decoder($string);

		return preg_split('//u', $string);
	}

	private static function isLetter($character)
	{
		return RL_RegEx::match('^[\p{Latin}]$', $character);
	}

	private static function getLengthCharacters($string)
	{
		$string = RL_String::html_entity_decoder($string);

		return RL_String::strlen($string);
	}

	private static function rtrim($string, $limit)
	{
		return RL_String::rtrim(RL_String::substr($string, 0, $limit));
	}

	private static function splitByHtmlTags($string)
	{
		// add splitter strings around tags
		$string = RL_RegEx::replace(
			'(<\/?[a-z][a-z0-9]?.*?>|<!--.*?-->)',
			self::$comment_tag_splitter . '\1' . self::$comment_tag_splitter,
			$string
		);

		return explode(self::$comment_tag_splitter, $string);
	}

	private static function getPartsToKeep($parts, $last_text_part)
	{
		$parts_to_keep = [];
		$opening_tags  = [];

		foreach ($parts as $i => $part)
		{
			// Include all parts up to the last text part we need to include
			if ($i <= $last_text_part)
			{
				$parts_to_keep[] = $part;
				continue;
			}

			// this is a text part. So ignore it.
			if ( ! ($i % 2))
			{
				continue;
			}

			RL_RegEx::match(
				'^<(?<closing>\/?)(?<type>[a-z][a-z0-9]*)',
				$part,
				$tag
			);

			// This is a self closing tag. So ignore it.
			if (RL_HtmlTag::isSelfClosingTag($tag['type']))
			{
				continue;
			}

			// This is a closing tag of the previous opening tag. So ignore both
			if ($tag['closing'] && $tag['type'] === end($opening_tags))
			{
				array_pop($opening_tags);
				array_pop($parts_to_keep);
				continue;
			}

			$parts_to_keep[] = $part;

			// This is a opening tag. So add it to the list to remember
			if ( ! $tag['closing'])
			{
				$opening_tags[] = $tag['type'];
			}
		}

		return $parts_to_keep;
	}

	private static function getLetters($string)
	{
		$characters = self::getCharacters($string);

		$letters = [];

		foreach ($characters as $character)
		{
			if ( ! is_numeric($character) && ! self::isLetter($character))
			{
				continue;
			}

			$letters[] = $character;
		}

		return $letters;
	}

	public static function triggerContentPlugins($string, $id = 0)
	{
		$item            = (object) [];
		$item->id        = $id;
		$item->text      = $string;
		$item->slug      = '';
		$item->catslug   = '';
		$item->introtext = null;
		$item->fulltext  = null;

		$article_params = new JRegistry;
		$article_params->loadArray(['inline' => false]);

		JPluginHelper::importPlugin('content');

		JFactory::getApplication()->triggerEvent('onContentPrepare', ['com_content.article', &$item, &$article_params, 0]);

		return $item->text;
	}
}

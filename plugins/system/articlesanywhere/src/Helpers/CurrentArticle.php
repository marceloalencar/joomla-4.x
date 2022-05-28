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
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Articles;
use RegularLabs\Plugin\System\ArticlesAnywhere\Filters\Filters as Filters;

class CurrentArticle
{
	protected static $article          = null;
	protected static $category_article = null;

	public static function getId()
	{
		$article = self::get();

		return $article->id ?? 0;
	}

	public static function getValue($key, $default = null)
	{
		$article = self::get();

		if ( ! $article)
		{
			return $default;
		}

		if (isset($article->$key))
		{
			return $article->$key;
		}

		$key_underscore = RL_String::toUnderscoreCase($key);

		if (isset($article->$key_underscore))
		{
			return $article->$key_underscore;
		}

		[$tag_start, $tag_end] = Params::getDataTagCharacters();

		$content = $tag_start . $key . $tag_end;
		$filters = new Filters(['id' => $article->id]);

		$data = new Articles(
			$tag_start . $key . $tag_end,
			[$filters]
		);

		$value = $data->render();

		$article->$key = ($value != $content) ? $value : $default;

		return $article->$key;
	}

	public static function get()
	{
		if (is_null(self::$article))
		{
			self::setArticleByUrl();
		}

		return static::$article;
	}

	public static function setArticleByUrl()
	{
		$input = JFactory::getApplication()->input;

		if ($input->get('option', '') !== 'com_content'
			|| $input->get('view', '') !== 'article')
		{
			return;
		}

		static::$article = (object) [
			'id' => $input->getInt('id'),
		];
	}

	public static function set($article)
	{
		$input = JFactory::getApplication()->input;

		$is_article = $article && ! empty($article->id) && isset($article->fulltext);

		if ( ! $is_article
			&& $input->get('option', '') === 'com_content'
			&& $input->get('view', '') === 'category')
		{
			self::setFromCategory();

			return;
		}

		if ( ! $is_article)
		{
			return;
		}

		static::$article = clone $article;
	}

	public static function setFromCategory()
	{
		if ( ! is_null(self::$category_article))
		{
			static::$article = clone self::$category_article;

			return;
		}

		$category_id = JFactory::getApplication()->input->getInt('id');

		if ( ! $category_id)
		{
			return;
		}

		$query = DB::getQuery()
			->select('article.id')
			->from(DB::quoteName('#__content', 'article'))
			->where(DB::is('article.catid', $category_id))
			->where(DB::is('article.state', 1));

		$article_id = DB::getResults($query, 'result');

		if ( ! $article_id)
		{
			return;
		}

		self::$category_article = (object) [
			'id' => $article_id,
		];

		static::$article = clone self::$category_article;
	}
}

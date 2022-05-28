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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper as JComponentHelper;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Layout\LayoutHelper as JLayout;
use Joomla\CMS\Router\Route as JRoute;
use Joomla\CMS\Table\Table as JTable;
use Joomla\CMS\Uri\Uri as JUri;
use Joomla\CMS\User\User as JUser;
use Joomla\CMS\Workflow\Workflow as JWorkflow;
use Joomla\Component\Content\Site\Helper\RouteHelper as JContentHelperRoute;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Library\HtmlTag as RL_HtmlTag;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\ArticleLayout;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Image;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Layout;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Text;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\CurrentArticle;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\PluginTag;

class Article extends DataGroup
{
	protected static $data_key_aliases = [
		'author-alias' => 'created-by-alias',
	];
	protected static $default_data_key = 'article';
	protected static $main_table       = 'content';
	protected static $prefix           = 'article';
	protected static $ignore_group     = 'articles';

	public function getDatabaseKey($key = '', $add_prefix = true, $case = 'underscore')
	{
		$key = $key ?: $this->key;

		switch ($key)
		{
			case 'featured-up':
			case 'featured-down':
				return parent::getDatabaseKey('frontpage.' . $key, false, $case);

			case 'featured-ordering':
				return parent::getDatabaseKey('frontpage.ordering', false, $case);

			default:
				return parent::getDatabaseKey($key, $add_prefix, $case);
		}
	}

	/**
	 * @return array [table => condition]
	 */
	public function getJoins()
	{
		switch ($this->key)
		{
			case 'featured':
			case 'featured-up':
			case 'featured-down':
			case 'is-featured':
			case 'featured-ordering':
				return [
					DB::quoteName('#__content_frontpage', 'frontpage')
					=> DB::quoteName('frontpage.content_id') . ' = ' . DB::quoteName('article.id'),
				];

			default:
				return parent::getJoins();
		}
	}

	public function getQueryKeys()
	{
		switch ($this->key)
		{
			case 'article':
				return [];

			case 'has-access':
				return [
					'article.access',
				];

			case 'is-published':
				return [
					'article.state',
					'article.publish-up',
					'article.publish-down',
				];

			case 'featured':
			case 'featured-up':
			case 'featured-down':
			case 'is-featured':
			case 'featured-ordering':
				return [
					'article.featured',
					'frontpage.ordering',
					'frontpage.featured-up',
					'frontpage.featured-down',
				];

			case 'text':
				return [
					'article.introtext',
					'article.fulltext',
				];

			case 'url':
			case 'sefurl':
			case 'link':
				return [
					'article.title',
					'article.alias',
					'article.catid',
					'article.language',
				];

			case 'edit':
			case 'edit-url':
				return [
					'article.alias',
					'article.catid',
					'article.language',
					'article.state',
					'article.created-by',
					'article.publish-up',
					'article.publish-down',
					'article.checked-out',
					'article.checked-out-time',
				];

			case 'can-edit':
				return [
					'article.state',
					'article.created-by',
				];

			case 'is-checked-out':
				return [
					'article.checked-out',
				];

			case 'readmore':
				return [
					'article.title',
					'article.alias',
					'article.catid',
					'article.language',
					'article.attribs',
				];

			case 'link-a':
			case 'link-b':
			case 'link-c':
				return [
					'article.urls',
				];

			default:
				break;
		}


		if (RL_RegEx::match('^image-(intro|introtext|fulltext)(-|$)', $this->key))
		{
			return [
				'article.title',
				'article.images',
			];
		}

		if (RL_RegEx::match('^meta-', $this->key))
		{
			return [
				'article.metadata',
			];
		}

		return parent::getQueryKeys();
	}

	public function getRequiredQueryKeys()
	{
		return ['article.id'];
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		switch ($this->key)
		{
			case 'article':
				return $this->getArticleLayout();

			case 'created':
				return $this->getCreated();

			case 'modified':
				return $this->getModified();

			case 'publish-up':
				return $this->getPublishUp();

			case 'hits':
				return $this->getHits();

			case 'has-access':
				return $this->hasAccess();

			case 'is-published':
				return $this->isPublished();

			case 'featured':
			case 'is-featured':
				return $this->isFeatured();

			case 'featured-ordering':
				return $this->get('frontpage.ordering');

			case 'featured-up':
			case 'featured-down':
				return $this->get('frontpage.' . $this->key);

			case 'text':
			case 'introtext':
			case 'fulltext':
				return $this->getArticleText($this->key);

			case 'link':
				return $this->getLinkStart();

			case 'sefurl':
				return $this->getSefUrl();


			case 'is-checked-out':
				return $this->isCheckedOut();

			case 'url':
				return $this->getUrl();

			case 'readmore':
				return $this->getReadMore();

			case '/link':
				return '</a>';

			case 'link-a':
			case 'link-b':
			case 'link-c':
				return $this->createUrlsLinkByKey();

			case 'meta-robots':
			case 'meta-author':
			case 'meta-rights':
				return $this->get('metadata.' . RL_String::substr($this->key, 5));

			default:
				break;
		}

		$article_image_regex = '^image-(?<id>intro|introtext|full|fulltext)$';

		if (RL_RegEx::match($article_image_regex, $this->key, $match))
		{
			return $this->getArticleImageByMatch($match);
		}


		return parent::getValue();
	}

	/**
	 * @param array|object|string $values
	 * @param bool                $exclude
	 *
	 * @return string
	 */
	public function getWhere($values, $glue = 'OR')
	{
		if ($this->filter_key == 'featured')
		{
			return $this->getWhereForFeatured($values);
		}

		if (empty($values))
		{
			return '';
		}

		switch ($this->filter_key)
		{
			case 'article':
				return $this->getWhereFromArticleComboKey($values, $glue);

			default:
				return parent::getWhere($values, $glue);
		}
	}

	/**
	 * @return string
	 */
	public function getWhereForFeatured($values)
	{
		$value = RL_Array::implode(Data::valuesToSimpleArray($values));

		$nowDate  = DB::getNowDate();
		$nullDate = DB::getNullDate();

		$wheres = [];

		if ($value != '=1')
		{
			$wheres[] = DB::is($this->getDatabaseKey('featured'), '!=1');
			$wheres[] = DB::combine([
				DB::quoteName('frontpage.featured_up') . ' IS NOT NULL',
				DB::quoteName('frontpage.featured_up') . ' > ' . DB::quote($nowDate),
			], 'AND');
			$wheres[] = DB::combine([
				DB::quoteName('frontpage.featured_down') . ' IS NOT NULL',
				DB::quoteName('frontpage.featured_down') . ' != ' . DB::quote($nullDate),
				DB::quoteName('frontpage.featured_down') . ' <= ' . DB::quote($nowDate),
			], 'AND');

			return DB::combine($wheres, 'OR');
		}

		$wheres[] = DB::is($this->getDatabaseKey('featured'), 1);
		$wheres[] = DB::combine([
			DB::quoteName('frontpage.featured_up') . ' IS NULL',
			DB::quoteName('frontpage.featured_up') . ' <= ' . DB::quote($nowDate),
		], 'OR');
		$wheres[] = DB::combine([
			DB::quoteName('frontpage.featured_down') . ' IS NULL',
			DB::quoteName('frontpage.featured_down') . ' = ' . DB::quote($nullDate),
			DB::quoteName('frontpage.featured_down') . ' > ' . DB::quote($nowDate),
		], 'OR');

		return DB::combine($wheres, 'AND');
	}

	/**
	 * @return string
	 */
	public function getWhereFromArticleComboKey($values, $glue = 'OR')
	{
		$type_values = [
			'id'    => [],
			'alias' => [],
			'title' => [],
		];

		$values = Data::valuesToSimpleArray($values);

		foreach ($values as $value)
		{
			$no_operator = DB::removeOperator($value);
			$exclude     = DB::isExclude($value);

			if ($exclude)
			{
				$glue = 'AND';
			}

			if ($no_operator === 'current')
			{
				$type_values['id'][] = ($exclude ? '!' : '')
					. CurrentArticle::getId();
				continue;
			}

			if (is_numeric($no_operator))
			{
				$type_values['id'][] = $value;
			}

			if (strtolower($no_operator) === $no_operator)
			{
				$type_values['alias'][] = $value;
			}

			$type_values['title'][] = $value;
		}

		$wheres = [];

		if ( ! empty($type_values['id']))
		{
			$wheres[] = DB::is($this->getDatabaseKey('id'), $type_values['id'], ['handle_wildcards' => false, 'glue' => $glue]);
		}

		if ( ! empty($type_values['alias']))
		{
			$wheres[] = DB::is($this->getDatabaseKey('alias'), $type_values['alias'], ['glue' => $glue]);
		}

		if ( ! empty($type_values['title']))
		{
			$wheres[] = DB::is($this->getDatabaseKey('title'), $type_values['title'], ['glue' => $glue]);
		}

		return DB::combine($wheres, $glue);
	}

	public function isFeatured()
	{
		if ($this->get('featured') != 1)
		{
			return false;
		}

		$publish_up   = $this->get('frontpage.featured-up');
		$publish_down = $this->get('frontpage.featured-down');

		$nowDate  = DB::getNowDate();
		$nullDate = DB::getNullDate();

		return $publish_up <= $nowDate
			&& (
				empty($publish_down)
				|| $publish_down == $nullDate
				|| $publish_down >= $nowDate
			);
	}

	public function isPublished()
	{
		if ($this->get('state') != 1)
		{
			return false;
		}

		$publish_up   = $this->get('publish-up');
		$publish_down = $this->get('publish-down');

		$nowDate  = DB::getNowDate();
		$nullDate = DB::getNullDate();

		return $publish_up <= $nowDate
			&& (
				empty($publish_down)
				|| $publish_down == $nullDate
				|| $publish_down >= $nowDate
			);
	}

	protected static function getExtraFields()
	{
		return [
			'text',
			'has-access', 'is-published',
			'is-featured', 'featured-up', 'featured-down', 'featured-ordering',
			'url', 'sefurl', 'link', '/link', 'readmore',
			'edit', 'edit-url', 'edit-link', '/edit-link', 'can-edit', 'is-checked-out',
			'link-a', 'link-b', 'link-c',
			'meta-robots', 'meta-author', 'meta-rights',
		];
	}

	protected static function getFields()
	{
		return array_merge(
			self::getDatabaseFields(),
			self::getExtraFields(),
			self::getAttribFields(),
			self::getImageFields(),
			self::getUrlFields()
		);
	}

	protected static function getJsonKeys()
	{
		return [
			'images'   => self::getImageFields(),
			'urls'     => self::getUrlFields(),
			'attribs'  => self::getAttribFields(),
			'metadata' => self::getMetadataFields(),
		];
	}

	protected static function getPossiblePlainKeys()
	{
		return self::getFields();
	}

	protected static function getPossibleRegexKeys()
	{
		return array_merge(
			parent::getPossibleRegexKeys(),
			['^image-(?:intro|introtext|fulltext|random|[0-9]+)$'],
			['^/?(?:video|youtube|vimeo)-(?:random|[0-9]+)$']
		);
	}

	protected function getArticleImageByMatch($match)
	{
		$key = $this->subkey ?: 'tag';

		if ($key === 'tag' && $this->getAttribute('layout'))
		{
			$key = 'layout';
		}

		switch ($match['id'])
		{
			case 'full':
			case 'fulltext':
				$type       = 'fulltext';
				$image_data = (object) [
					'type'    => 'full_image',
					'src'     => $this->get('article.images.image_fulltext'),
					'float'   => $this->get('article.images.float_fulltext'),
					'alt'     => $this->get('article.images.image_fulltext_alt'),
					'caption' => $this->get('article.images.image_fulltext_caption'),
				];
				break;

			case 'intro':
			case 'introtext':
			default:
				$type       = 'intro';
				$image_data = (object) [
					'type'    => 'intro_image',
					'src'     => $this->get('article.images.image_intro'),
					'float'   => $this->get('article.images.float_intro'),
					'alt'     => $this->get('article.images.image_intro_alt'),
					'caption' => $this->get('article.images.image_intro_caption'),
				];
				break;
		}

		return Image::getOutputByKey($key, $image_data, $this->attributes, $type);
	}

	protected function getIgnoreWhereState()
	{
		$ignore = $this->getIgnoreState();

		if ($ignore)
		{
			return false;
		}

		return DB::getArticleIsPublishedFilters(static::$prefix);
	}

	private static function getAttribFields()
	{
		return [
			'article_layout',
			'show_title',
			'link_titles',
			'show_tags',
			'show_intro',
			'info_statement_position',
			'info_statement_show_title',
			'show_category',
			'link_category',
			'show_parent_category',
			'link_parent_category',
			'show_author',
			'link_author',
			'show_create_date',
			'show_modify_date',
			'show_publish_date',
			'show_item_navigation',
			'show_vote',
			'show_hits',
			'show_noauth',
			'urls_position',
			'alternative_readmore',
			'article_page_title',
			'show_publishing_options',
			'show_article_options',
			'show_urls_images_backend',
			'show_urls_images_frontend',
		];
	}

	private static function getImageFields()
	{
		return [
			'image_intro',
			'float_intro',
			'image_intro_alt',
			'image_intro_caption',
			'image_fulltext',
			'float_fulltext',
			'image_fulltext_alt',
			'image_fulltext_caption',
		];
	}

	private static function getMetadataFields()
	{
		return [
			'robots',
			'author',
			'rights',
		];
	}

	private static function getUrlFields()
	{
		return [
			'urla',
			'urlatext',
			'targeta',
			'urlb',
			'urlbtext',
			'targetb',
			'urlc',
			'urlctext',
			'targetc',
		];
	}

	private function canEdit()
	{
		if ( ! in_array($this->get('state'), [JWorkflow::CONDITION_UNPUBLISHED, JWorkflow::CONDITION_PUBLISHED]))
		{
			return false;
		}

		$user = JFactory::getApplication()->getIdentity() ?: JFactory::getUser();

		if ($user->get('guest'))
		{
			return false;
		}

		$userId = $user->get('id');

		if (empty($userId))
		{
			return false;
		}

		$asset = 'com_content.article.' . $this->get('id');

		// Check general edit permission first.
		if ($user->authorise('core.edit', $asset))
		{
			return true;
		}

		// Now check if edit.own is available.
		if ( ! $user->authorise('core.edit.own', $asset))
		{
			return false;
		}

		// Check for a valid user and that they are the owner.
		if ($userId != $this->get('created-by'))
		{
			return false;
		}

		return true;
	}

	private function createUrlsLink($link, $label, $target)
	{
		$label = $label ?: $link;
		$text  = htmlspecialchars($label, ENT_COMPAT, 'UTF-8');

		switch ($target)
		{
			case 1:
				// Open in a new window
				$attribs = [
					'target' => '_blank',
					'rel'    => 'nofollow noopener noreferrer',
				];

				return JHtml::_('link', $link, $text, $attribs);

			case 2:
				// Open in a popup window
				$attribs = [
					'target'  => '_blank',
					'onclick' => 'window.open('
						. 'this.href,'
						. "'targetWindow',"
						. "'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=600'"
						. ');'
						. 'return false;',
				];

				return JHtml::_('link', $link, $text, $attribs);

			case 3:
				$attribs = [
					'rel'         => 'noopener noreferrer',
					'data-toggle' => 'modal',
					'data-target' => '#linkModal',
				];

				return JHtml::_('link', $link, $text, $attribs)
					. JHtml::_(
						'bootstrap.renderModal',
						'linkModal',
						[
							'url'        => $link,
							'title'      => $label,
							'height'     => '100%',
							'width'      => '100%',
							'modalWidth' => '500',
							'bodyHeight' => '500',
							'footer'     => '<button type="button" class="btn btn-secondary" data-dismiss="modal" aria-hidden="true">'
								. JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
						]
					);

			default:
				// Open in parent window
				$attribs = [
					'rel' => 'nofollow',
				];

				return JHtml::_('link', $link, $text, $attribs);
		}
	}

	private function createUrlsLinkByKey()
	{
		$id = $this->key === 'link-c'
			? 'c'
			: (
			$this->key === 'link-b'
				? 'b'
				: 'a'
			);

		$link = $this->getValueFromJsonKey('urls', 'url' . $id);

		if ( ! $link)
		{
			return '';
		}

		$label  = $this->getValueFromJsonKey('urls', 'url' . $id . 'text');
		$target = $this->getValueFromJsonKey('urls', 'url' . $id . 'target');

		return $this->createUrlsLink($link, $label, $target);
	}

	private function getArticleLayout()
	{
		return ArticleLayout::render($this->get('id'), $this->attributes);
	}

	private function getArticleText($key = 'text')
	{
		$article_id = $this->get('id');

		$cache = new RL_Cache([__METHOD__, $article_id, $key, $this->attributes]);

		if ($cache->exists())
		{
			return $cache->get();
		}

		$text = $this->getRawArticleText($key);

		if (in_array($key, ['text', 'fulltext'], true))
		{
			$this->hit();
		}

		if ($this->getAttribute('force-content-triggers', Params::get()->force_content_triggers))
		{
			$text = Text::triggerContentPlugins($text, $article_id);
		}

		return $cache->set($text);
	}

	private function getCreated()
	{
		return $this->getValueViaLayout('created', 'joomla.content.info_block.create_date');
	}

	private function getEditLink()
	{
		$article = (object) [
			'id'               => $this->get('id'),
			'state'            => $this->get('state'),
			'checked_out'      => $this->get('checked-out'),
			'checked_out_time' => $this->get('checked-out-time'),
			'publish_up'       => $this->get('publish-up'),
			'publish_down'     => $this->get('publish-down'),
		];

		if ( ! $this->canEdit())
		{
			return '';
		}

		$attribs['aria-describedby'] = 'editarticle-' . (int) $article->id;
		$attribs['class']            = $this->getAttribute('class');

		$text = $this->getAttribute('text');

		// Show checked_out icon if the article is checked out by a different user
		if ($this->isCheckedOut())
		{
			if ( ! $text)
			{
				$checkoutUser = new JUser($article->checked_out);
				$date         = JHtml::_('date', $article->checked_out_time);

				$tooltip = $this->getAttribute('tooltip') ?: JText::sprintf('COM_CONTENT_CHECKED_OUT_BY', $checkoutUser->name) . ' <br> ' . $date;

				$text = JLayout::render(
					'joomla.content.icons.edit_lock',
					[
						'article' => $article,
						'tooltip' => $tooltip,
						'legacy'  => false,
					]
				);
			}

			return JHtml::_('link', '#', $text, $attribs);
		}

		if ( ! $text)
		{
			$tooltip = $this->getAttribute('tooltip')
				?: (
				$article->state === JWorkflow::CONDITION_UNPUBLISHED
					? JText::_('COM_CONTENT_EDIT_UNPUBLISHED_ARTICLE')
					: JText::_('COM_CONTENT_EDIT_PUBLISHED_ARTICLE')
				);

			$text = JLayout::render(
				'joomla.content.icons.edit',
				[
					'article' => $article,
					'tooltip' => $tooltip,
					'legacy'  => false,
				]
			);
		}

		return JHtml::_('link', $this->getEditUrl(), $text, $attribs);
	}

	private function getEditLinkStart()
	{
		return '<a href="' . $this->getEditUrl() . '">';
	}

	private function getEditUrl()
	{
		if ( ! $this->canEdit() || $this->isCheckedOut())
		{
			return '';
		}

		$slug = $this->get('alias') ? ($this->get('id') . ':' . $this->get('alias')) : $this->get('id');

		$uri = JUri::getInstance();

		$contentUrl = JContentHelperRoute::getArticleRoute($slug, $this->get('catid'), $this->get('language'));
		$url        = $contentUrl . '&task=article.edit&a_id=' . $this->get('id') . '&return=' . base64_encode($uri);

		return JRoute::_($url);
	}

	private function getHits()
	{
		return $this->getValueViaLayout('hits');
	}

	private function getLinkStart()
	{
		$attribs = [
			'href'       => $this->getSefUrl(),
			'class'      => $this->getAttribute('class'),
			'itemprop'   => 'url',
			'aria-label' => JText::sprintf('JGLOBAL_READ_MORE_TITLE', htmlspecialchars($this->get('title'), ENT_QUOTES, 'UTF-8')),
		];

		return '<a ' . RL_HtmlTag::flattenAttributes($attribs) . '>';
	}

	private function getModified()
	{
		return $this->getValueViaLayout('modified', 'joomla.content.info_block.modify_date');
	}

	private function getPublishUp()
	{
		return $this->getValueViaLayout('publish_up', 'joomla.content.info_block.publish_date');
	}

	private function getRawArticleText($key = 'text')
	{
		switch ($key)
		{
			case 'introtext':
			case 'fulltext':
				return $this->values['article.' . $key];

			default:
				return $this->get('introtext')
					. $this->get('fulltext');
		}
	}

	private function getReadMore()
	{
		$config = JComponentHelper::getParams('com_content');
		$config->set('access-view', true);

		$item = (object) [
			'title'                => $this->get('title'),
			'alternative_readmore' => $this->get('attribs.alternative_readmore'),
		];

		if ($this->hasAttribute('text'))
		{
			$item->alternative_readmore = str_replace('%title%', $item->title, $this->getAttribute('text'));

			if ( ! $this->hasAttribute('add-title'))
			{
				$this->setAttribute('add-title', false);
			}
		}

		$add_title = $this->getAttribute('add-title', Params::get()->add_readmore_title);
		$config->set('show_readmore_title', $add_title);

		$url = $this->getSefUrl();

		$layout = $this->getAttribute('layout', true);

		if ( ! $layout || $this->hasAttribute('class', true))
		{
			$text    = $this->getReadMoreText($item, $config);
			$attribs = [
				'class'      => $this->getAttribute('class'),
				'itemprop'   => 'url',
				'aria-label' => JText::sprintf('JGLOBAL_READ_MORE_TITLE', htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8')),
			];

			return JHtml::_('link', $url, $text, $attribs);
		}

		$layout_id = Layout::getId($this->getAttribute('layout', true), 'joomla.content.readmore');

		return JLayout::render(
			$layout_id,
			[
				'item'   => $item,
				'params' => $config,
				'link'   => $url,
			]
		);
	}

	private function getReadMoreText($item, $config)
	{
		if ( ! $config->get('show_readmore_title'))
		{
			return $item->alternative_readmore ?: JText::_('COM_CONTENT_READ_MORE_TITLE');
		}

		$text = $item->alternative_readmore ?: JText::_('COM_CONTENT_READ_MORE');

		return $text . ' ' . JHtml::_('string.truncate', $item->title, $config->get('readmore_limit'));
	}

	private function getSefUrl()
	{
		if ( ! PluginTag::$use_sef)
		{
			return $this->getUrl();
		}

		return JRoute::link('site', $this->getUrl(), true);
	}

	private function getUrl()
	{
		$slug = $this->get('alias') ? ($this->get('id') . ':' . $this->get('alias')) : $this->get('id');

		return JContentHelperRoute::getArticleRoute($slug, $this->get('catid'), $this->get('language'));
	}

	private function getValueViaLayout($key, $default_layout = '')
	{
		$layout = $this->getAttribute('layout', false);

		if ( ! $layout)
		{
			return $this->get('article.' . $key);
		}

		$default_layout = $default_layout ?: 'joomla.content.info_block.' . $key;
		$layout_id      = Layout::getId($this->getAttribute('layout', true), $default_layout);

		$displayData = [
			'item' => (object) [
				RL_String::toUnderscoreCase($key) => $this->get('article.' . $key),
			],
		];

		return JLayout::render($layout_id, $displayData);
	}

	private function hasAccess()
	{
		return in_array($this->get('access'), Params::getAuthorisedViewLevels());
	}

	private function hit()
	{
		if ( ! Params::get()->increase_hits_on_text)
		{
			return;
		}

		$article_id = $this->get('id');

		$table = JTable::getInstance('Content', 'JTable');
		$table->hit($article_id);
	}

	private function isCheckedOut()
	{
		$checked_out = $this->get('checked-out');
		$user        = JFactory::getApplication()->getIdentity() ?: JFactory::getUser();

		return $checked_out && $checked_out != $user->get('id');
	}
}

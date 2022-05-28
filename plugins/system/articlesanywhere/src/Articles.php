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

use Exception;
use Joomla\CMS\Factory as JFactory;
use Joomla\Database\DatabaseQuery as JDatabaseQuery;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataTags\DataTags;
use RegularLabs\Plugin\System\ArticlesAnywhere\Filters\Filter;
use RegularLabs\Plugin\System\ArticlesAnywhere\Filters\Filters;
use RegularLabs\Plugin\System\ArticlesAnywhere\ForeachTags\Tags as ForeachTags;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\CurrentArticle;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\IfStatements\IfStatements;
use RegularLabs\Plugin\System\ArticlesAnywhere\Numbers\Numbers;
use RegularLabs\Plugin\System\ArticlesAnywhere\Orderings\Orderings;
use RegularLabs\Plugin\System\ArticlesAnywhere\Pagination\Pagination;

class Articles
{
	/**
	 * @var Article[]
	 */
	private $articles;
	/**
	 * @var array
	 */
	private $articles_values;
	/**
	 * @var array
	 */
	private $attributes;
	/**
	 * @var array
	 */
	private $current_article_values;
	/**
	 * @var DataTags
	 */
	private $data_tags;
	/**
	 * @var Filters[]
	 */
	private $filter_groups;
	/**
	 * @var ForeachTags
	 */
	private $foreach_tags;
	/**
	 * @var string
	 */
	private $html;
	/**
	 * @var IfStatements
	 */
	private $if_statements;
	/**
	 * @var int
	 */
	private $limit = 1;
	/**
	 * @var Numbers[]
	 */
	private $numbers = [];
	/**
	 * @var Numbers
	 */
	private $numbers_template;
	/**
	 * @var int
	 */
	private $offset = 0;
	/**
	 * @var Orderings
	 */
	private $orderings;
	/**
	 * @var Pagination
	 */
	private $pagination;
	/**
	 * @var int
	 */
	private $pagination_limit;
	/**
	 * @var int
	 */
	private $pagination_offset;
	/**
	 * @var object
	 */
	private $params;
	/**
	 * @var DataGroup[]
	 */
	private $select_data_groups;
	/**
	 * @var string
	 */
	private $tag_type;
	/**
	 * @var int
	 */
	private $total = 0;


	/**
	 * Articles constructor.
	 *
	 * @param string    $html
	 * @param Filters[] $filter_groups
	 * @param array     $attributes
	 * @param string    $tag_type 'article' or 'articles'
	 * @param object    $database 'article' or 'articles'
	 */
	public function __construct($html, $filter_groups, $attributes = null, $tag_type = 'article', $database = null)
	{
		if (empty($html))
		{
			$html = '[article]';
		}

		$this->html       = $html;
		$this->attributes = $attributes;

		if (isset($this->attributes->per_page) && ! isset($this->attributes->pagination))
		{
			$this->attributes->pagination = true;
		}

		$this->params   = Params::get($this->attributes);
		$this->tag_type = $tag_type;


		$this->filter_groups = $filter_groups;
		$this->data_tags     = new DataTags($html);
		$this->if_statements = new IfStatements($html);
		$this->foreach_tags  = new ForeachTags($html);

		$this->setFilterValues();

		$data_tag_data_groups     = $this->data_tags->getDataGroups();
		$if_statement_data_groups = $this->if_statements->getDataGroups();

		$this->select_data_groups = array_merge(
			$data_tag_data_groups,
			$if_statement_data_groups
		);


		$this->articles_values = $this->getValues();
		$this->total           = $this->total ?: count($this->articles_values);


		$total_results       = count($this->articles_values);
		$total_no_limits     = $this->total;
		$total_no_pagination = min($this->limit, $this->total - $this->getOffset());

		$this->numbers_template = new Numbers(
			$total_results,
			$total_no_limits,
			$total_no_pagination,
			$this->limit,
			$this->offset
		);

		$this->articles = $this->getArticles();
	}

	/**
	 * @return void
	 */
	private function setFilterValues()
	{
		$values = $this->getCurrentArticleValues();

		foreach ($this->filter_groups as $filters)
		{
			$data_groups = $filters->getValueDataGroups();

			foreach ($data_groups as $data_group)
			{
				$data_group->setValues($values, null);
			}
		}
	}

	/**
	 *
	 */
	private function setOffsetAndLimits()
	{
	}

	/**
	 * @return array|mixed
	 */
	private function getValues()
	{


		return $this->getValuesFromSingleArticleTag();
	}

	/**
	 * @return Pagination
	 */
	private function getPagination()
	{
	}

	/**
	 * @return int
	 */
	private function getOffset()
	{
	}

	/**
	 * @return Article[]
	 */
	private function getArticles()
	{
		$articles = [];


		foreach ($this->articles_values as $i => $article_values)
		{
			/* @var DataTags $data_tags */
			$data_tags = RL_Object::clone($this->data_tags);
			/* @var IfStatements $if_statements */
			$if_statements = RL_Object::clone($this->if_statements);
			/* @var ForeachTags $foreach_tags */
			$foreach_tags = RL_Object::clone($this->foreach_tags);

			$count = $i + 1;

			$this->numbers[$count] = RL_Object::clone($this->numbers_template);

			$articles[] = new Article(
				$data_tags,
				$if_statements,
				$foreach_tags,
				$this,
				$this->html,
				$count,
				$this->numbers_template->get('total')
			);
		}

		return $articles;
	}

	/**
	 * @return array
	 */
	public function getCurrentArticleValues()
	{
		$article_id = CurrentArticle::getId();

		if ( ! is_null($this->current_article_values))
		{
			return $this->current_article_values;
		}

		/* @var DataGroup[] $data_groups */
		$data_groups = array_merge(
			$this->data_tags->getCurrentDataGroups(),
			$this->if_statements->getDataGroups()
		);


		foreach ($this->filter_groups as $filters)
		{
			$data_groups = array_merge($data_groups, $filters->getValueDataGroups());
		}

		$values = $this->getResultsFromDatabase(
			[],
			[$article_id],
			true,
			false,
			false,
			true,
			$data_groups,
			true
		);

		$this->current_article_values = $values[0] ?? [];

		return $this->current_article_values;
	}

	protected function isSingle()
	{


		return true;
	}

	/**
	 * @throws Exception
	 */
	private function setPaginationOffsetAndLimit()
	{
	}

	/**
	 * @return int
	 */
	private function getLimit()
	{
	}

	/**
	 * @param null $filters
	 *
	 * @return array|mixed
	 */
	private function getValuesFromSingleArticleTag($filters = null)
	{
		$filters = $filters ?? $this->filter_groups[0]->get();

		return $this->getResultsFromDatabase(
			$filters,
			[],
			true,
			false,
			true
		);
	}

	/**
	 * @param array $filters
	 * @param array $ids
	 * @param bool  $get_values
	 * @param bool  $apply_ordering
	 * @param bool  $apply_limits
	 * @param null  $data_groups
	 *
	 * @return array|mixed
	 */
	private function getResultsFromDatabase(
		$filters = [],
		$ids = [],
		$get_values = true,
		$apply_ordering = true,
		$apply_limits = true,
		$allow_caching = true,
		$data_groups = null,
		$use_local_db = false
	)
	{
		$selects   = [];
		$joins     = [];
		$wheres    = [];
		$group_bys = [];

		$selects[] = DB::quoteName('article.id', 'article.id');

		if ($get_values)
		{
			$selects = array_merge($selects, $this->getValueSelects($data_groups));
			$joins   = array_merge($joins, $this->getValueJoins($data_groups));
		}

		if ( ! empty($filters))
		{
			$selects = array_merge($selects, $this->getFilterSelects($filters));
			$wheres  = array_merge($wheres, $this->getFilterWheres($filters));
			$joins   = array_merge($joins, $this->getFilterJoins($filters));

		}

		$wheres = RL_Array::clean($wheres);

		if (empty($filters) || empty($wheres))
		{
			$wheres[] = DB::is('article.id', $ids, ['handle_wildcards' => false]);
		}


		$selects   = RL_Array::clean($selects);
		$joins     = RL_Array::clean($joins);
		$wheres    = RL_Array::clean($wheres);
		$group_bys = RL_Array::clean($group_bys);

		if (empty($selects))
		{
			return [];
		}

		$query = DB::getQuery()
			->from(DB::quoteName('#__content', 'article'))
			->select($selects)
			->where($wheres);

		foreach ($joins as $table => $condition)
		{
			$query->join('LEFT', $table, $condition);
		}

		$this->addCategoryStateChecks($query);


		if ( ! $apply_ordering && count($ids) > 1)
		{
			// Use the FIELD() function to keep the ordering of the given $ids
			$query->order('FIELD(article.id,' . implode(',', $ids) . ')');
		}


//		if ( ! $get_values)
//		{
//			$this->setState($query);
//			$this->setAccess($query);
//			$this->setLanguage($query);
//		}

		$offset = 0;
		$limit  = 1;

		$query->setLimit($limit, $offset);


		return DB::getResults($query, $get_values ? 'assocList' : 'column', $use_local_db, $allow_caching);
	}

	/**
	 * @param JDatabaseQuery $query
	 */
	private function addCategoryStateChecks($query)
	{
		// Join to check for category published state in parent categories up the tree
		// If all categories are published, badcats.id will be null, and we just use the article state
		$subquery = DB::getQuery()
			->from(DB::quoteName('#__categories', 'category'))
			->select('category.id')
			->join(
				'LEFT',
				DB::quoteName('#__categories', 'parent'),
				DB::quoteName('category.lft') . ' BETWEEN ' . DB::quoteName('parent.lft') . ' AND ' . DB::quoteName('parent.rgt')
			)
			->where(DB::quoteName('parent.extension') . ' = ' . DB::quote('com_content'))
			->where(DB::quoteName('parent.published') . ' <= 0')
			->group('category.id');

		$query->join(
			'LEFT OUTER',
			'(' . (string) $subquery . ') AS `badcats`',
			DB::quoteName('badcats.id') . ' = ' . DB::quoteName('article.catid')
		)->where('(CASE WHEN ' . DB::quoteName('badcats.id') . ' IS NULL THEN ' . DB::quoteName('article.state') . ' ELSE 0 END) > 0');
	}

	/**
	 * @param $ids
	 *
	 * @return array|mixed
	 */
	private function getValuesForSingleArticleID($ids)
	{
	}

	/**
	 * @param $nr_of_results
	 *
	 * @return bool
	 */
	private function shouldUseSeparateLimitOrderingQuery($nr_of_results)
	{
	}

	/**
	 * @param null $data_groups
	 *
	 * @return array
	 */
	private function getValueSelects($data_groups = null)
	{
		$data_groups = $data_groups ?? $this->select_data_groups;

		$selects = [];

		foreach ($data_groups as $data_group)
		{
			$selects = array_merge($selects, $data_group->getSelects());
		}

		return $selects;
	}

	/**
	 * @param null $data_groups
	 *
	 * @return array
	 */
	private function getValueJoins($data_groups = null)
	{
		$data_groups = $data_groups ?? $this->select_data_groups;

		$joins = [];

		foreach ($data_groups as $data_group)
		{
			$joins = array_merge($joins, $data_group->getJoins());
		}

		return $joins;
	}

	/**
	 * @param $filters Filter[]
	 *
	 * @return array
	 */
	private function getFilterSelects($filters)
	{
		$selects = [];

		foreach ($filters as $filter)
		{
			$selects = array_merge($selects, $filter->getDataGroup()->getSelectsForFilters());
		}

		return $selects;
	}

	/**
	 * @param $filters Filter[]
	 *
	 * @return array
	 */
	private function getFilterWheres($filters)
	{
		$wheres = [];

		// Add ignore filters for Articles
		$data_group = DataHelper::getDataGroup('id', $this->attributes, 'Article');

		$ignores_where = $data_group->getIgnoreWhere();

		if ( ! empty($ignores_where))
		{
			$wheres[] = $ignores_where;
		}

		foreach ($filters as $filter)
		{
			$where = $filter->getDataGroup()->getWhere($filter->getValues(), $filter->getGlue());

			if (empty($where))
			{
				continue;
			}

			$wheres[] = $where;

			// Add ignore filters for specific DataGroup
			// But we can ignore the Articles DataGroup as we already added those
			if ($filter->getDataGroup()->getGroupName() === 'Articles')
			{
				continue;
			}

			$ignores_where = $filter->getDataGroup()->getIgnoreWhere();

			if (empty($ignores_where))
			{
				continue;
			}

			$wheres[] = $ignores_where;
		}

		return $wheres;
	}

	/**
	 * @param $filters Filter[]
	 *
	 * @return array
	 */
	private function getFilterJoins($filters)
	{
		$joins = [];

		foreach ($filters as $filter)
		{
			$joins = array_merge($joins, $filter->getDataGroup()->getJoinsForFilters());
		}

		return $joins;
	}

	/**
	 * @param $filters Filter[]
	 *
	 * @return array
	 */
	private function getGroupBys($filters)
	{
	}

	/**
	 * @return array
	 */
	private function getOrderingSelects()
	{
	}

	/**
	 * @return array
	 */
	private function getOrderingJoins()
	{
	}

	/**
	 * @param $query
	 */
	private function applyOrdering($query)
	{
	}

	/**
	 * @return int
	 */
	private function getQueryOffset()
	{
	}

	/**
	 * @return int|mixed
	 */
	private function getQueryLimit()
	{
	}

	/**
	 * @return bool
	 */
	private function hasExoticOrderings()
	{
	}

	/**
	 * @param int|string $count
	 *
	 * @return array
	 */
	public function getArticleValues($count)
	{
		if ($count === 'current')
		{
			return $this->getCurrentArticleValues();
		}

		return $this->articles_values[$count - 1] ?? [];
	}

	/**
	 * @param int $count
	 *
	 * @return Numbers|null
	 */
	public function getNumbers($count)
	{
		return $this->numbers[$count] ?? null;
	}

	/**
	 * @return string
	 */
	public function render()
	{
		if (empty($this->articles))
		{
			return $this->params->output_when_empty ?? '';
		}

		$html = [];

		foreach ($this->articles as $article)
		{
			$html[] = $article->render();
		}



		return RL_Array::implode($html, '');
	}

	/**
	 * @param string $position
	 *
	 * @return string
	 */
	public function renderPagination($position)
	{
	}
}

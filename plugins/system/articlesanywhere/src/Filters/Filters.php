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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Filters;

defined('_JEXEC') or die;

use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\CurrentArticle;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;

class Filters
{
	private $filters = [];
	private $params;

	/**
	 * @param array $filter_data
	 * @param array $params
	 */
	public function __construct($filter_data, $params = [])
	{
		if (empty($filter_data))
		{
			$filter_data = [
				'id' => CurrentArticle::getId(),
			];
		}

		$this->params = $params;
		$this->setFilters($filter_data);
	}

	private function setFilters($data)
	{
		if (empty($data))
		{
			return;
		}

		$this->removeUnsupportedFilterKeys($data);

		foreach ($data as $key => $data_value)
		{
			$data_group = DataHelper::getDataGroup($key, $this->params);

			if ( ! $data_group)
			{
				continue;
			}

			$this->filters[] = new Filter($key, $data_value, $data_group);
		}
	}

	private function removeUnsupportedFilterKeys(&$data)
	{
		$data = array_intersect_key(
			(array) $data,
			[
				'article' => '',
				'title'   => '',
				'alias'   => '',
				'id'      => '',
			]
		);
	}

	/**
	 * @return Filter[]
	 */
	public function get()
	{
		return $this->filters;
	}

	/**
	 * @return array
	 */
	public function getValueDataGroups()
	{
		$data_groups = [];

		foreach ($this->filters as $filter)
		{
			$data_groups = array_merge($data_groups, $filter->getValueDataGroups());
		}

		return $data_groups;
	}
}

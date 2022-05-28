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

use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Date;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;

class Filter
{
	/* @var DataGroup */
	private $data_group;
	private $glue = 'OR';
	private $key;
	/* @var Value[] */
	private $values = [];

	/**
	 * @param string    $key
	 * @param string    $values
	 * @param DataGroup $data_group
	 */
	public function __construct($key, $values, $data_group)
	{
		$this->data_group = $data_group;
		$this->key        = $key;

		$this->setValuesAndGlue($values);
	}

	private function setValuesAndGlue($values)
	{
		if (is_numeric($values))
		{
			$this->setValues([$values]);

			return;
		}

		if (empty($values))
		{
			return;
		}

		if (Data::isRange($values))
		{
			$this->setValues([$values]);

			return;
		}

		$clean_value = DB::removeOperator($values);
		$is_date     = Date::valueIsDate(Data::placeholderToDate($clean_value));

		if ($values !== '+' && ! $is_date)
		{
			// comma is alias of the OR separator: ||
			$values = implode(' || ', RL_Array::toArray($values, ','));

			// plus is alias of the AND separator: &&
			$values = implode(' && ', RL_Array::toArray($values, '+'));
		}

		// no AND separator (&&) found. So handle everything as OR's
		if (strpos($values, ' && ') === false)
		{
			$values = RL_Array::toArray($values, ' || ');
			$this->setValues($values);

			return;
		}

		// AND separator (&&) found. So handle everything as AND's
		$this->glue = 'AND';

		$values = str_replace(' || ', ' && ', $values);
		$values = RL_Array::toArray($values, ' && ');

		$this->setValues($values);
	}

	private function setValues($values)
	{
		foreach ($values as $value)
		{
			if ($value === 'current')
			{
				$value = 'this:' . $this->key;
			}

			$this->values[] = new Value($value);
		}
	}

	public function getDataGroup()
	{
		return $this->data_group;
	}

	public function getGlue()
	{
		return $this->glue;
	}

	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @return array
	 */
	public function getValueDataGroups()
	{
		$data_groups = [];

		foreach ($this->values as $value)
		{
			$data_group = $value->getDataGroup();

			if ( ! $data_group)
			{
				continue;
			}

			$data_groups[] = $data_group;
		}

		return $data_groups;
	}

	public function getValues()
	{
		$values = [];

		foreach ($this->values as $value)
		{
			$values[] = $value->get();
		}

		return $values;
	}
}

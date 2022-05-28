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
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Data as DataHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;

class Value
{
	/* @var DataGroup */
	private $data_group;
	private $value;

	/**
	 * @param string $value
	 */
	public function __construct($value)
	{
		$this->value = $value;

		$this->setDataGroup();
	}

	/**
	 * @return ValuesObject
	 */
	public function get()
	{
		if ($this->data_group)
		{
			return new ValuesObject($this->data_group->getOutputRaw());
		}

		$value = DataHelper::getRangeObject($this->value);

		if ($value instanceof ValuesObject)
		{
			return $value;
		}

		$value = DataHelper::getDateObject($this->value);

		if ($value instanceof ValuesObject)
		{
			return $value;
		}

		$operator = DB::getOperator($value);
		$value    = DB::removeOperator($value);

		if (in_array($operator, ['<=', '<', '<>'], true) && $value !== 'NULL')
		{
			return new ValuesObject(
				[
					$operator . $value,
					'NULL',
				],
				'OR'
			);
		}

		return new ValuesObject($operator . $value);
	}

	public function getDataGroup()
	{
		return $this->data_group;
	}

	public function setDataGroup()
	{
		if (strpos($this->value, ':') === false)
		{
			return;
		}

		[$prefix] = RL_Array::toArray($this->value, ':');

		if ( ! in_array($prefix, ['this', 'input', 'user'], true))
		{
			return;
		}

		$this->data_group = DataHelper::getDataGroup($this->value);
	}
}


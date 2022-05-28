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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\IfStatements;

defined('_JEXEC') or die;

use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\DataGroup;

class Tag
{
	/**
	 * @var Condition[]
	 */
	private $conditions;
	/**
	 * @var array
	 */
	private $match;
	/**
	 * @var string
	 */
	private $type;

	/**
	 * @param array $match
	 */
	public function __construct($match)
	{
		$this->match = $match;

		$this->type = str_replace(' ', '', $match['type']);

		$this->conditions = new Conditions($match['condition'] ?? '');
	}

	/**
	 * @return Condition[]
	 */
	public function getConditions()
	{
		return $this->conditions->getConditions();
	}

	/**
	 * @return DataGroup[]
	 */
	public function getDataGroups()
	{
		$data_groups = [];

		foreach ($this->conditions->getConditions() as $condition)
		{
			$data_groups = array_merge($data_groups, $condition->getDataGroups());
		}

		return $data_groups;
	}

	/**
	 * @return string
	 */
	public function getOutput()
	{
		return $this->match['content'];
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return bool
	 */
	public function pass()
	{
		if ($this->type === 'else')
		{
			return true;
		}

		$pass     = false;
		$operator = $this->conditions->getOperator();

		foreach ($this->conditions->getConditions() as $condition)
		{
			$pass = $condition->pass();

			if ($pass && $operator === 'OR')
			{
				return true;
			}

			if ( ! $pass && $operator === 'AND')
			{
				return false;
			}
		}

		return $pass;
	}

//	/**
//	 * @param string $html
//	 */
//	public function replace(&$html)
//	{
//		$output = $this->getOutput();
//
//		$html = RL_String::replaceOnce($this->match[0], $output, $html);
//	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @param array   $values
	 * @param Numbers $numbers
	 */
	public function setValues($values, Numbers $numbers)
	{
		foreach ($this->conditions->getConditions() as $condition)
		{
			$condition->setValues($values, $numbers);
		}
	}
}

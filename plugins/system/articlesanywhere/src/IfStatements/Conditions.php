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

use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\StringHelper as RL_String;

class Conditions
{
	/**
	 * @var array
	 */
	private $conditions = [];
	/**
	 * @var string
	 */
	private $operator = 'AND';

	/**
	 * @param string $string
	 */
	public function __construct($string)
	{
		$this->setConditions($string);
	}

	/**
	 * @param string $string
	 */
	private function setConditions($string)
	{
		if (empty($string))
		{
			return;
		}

		$string = RL_String::html_entity_decoder($string);

		$string = str_replace(
			[' AND ', ' OR '],
			[' && ', ' || '],
			$string
		);

		$this->operator = strpos($string, ' || ') !== false ? 'OR' : 'AND';

		$string = str_replace(' && ', ' || ', $string);

		$parts = RL_Array::toArray($string, ' || ');

		foreach ($parts as $part)
		{
			$this->conditions[] = new Condition($part);
		}
	}

	/**
	 * @return Condition[]
	 */
	public function getConditions()
	{
		return $this->conditions;
	}

	/**
	 * @return string
	 */
	public function getOperator()
	{
		return $this->operator;
	}
}

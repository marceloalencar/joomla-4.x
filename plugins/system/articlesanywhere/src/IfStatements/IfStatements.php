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

use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\Numbers\Numbers;

class IfStatements
{
	/**
	 * @var IfStatement[]
	 */
	private $statements = [];

	/**
	 * @param string $string
	 */
	public function __construct($string)
	{
		$this->setStatements($string);
	}

	/**
	 * @param $string
	 */
	private function setStatements($string)
	{
		$regex = Params::getRegex('ifstatement');

		RL_RegEx::matchAll($regex, $string, $matches);

		if (empty($matches))
		{
			return;
		}

		foreach ($matches as $match)
		{
			$this->statements[] = new IfStatement($match);
		}
	}

	/**
	 * @return array
	 */
	public function getDataGroups()
	{
		$data_groups = [];

		foreach ($this->statements as $if_statement)
		{
			$data_groups = array_merge($data_groups, $if_statement->getDataGroups());
		}

		return $data_groups;
	}

	/**
	 * @return IfStatement[]
	 */
	public function getStatements()
	{
		return $this->statements;
	}

	/**
	 * @param $html
	 */
	public function replace(&$html)
	{
		foreach ($this->statements as $if_statement)
		{
			$if_statement->replace($html);
		}
	}

	/**
	 * @param array   $values
	 * @param Numbers $numbers
	 */
	public function setValues($values, Numbers $numbers)
	{
		foreach ($this->statements as &$if_statement)
		{
			$if_statement->setValues($values, $numbers);
		}
	}

}

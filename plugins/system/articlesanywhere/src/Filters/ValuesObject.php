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

class ValuesObject
{
	private $glue;
	private $values;

	/**
	 * @param array|string $value
	 * @param string       $glue
	 */
	public function __construct($values, $glue = 'AND')
	{
		$this->values = is_array($values) ? $values : [$values];
		$this->glue   = $glue;
	}

	/**
	 * @return string
	 */
	public function getGlue()
	{
		return $this->glue;
	}

	/**
	 * @return array
	 */
	public function getValues()
	{
		return $this->values;
	}
}


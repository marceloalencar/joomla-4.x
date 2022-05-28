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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\ForeachTags;

defined('_JEXEC') or die;

use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;
use RegularLabs\Plugin\System\ArticlesAnywhere\Numbers\Numbers;

class Tags
{
	/**
	 * @var Tag[]
	 */
	private $tags = [];

	/**
	 * @param string $string
	 */
	public function __construct($string)
	{
	}

	/**
	 * @param string $string
	 */
	private function setTags($string)
	{
	}

	/**
	 * @return array
	 */
	public function getDataGroups()
	{
	}

	/**
	 * @return Tag[]
	 */
	public function getTags()
	{
	}

	/**
	 * @param $html
	 */
	public function replace(&$html)
	{
	}

	/**
	 * @param array   $values
	 * @param Numbers $numbers
	 */
	public function setValues($values, Numbers $numbers)
	{
	}
}

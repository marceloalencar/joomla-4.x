<?php
/**
 * @package         Regular Labs Library
 * @version         22.5.9993
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Library\Form\Field;

defined('_JEXEC') or die;

use RegularLabs\Library\DB as RL_DB;
use RegularLabs\Library\Form\FormField as RL_FormField;

class TagsField extends RL_FormField
{
	static $options         = null;
	public $is_select_list  = true;
	public $use_ajax        = true;
	public $use_tree_select = true;

	public function getNamesByIds($values, $attributes)
	{
		$query = $this->db->getQuery(true)
			->select('a.title')
			->from('#__tags AS a')
			->where(RL_DB::is('a.id', $values))
			->order('a.title');

		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	protected function getOptions()
	{
		if ( ! is_null(self::$options))
		{
			return self::$options;
		}

		$query = $this->db->getQuery(true)
			->select('a.id as value, a.title as text, a.parent_id AS parent')
			->from('#__tags AS a')
			->select('COUNT(DISTINCT b.id) - 1 AS level')
			->join('LEFT', '#__tags AS b ON a.lft > b.lft AND a.rgt < b.rgt')
			->where('a.alias <> ' . $this->db->quote('root'))
			->where('a.published IN (0,1)')
			->group('a.id')
			->order('a.lft ASC');
		$this->db->setQuery($query);

		self::$options = $this->db->loadObjectList();

		return self::$options;
	}
}

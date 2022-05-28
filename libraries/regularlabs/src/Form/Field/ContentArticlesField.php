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

use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\DB as RL_DB;
use RegularLabs\Library\Form\Form;
use RegularLabs\Library\Form\FormField as RL_FormField;

class ContentArticlesField extends RL_FormField
{
	public $is_select_list = true;
	public $use_ajax       = true;

	public function getNamesByIds($values, $attributes)
	{
		$query = $this->db->getQuery(true)
			->from('#__content AS i')
			->select('i.id, i.title as name, i.language, c.title as category, i.state as published')
			->join('LEFT', '#__categories AS c ON c.id = i.catid')
			->where(RL_DB::is('i.id', $values))
			->order('i.title, i.ordering, i.id');
		$this->db->setQuery($query);
		$articles = $this->db->loadObjectList();

		return Form::getNamesWithExtras($articles, ['language', 'category', 'id', 'unpublished']);
	}

	protected function getOptions()
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from('#__content AS i')
			->where('i.access > -1');
		$this->db->setQuery($query);
		$total = $this->db->loadResult();

		if ($total > $this->max_list_count)
		{
			return -1;
		}

		$query->clear('select')
			->select('i.id, i.title as name, i.language, c.title as category, i.state as published')
			->join('LEFT', '#__categories AS c ON c.id = i.catid')
			->order('i.title, i.ordering, i.id');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		$options = $this->getOptionsByList($list, ['language', 'category', 'id', 'unpublished']);

		if ($this->get('showselect'))
		{
			array_unshift($options, JHtml::_('select.option', '-', '&nbsp;', 'value', 'text', true));
			array_unshift($options, JHtml::_('select.option', '-', '- ' . JText::_('Select Item') . ' -'));
		}

		return $options;
	}
}

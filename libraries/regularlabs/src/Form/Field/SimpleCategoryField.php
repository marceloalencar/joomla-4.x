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
use Joomla\CMS\Layout\FileLayout as JFileLayout;
use RegularLabs\Library\Form\FormField as RL_FormField;

class SimpleCategoryField extends RL_FormField
{
	protected function getInput()
	{
		$categories = $this->getOptions();
		$options    = parent::getOptions();

		$options = array_merge($options, $categories);

		if ($this->get('show_none', true))
		{
			$empty_option        = JHtml::_('select.option', $this->get('none_value', ''), '- ' . JText::_('JNONE') . ' -');
			$empty_option->class = 'hidden';
			array_unshift($options, $empty_option);
		}

		if ($this->get('show_keep_original'))
		{
			$keep_original_option = JHtml::_('select.option', ' ', '- ' . JText::_('RL_KEEP_ORIGINAL_CATEGORY') . ' -');

			array_unshift($options, $keep_original_option);
		}

		$data                = $this->getLayoutData();
		$data['options']     = $options;
		$data['placeholder'] = JText::_($this->get('hint', 'RL_SELECT_OR_CREATE_A_CATEGORY'));
		$data['allowCustom'] = $this->get('allow_custom', true);

		return (new JFileLayout(
			'regularlabs.form.field.simplecategory',
			JPATH_SITE . '/libraries/regularlabs/layouts'
		))->render($data);
	}

	protected function getOptions()
	{
		$table = $this->get('table');

		if ( ! $table)
		{
			return [];
		}

		// Get the user groups from the database.
		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('category', 'value'),
				$this->db->quoteName('category', 'text'),
			])
			->from($this->db->quoteName('#__' . $table))
			->where($this->db->quoteName('category') . ' != ' . $this->db->quote(''))
			->group($this->db->quoteName('category'))
			->order($this->db->quoteName('category') . ' ASC');
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}
}

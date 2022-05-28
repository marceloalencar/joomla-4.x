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

use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Layout\FileLayout as JFileLayout;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Form\FormField as RL_FormField;

class CustomOptionsField extends RL_FormField
{
	protected function getInput()
	{
		$data                = $this->getLayoutData();
		$data['options']     = $this->getOptions();
		$data['value']       = RL_Array::toArray($this->value);
		$data['placeholder'] = JText::_('RL_ENTER_NEW_VALUES');

		return (new JFileLayout(
			'regularlabs.form.field.customoptions',
			JPATH_SITE . '/libraries/regularlabs/layouts'
		))->render($data);
	}

	protected function getOptions()
	{
		$values = RL_Array::toArray($this->value);

		$options = [];

		foreach ($values as $value)
		{
			$options[] = (object) [
				'value' => $value,
				'text'  => $value,
			];
		}

		return $options;
	}
}

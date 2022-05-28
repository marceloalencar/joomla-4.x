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
use RegularLabs\Library\Form\FormField as RL_FormField;

class LanguagesField extends RL_FormField
{
	public $is_select_list = true;

	public function getNamesByIds($values, $attributes)
	{
		$languages = JHtml::_('contentlanguage.existing');

		$names = [];

		foreach ($languages as $language)
		{
			if (empty($language->value))
			{
				continue;
			}

			if ( ! in_array($language->value, $values))
			{
				continue;
			}

			$names[] = $language->text . ' [' . $language->value . ']';
		}

		return $names;
	}

	protected function getOptions()
	{
		$languages = JHtml::_('contentlanguage.existing');

		$value = $this->get('value', []);

		if ( ! is_array($value))
		{
			$value = [$value];
		}

		$options = [];

		foreach ($languages as $language)
		{
			if (empty($language->value))
			{
				continue;
			}

			$options[] = (object) [
				'value'    => $language->value,
				'text'     => $language->text . ' [' . $language->value . ']',
				'selected' => in_array($language->value, $value, true),
			];
		}

		return $options;
	}
}

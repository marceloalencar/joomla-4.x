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

use Joomla\CMS\Form\Field\CheckboxesField as JCheckboxesField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text as JText;
use UnexpectedValueException;
use function count;

class CheckboxesField extends JCheckboxesField
{
	/**
	 * Name of the layout being used to render the field
	 *
	 * @var    string
	 */
	protected $layout = 'regularlabs.form.field.checkboxes';

//	/**
//	 * Method to attach a Form object to the field.
//	 *
//	 * @param SimpleXMLElement $element     The SimpleXMLElement object representing the `<field>` tag for the form field object.
//	 * @param mixed            $value       The form field value to validate.
//	 * @param string           $group       The field name group control value. This acts as an array container for the field.
//	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
//	 *                                      full field name would end up being "bar[foo]".
//	 *
//	 * @return  boolean  True on success.
//	 */
//	public function setup(SimpleXMLElement $element, $value, $group = null)
//	{
//		$return = parent::setup($element, $value, $group);
//
//		if ( ! $return || ! empty($value))
//		{
//			return $return;
//		}
//
//		$checked = [];
//
//		foreach ($this->element->children() as $element)
//		{
//			switch ($element->getName())
//			{
//				// The element is an <option />
//				case 'option':
//					$checked[] = (string) ($element['checked'] ?? $element);
//					break;
//
//				// The element is a <group />
//				case 'group':
//					foreach ($element->children() as $option)
//					{
//						//$checked[] = (string) ($option['checked'] ?? $option);
//					}
//					break;
//
//				// Unknown element type.
//				default:
//					break;
//			}
//		}
//
//		$this->checkedOptions = implode(',', $checked);
//
//		return $return;
//	}

	protected function getOptions()
	{
		$groups = $this->getGroups();

		return $this->flattenGroups($groups);
	}

	protected function getGroups()
	{
		$fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
		$groups    = [];
		$label     = 0;

		foreach ($this->element->children() as $element)
		{
			switch ($element->getName())
			{
				// The element is an <option />
				case 'option':
					if ( ! isset($groups[$label]))
					{
						$groups[$label] = [];
					}

					$groups[$label][] = $this->getOption($element, $fieldname);
					break;

				// The element is a <group />
				case 'group':
					// Get the group label.
					$groupLabel = (string) $element['label'];
					if ($groupLabel)
					{
						$label = JText::_($groupLabel);
					}

					// Initialize the group if necessary.
					if ( ! isset($groups[$label]))
					{
						$groups[$label] = [];
					}

					// Iterate through the children and build an array of options.
					foreach ($element->children() as $option)
					{
						// Only add <option /> elements.
						if ($option->getName() !== 'option')
						{
							continue;
						}

						$groups[$label][] = $this->getOption($option, $fieldname);
					}

					if ($groupLabel)
					{
						$label = count($groups);
					}

					break;

				// Unknown element type.
				default:
					throw new UnexpectedValueException(sprintf('Unsupported element %s in GroupedlistField', $element->getName()), 500);
			}
		}

		reset($groups);

		return $groups;
	}

	protected function flattenGroups($groups)
	{
		$options = [];

		foreach ($groups as $group_name => $group)
		{
			if ($group_name !== 0)
			{
				$options[] = $group_name;
			}

			foreach ($group as $option)
			{
				$options[] = $option;
			}
		}

		return $options;
	}

	protected function getOption($option, $fieldname)
	{
		$value = (string) $option['value'];
		$text  = trim((string) $option) != '' ? trim((string) $option) : $value;

		$disabled = (string) $option['disabled'];
		$disabled = ($disabled === 'true' || $disabled === 'disabled' || $disabled === '1');
		$disabled = $disabled || ($this->readonly && $value != $this->value);

		$checked = (string) $option['checked'];
		$checked = ($checked === 'true' || $checked === 'checked' || $checked === '1');

		$selected = (string) $option['selected'];
		$selected = ($selected === 'true' || $selected === 'selected' || $selected === '1');

		$tmp = [
			'value'    => $value,
			'text'     => JText::alt($text, $fieldname),
			'disable'  => $disabled,
			'class'    => (string) $option['class'],
			'selected' => ($checked || $selected),
			'checked'  => ($checked || $selected),
		];

		// Set some event handler attributes. But really, should be using unobtrusive js.
		$tmp['onclick']  = (string) $option['onclick'];
		$tmp['onchange'] = (string) $option['onchange'];

		if ((string) $option['showon'])
		{
			$encodedConditions = json_encode(
				FormHelper::parseShowOnConditions((string) $option['showon'], $this->formControl, $this->group)
			);

			$tmp['optionattr'] = " data-showon='" . $encodedConditions . "'";
		}

		return (object) $tmp;
	}

	protected function getLayoutPaths()
	{
		$paths   = parent::getLayoutPaths();
		$paths[] = JPATH_LIBRARIES . '/regularlabs/layouts';

		return $paths;
	}
}

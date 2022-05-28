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

use InvalidArgumentException;
use Joomla\CMS\Form\Form;
use RuntimeException;
use function count;

defined('_JEXEC') or die;

class SubformField extends \Joomla\CMS\Form\Field\SubformField
{
	/**
	 * @var    string
	 */
	protected $layout = 'regularlabs.form.field.subform.repeatable';

	/**
	 * @param string $name  The property name for which to set the value.
	 * @param mixed  $value The value of the property.
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{

			case 'layout':
				$this->layout = (string) $value;

				if ( ! $this->layout)
				{
					$this->layout = ! $this->multiple ? 'joomla.form.field.subform.default' : 'regularlabs.form.field.subform.repeatable';
				}

				break;

			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Loads the form instance for the subform.
	 *
	 * @return  Form  The form instance.
	 *
	 * @throws  InvalidArgumentException if no form provided.
	 * @throws  RuntimeException if the form could not be loaded.
	 */
	public function loadSubForm()
	{
		$control = $this->name;

		if ($this->multiple)
		{
			$control .= '[' . $this->fieldname . 'X]';
		}

		// Prepare the form template
		$formname = 'subform.' . str_replace(['jform[', '[', ']'], ['', '.', ''], $this->name);

		return $this->loadSubFormByName($formname, $control);
	}

	/**
	 * Loads the form instance for the subform by given name and control.
	 *
	 * @param string $name    The name of the form.
	 * @param string $control The control name of the form.
	 *
	 * @return  Form  The form instance.
	 *
	 * @throws  InvalidArgumentException if no form provided.
	 * @throws  RuntimeException if the form could not be loaded.
	 */
	protected function loadSubFormByName($name, $control)
	{
		// Prepare the form template
		return Form::getInstance($name, $this->formsource, ['control' => $control]);
	}

	/**
	 * Binds given data to the subform and its elements.
	 *
	 * @param Form $subForm Form instance of the subform.
	 *
	 * @return  Form[]  Array of Form instances for the rows.
	 */
	protected function loadSubFormData(Form $subForm)
	{
		$value = $this->value ? (array) $this->value : [];

		// Simple form, just bind the data and return one row.
		if ( ! $this->multiple)
		{
			$subForm->bind($value);

			return [$subForm];
		}

		// Multiple rows possible: Construct array and bind values to their respective forms.
		$forms = [];
		$value = array_values($value);

		// Show as many rows as we have values, but at least min and at most max.
		$c = max($this->min, min(count($value), $this->max));

		for ($i = 0; $i < $c; $i++)
		{
			$control  = $this->name . '[' . $this->fieldname . $i . ']';
			$itemForm = $this->loadSubFormByName($subForm->getName() . $i, $control);

			if ( ! empty($value[$i]))
			{
				$itemForm->bind($value[$i]);
			}

			$forms[] = $itemForm;
		}

		return $forms;
	}

	protected function getLayoutPaths()
	{
		$paths   = parent::getLayoutPaths();
		$paths[] = JPATH_LIBRARIES . '/regularlabs/layouts';

		return $paths;
	}
}

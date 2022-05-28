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

class RangeField extends \Joomla\CMS\Form\Field\RangeField
{
	/**
	 * @var    string
	 */
	protected $layout = 'regularlabs.form.field.range';

	/**
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		$this->value = (float) ($this->value ?: $this->default);

		if ( ! empty($this->max))
		{
			$this->value = min($this->value, $this->max);
		}
		if ( ! empty($this->min))
		{
			$this->value = max($this->value, $this->min);
		}

		return $this->getRenderer($this->layout)->render($this->getLayoutData());
	}

	/**
	 * @return  array
	 */
	protected function getLayoutData()
	{
		$data = parent::getLayoutData();
		// Initialize some field attributes.
		$extraData = [
			'prepend'     => (string) ($this->element['prepend'] ?? ''),
			'append'      => (string) ($this->element['append'] ?? ''),
			'class_range' => (string) ($this->element['class_range'] ?? ''),
		];

		return array_merge($data, $extraData);
	}

	protected function getLayoutPaths()
	{
		$paths   = parent::getLayoutPaths();
		$paths[] = JPATH_LIBRARIES . '/regularlabs/layouts';

		return $paths;
	}
}

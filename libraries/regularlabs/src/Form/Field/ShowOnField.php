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

use RegularLabs\Library\Form\FormField as RL_FormField;
use RegularLabs\Library\ShowOn as RL_ShowOn;

class ShowOnField extends RL_FormField
{
	protected function getInput()
	{
		$value       = (string) $this->get('value');
		$class       = $this->get('class', '');
		$formControl = $this->get('form', $this->formControl);
		$formControl = $formControl == 'root' ? '' : $formControl;

		if ( ! $value)
		{
			return $this->getControlGroupEnd()
				. RL_ShowOn::close()
				. $this->getControlGroupStart();
		}

		return $this->getControlGroupEnd()
			. RL_ShowOn::open($value, $formControl, $this->group, $class)
			. $this->getControlGroupStart();
	}

	protected function getLabel()
	{
		return '';
	}
}

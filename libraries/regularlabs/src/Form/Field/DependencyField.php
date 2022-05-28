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

class DependencyField extends RL_FormField
{
	protected function getInput()
	{
		$file  = $this->get('file');
		$label = $this->get('label', 'the main extension');

		DependencyFieldHelper::setMessage($file, $label);

		return '';
	}

	protected function getLabel()
	{
		return '';
	}
}

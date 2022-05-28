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

use RegularLabs\Library\Extension as RL_Extension;
use RegularLabs\Library\Form\FormField as RL_FormField;

class IsInstalledField extends RL_FormField
{
	protected function getLabel()
	{
		$is_installed = RL_Extension::isInstalled($this->get('extension'), $this->get('extension_type'), $this->get('folder'));

		return '</div><div><input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="' . (int) $is_installed . '">';
	}
}

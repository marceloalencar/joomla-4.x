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

use Joomla\CMS\Form\Field\TextareaField as JTextareaField;
use RegularLabs\Library\Document as RL_Document;

class TextAreaField extends JTextareaField
{
	protected $layout = 'regularlabs.form.field.textarea';

	protected function getLayoutPaths()
	{
		$paths   = parent::getLayoutPaths();
		$paths[] = JPATH_LIBRARIES . '/regularlabs/layouts';

		return $paths;
	}

	protected function getLayoutData()
	{
		RL_Document::script('regularlabs.textarea');

		$data = parent::getLayoutData();

		$extraData = [
			'show_insert_date_name' => (bool) $this->element['show_insert_date_name'] ?? false,
			'add_separator'         => (bool) $this->element['add_separator'] ?? true,
		];

		return array_merge($data, $extraData);
	}
}

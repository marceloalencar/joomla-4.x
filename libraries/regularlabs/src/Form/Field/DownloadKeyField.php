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

use Joomla\CMS\Layout\FileLayout as JFileLayout;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Form\FormField as RL_FormField;

class DownloadKeyField extends RL_FormField
{
	protected function getInput()
	{
		RL_Document::script('regularlabs.script');
		RL_Document::script('regularlabs.downloadkey');

		return (new JFileLayout(
			'regularlabs.form.field.downloadkey',
			JPATH_SITE . '/libraries/regularlabs/layouts'
		))->render(
			[
				'id'        => $this->id,
				'extension' => strtolower($this->get('extension', 'all')),
				'use_modal' => $this->get('use-modal', true),
			]
		);
	}
}

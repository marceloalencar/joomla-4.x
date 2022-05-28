<?php
/**
 * @package         Articles Anywhere
 * @version         12.3.1
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\EditorButtonPlugin as RL_EditorButtonPlugin;
use RegularLabs\Library\Extension as RL_Extension;

defined('_JEXEC') or die;

if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/regularlabs.xml')
	|| ! is_file(JPATH_LIBRARIES . '/regularlabs/src/EditorButtonPlugin.php')
	|| ! is_file(JPATH_LIBRARIES . '/regularlabs/src/DownloadKey.php')
)
{
	return;
}

if ( ! RL_Document::isJoomlaVersion(4))
{
	RL_Extension::disable('articlesanywhere', 'plugin', 'editors-xtd');

	return;
}

if (true)
{
	class PlgButtonArticlesAnywhere extends RL_EditorButtonPlugin
	{
		protected $button_icon = '<svg xmlns="http://www.w3.org/2000/svg" style="fill:none;" width="24" height="24" fill="none" stroke="currentColor">'
		. '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />'
		. '</svg>';

		protected function getPopupOptions()
		{
			$options = parent::getPopupOptions();

			$options['confirmCallback'] = 'RegularLabs.ArticlesAnywhere.Button.insertText(\'' . $this->editor_name . '\')';
			$options['confirmText']     = JText::_('RL_INSERT');

			return $options;
		}

		protected function loadScripts()
		{
			RL_Document::script('articlesanywhere.button');
		}
	}
}

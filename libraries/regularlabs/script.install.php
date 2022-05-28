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

defined('_JEXEC') or die;

if ( ! class_exists('RegularLabsInstallerScript'))
{
	require_once __DIR__ . '/script.install.helper.php';

	class RegularLabsInstallerScript extends RegularLabsInstallerScriptHelper
	{
		public $alias          = 'regularlabs';
		public $extension_type = 'library';
		public $name           = 'Regular Labs Library';
		public $soft_break     = true;

		public function onBeforeInstall($route)
		{
			if ( ! parent::onBeforeInstall($route))
			{
				return false;
			}

			return $this->isNewer();
		}

		public function onAfterInstall($route)
		{
			$this->deleteJoomla3Files();
			$this->deleteOldFiles();

			return parent::onAfterInstall($route);
		}

		public function uninstall($adapter)
		{
			$this->uninstallPlugin($this->extname, 'system');
		}

		private function deleteJoomla3Files()
		{
			$this->delete(
				[
					JPATH_SITE . '/libraries/' . $this->extname . '/fields',
					JPATH_SITE . '/libraries/' . $this->extname . '/helpers',
					JPATH_SITE . '/libraries/' . $this->extname . '/layouts/range.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/layouts/repeatable-table',
					JPATH_SITE . '/libraries/' . $this->extname . '/layouts/repeatable-table.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/CacheNew.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/Database.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/EditorButton.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/EditorButtonHelper.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/Field.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/FieldGroup.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/Form.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/Log.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/MobileDetect.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/ParametersNew.php',
					JPATH_SITE . '/media/' . $this->extname . '/css/codemirror.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/codemirror.min.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/color.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/color.min.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/colorpicker.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/colorpicker.min.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/form.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/form.min.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/frontend.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/frontend.min.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/multiselect.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/multiselect.min.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/popup.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/popup.min.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/style.css',
					JPATH_SITE . '/media/' . $this->extname . '/css/style.min.css',
					JPATH_SITE . '/media/' . $this->extname . '/fonts',
					JPATH_SITE . '/media/' . $this->extname . '/images/icon-color.png',
					JPATH_SITE . '/media/' . $this->extname . '/images/logo.png',
					JPATH_SITE . '/media/' . $this->extname . '/images/minicolors.png',
					JPATH_SITE . '/media/' . $this->extname . '/js/codemirror.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/codemirror.min.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/color.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/color.min.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/colorpicker.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/colorpicker.min.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/form.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/form.min.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/jquery.cookie.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/jquery.cookie.min.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/multiselect.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/multiselect.min.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/textareaplus.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/textareaplus.min.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/toggler.js',
					JPATH_SITE . '/media/' . $this->extname . '/js/toggler.min.js',
					JPATH_SITE . '/media/' . $this->extname . '/less',
				]
			);
		}

		private function deleteOldFiles()
		{
			$this->delete(
				[
					JPATH_SITE . '/libraries/' . $this->extname . '/src/Api',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/Condition',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/Conditions.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/Form/Field/ConditionField.php',
					JPATH_SITE . '/libraries/' . $this->extname . '/src/Form/Field/ConditionSelectionField.php',
				]
			);
		}
	}
}

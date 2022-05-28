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

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgEditorsXtdArticlesAnywhereInstallerScript extends PlgEditorsXtdArticlesAnywhereInstallerScriptHelper
{
	public $alias          = 'articlesanywhere';
	public $extension_type = 'plugin';
	public $name           = 'ARTICLESANYWHERE';
	public $plugin_folder  = 'editors-xtd';

	public function onAfterInstall($route)
	{
		$this->deleteJoomla3Files();

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
				JPATH_SITE . '/plugins/editors-xtd/' . $this->extname . '/helper.php',
				JPATH_SITE . '/plugins/editors-xtd/' . $this->extname . '/popup.php',
			]
		);
	}
}

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

class PlgSystemArticlesAnywhereInstallerScript extends PlgSystemArticlesAnywhereInstallerScriptHelper
{
	public $alias          = 'articlesanywhere';
	public $extension_type = 'plugin';
	public $name           = 'ARTICLESANYWHERE';

	public function onAfterInstall($route)
	{
		$this->deleteJoomla3Files();

		return parent::onAfterInstall($route);
	}

	private function deleteJoomla3Files()
	{
		$this->delete(
			[
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Collection',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Components',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Output',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/PluginTags',

				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Helpers/article_model.php',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Helpers/article_view.php',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Helpers/Pagination.php',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Helpers/ValueHelper.php',

				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Area.php',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Config.php',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/CurrentArticle.php',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/CurrentItem.php',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Factory.php',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Params.php',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/Protect.php',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/config.yaml',
				JPATH_SITE . '/plugins/system/' . $this->extname . '/src/registeredurlparams.xml',

				JPATH_SITE . '/plugins/system/' . $this->extname . '/vendor',
			]
		);
	}

	public function uninstall($adapter)
	{
		$this->uninstallPlugin($this->extname, 'editors-xtd');
	}
}

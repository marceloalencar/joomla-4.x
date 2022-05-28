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

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Joomla\Registry\Registry as JRegistry;
use RegularLabs\Library\Color as RL_Color;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\DownloadKey as RL_DownloadKey;
use RegularLabs\Library\Extension as RL_Extension;
use RegularLabs\Library\Language as RL_Language;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\SystemPlugin as RL_SystemPlugin;
use RegularLabs\Library\Uri as RL_Uri;
use RegularLabs\Plugin\System\RegularLabs\QuickPage as RL_QuickPage;

if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/regularlabs.xml')
	|| ! is_file(JPATH_LIBRARIES . '/regularlabs/src/SystemPlugin.php')
	|| ! is_file(JPATH_LIBRARIES . '/regularlabs/src/DownloadKey.php')
)
{
	return;
}

RL_Language::load('plg_system_regularlabs');

$config = new JConfig;

$input = JFactory::getApplication()->input;

// Deal with error reporting when loading pages we don't want to break due to php warnings
if ( ! in_array($config->error_reporting, ['none', '0'])
	&& (
		($input->get('option', '') == 'com_regularlabsmanager'
			&& ($input->get('task', '') == 'update' || $input->get('view', '') == 'process')
		)
		||
		($input->getInt('rl_qp') == 1 && $input->get('url', '') != '')
	)
)
{
	RL_Extension::orderPluginFirst('regularlabs');

	error_reporting(E_ERROR);
}

class PlgSystemRegularLabs extends RL_SystemPlugin
{
	public $_enable_in_admin = true;
	public $_jversion        = 4;

	/**
	 * @return bool|mixed|string|void|null
	 * @throws Exception
	 */
	public function onAjaxRegularlabs()
	{
		$input = JFactory::getApplication()->input;

		$format = $input->getString('format', 'json');

		if ($input->getBool('getDownloadKey'))
		{
			return RL_DownloadKey::get();
		}

		if ($input->getBool('checkDownloadKey'))
		{
			return $this->checkDownloadKey();
		}

		if ($input->getBool('saveDownloadKey'))
		{
			return $this->saveDownloadKey();
		}

		if ($input->getBool('saveColor'))
		{
			$this->saveColor();
		}

		$attributes = RL_Uri::getCompressedAttributes();
		$attributes = new JRegistry($attributes);

		$field_class = $attributes->get('field_class');

		if (empty($field_class) || ! class_exists($field_class))
		{
			return false;
		}

		$type = $attributes->type ?? '';

		$method = 'getAjax' . ucfirst($format) . ucfirst($type);

		$field_class = new $field_class;

		if ( ! method_exists($field_class, $method))
		{
			return false;
		}

		return $field_class->$method($attributes);
	}

	/**
	 * @param string $buffer
	 */
	protected function loadStylesAndScripts(&$buffer)
	{
		self::addStylesheetToInstaller();
	}

	/**
	 * @return  void
	 */
	public function onAfterRoute(): void
	{
		if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/regularlabs.xml'))
		{
			if (JFactory::getApplication()->isClient('administrator'))
			{
				JFactory::getApplication()->enqueueMessage('The Regular Labs Library folder is missing or incomplete: ' . JPATH_LIBRARIES . '/regularlabs', 'error');
			}

			return;
		}

		RL_QuickPage::render();
	}

	/**
	 * @return  void
	 */
	public function onAfterRender(): void
	{
		if ( ! RL_Document::isAdmin(true) || ! RL_Document::isHtml())
		{
			return;
		}

		$this->removeEmptyFormControlGroups();
		$this->removeFormColumnLayout();
	}

	/**
	 * @throws Exception
	 */
	private function addStylesheetToInstaller()
	{
		if (JFactory::getApplication()->input->getCmd('option') !== 'com_installer')
		{
			return;
		}

		if ( ! self::hasRegularLabsMessages())
		{
			return;
		}

		RL_Document::style('regularlabs.admin-form');
	}

	/**
	 * @return false|mixed|string|null
	 * @throws Exception
	 */
	private function checkDownloadKey()
	{
		$key       = JFactory::getApplication()->input->getString('key');
		$extension = JFactory::getApplication()->input->getString('extension', 'all');

		return RL_DownloadKey::isValid($key, $extension);
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	private function hasRegularLabsMessages()
	{
		foreach (JFactory::getApplication()->getMessageQueue() as $message)
		{
			if ( ! isset($message['message'])
				|| strpos($message['message'], 'class="rl-') === false)
			{
				continue;
			}

			return true;
		}

		return false;
	}

	private function removeEmptyFormControlGroups()
	{
		$html = $this->app->getBody();

		if ($html == '')
		{
			return;
		}

		$html = RL_RegEx::replace(
			'<div class="(control-label|controls)">\s*</div>',
			'',
			$html
		);

		$html = RL_RegEx::replace(
			'<div class="control-group">\s*</div>',
			'',
			$html
		);

		$this->app->setBody($html);
	}

	private function removeFormColumnLayout()
	{
		if ($this->app->isClient('site'))
		{
			return;
		}

		if ($this->app->input->get('option', '') != 'com_plugins'
			|| $this->app->input->get('view', '') != 'plugin'
			|| $this->app->input->get('layout', '') != 'edit')
		{
			return;
		}

		$html = $this->app->getBody();

		if ($html == '')
		{
			return;
		}

		$html = str_replace('column-count-md-2 column-count-lg-3', '', $html);

		$this->app->setBody($html);
	}

	/**
	 * @throws Exception
	 */
	private function saveColor()
	{
		$input = JFactory::getApplication()->input;

		$table     = $input->get('table', '');
		$item_id   = $input->getInt('item_id');
		$color     = $input->getString('color');
		$id_column = $input->get('id_column', 'id');

		return RL_Color::save($table, $item_id, $color, $id_column);
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	private function saveDownloadKey()
	{
		$key = JFactory::getApplication()->input->getString('key');

		return RL_DownloadKey::store($key);
	}
}

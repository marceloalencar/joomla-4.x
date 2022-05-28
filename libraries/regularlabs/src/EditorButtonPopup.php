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

namespace RegularLabs\Library;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use Joomla\Registry\Registry as JRegistry;
use ReflectionClass;

class EditorButtonPopup
{
	protected $extension         = '';
	protected $main_type         = 'plugin';
	protected $require_core_auth = true;
	private   $_params           = null;

	public function render()
	{
		if ( ! Extension::isAuthorised($this->require_core_auth))
		{
			throw new Exception(JText::_("ALERTNOTAUTH"));
		}

		$this->params = $this->getParams();

		if ( ! Extension::isEnabledInArea($this->params))
		{
			throw new Exception(JText::_("ALERTNOTAUTH"));
		}

		$this->loadLanguages();

		$doc             = Document::get();
		$asset_manager   = Document::getAssetManager();
		$direction       = $doc->getDirection();
		$template_params = $this->getTemplateParams();

		// Get the hue value
		preg_match('#^hsla?\(([0-9]+)[\D]+([0-9]+)[\D]+([0-9]+)[\D]+([0-9](?:.\d+)?)?\)$#i', $template_params->get('hue', 'hsl(214, 63%, 20%)'), $matches);

		// Enable assets
		$asset_manager->getRegistry()->addTemplateRegistryFile('atum', 1);

		$asset_manager->usePreset(
			'template.atum.' . ($direction === 'rtl' ? 'rtl' : 'ltr')
		)->addInlineStyle(':root {
				--hue: ' . $matches[1] . ';
				--template-bg-light: ' . $template_params->get('bg-light', '--template-bg-light') . ';
				--template-text-dark: ' . $template_params->get('text-dark', '--template-text-dark') . ';
				--template-text-light: ' . $template_params->get('text-light', '--template-text-light') . ';
				--template-link-color: ' . $template_params->get('link-color', '--template-link-color') . ';
				--template-special-color: ' . $template_params->get('special-color', '--template-special-color') . ';
			}');

		// No template.js for modals
		//$asset_manager->disableScript('template.atum');

		// Override 'template.active' asset to set correct ltr/rtl dependency
		$asset_manager->registerStyle('template.active', '', [], [], ['template.atum.' . ($direction === 'rtl' ? 'rtl' : 'ltr')]);

		// Browsers support SVG favicons
		$doc->addHeadLink(JHtml::_('image', 'joomla-favicon.svg', '', [], true, 1), 'icon', 'rel', ['type' => 'image/svg+xml']);
		$doc->addHeadLink(JHtml::_('image', 'favicon.ico', '', [], true, 1), 'alternate icon', 'rel', ['type' => 'image/vnd.microsoft.icon']);
		$doc->addHeadLink(JHtml::_('image', 'joomla-favicon-pinned.svg', '', [], true, 1), 'mask-icon', 'rel', ['color' => '#000']);

		Document::script('regularlabs.admin-form');
		Document::style('regularlabs.admin-form');
		Document::style('regularlabs.popup');

		$this->init();
		$this->loadScripts();
		$this->loadStyles();

		echo $this->renderTemplate();
	}

	protected function getParams()
	{
		if ( ! is_null($this->_params))
		{
			return $this->_params;
		}

		switch ($this->main_type)
		{
			case 'component':
				if (Protect::isComponentInstalled($this->extension))
				{
					// Load component parameters
					$this->_params = Parameters::getComponent($this->extension);
				}
				break;

			case 'plugin':
			default:
				if (Protect::isSystemPluginInstalled($this->extension))
				{
					// Load plugin parameters
					$this->_params = Parameters::getPlugin($this->extension);
				}
				break;
		}

		return $this->_params;
	}

	protected function loadLanguages()
	{
		Language::load('joomla', JPATH_ADMINISTRATOR);
		Language::load('plg_system_regularlabs');
		Language::load('plg_editors-xtd_' . $this->extension);
		Language::load('plg_system_' . $this->extension);
	}

	protected function getTemplateParams()
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->quoteName('s.params'))
			->from($db->quoteName('#__template_styles', 's'))
			->where($db->quoteName('s.template') . ' = ' . $db->quote('atum'))
			->order($db->quoteName('s.home'));
		$db->setQuery($query, 0, 1);
		$template = $db->loadObject();

		return new JRegistry($template->params ?? null);
	}

	protected function init()
	{
	}

	protected function loadScripts()
	{
	}

	protected function loadStyles()
	{
	}

	private function renderTemplate()
	{
		ob_start();
		include dirname($this->getDir()) . '/tmpl/popup.php';
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	private function getDir()
	{
		// use static::class instead of get_class($this) after php 5.4 support is dropped
		$rc = new ReflectionClass(get_class($this));

		return dirname($rc->getFileName());
	}
}

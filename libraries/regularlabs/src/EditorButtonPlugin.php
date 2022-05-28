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

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Object\CMSObject as JObject;
use Joomla\CMS\Plugin\CMSPlugin as JCMSPlugin;
use Joomla\CMS\Session\Session;
use ReflectionClass;

class EditorButtonPlugin extends JCMSPlugin
{
	protected $asset                = null;
	protected $author               = null;
	protected $button_icon          = '';
	protected $check_installed      = null;
	protected $editor_name          = '';
	protected $enable_on_acymailing = false;
	protected $folder               = null;
	protected $main_type            = 'plugin';
	protected $popup_class          = '';
	protected $require_core_auth    = true;
	private   $_params              = null;
	private   $_pass                = null;

	public function __construct(&$subject, $config = [])
	{
		parent::__construct($subject, $config);

		$this->popup_class = $this->popup_class ?: 'Plugin.EditorButton.' . $this->getShortName() . '.Popup';
	}

	/**
	 * Get the short name of the field class
	 * PlgButtonFoobar => Foobar
	 *
	 * @return string
	 */
	private function getShortName()
	{
		return substr((new ReflectionClass($this))->getShortName(), strlen('PlgButton'));
	}

	/**
	 * Display the button
	 *
	 * @param string  $name   The name of the button to display.
	 * @param string  $asset  The name of the asset being edited.
	 * @param integer $author The id of the author owning the asset being edited.
	 *
	 * @return  JObject|false
	 */
	public function onDisplay($editor_name, $asset, $author)
	{
		$this->editor_name = $editor_name;
		$this->asset       = $asset;
		$this->author      = $author;

		if ( ! $this->passChecks())
		{
			return false;
		}

		return $this->render();
	}

	/**
	 * @return bool
	 */
	private function passChecks()
	{
		if ( ! is_null($this->_pass))
		{
			return $this->_pass;
		}

		$this->_pass = false;

		if ( ! Extension::isFrameworkEnabled())
		{
			return false;
		}

		if ( ! Extension::isAuthorised($this->require_core_auth))
		{
			return false;
		}

		if ( ! $this->isInstalled())
		{
			return false;
		}

		if ( ! $this->enable_on_acymailing && JFactory::getApplication()->input->get('option', '') == 'com_acymailing')
		{
			return false;
		}

		$params = $this->getParams();

		if ( ! Extension::isEnabledInComponent($params))
		{
			return false;
		}

		if ( ! Extension::isEnabledInArea($params))
		{
			return false;
		}

		if ( ! $this->extraChecks($params))
		{
			return false;
		}

		$this->_pass = true;

		return true;
	}

	protected function render()
	{
		$this->loadScripts();
		$this->loadStyles();

		return $this->renderPopupButton();
	}

	private function isInstalled()
	{
		$extensions = ! is_null($this->check_installed)
			? $this->check_installed
			: [$this->main_type];

		return Extension::areInstalled($this->_name, $extensions);
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
				if (Protect::isComponentInstalled($this->_name))
				{
					// Load component parameters
					$this->_params = Parameters::getComponent($this->_name);
				}
				break;

			case 'plugin':
			default:
				if (Protect::isSystemPluginInstalled($this->_name))
				{
					// Load plugin parameters
					$this->_params = Parameters::getPlugin($this->_name);
				}
				break;
		}

		return $this->_params;
	}

	public function extraChecks($params)
	{
		return true;
	}

	protected function loadScripts()
	{
	}

	protected function loadStyles()
	{
	}

	protected function renderPopupButton()
	{
		$button = new JObject;

		$button->setProperties([
			'modal'   => true,
			'name'    => $this->_name,
			'text'    => $this->getButtonText(),
			'icon'    => $this->_name . '" aria-hidden="true">' . $this->button_icon . '<span></span class="hidden',
			'iconSVG' => $this->button_icon,
			'link'    => $this->getPopupLink(),
			'options' => $this->getPopupOptions(),
		]);

		return $button;
	}

	protected function getButtonText()
	{
		$params = $this->getParams();

		$text_ini = strtoupper(str_replace(' ', '_', $params->button_text ?? $this->_name));
		$text     = JText::_($text_ini);

		if ($text == $text_ini)
		{
			$text = JText::_($params->button_text ?? $this->_name);
		}

		return trim($text);
	}

	protected function getPopupLink()
	{
		switch ($this->main_type)
		{
			case 'component':
				return 'index.php?'
					. 'option=com_' . $this->_name
					. '&view=items'
					. '&layout=popup'
					. '&tmpl=component'
					. '&editor=' . $this->editor_name
					. '&' . Session::getFormToken() . '=1';

			case 'plugin':
			default:
				return 'index.php?rl_qp=1'
					. '&class=' . $this->popup_class
					. '&editor=' . $this->editor_name
					. '&' . Session::getFormToken() . '=1';
		}
	}

	protected function getPopupOptions()
	{
		return [
			'height'     => '1600px',
			'width'      => '1200px',
			'bodyHeight' => '70',
			'modalWidth' => '80',
		];
	}
}

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

use Joomla\CMS\Filesystem\Folder as JFolder;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\DB as RL_DB;
use RegularLabs\Library\Form\FormField as RL_FormField;
use RegularLabs\Library\RegEx as RL_RegEx;

class ComponentsField extends RL_FormField
{
	static $components     = null;
	public $attributes     = [
		'frontend' => true,
		'admin'    => true,
	];
	public $is_select_list = true;
	public $use_ajax       = true;

	public function getNamesByIds($values, $attributes)
	{
		$query = $this->db->getQuery(true)
			->select('e.name, e.element')
			->from('#__extensions AS e')
			->where('e.type = ' . $this->db->quote('component'))
			->where(RL_DB::is('e.element', $values))
			->order('e.name');

		$this->db->setQuery($query);
		$components = $this->db->loadObjectList();

		$lang = $this->app->getLanguage();

		$names = [];

		foreach ($components as $component)
		{
			$name = $component->name;

			if (strpos($component->name, ' ') === false)
			{
				// Load the core file then
				// Load extension-local file.
				$lang->load($component->element . '.sys', JPATH_BASE, null, false, false)
				|| $lang->load($component->element . '.sys', JPATH_ADMINISTRATOR . '/components/' . $component->element, null, false, false)
				|| $lang->load($component->element . '.sys', JPATH_BASE, $lang->getDefault(), false, false)
				|| $lang->load($component->element . '.sys', JPATH_ADMINISTRATOR . '/components/' . $component->element, $lang->getDefault(), false, false);

				$name = JText::_(strtoupper($name));
			}

			$names[] = $name;
		}

		return $names;
	}

	protected function getListOptions($attributes)
	{
		$frontend = $attributes['frontend'];
		$admin    = $attributes['admin'];

		if ( ! $frontend && ! $admin)
		{
			return [];
		}

		$components = $this->getComponents();

		$comps = [];
		$lang  = $this->app->getLanguage();

		foreach ($components as $component)
		{
			if (empty($component->element))
			{
				continue;
			}

			$component_folder = ($frontend ? JPATH_SITE : JPATH_ADMINISTRATOR) . '/components/' . $component->element;

			if ( ! JFolder::exists($component_folder) && $admin)
			{
				$component_folder = JPATH_ADMINISTRATOR . '/components/' . $component->element;
			}

			// return if there is no main component folder
			if ( ! JFolder::exists($component_folder))
			{
				continue;
			}

			// return if there is no view(s) folder
			if (
				! JFolder::exists($component_folder . '/src/View')
				&& ! JFolder::exists($component_folder . '/views')
				&& ! JFolder::exists($component_folder . '/view')
			)
			{
				continue;
			}

			if (strpos($component->name, ' ') === false)
			{
				// Load the core file then
				// Load extension-local file.
				$lang->load($component->element . '.sys', JPATH_BASE, null, false, false)
				|| $lang->load($component->element . '.sys', JPATH_ADMINISTRATOR . '/components/' . $component->element, null, false, false)
				|| $lang->load($component->element . '.sys', JPATH_BASE, $lang->getDefault(), false, false)
				|| $lang->load($component->element . '.sys', JPATH_ADMINISTRATOR . '/components/' . $component->element, $lang->getDefault(), false, false);

				$component->name = JText::_(strtoupper($component->name));
			}

			$comps[RL_RegEx::replace('[^a-z0-9_]', '', $component->name . '_' . $component->element)] = $component;
		}

		ksort($comps);

		$options = [];

		foreach ($comps as $component)
		{
			$key = $component->element;

			if ($this->get('no_com_prefix'))
			{
				$key = RL_RegEx::replace('^com_', '', $key);
			}

			$options[] = JHtml::_('select.option', $key, $component->name);
		}

		return $options;
	}

	private function getComponents()
	{
		if ( ! is_null(self::$components))
		{
			return self::$components;
		}

		$query = $this->db->getQuery(true)
			->select('e.name, e.element')
			->from('#__extensions AS e')
			->where('e.type = ' . $this->db->quote('component'))
			->where('e.name != ""')
			->where('e.element != ""')
			->group('e.element')
			->order('e.element, e.name');
		$this->db->setQuery($query);
		self::$components = $this->db->loadObjectList();

		return self::$components;
	}
}

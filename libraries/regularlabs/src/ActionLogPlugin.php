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
use Joomla\Component\Actionlogs\Administrator\Plugin\ActionLogPlugin as JActionLogPlugin;
use RegularLabs\Library\Language as RL_Language;

class ActionLogPlugin extends JActionLogPlugin
{
	static $ids                      = [];
	static $item_types               = [];
	static $item_titles              = [];
	public $alias                    = '';
	public $events                   = [];
	public $lang_prefix_change_state = 'PLG_SYSTEM_ACTIONLOGS';
	public $lang_prefix_delete       = 'PLG_SYSTEM_ACTIONLOGS';
	public $lang_prefix_install      = 'PLG_ACTIONLOG_JOOMLA';
	public $lang_prefix_save         = 'PLG_SYSTEM_ACTIONLOGS';
	public $lang_prefix_uninstall    = 'PLG_ACTIONLOG_JOOMLA';
	public $name                     = '';
	public $option                   = '';
	public $table                    = null;

	public function __construct(&$subject, array $config = [])
	{
		parent::__construct($subject, $config);

		Language::load('plg_actionlog_joomla', JPATH_ADMINISTRATOR);
		Language::load('plg_actionlog_' . $this->alias);

		$config = Parameters::getComponent($this->alias);

		$enable_actionlog = $config->enable_actionlog ?? true;
		$this->events     = $enable_actionlog ? ['*'] : [];

		if ($enable_actionlog && ! empty($config->actionlog_events))
		{
			$this->events = ArrayHelper::toArray($config->actionlog_events);
		}

		$this->name   = JText::_($this->name);
		$this->option = $this->option ?: 'com_' . $this->alias;
	}

	public function addItem($extension, $type, $title)
	{
		self::$item_types[$extension]  = $type;
		self::$item_titles[$extension] = $title;
	}

	public function onContentChangeState($context, $ids, $value)
	{
		if (strpos($context, $this->option) === false)
		{
			return;
		}

		if ( ! ArrayHelper::find(['*', 'change_state'], $this->events))
		{
			return;
		}

		switch ($value)
		{
			case 0:
				$languageKey = $this->lang_prefix_change_state . '_CONTENT_UNPUBLISHED';
				$action      = 'unpublish';
				break;
			case 1:
				$languageKey = $this->lang_prefix_change_state . '_CONTENT_PUBLISHED';
				$action      = 'publish';
				break;
			case 2:
				$languageKey = $this->lang_prefix_change_state . '_CONTENT_ARCHIVED';
				$action      = 'archive';
				break;
			case -2:
				$languageKey = $this->lang_prefix_change_state . '_CONTENT_TRASHED';
				$action      = 'trash';
				break;
			default:
				return;
		}

		$item = $this->getItem($context);

		if ( ! $this->table)
		{
			if ( ! is_file($item->file))
			{
				return;
			}

			require_once $item->file;

			$this->table = (new $item->model)->getTable();
		}

		foreach ($ids as $id)
		{
			$this->table->load($id);

			$title    = $this->table->title ?? $this->table->name ?? $this->table->id;
			$itemlink = str_replace('{id}', $this->table->id, $item->url);

			$message = [
				'action'   => $action,
				'type'     => $item->title,
				'id'       => $id,
				'title'    => $title,
				'itemlink' => $itemlink,
			];

			$this->addLog([$message], $languageKey, $context);
		}
	}

	public function getItem($context)
	{
		$item = $this->getItemData($context);

		if ( ! isset($item->file))
		{
			$item->file = JPATH_ADMINISTRATOR . '/components/' . $item->option . '/models/' . $item->type . '.php';
		}

		if ( ! isset($item->model))
		{
			$item->model = $this->alias . 'Model' . ucfirst($item->type);
		}

		if ( ! isset($item->url))
		{
			$item->url = 'index.php?option=' . $item->option . '&view=' . $item->type . '&layout=edit&id={id}';
		}

		return $item;
	}

	private function getItemData($extension)
	{
		if (strpos($extension, '.') !== false)
		{
			[$extension, $type] = explode('.', $extension);
		}

		RL_Language::load($extension);

		$type  = $type ?? self::$item_types[$extension] ?? 'item';
		$title = self::$item_titles[$extension] ?? JText::_($extension) . ' ' . JText::_('RL_ITEM');

		return (object) [
			'context' => $extension . '.' . $type,
			'option'  => $extension,
			'type'    => $type,
			'title'   => $title,
		];
	}

	public function onExtensionAfterDelete($context, $table)
	{
		self::onContentAfterDelete($context, $table);
	}

	public function onContentAfterDelete($context, $table)
	{
		if (strpos($context, $this->option) === false)
		{
			return;
		}

		if ( ! ArrayHelper::find(['*', 'delete'], $this->events))
		{
			return;
		}

		$item = $this->getItem($context);

		$title = $table->title ?? $table->name ?? $table->id;

		$message = [
			'action' => 'deleted',
			'type'   => $item->title,
			'id'     => $table->id,
			'title'  => $title,
		];

		$this->addLog([$message], $this->lang_prefix_delete . '_CONTENT_DELETED', $context);
	}

	public function onExtensionAfterInstall($installer, $eid)
	{
		// Prevent duplicate logs
		if (in_array('install_' . $eid, self::$ids, true))
		{
			return;
		}

		$context = JFactory::getApplication()->input->get('option', '');

		if (strpos($context, $this->option) === false)
		{
			return;
		}

		if ( ! ArrayHelper::find(['*', 'install'], $this->events))
		{
			return;
		}

		$extension = Extension::getById($eid);

		if (empty($extension->manifest_cache))
		{
			return;
		}

		$manifest = json_decode($extension->manifest_cache);

		if (empty($manifest->name))
		{
			return;
		}

		self::$ids[] = 'install_' . $eid;

		$message = [
			'action'         => 'install',
			'type'           => $this->lang_prefix_install . '_TYPE_' . strtoupper($manifest->attributes()->type),
			'id'             => $eid,
			'extension_name' => JText::_($manifest->name),
		];

		$languageKey = $this->lang_prefix_install . '_' . strtoupper($manifest->attributes()->type) . '_INSTALLED';
		if ( ! JFactory::getApplication()->getLanguage()->hasKey($languageKey))
		{
			$languageKey = $this->lang_prefix_install . '_EXTENSION_INSTALLED';
		}

		$this->addLog([$message], $languageKey, 'com_regularlabsmanager');
	}

	public function onExtensionAfterSave($context, $table, $isNew)
	{
		self::onContentAfterSave($context, $table, $isNew);
	}

	public function onContentAfterSave($context, $table, $isNew, $data = [])
	{
		$data = ArrayHelper::toArray($data);

		if (isset($data['ignore_actionlog']) && $data['ignore_actionlog'])
		{
			return;
		}

		if (strpos($context, $this->option) === false)
		{
			return;
		}

		$event = $isNew ? 'create' : 'update';

		if ( ! ArrayHelper::find(['*', $event], $this->events))
		{
			return;
		}

		$item = $this->getItem($context);

		$title    = $table->title ?? $table->name ?? $table->id;
		$item_url = str_replace('{id}', $table->id, $item->url);

		$message = [
			'action'   => $isNew ? 'add' : 'update',
			'type'     => $item->title,
			'id'       => $table->id,
			'title'    => $title,
			'itemlink' => $item_url,
		];

		$languageKey = $isNew ? $this->lang_prefix_save . '_CONTENT_ADDED' : $this->lang_prefix_save . '_CONTENT_UPDATED';

		$this->addLog([$message], $languageKey, $context);
	}

	public function onExtensionAfterUninstall($installer, $eid, $result)
	{
		// Prevent duplicate logs
		if (in_array('uninstall_' . $eid, self::$ids, true))
		{
			return;
		}

		$context = JFactory::getApplication()->input->get('option', '');

		if (strpos($context, $this->option) === false)
		{
			return;
		}

		if ( ! ArrayHelper::find(['*', 'uninstall'], $this->events))
		{
			return;
		}

		if ($result === false)
		{
			return;
		}

		$manifest = $installer->get('manifest');

		if ($manifest === null)
		{
			return;
		}

		self::$ids[] = 'uninstall_' . $eid;

		$message = [
			'action'         => 'uninstall',
			'type'           => $this->lang_prefix_install . '_TYPE_' . strtoupper($manifest->attributes()->type),
			'id'             => $eid,
			'extension_name' => JText::_($manifest->name),
		];

		$languageKey = $this->lang_prefix_uninstall . '_EXTENSION_UNINSTALLED';

		$this->addLog([$message], $languageKey, 'com_regularlabsmanager');
	}
}

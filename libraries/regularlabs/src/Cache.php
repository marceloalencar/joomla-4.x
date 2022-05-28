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
use Joomla\CMS\Cache\CacheControllerFactoryInterface as JCacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\OutputController as JOutputController;
use Joomla\CMS\Factory as JFactory;

class Cache
{
	/**
	 * @var array
	 */
	static $cache = [];
	/**
	 * @var [JOutputController]
	 */
	private $file_cache_controllers = [];
	/**
	 * @var bool
	 */
	private $force_caching = true;
	/**
	 * @var string
	 */
	private $group;
	/**
	 * @var string
	 */
	private $id;
	/**
	 * @var int
	 */
	private $time_to_life_in_seconds = 0;
	/**
	 * @var bool
	 */
	private $use_files = false;

	/**
	 * @param $id
	 */
	public function __construct($id = null, $group = 'regularlabs')
	{
		if (is_null($id))
		{
			$caller = debug_backtrace()[1];
			$id     = [
				$caller['class'],
				$caller['function'],
				$caller['args'],
			];
		}

		if ( ! is_string($id))
		{
			$id = json_encode($id);
		}

		$this->id    = md5($id);
		$this->group = $group;
	}

	/**
	 * @return bool
	 */
	public function exists()
	{
		if ( ! $this->use_files)
		{
			return $this->existsMemory();
		}

		return $this->existsMemory() || $this->existsFile();
	}

	/**
	 * @return bool
	 */
	private function existsMemory()
	{
		return isset(static::$cache[$this->id]);
	}

	/**
	 * @return bool
	 */
	private function existsFile()
	{
		if (JFactory::getApplication()->get('debug') || JFactory::getApplication()->input->get('debug'))
		{
			return false;
		}

		return $this->getFileCache()->contains($this->id);
	}

	/**
	 * @return JOutputController
	 */
	private function getFileCache()
	{
		$options = [
			'defaultgroup' => $this->group,
		];

		if ($this->time_to_life_in_seconds)
		{
			$options['lifetime'] = $this->time_to_life_in_seconds;
		}

		if ($this->force_caching)
		{
			$options['caching'] = true;
		}

		$id = json_encode($options);

		if (isset($this->file_cache_controllers[$id]))
		{
			return $this->file_cache_controllers[$id];
		}

		$this->file_cache_controllers[$id] = JFactory::getContainer()
			->get(JCacheControllerFactoryInterface::class)
			->createCacheController('output', $options);

		return $this->file_cache_controllers[$id];
	}

	/**
	 * @return null|mixed
	 */
	public function get()
	{
		return $this->use_files
			? $this->getFile()
			: $this->getMemory();
	}

	/**
	 * @return false|mixed
	 * @throws Exception
	 */
	private function getFile()
	{
		if ($this->existsMemory())
		{
			return $this->getMemory();
		}

		$data = $this->getFileCache()->get($this->id);

		$this->setMemory($data);

		return $data;
	}

	/**
	 * @return null|mixed
	 */
	private function getMemory()
	{
		if ( ! $this->existsMemory())
		{
			return null;
		}

		$data = static::$cache[$this->id];

		return is_object($data) ? clone $data : $data;
	}

	// Get the cached object from the Joomla cache

	/**
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	private function setMemory($data)
	{
		static::$cache[$this->id] = $data;

		return $data;
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function set($data)
	{
		return $this->use_files
			? $this->setFile($data)
			: $this->setMemory($data);
	}

	/**
	 * @param mixed $data
	 *
	 * @return mixed
	 * @throws Exception
	 */
	private function setFile($data)
	{
		$this->setMemory($data);

		if (JFactory::getApplication()->get('debug') || JFactory::getApplication()->input->get('debug'))
		{
			return $data;
		}

		$this->getFileCache()->store($data, $this->id);

		return $data;
	}

	/**
	 * @param int  $time_to_life_in_minutes
	 * @param bool $force_caching
	 *
	 * @return $this
	 */
	public function useFiles($time_to_life_in_minutes = 0, $force_caching = true)
	{
		$this->use_files = true;

		// convert ttl to minutes
		$this->time_to_life_in_seconds = $time_to_life_in_minutes * 60;

		$this->force_caching = $force_caching;

		return $this;
	}
}

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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataTags;

defined('_JEXEC') or die;

use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class DataTags
{
	/* @var DataTag[] */
	private $current_data_tags = [];
	/* @var DataTag[] */
	private $data_tags = [];

	/**
	 * @param string $string
	 * @param string $selector
	 */
	public function __construct($string, $selector = '')
	{
		$this->initTags($string, $selector);
	}

	/**
	 * @param string $string
	 * @param string $selector
	 */
	private function initTags($string, $selector = '')
	{
		$regex = Params::getRegex('datatag');

		RL_RegEx::matchAll($regex, $string, $matches);

		if (empty($matches))
		{
			return;
		}

		$data_tags         = [];
		$current_data_tags = [];

		foreach ($matches as $match)
		{
			if ($selector && $match['article_selector'] !== $selector)
			{
				continue;
			}

			$data_tag = new DataTag($match);

			if ( ! $data_tag->getDataGroup())
			{
				continue;
			}

			if ($match['article_selector'] === 'this')
			{
				$current_data_tags[] = $data_tag;
				continue;
			}

			$data_tags[] = $data_tag;
		}

		$this->setTags($data_tags);
		$this->setCurrentTags($current_data_tags);
	}

	/**
	 * @param array $data_tags
	 */
	public function setTags($data_tags = [])
	{
		$this->data_tags = $data_tags;
	}

	/**
	 * @param array $data_tags
	 */
	private function setCurrentTags($data_tags = [])
	{
		$this->current_data_tags = $data_tags;
	}

	/**
	 * @return array
	 */
	public function getCurrentDataGroups()
	{
		$data_groups = [];

		foreach ($this->current_data_tags as $data_tag)
		{
			$data_groups[] = $data_tag->getDataGroup();
		}

		return $data_groups;
	}

	/**
	 * @return DataTag[]
	 */
	public function getCurrentDataTags()
	{
		return $this->current_data_tags;
	}

	/**
	 * @return array
	 */
	public function getDataGroups()
	{
		$data_groups = [];

		foreach ($this->data_tags as $data_tag)
		{
			$data_groups[] = $data_tag->getDataGroup();
		}

		return $data_groups;
	}

	/**
	 * @return DataTag[]
	 */
	public function getTags()
	{
		return $this->data_tags;
	}

	/**
	 * @param string $html
	 */
	public function replace(&$html)
	{
		foreach ($this->current_data_tags as $data_tag)
		{
			$data_tag->replace($html);
		}

		foreach ($this->data_tags as $data_tag)
		{
			$data_tag->replace($html);
		}

		// NOT NEEDED
//		// Run over content again to replace any remaining tags (left for instance by the foreach tag)
//		foreach ($this->data_tags as $data_tag)
//		{
//			$data_tag->replace($html, false);
//		}
	}
}

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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups;

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper as JLayout;
use Joomla\Registry\Registry as JRegistry;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Layout;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;

class Author extends DataGroup
{
	protected static $database_column_case = 'camel';
	protected static $default_data_key     = 'author';
	protected static $main_table           = 'users';
	protected static $prefix               = 'author';

	/**
	 * @return array [table => condition]
	 */
	public function getJoins()
	{
		return [
			DB::quoteName('#__users', 'author')
			=> DB::quoteName('author.id') . ' = ' . DB::quoteName('article.created_by'),
		];
	}

	public function getQueryKeys()
	{
		if ($this->key === 'author')
		{
			return [
				'author.name',
				'article.created-by-alias',
				'article.attribs',
			];
		}

		return parent::getQueryKeys();
	}

	public function getRequiredQueryKeys()
	{
		return ['author.id'];
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		if ($this->key === 'password')
		{
			return '***';
		}

		if ($this->key === 'author')
		{
			return $this->getValueAuthor();
		}

		return parent::getValue();
	}

	/**
	 * @return string
	 */
	public function getValueAuthor()
	{
		$layout = $this->getAttribute('layout', false);

		if ( ! $layout)
		{
			return $this->get('article.created-by-alias', $this->get('author.name'));
		}

		$layout_id = Layout::getId($layout, 'joomla.content.info_block.author');

		$displayData = [
			'item'   => (object) [
				'created_by_alias' => $this->get('article.created-by-alias'),
				'author'           => $this->get('author.name'),
				'contact_link'     => '',
			],
			'params' => new JRegistry($this->get('article.attribs')),
		];

		return JLayout::render($layout_id, $displayData);
	}

	protected static function getJsonKeys()
	{
		return [
			'params' => [
				'admin_style',
				'admin_language',
				'language',
				'editor',
				'timezone',
				'a11y_mono',
				'a11y_contrast',
				'a11y_highlight',
				'a11y_font',
			],
		];
	}
}

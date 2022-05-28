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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class ArticleLayout
{
	public static function render($id, $attributes)
	{
		if ( ! $id)
		{
			return '';
		}

//		if (
//			JFactory::getApplication()->input->get('option', '') === 'com_finder'
//			&& JFactory::getApplication()->input->get('format', '') === 'json'
//		)
//		{
//			// Force simple layout for finder indexing, as the setParams causes errors
//			$text = Factory::getOutput('Text', $this->config, $this->item, $this->values);
//
//			return
//				'<h2>' . $this->item->get('title') . '</h2>'
//				. $text->get('text', $attributes);
//		}

		$params = Params::get();

		if (isset($attributes->force_content_triggers))
		{
			$params->force_content_triggers = $attributes->force_content_triggers;
			unset($attributes->force_content_triggers);
		}

		[$template, $layout] = self::getTemplateAndLayout($attributes);

		$view = new ArticleView;

		$view->setParams($id, $template, $layout, $params, $attributes);

		return $view->display();
	}

	private static function getTemplateAndLayout($data)
	{
		if ( ! isset($data->template) && isset($data->layout) && strpos($data->layout, ':') !== false)
		{
			[$data->template, $data->layout] = explode(':', $data->layout);
		}

		//	$article_layout = $this->item->get('article_layout');
		$article_layout = 'default';

		$layout = ! empty($data->layout)
			? $data->layout
			: (($article_layout ?? null) ?: 'default');

		$template = ! empty($data->template)
			? $data->template
			: JFactory::getApplication()->getTemplate();

		if (strpos($layout, ':') !== false)
		{
			[$template, $layout] = RL_Array::toArray($layout, ':');
		}

		// Layout is a template, so return default layout
		if (empty($data->template) && file_exists(JPATH_THEMES . '/' . $layout))
		{
			return [$layout, 'default'];
		}

		// Value is not a template, so a layout
		return [$template, $layout];
	}
}

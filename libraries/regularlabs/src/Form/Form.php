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

namespace RegularLabs\Library\Form;

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Layout\FileLayout as JFileLayout;
use Joomla\CMS\Plugin\PluginHelper as JPluginHelper;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Parameters as RL_Parameters;
use RegularLabs\Library\RegEx as RL_RegEx;

class Form
{
	/**
	 * Return an array with names with added extras and formatting
	 *
	 * @param array $item
	 * @param array $extras
	 *
	 * @return array
	 */
	static public function getNamesWithExtras($items, $extras = [])
	{
		$names = [];

		foreach ($items as $item)
		{
			$names[] = self::getNameWithExtras($item, $extras);
		}

		return $names;
	}

	/**
	 * Return a name with added extras and formatting
	 *
	 * @param object $item
	 * @param array  $extras
	 *
	 * @return string
	 */
	static public function getNameWithExtras($item, $extras = [])
	{
		$name = trim($item->name);

		foreach ($extras as $extra)
		{
			if ($extra == 'language' && $item->{$extra} == '*')
			{
				continue;
			}

			if (in_array($extra, ['id', 'alias'], true)
				&& $item->{$extra} == $item->name)
			{
				continue;
			}

			if ($extra == 'unpublished')
			{
				$name .= isset($item->published) && ! $item->published
					? ' (' . JText::_('JUNPUBLISHED') . ')'
					: '';
				continue;
			}

			if ($extra == 'disabled')
			{
				$name .= isset($item->disabled) && $item->disabled
					? ' (' . JText::_('JDISABLED') . ')'
					: '';
				continue;
			}

			if (empty($item->{$extra}))
			{
				continue;
			}

			if (isset($item->{'add_' . $extra}) && ! $item->{'add_' . $extra})
			{
				continue;
			}

			$name .= ' [' . $item->{$extra} . ']';
		}

		return self::prepareSelectItem($name);
	}

	public static function prepareSelectItem($string, $remove_first = 0)
	{
		if (empty($string))
		{
			return '';
		}

		$string = str_replace(['&nbsp;', '&#160;'], ' ', $string);
		$string = RL_RegEx::replace('- ', '  ', $string);

		for ($i = 0; $remove_first > $i; $i++)
		{
			$string = RL_RegEx::replace('^  ', '', $string, '');
		}

		if (RL_RegEx::match('^( *)(.*)$', $string, $match, ''))
		{
			[$string, $pre, $name] = $match;

			$pre = str_replace('  ', ' ·  ', $pre);
			$pre = RL_RegEx::replace('(( ·  )*) ·  ', '\1 »  ', $pre);
			$pre = str_replace('  ', ' &nbsp; ', $pre);

			$string = $pre . $name;
		}

		return $string;
	}

	/**
	 * Render a full select list
	 *
	 * @param array  $options
	 * @param string $name
	 * @param string $value
	 * @param string $id
	 * @param array  $attributes
	 * @param bool   $treeselect
	 *
	 * @return string
	 */
	public static function selectList($options, $name, $value, $id, $attributes = [], $treeselect = false, $collapse_children = false)
	{
		if (empty($options))
		{
			return '<fieldset class="radio">' . JText::_('RL_NO_ITEMS_FOUND') . '</fieldset>';
		}

		$params = RL_Parameters::getPlugin('regularlabs');

		if ( ! is_array($value))
		{
			$value = explode(',', $value);
		}

		if (count($value) === 1 && strpos($value[0], ',') !== false)
		{
			$value = explode(',', $value[0]);
		}

		$count = 0;
		if ($options != -1)
		{
			foreach ($options as $option)
			{
				$count++;
				if (isset($option->links))
				{
					$count += count($option->links);
				}
				if ($count > $params->max_list_count)
				{
					break;
				}
			}
		}

		if ($options == -1 || $count > $params->max_list_count)
		{
			if (is_array($value))
			{
				$value = implode(',', $value);
			}
			if ( ! $value)
			{
				$input = '<textarea name="' . $name . '" id="' . $id . '" cols="40" rows="5">' . $value . '</textarea>';
			}
			else
			{
				$input = '<input type="text" name="' . $name . '" id="' . $id . '" value="' . $value . '" size="60">';
			}

			$plugin = JPluginHelper::getPlugin('system', 'regularlabs');

			$url = ! empty($plugin->id)
				? 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $plugin->id
				: 'index.php?option=com_plugins&&filter[folder]=&filter[search]=Regular%20Labs%20Library';

			$label   = JText::_('RL_ITEM_IDS');
			$text    = JText::_('RL_MAX_LIST_COUNT_INCREASE');
			$tooltip = JText::_('RL_MAX_LIST_COUNT_INCREASE_DESC,' . $params->max_list_count . ',RL_MAX_LIST_COUNT');
			$link    = '<a href="' . $url . '" target="_blank" id="' . $id . '_msg"'
				. ' class="hasPopover" title="' . $text . '" data-content="' . htmlentities($tooltip) . '">'
				. '<span class="icon icon-cog"></span>'
				. $text
				. '</a>';

			$script = 'jQuery("#' . $id . '_msg").popover({"html": true,"trigger": "hover focus","container": "body"})';

			return '<fieldset class="radio">'
				. '<label for="' . $id . '">' . $label . ':</label>'
				. $input
				. '<br><small>' . $link . '</small>'
				. '</fieldset>'
				. '<script>' . $script . '</script>';
		}

		$layout = self::getLayout($options, $treeselect);
		$path   = $treeselect ? JPATH_SITE . '/libraries/regularlabs/layouts' : null;

		$data = array_merge(
			compact('id', 'name', 'value', 'options'),
			[
				'multiple'          => false,
				'autofocus'         => false,
				'onchange'          => '',
				'dataAttribute'     => '',
				'readonly'          => false,
				'disabled'          => '',
				'hint'              => false,
				'required'          => false,
				'collapse_children' => $collapse_children,
				'groups'            => $options,
			],
			$attributes
		);

		$renderer = new JFileLayout($layout, $path);

		return $renderer->render($data);
	}

	/**
	 * @param array $options
	 * @param bool  $treeselect
	 *
	 * @return string
	 */
	public static function getLayout($options, $treeselect = false)
	{
		if ($treeselect)
		{
			return 'regularlabs.form.field.treeselect';
		}

		if (is_array(reset($options)))
		{
			return 'joomla.form.field.groupedlist-fancy-select';
		}

		return 'joomla.form.field.list-fancy-select';
	}

	/**
	 * Render a select list loaded via Ajax
	 *
	 * @param string $field_class
	 * @param string $name
	 * @param string $value
	 * @param string $id
	 * @param array  $attributes
	 * @param bool   $treeselect
	 *
	 * @return string
	 */
	public static function selectListAjax($field_class, $name, $value, $id, $attributes = [], $treeselect = false, $collapse_children = false)
	{
		RL_Document::style('regularlabs.admin-form');
		RL_Document::script('regularlabs.admin-form');
		RL_Document::script('regularlabs.regular');
		RL_Document::script('regularlabs.script');

		if ($treeselect)
		{
			RL_Document::script('regularlabs.treeselect');
		}

		if (is_array($value))
		{
			$value = implode(',', $value);
		}

		$input = JFactory::getApplication()->input;

		$ajax_data = [
			'parent_request'    => [
				'option' => $input->get('option', ''),
				'view'   => $input->get('view', ''),
				'id'     => $input->getInt('id'),
			],
			'field_class'       => $field_class,
			'value'             => $value,
			'attributes'        => $attributes,
			'treeselect'        => $treeselect,
			'collapse_children' => $collapse_children,
		];

		return '<div>'
			. '<textarea name="' . $name . '" id="' . $id . '" cols="40" rows="5" class="w-100"'
			. ' data-rl-ajax="' . htmlspecialchars(json_encode($ajax_data)) . '">' . $value . '</textarea>'
			. '<div class="rl-spinner"></div>'
			. '</div>';
	}

//	/**
//	 * Render a simple select list
//	 *
//	 * @param array  $options
//	 * @param        $string $name
//	 * @param string $value
//	 * @param string $id
//	 * @param int    $size
//	 * @param bool   $multiple
//	 *
//	 * @return string
//	 */
//	public static function selectListSimple(&$options, $name, $value, $id, $size = 0, $multiple = false)
//	{
//		return self::selectlist($options, $name, $value, $id, $size, $multiple, true);
//	}

//	/**
//	 * Render a simple select list loaded via Ajax
//	 *
//	 * @param string $field
//	 * @param string $name
//	 * @param string $value
//	 * @param string $id
//	 * @param array  $attributes
//	 *
//	 * @return string
//	 */
//	public static function selectListSimpleAjax($field, $name, $value, $id, $attributes = [])
//	{
//		return self::selectListAjax($field, $name, $value, $id, $attributes, true);
//	}
//	/**
//	 * Replace style placeholders with actual style attributes
//	 *
//	 * @param string $string
//	 *
//	 * @return string
//	 */
//	private static function handlePreparedStyles($string)
//	{
//		// No placeholders found
//		if (strpos($string, '[[:') === false)
//		{
//			return $string;
//		}
//
//		// Doing following replacement in 3 steps to prevent the Regular Expressions engine from exploding
//
//		// Replace style tags right after the html tags
//		$string = RegEx::replace(
//			';?:\]\]\s*\[\[:',
//			';',
//			$string
//		);
//		$string = RegEx::replace(
//			'>\s*\[\[\:(.*?)\:\]\]',
//			' style="\1">',
//			$string
//		);
//
//		// No more placeholders found
//		if (strpos($string, '[[:') === false)
//		{
//			return $string;
//		}
//
//		// Replace style tags prepended with a minus and any amount of whitespace: '- '
//		$string = RegEx::replace(
//			'>((?:-\s*)+)\[\[\:(.*?)\:\]\]',
//			' style="\2">\1',
//			$string
//		);
//
//		// No more placeholders found
//		if (strpos($string, '[[:') === false)
//		{
//			return $string;
//		}
//
//		// Replace style tags prepended with whitespace, a minus and any amount of whitespace: ' - '
//		$string = RegEx::replace(
//			'>((?:\s+-\s*)+)\[\[\:(.*?)\:\]\]',
//			' style="\2">\1',
//			$string
//		);
//
//		return $string;
//	}
}

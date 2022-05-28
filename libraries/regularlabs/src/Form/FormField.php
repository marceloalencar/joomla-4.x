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

use DateTimeZone;
use JDatabaseDriver;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Form\Form as JForm;
use Joomla\CMS\Form\FormField as JFormField;
use Joomla\CMS\Form\FormHelper as JFormHelper;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use Joomla\Registry\Registry as JRegistry;
use ReflectionClass;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Parameters as RL_Parameters;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use SimpleXMLElement;

class FormField extends JFormField
{
	/**
	 * @var array
	 */
	public $attributes = [];
	/**
	 * @var JDatabaseDriver|null
	 */
	public $db = null;
	/**
	 * @var bool
	 */
	public $is_select_list = false;
	/**
	 * @var int
	 */
	public $max_list_count = 0;
	/**
	 * @var null
	 */
	public $params = null;
	/**
	 * @var bool
	 */
	public $use_ajax = false;
	/**
	 * @var bool
	 */
	public $use_tree_select = false;
	/**
	 * @var bool
	 */
	public $collapse_children = false;
	/**
	 * @var array
	 */
	public $parent_request = [];

	/**
	 * @param JForm $form
	 */
	public function __construct($form = null)
	{
		$this->type = $this->type ?? $this->getShortFieldName();

		parent::__construct($form);

		$this->db  = JFactory::getDbo();
		$this->app = JFactory::getApplication();

		$params = RL_Parameters::getPlugin('regularlabs');

		$this->max_list_count = $params->max_list_count;

		RL_Document::style('regularlabs.admin-form');
	}

	/**
	 * Get the short name of the field class
	 * FoobarField => Foobar
	 *
	 * @return string
	 */
	private function getShortFieldName()
	{
		return substr((new ReflectionClass($this))->getShortName(), 0, -strlen('Field'));
	}

	/**
	 * @return string
	 */
	public function getAjaxRaw(JRegistry $attributes)
	{
		return $this->selectListForAjax($attributes);
	}

	public function selectListForAjax($data)
	{
		return $this->selectListFromData($data);
	}

	public function selectListFromData($data)
	{
		$data_attributes = $data->get('attributes', []);

		$this->parent_request = $data->get('parent_request', []);
		$name                 = $this->name ?: $data->get('name', $this->type);
		$id                   = $this->id ?: $data->get('id', strtolower($name));
		$value                = $this->value ?: $data->get('value', []);
		$class                = $data->get('class', $data_attributes->class ?? '');
		$multiple             = $data->get('multiple', $data_attributes->multiple ?? 0);
		$tree_select          = $data->get('treeselect', $this->use_tree_select);
		$collapse_children    = $data->get('collapse_children', $this->collapse_children);

		$attributes = compact('class', 'multiple');

		foreach ($this->attributes as $key => $default)
		{
			$attributes[$key] = $data->get($key, $this->get($key, $default));
		}

		foreach ($data_attributes as $key => $val)
		{
			$this->params[$key] = $this->sanitizeValue($val);
			$attributes[$key]   = $this->sanitizeValue($val);
		}

		$attributes = array_diff_key($attributes, ['name' => '', 'type' => '']);

		$options = $this->getListOptions($attributes);

		if ($this->get('text_as_value'))
		{
			$this->setTextAsValue($options);
		}

		return Form::selectList(
			$options,
			$name,
			$value,
			$id,
			$attributes,
			$tree_select && $multiple,
			$tree_select && $multiple && $collapse_children
		);
	}

	/**
	 * Get a value from the field params
	 *
	 * @param string $key
	 * @param string $default
	 *
	 * @return bool|string
	 */
	public function get($key, $default = '')
	{
		$value = $default;

		if (isset($this->params[$key]) && (string) $this->params[$key] != '')
		{
			$value = (string) $this->params[$key];
		}

		return $this->sanitizeValue($value);
	}

	/**
	 * @param string $value
	 *
	 * @return bool|string
	 */
	public function sanitizeValue($value)
	{
		if (is_bool($value) || is_array($value) || is_object($value))
		{
			return $value;
		}

		if ($value === 'true')
		{
			return true;
		}

		if ($value === 'false')
		{
			return false;
		}

		return (string) $value;
	}

	/**
	 * @param array $attributes
	 *
	 * @return array
	 */
	protected function getListOptions($attributes)
	{
		return $this->getOptions();
	}

	private function setTextAsValue(&$options)
	{
		if (empty($options))
		{
			return;
		}

		foreach ($options as &$option)
		{
			$option->value = $option->text;
		}
	}

	/**
	 * Return the field options (array)
	 * Overrules the Joomla core functionality
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		if (empty($this->element->option))
		{
			return [];
		}

		$fieldname = RL_RegEx::replace('[^a-z0-9_\-]', '_', $this->fieldname);

		$options = [];

		foreach ($this->element->option as $option)
		{
			$value = (string) $option['value'];
			$text  = trim((string) $option) != '' ? trim((string) $option) : $value;

			$disabled = (string) $option['disabled'];
			$disabled = ($disabled === 'true' || $disabled === 'disabled' || $disabled === '1');
			$disabled = $disabled || ($this->readonly && $value != $this->value);

			$checked = (string) $option['checked'];
			$checked = ($checked === 'true' || $checked === 'checked' || $checked === '1');

			$selected = (string) $option['selected'];
			$selected = ($selected === 'true' || $selected === 'selected' || $selected === '1');

			$attributes = '';

			if ((string) $option['showon'])
			{
				$encodedConditions = json_encode(
					JFormHelper::parseShowOnConditions((string) $option['showon'], $this->formControl, $this->group)
				);

				$attributes .= ' data-showon="' . $encodedConditions . '"';
			}

			// Add the option object to the result set.
			$options[] = [
				'value'      => $value,
				'text'       => '- ' . JText::alt($text, $fieldname) . ' -',
				'disable'    => $disabled,
				'class'      => (string) $option['class'],
				'selected'   => ($checked || $selected),
				'checked'    => ($checked || $selected),
				'onclick'    => (string) $option['onclick'],
				'onchange'   => (string) $option['onchange'],
				'optionattr' => $attributes,
			];
		}

		return $options;
	}

	public function getControlGroupEnd()
	{
		return '</div></div>';
	}

	public function getControlGroupStart()
	{
		return '<div class="control-group"><div class="control-label">';
	}

	/**
	 * Return a array of options using the custom prepare methods
	 *
	 * @param array $list
	 * @param array $extras
	 * @param int   $levelOffset
	 *
	 * @return array
	 */
	public function getOptionsByList($list, $extras = [], $levelOffset = 0, $key = 'id')
	{
		$options = [];
		foreach ($list as $id => $item)
		{
			$options[$id] = $this->getOptionByListItem($item, $extras, $levelOffset, $key);
		}

		return $options;
	}

	/**
	 * Return a list option using the custom prepare methods
	 *
	 * @param object $item
	 * @param array  $extras
	 * @param int    $levelOffset
	 *
	 * @return mixed
	 */
	public function getOptionByListItem($item, $extras = [], $levelOffset = 0, $key = 'id')
	{
		$name = Form::getNameWithExtras($item, $extras);

		$option = JHtml::_('select.option', $item->{$key}, $name, 'value', 'text', 0);

		if (isset($item->level))
		{
			$option->level = $item->level + $levelOffset;
		}

		return $option;
	}

	/**
	 * Return a recursive options list using the custom prepare methods
	 *
	 * @param array $items
	 * @param int   $root
	 *
	 * @return array
	 */
	public function getOptionsTreeByList($items = [], $root = 0)
	{
		// establish the hierarchy of the menu
		// TODO: use node model
		$children = [];

		if ( ! empty($items))
		{
			// first pass - collect children
			foreach ($items as $v)
			{
				$pt   = $v->parent_id;
				$list = @$children[$pt] ?: [];
				array_push($list, $v);
				$children[$pt] = $list;
			}
		}

		// second pass - get an indent list of the items
		$list = JHtml::_('menu.treerecurse', $root, '', [], $children, 9999, 0, 0);

		// assemble items to the array
		$options = [];
		if ($this->get('show_ignore'))
		{
			if (in_array('-1', $this->value))
			{
				$this->value = ['-1'];
			}
			$options[] = JHtml::_('select.option', '-1', '- ' . JText::_('RL_IGNORE') . ' -', 'value', 'text', 0);
			$options[] = JHtml::_('select.option', '-', '&nbsp;', 'value', 'text', 1);
		}

		foreach ($list as $item)
		{
			$item->treename = Form::prepareSelectItem($item->treename, 1);

			$options[] = JHtml::_('select.option', $item->id, $item->treename, 'value', 'text', 0);
		}

		return $options;
	}

	/**
	 * Passes along to the JText method.
	 * This is used for the array_walk in the sprintf method above.
	 *
	 * @param $string
	 */
	public function jText(&$string)
	{
		$string = JText::_($string);
	}

	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$this->params = $element->attributes();

		return parent::setup($element, $value, $group);
	}

	/**
	 * Return the field input markup
	 * Return empty by default
	 *
	 * @return string
	 */
	protected function getInput()
	{
		if ( ! $this->is_select_list)
		{
			return '';
		}

		if ( ! $this->use_ajax && ! $this->use_tree_select)
		{
			return $this->selectList();
		}

		return $this->selectListAjax();
	}

	public function selectList()
	{
		return $this->selectListFromData($this);
	}

	public function selectListAjax()
	{
		$class    = $this->get('class', '');
		$multiple = $this->get('multiple', false);

		if ( ! $this->use_tree_select)
		{
			RL_Document::usePreset('choicesjs');
			RL_Document::useScript('webcomponent.field-fancy-select');
		}

		$attributes = compact('class', 'multiple');

		foreach ($this->attributes as $key => $default)
		{
			$attributes[$key] = $this->get($key, $default);
		}

		foreach ($this->params as $key => $value)
		{
			$attributes[$key] = (string) $value;
		}

		$tree_select = $this->use_tree_select && $multiple;

		return Form::selectListAjax(
			get_class($this),
			$this->name,
			$this->value,
			$this->id,
			$attributes,
			$tree_select,
			$tree_select && $this->collapse_children
		);
	}

	protected function getLabel()
	{
		$this->element['label'] = $this->prepareText($this->element['label']);

		return $this->element['label'] == '---' ? '&nbsp;' : parent::getLabel();
	}

	/**
	 * Prepare the option string, handling language strings
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function prepareText($string = '')
	{
		$string = trim((string) $string);

		if ($string == '')
		{
			return '';
		}

		$string = JText::_($string);
		$string = $this->replaceDateTags($string);
		$string = $this->fixLanguageStringSyntax($string);

		return $string;
	}

	public function replaceDateTags($string)
	{
		if ( ! RL_RegEx::matchAll('\[date:(?<format>.*?)\]', $string, $matches))
		{
			return $string;
		}

		$date = JFactory::getDate();

		$tz = new DateTimeZone(JFactory::getApplication()->getCfg('offset'));
		$date->setTimeZone($tz);

		foreach ($matches as $match)
		{
			$replace = $date->format($match['format'], true);
			$string  = str_replace($match[0], $replace, $string);
		}

		return $string;
	}

	/**
	 * Fix some syntax/encoding issues in option text strings
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	private function fixLanguageStringSyntax($string = '')
	{
		$string = str_replace('[:COMMA:]', ',', $string);
		$string = trim(RL_String::html_entity_decoder($string));
		$string = str_replace('&quot;', '"', $string);
		$string = str_replace('span style="font-family:monospace;"', 'span class="rl_code"', $string);

		return $string;
	}

	/**
	 * Replace language strings in a string
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	private function sprintf($string = '')
	{
		$string = trim($string);

		if (strpos($string, ',') === false)
		{
			return $string;
		}

		$string_parts = explode(',', $string);
		$first_part   = array_shift($string_parts);

		if ($first_part === strtoupper($first_part))
		{
			$first_part = JText::_($first_part);
		}

		$first_part = RL_RegEx::replace('\[\[%([0-9]+):[^\]]*\]\]', '%\1$s', $first_part);

		array_walk($string_parts, '\RegularLabs\Library\Field::jText');

		return vsprintf($first_part, $string_parts);
	}
}

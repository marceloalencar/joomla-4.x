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

use Joomla\CMS\Factory as JFactory;
use ReflectionClass;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Boolean as BooleanHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Calculation as CalculationHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Date as DateHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Image as ImageHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Text as TextHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers\Video as VideoHelper;
use RegularLabs\Plugin\System\ArticlesAnywhere\Filters\ValuesObject;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class DataGroup
{
	protected static $cache_time           = (12 * 60);
	protected static $data_key_aliases     = [];
	protected static $data_subkey_aliases  = [];
	protected static $database_column_case = 'underscore';
	protected static $default_data_key     = '';
	protected static $default_data_subkey  = '';
	protected static $main_table           = '';
	protected static $prefix               = '';
	protected static $ignore_group         = '';
	protected        $article_selector;
	protected        $attributes;
	protected        $params;
	protected        $filter_key;
	protected        $key;
	protected        $numbers;
	protected        $parent_key;
	protected        $row                  = 0;
	protected        $subkey;
	protected        $subkey_original;
	protected        $values               = [];

	/**
	 * @param string $key
	 * @param object $attributes
	 * @param string $article_selector
	 */
	public function __construct($key, $subkey = '', $attributes = null, $article_selector = '')
	{
		$this->filter_key       = $this->prepareKey($key, false);
		$this->key              = $this->prepareKey($key);
		$this->subkey_original  = $subkey;
		$this->subkey           = $this->prepareSubKey($subkey);
		$this->attributes       = $attributes ?: (object) [];
		$this->article_selector = $article_selector;

		$this->attributes = RL_Object::changeKeyCase($this->attributes, 'underscore');
		$this->params     = Params::get($this->attributes);

		$this->attributes = RL_Object::replaceKeys($this->attributes, $this->getAttributeAliases());
	}

	protected function prepareKey($key, $set_default = true)
	{
		if (isset(static::$data_key_aliases[$key]))
		{
			$key = static::$data_key_aliases[$key];
		}

		if (static::$prefix)
		{
			$key = RL_String::removePrefix($key, static::$prefix . '-');
		}

		// Set key to the default, if it is the same as the prefix
		// This means that you can't ever set the data key to the same value as the prefix, if the default is different, like:
		// [foo:foo] if default key is 'bar'
		// Will this ever be an issue?
		if ($set_default && static::$default_data_key && $key === static::$prefix)
		{
			$key = static::$default_data_key;
		}

		if (isset(static::$data_key_aliases[$key]))
		{
			$key = static::$data_key_aliases[$key];
		}

		return $key;
	}

	protected function prepareSubKey($subkey)
	{
		if (isset(static::$data_subkey_aliases[$subkey]))
		{
			$subkey = static::$data_subkey_aliases[$subkey];
		}

		return $subkey ?: static::$default_data_subkey;
	}

	protected function getAttributeAliases()
	{
		return [
			'characters' => ['limit'],
			'calc'       => ['calculate', 'calculation'],
			'showlabel'  => ['label', 'show_label'],
		];
	}

	static function getGroupNameStatic()
	{
		return (new ReflectionClass(static::class))->getShortName();
	}

	public static function getPossibleKeys()
	{
		$data_group = static::class;

		// $cache = (new RL_Cache([__METHOD__, $data_group]))->useFiles(static::$cache_time);
		$cache = (new RL_Cache([__METHOD__, $data_group]));

		if ($cache->exists())
		{
			return $cache->get();
		}

		$plain = RL_String::toDashCase(static::getPossiblePlainKeys());

		$plain = array_merge($plain, array_keys(static::$data_key_aliases));

		$plain = static::preparePossiblePlainKeys($plain);

		$regex = static::getPossibleRegexKeys();

		$possible_keys = (object) compact('plain', 'regex');

		return $cache->set($possible_keys);
	}

	protected static function getPossiblePlainKeys()
	{
		return [];
	}

	protected static function preparePossiblePlainKeys($plain_keys = [])
	{
		$prefix = static::getPrefix();

		if ($prefix && static::getDefaultDataKey())
		{
			$plain_keys[] = $prefix;
		}

		return $plain_keys;
	}

	protected static function getPossibleRegexKeys()
	{
		$prefix = static::getPrefix();

		if ( ! $prefix)
		{
			return [];
		}

		return ['^' . $prefix . ':'];
	}

	public static function getPrefix()
	{
		return static::$prefix;
	}

	public static function getDefaultDataKey()
	{
		return static::$default_data_key;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function attributeExists($key)
	{
		return isset($this->attributes->{$key});
	}

	public function getArticleSelector()
	{
		return $this->article_selector;
	}

	public function setArticleSelector($prefix)
	{
		$this->article_selector = $prefix;
	}

	/**
	 * @param string     $key
	 * @param null|mixed $default
	 *
	 * @return null|mixed
	 */
	public function getAttribute($key, $default = null)
	{
		$key = RL_String::toUnderscoreCase($key);

		return $this->attributes->{$key} ?? $default;
	}

	/**
	 * @return array
	 */
	public function getForeachData()
	{
		return RL_Array::toArray($this->getOutputRaw());
	}

	/**
	 * @return mixed
	 */
	public function getOutputRaw()
	{
		return $this->getValue();
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		$key = self::getDatabaseKey($this->key);

		if (isset($this->values[$key]))
		{
			return $this->values[$key];
		}

		foreach ($this->values as $value)
		{
			if (is_array($value) && isset($value[$this->key]))
			{
				return $value[$this->key];
			}

			if (is_object($value) && isset($value->{$this->key}))
			{
				return $value->{$this->key};
			}
		}

		return '';
	}

	public function getDatabaseKey($key = '', $add_prefix = true, $case = 'underscore')
	{
		$key = $key ?: $this->key;

		if ($key === static::getPrefix() && static::getDefaultDataKey())
		{
			$key = static::getDefaultDataKey();
		}

		$key = RL_String::toCase($key, $case);

		if ( ! $add_prefix)
		{
			return $key;
		}

		return $this->getDatabasePrefix() . '.' . $key;
	}

	public function getDatabasePrefix()
	{
		return RL_String::toUnderscoreCase(RL_String::fromCamelCase($this->getClassName()));
	}

	public static function getClassName()
	{
		$class = explode('\\', get_called_class());

		return end($class);
	}

	/**
	 * @return array [table => field]
	 */
	public function getGroupBys()
	{
		return [];
	}

	public function getGroupName()
	{
		return (new ReflectionClass($this))->getShortName();
	}

	public function getIgnoreWhere()
	{
		$wheres = [];

		$where_state = $this->getIgnoreWhereState();

		if ($where_state)
		{
			$wheres[] = $where_state;
		}

		$where_access = $this->getIgnoreWhereAccess();

		if ($where_access)
		{
			$wheres[] = $where_access;
		}

		$where_language = $this->getIgnoreWhereLanguage();

		if ($where_language)
		{
			$wheres[] = $where_language;
		}

		return DB::combine($wheres, 'AND');
	}

	protected function getIgnoreWhereState()
	{
		$ignore = $this->getIgnoreState();

		if ($ignore)
		{
			return false;
		}

		return DB::is(static::$prefix . '.published', 1);
	}

	protected function getIgnoreWhereAccess()
	{
		$ignore = $this->getIgnoreAccess();

		if ($ignore)
		{
			return false;
		}

		return DB::in(static::$prefix . '.access', Params::getAuthorisedViewLevels());
	}

	protected function getIgnoreWhereLanguage()
	{
		$ignore = $this->getIgnoreLanguage();

		if ($ignore)
		{
			return false;
		}

		return DB::in(static::$prefix . '.language', [JFactory::getApplication()->getLanguage()->getTag(), '*']);
	}

	protected function getIgnoreState()
	{
		return $this->getIgnoreByType('state');
	}

	protected function getIgnoreAccess()
	{
		return $this->getIgnoreByType('access');
	}

//	/**
//	 * @return string
//	 */
//	public function getOutputValue()
//	{
//		$value = $this->getOutputRaw();
//
//		if (is_array($value))
//		{
//			return RL_Array::implode($value, ', ');
//		}
//
//		return $value;
//	}

	protected function getIgnoreLanguage()
	{
		return $this->getIgnoreByType('language');
	}

	private function getIgnoreByType($type = 'state')
	{
		if (empty(static::$ignore_group))
		{
			return true;
		}

		$ignore_type_group = static::$ignore_group === 'articles'
			? 'ignore_' . $type
			: 'ignore_' . $type . '_' . static::$ignore_group;

		if ( ! isset($this->params->{$ignore_type_group}))
		{
			return true;
		}

		return $this->params->{$ignore_type_group} === '1';
	}

	/**
	 * @return array [table => condition]
	 */
	public function getJoinsForFilters()
	{
		return $this->getJoins();
	}

	/**
	 * @return array [table => condition]
	 */
	public function getJoins()
	{
		return [];
	}

	public function getKey()
	{
		return $this->key;
	}

	public function getOriginalSubkey()
	{
		return $this->subkey_original;
	}

	/**
	 * @return mixed
	 */
	public function getOutput()
	{
		$value = $this->getValue();

		$this->postProcessOutput($value);

		return $value;
	}

	/**
	 * @param string $output
	 */
	public function postProcessOutput(&$output)
	{
		$this->postProcessText($output);
		$this->postProcessDate($output);
		$this->postProcessCalculations($output);
		$this->postProcessBoolean($output);
	}

	public function postProcessText(&$output)
	{
		$output = TextHelper::process($output, $this->key, $this->attributes);
	}

	public function postProcessDate(&$output)
	{
		$output = DateHelper::process($output, $this->key, $this->attributes);
	}

	public function postProcessCalculations(&$output)
	{
		$output = CalculationHelper::process($output, $this->key, $this->attributes);
	}

	public function postProcessBoolean(&$output)
	{
		$output = BooleanHelper::process($output, $this->key, $this->attributes);
	}

	/**
	 * @return array
	 */
	public function getSelects()
	{
		return $this->getSelectsByKeys($this->getAllQueryKeys());
	}

	/**
	 * @param array $keys
	 *
	 * @return array
	 */
	public function getSelectsByKeys($keys)
	{
		$selects = [];

		foreach ($keys as $select => $alias)
		{
			if (is_numeric($select))
			{
				[$table, $column] = explode('.', $alias, 2);
				$case   = $table === static::$prefix ? static::$database_column_case : 'underscore';
				$select = $table . '.' . RL_String::toCase($column, $case);
			}

			$selects[] = DB::quoteName($select, $alias);
		}

		return array_unique($selects);
	}

	private function getAllQueryKeys()
	{
		return array_merge($this->getRequiredQueryKeys(), $this->getQueryKeys());
	}

	public function getRequiredQueryKeys()
	{
		return [];
	}

	/**
	 * @return array
	 */
	public function getQueryKeys()
	{
		if ($this->key[0] === '/')
		{
			return [];
		}

		foreach ($this->getJsonKeys() as $json_column => $json_keys)
		{
			if ( ! in_array($this->key, $json_keys, true)
				&& ! in_array(RL_String::toUnderscoreCase($this->key), $json_keys, true))
			{
				continue;
			}

			return [static::$prefix . '.' . $json_column];
		}

		$key = self::getDatabaseKey($this->key, false);

		if (in_array($key, $this->getDatabaseFields(), true)
			|| in_array(RL_String::toCamelCase($key), $this->getDatabaseFields(), true))
		{
			return [self::getDatabaseKey($this->key, true)];
		}

		return [];
	}

	protected static function getJsonKeys()
	{
		return [];
	}

	protected static function getDatabaseFields()
	{
		$data_group = static::class;

		$cache = (new RL_Cache([__METHOD__, $data_group]))->useFiles();

		if ($cache->exists())
		{
			return $cache->get();
		}

		if ( ! static::$main_table)
		{
			return [];
		}

		$table_fields = array_keys(DB::get()->getTableColumns('#__' . static::$main_table));

		$value_fields = array_diff(
			$table_fields,
			array_keys(static::getJsonKeys())
		);

		return $cache->set($value_fields);
	}

	/**
	 * @return array
	 */
	public function getSelectsForFilters()
	{
		return [];
	}

	/**
	 * @return string
	 */
	public function getValueFromJsonKey($parent_key, $child_key, $prefix = '')
	{
		$prefix     = $prefix ?: static::$prefix;
		$parent_key = $prefix . '.' . $parent_key;

		if ( ! isset($this->values[$parent_key]))
		{
			return '';
		}

		if (is_array($this->values[$parent_key]) && isset($this->values[$parent_key][$child_key]))
		{
			return $this->values[$parent_key][$child_key];
		}

		if (is_object($this->values[$parent_key]) && isset($this->values[$parent_key]->{$child_key}))
		{
			return $this->values[$parent_key]->{$child_key};
		}

		return '';
	}

	/**
	 * @return array
	 */
	public function getValues()
	{
		return $this->values;
	}

	public function setValues($values, $numbers)
	{
		$possible_value_keys = static::getValueKeys();

		$this->values = $values;
		if ( ! in_array('*', $possible_value_keys))
		{
			$possible_value_keys = array_merge($possible_value_keys, $this->getAllQueryKeys());
			$possible_value_keys = RL_Array::unique($possible_value_keys);
			$this->values        = array_intersect_key($this->values, array_flip($possible_value_keys));
		}

		$this->values['value']    = $values['value'] ?? '';
		$this->values['rawvalue'] = $values['rawvalue'] ?? '';

		$this->convertJsonValues($this->values);

		$this->numbers = $numbers;
	}

	protected static function getValueKeys()
	{
		return [];
	}

	private function convertJsonValues(&$values)
	{
		$json_keys = static::getJsonKeys();

		if (empty($json_keys))
		{
			return;
		}

		foreach ($json_keys as $column_key => $value_keys)
		{
			if ( ! isset($values[static::$prefix . '.' . $column_key]))
			{
				continue;
			}

			$value = $values[static::$prefix . '.' . $column_key];

			if (empty($value) || ! is_string($value) || $value[0] !== '{')
			{
				continue;
			}

			$values[static::$prefix . '.' . $column_key] = json_decode($value);
		}
	}

	/**
	 * @param array|object|string $values
	 * @param bool                $exclude
	 *
	 * @return string
	 */
	public function getWhere($values, $glue = 'OR')
	{
		if (empty($values))
		{
			return DB::is($this->getDatabaseKey(), $values, compact('glue'));
		}

		if (is_object($values))
		{
			return $this->getWhereFromObject($values, $glue);
		}

		if (is_array($values))
		{
			return $this->getWhereFromArray($values, $glue);
		}

		if ($values === '=+')
		{
			return '('
				. DB::quoteName($this->getDatabaseKey()) . ' != ' . DB::quote('')
				. ' AND '
				. DB::quoteName($this->getDatabaseKey()) . ' IS NOT NULL'
				. ')';
		}

		return DB::is($this->getDatabaseKey(), $values, compact('glue'));
	}

	/**
	 * @param ValuesObject $values
	 * @param bool         $exclude
	 *
	 * @return string
	 */
	private function getWhereFromObject($values, $glue = 'OR')
	{
		$wheres = [];

		foreach ($values->getValues() as $value)
		{
			$wheres[] = $this->getWhere($value, $glue);
		}

		return DB::combine($wheres, $values->getGlue());
	}

	/**
	 * @param array $values
	 * @param bool  $exclude
	 *
	 * @return string
	 */
	private function getWhereFromArray($values, $glue = 'OR')
	{
		$wheres = [];

		foreach ($values as $value)
		{
			$wheres[] = $this->getWhere($value, $glue);
		}

		return DB::combine($wheres, $glue);
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasAttribute($key, $false_if_empty = false)
	{
		$key = RL_String::toUnderscoreCase($key);

		return $false_if_empty
			? ! empty($this->attributes->{$key})
			: isset($this->attributes->{$key});
	}

	/**
	 * @return bool
	 */
	public function hasValue()
	{
		$key = self::getDatabaseKey($this->key);

		if (isset($this->values[$key]))
		{
			return true;
		}

		foreach ($this->values as $value)
		{
			if (is_array($value) && isset($value[$this->key]))
			{
				return true;
			}

			if (is_object($value) && isset($value->{$this->key}))
			{
				return true;
			}
		}

		return false;
	}

	public function removeAttribute($key)
	{
		unset($this->attributes->{$key});
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	public function setAttribute($key, $value)
	{
		$key = RL_String::toUnderscoreCase($key);

		$this->attributes->{$key} = $value;
	}

	public function setParentKey($key)
	{
		$this->parent_key = $key;
	}

	public function setRow($row)
	{
		$this->row = $row;
	}

	protected static function getExtraFields()
	{
		return [];
	}

	protected static function getFields()
	{
		return [];
	}

	protected function getContentImageByMatch($match, $text)
	{
	}

	protected function get($key, $default = null)
	{
		$parts = RL_Array::toArray($key, '.');

		if (count($parts) === 1)
		{
			[$key] = $parts;

			return $this->values[static::$prefix . '.' . $key] ?? $default;
		}

		if (count($parts) === 3)
		{
			[$prefix, $parent, $key] = $parts;

			return $this->values[$prefix . '.' . $parent]->{$key} ?? $default;
		}

		[$prefix, $key] = $parts;

		if (isset($this->values[$prefix . '.' . $key])
			&& $this->values[$prefix . '.' . $key] !== ''
		)
		{
			return $this->values[$prefix . '.' . $key];
		}

		[$parent, $key] = $parts;

		if (isset($this->values[static::$prefix . '.' . $parent]->{$key})
			&& $this->values[static::$prefix . '.' . $parent]->{$key} !== ''
		)
		{
			return $this->values[static::$prefix . '.' . $parent]->{$key};
		}

		return $default;
	}

	protected function getContentVideoByMatch($match, $text)
	{
	}
}

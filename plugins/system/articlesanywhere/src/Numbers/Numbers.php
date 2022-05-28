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

namespace RegularLabs\Plugin\System\ArticlesAnywhere\Numbers;

defined('_JEXEC') or die;

use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;

class Numbers
{
	private $aliases;
	private $numbers;
	private $pagination;

	public function __construct($total_results, $total_no_limits, $total_no_pagination, $limit, $offset, $pagination_numbers = null)
	{
		$this->aliases = self::getAliases();
		$this->initNumbers();

	}

	public static function getAliases()
	{
		return [
			'first'  => 'is-first',
			'last'   => 'is-last',
			'even'   => 'is-even',
			'uneven' => 'is-uneven',

			'first-no-limit'  => 'is-first-no-limit',
			'last-no-limit'   => 'is-last-no-limit',
			'even-no-limit'   => 'is-even-no-limit',
			'uneven-no-limit' => 'is-uneven-no-limit',

			'first-no-pagination'  => 'is-first-no-pagination',
			'last-no-pagination'   => 'is-last-no-pagination',
			'even-no-pagination'   => 'is-even-no-pagination',
			'uneven-no-pagination' => 'is-uneven-no-pagination',

			'total-pages' => 'pages',
		];
	}

	private function initNumbers()
	{
		$this->numbers = (object) self::getDefaultNumbers();
	}

	public function setNumber($key, $value)
	{
		$this->numbers->{$key} = $value;
	}

	private function setPage()
	{
	}

	public static function getDefaultNumbers()
	{
		return array_merge(
			self::getDefaultCountsByType(),
			self::getDefaultCountsByType('no-limit'),
			self::getDefaultCountsByType('no-pagination'),
			[
				'limit'  => 1,
				'offset' => 0,

				'pages'             => 1,
				'total-pages'       => 1,
				'page'              => 1,
				'per-page'          => 1,
				'is-first-page'     => true,
				'is-last-page'      => true,
				'has-next-page'     => false,
				'has-previous-page' => false,
				'next-page'         => 1,
				'previous-page'     => 1,
			]
		);
	}

	private static function getDefaultCountsByType($suffix = '')
	{
		$suffix = $suffix ? '-' . $suffix : '';

		return [
			'is-current' . $suffix   => false,
			'total' . $suffix        => 1,
			'count' . $suffix        => 1,
			'is-first' . $suffix     => true,
			'is-last' . $suffix      => true,
			'has-next' . $suffix     => true,
			'has-previous' . $suffix => false,
			'next' . $suffix         => 1,
			'previous' . $suffix     => 1,
			'is-even' . $suffix      => false,
			'is-uneven' . $suffix    => true,
		];
	}

	public function get($key)
	{
		if (RL_RegEx::match('every-(?<number>[0-9]+)', $key, $match))
		{
			return $this->isEvery($match['number']);
		}

		if (RL_RegEx::match('is-(?<number>[0-9]+)-of-(?<column>[0-9]+)', $key, $match))
		{
			return $this->isColumn($match['number'], $match['column']);
		}

		return $this->getNumber($key) ?? null;
	}

	private function isEvery($number = 1)
	{
	}

	private function isColumn($number = 1, $column_count = 1)
	{
	}

	private function getNumber($key)
	{
		$key = RL_String::toDashCase($key);

		if (isset($this->aliases[$key]))
		{
			$key = $this->aliases[$key];
		}

		return $this->numbers->{$key};
	}

	public function setCount($count)
	{
		$this->setCountByType(
			$count
		);

		$page_offset = ($this->getNumber('page') - 1) * $this->getNumber('per-page');

		$this->setCountByType(
			$count + $page_offset,
			'no-pagination'
		);

		$this->setCountByType(
			$count + $page_offset + $this->getNumber('offset'),
			'no-limit'
		);
	}

	private function setCountByType($count, $suffix = '')
	{
		$suffix = $suffix ? '-' . $suffix : '';

		$first    = 1;
		$last     = $this->numbers->{'total' . $suffix};
		$is_first = $count === $first;
		$is_last  = $count === $last;

		$this->setNumber('count' . $suffix, $count);
		$this->setNumber('is-first' . $suffix, $is_first);
		$this->setNumber('is-last' . $suffix, $is_last);
		$this->setNumber('has-next' . $suffix, ! $is_last);
		$this->setNumber('has-previous' . $suffix, ! $is_first);
		$this->setNumber('next' . $suffix, $is_last ? $first : $count + 1);
		$this->setNumber('previous' . $suffix, $is_first ? $last : $count - 1);
		$this->setNumber('is-even' . $suffix, ($count % 2) === 0);
		$this->setNumber('is-uneven' . $suffix, ($count % 2) !== 0);
	}
}

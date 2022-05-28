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

use Joomla\Registry\Registry as JRegistry;
use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Library\File as RL_File;
use RegularLabs\Library\HtmlTag as RL_HtmlTag;
use RegularLabs\Library\Image as RL_Image;
use RegularLabs\Library\ObjectHelper as RL_Object;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\StringHelper as RL_String;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class Image
{
	public static function getImageDataFromText($text, $id)
	{
		if (empty($text))
		{
			return (object) [];
		}

		$images = self::getAllImagesFromText($text);

		if (empty($images))
		{
			return (object) [];
		}

		if ($id === 'random')
		{
			$id = rand(1, count($images));
		}

		return RL_Object::clone($images[$id]) ?? (object) [];
	}

	public static function getAllImagesFromText($text)
	{
		$cache = new RL_Cache;

		if ($cache->exists())
		{
			return $cache->get();
		}

		RL_RegEx::matchAll(
			'<img\s[^>]*src=([\'"]).*?\1[^>]*>',
			$text,
			$matches
		);

		$images = [];

		foreach ($matches as $i => $match)
		{
			$images[$i + 1] = (object) RL_HtmlTag::getAttributes($match[0]);
		}

		return $cache->set($images);
	}

	public static function getOutputByKey($key, $image_data, $attributes = null, $type = 'content', $default_title = '')
	{
		if (empty($image_data->src))
		{
			return '';
		}

		$attributes = $attributes ?? (object) [];

		if (isset($attributes->suffix))
		{
			$image_data->src = RL_File::addSuffix($image_data->src, $attributes->suffix);
			unset($attributes->suffix);
		}

		if (in_array($key, ['resize', 'resized', 'thumb', 'thumbnail'], true))
		{
			$key = 'tag';

			$attributes->resize = true;
		}

		if ($key === 'tag')
		{
			$key = $image_data->key ?? $key;
		}

		self::setDimensions($image_data, $attributes);

		if (isset($attributes->width) || isset($attributes->height))
		{
			unset($image_data->width);
			unset($image_data->height);
		}

		$image_data = RL_Object::merge($image_data, $attributes);
		self::setAltAndTitle($type, $image_data, $default_title);

		$image = self::get($image_data);

		if ( ! $image)
		{
			return '';
		}

		switch ($key)
		{
			case 'tag':
				return $image->renderTag();

			case 'layout':
				$layout_id = Layout::getId($attributes->layout ?? '', 'joomla.content.' . $image_data->type);
				$type      = $image_data->type === 'intro_image' ? 'intro' : 'fulltext';

				$displayData = (object) [
					'params' => new JRegistry,
					'image'  => (object) [
						'path'    => $image->getPath(),
						'float'   => $image_data->float ?? '',
						'alt'     => $image_data->alt ?? '',
						'caption' => $image_data->caption ?? '',
					],
					'images' => json_encode((object) [
						'image_' . $type              => $image->getPath(),
						'float_' . $type              => $image_data->float ?? '',
						'image_' . $type . '_alt'     => $image_data->alt ?? '',
						'image_' . $type . '_caption' => $image_data->caption ?? '',
					]),
				];

				return Layout::render($layout_id, $displayData);

			case 'url':
			case 'src':
				return $image->getPath();

			case 'width':
				return $image->getWidth();

			case 'height':
				return $image->getHeight();

			default:
				return $image_data->{$key} ?? '';
		}
	}

	private static function setDimensions($image_data, &$attributes)
	{
		$params = Params::get();

		// resize attribute is not found and resize_images setting is off
		if (empty($attributes->resize) && ! $params->resize_images)
		{
			if (empty($attributes->width) && empty($attributes->height))
			{
				$attributes->width  = $image_data->width ?? null;
				$attributes->height = $image_data->height ?? null;
			}

			$attributes->resize = false;

			return;
		}

		//  width or height is found in the attributes, so force resizing
		if ( ! empty($attributes->width) || ! empty($attributes->height))
		{
			$attributes->resize = true;

			return;
		}

		$attributes->width  = $attributes->width ?? $image_data->width ?? null;
		$attributes->height = $attributes->height ?? $image_data->height ?? null;

		//   width or height is found in the $image_data either, so ignore default settings
//		if ( ! empty($attributes->width) || ! empty($attributes->height))
//		{
//			return;
//		}

		// resize attribute is not found
		if (empty($attributes->resize) && $params->resize_images !== '1')
		{
			return;
		}

		// image filetype is not supported or enabled
		if ( ! self::isEnabledResizeFiletype($image_data->src))
		{
			return;
		}

		$resize_type        = $attributes->resize_type ?? $params->resize_type;
		$attributes->resize = true;

		switch ($resize_type)
		{
			case 'crop':
				$attributes->width  = $params->resize_width;
				$attributes->height = $params->resize_height;

				return;

			case 'scale':
			default:
				$resize_using = $attributes->resize_using ?? $params->resize_using;

				$attributes->width  = $resize_using === 'width' ? $params->resize_width : null;
				$attributes->height = $resize_using === 'height' ? $params->resize_height : null;

				return;
		}
	}

	public static function setAltAndTitle($type, &$attributes, $default_title = '')
	{
		$attributes = empty($attributes) ? (object) [] : $attributes;

		self::crossFillAltAndTitle($attributes);

	}

	public static function get($attributes)
	{
		if (empty($attributes->src))
		{
			return null;
		}

		$resize_folder                 = $attributes->resize_folder ?? Params::get()->resize_folder;
		$attributes->{'resize-folder'} = dirname($attributes->src) . '/' . $resize_folder;
		$attributes->quality           = $attributes->quality ?? Params::get()->resize_quality;

		return new RL_Image($attributes->src, $attributes);
	}

	private static function isEnabledResizeFiletype($image_url)
	{
		$file_types = RL_Array::toArray(Params::get()->resize_filetypes);

		$extension = RL_File::getExtension($image_url);
		$extension = str_replace(
			['jpeg'],
			['jpg'],
			strtolower($extension)
		);

		return in_array($extension, $file_types, true);
	}

	private static function crossFillAltAndTitle(&$attributes)
	{
		$attributes = empty($attributes) ? (object) [] : $attributes;

		$params = Params::get();

		if ( ! $params->image_titles_cross_fill)
		{
			return;
		}

		if (empty($attributes->alt) && ! empty($attributes->title))
		{
			$attributes->alt = $attributes->title;
		}
		if (empty($attributes->title) && ! empty($attributes->alt))
		{
			$attributes->title = $attributes->alt;
		}
	}

	private static function getTitleFromFile($url, $attributes)
	{
	}

	private static function getCleanTitle($url)
	{
	}

	private static function getCleanFileName($url)
	{
	}
}

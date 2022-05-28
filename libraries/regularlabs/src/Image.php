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

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';

use Intervention\Image\Exception\NotReadableException as NotReadableException;
use Intervention\Image\ImageManagerStatic as ImageManager;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Uri\Uri as JUri;
use RegularLabs\Library\File as RL_File;
use RegularLabs\Library\HtmlTag as RL_HtmlTag;

class Image
{
	private $attributes;
	private $is_resized;
	private $main_image;
	private $main_image_path;

	private $source;
	private $file;
	private $dirname;
	private $basename;
	private $extension;
	private $filename;
	private $mime;
	private $width  = '';
	private $height = '';

	public function __construct($source, $attributes = null)
	{
		$this->source = $source;
		$this->file   = $source;

		if ($this->isInternal())
		{
			$this->source = self::cleanPath($source);
			$this->file   = JPATH_ROOT . '/' . ltrim($this->source, '/');
		}

		$this->attributes = $attributes ?? (object) [];

		if ( ! $this->isInternal() || ! $this->exists())
		{
			return;
		}

		$this->setFileInfo();
		$this->setDimensions();

		if ($this->isResized())
		{
			return;
		}

		if (empty($this->attributes->resize)
			|| (empty($this->attributes->width)
				&& empty($this->attributes->height))
		)
		{
			return;
		}

		$resized = $this->getResized(
			$this->attributes->width ?? null,
			$this->attributes->height ?? null,
			$this->attributes->{'resize-folder'} ?? null,
			$this->attributes->quality ?? null
		);

		if ( ! $resized)
		{
			return;
		}

		$this->source = $resized->basePath();
		$this->setFileInfo();
	}

	public function isInternal()
	{
		return ! is_null($this->dirname) || RL_File::isInternal($this->source);
	}

	public static function cleanPath($path)
	{
		if (strpos($path, JPATH_SITE . '/') === 0)
		{
			$path = substr($path, strlen(JPATH_SITE . '/'));
		}

		$path = ltrim(str_replace(JUri::root(), '', $path), '/');
		$path = strtok($path, '?');

		return $path;
	}

	public function exists()
	{
		return file_exists($this->file) && is_file($this->file);
	}

	/**
	 * Sets all instance properties from given path
	 */
	private function setFileInfo()
	{
		$info            = pathinfo($this->source);
		$this->dirname   = $info['dirname'] ?? null;
		$this->basename  = $info['basename'] ?? null;
		$this->extension = $info['extension'] ?? null;
		$this->filename  = $info['filename'] ?? null;

		$image_info = getimagesize($this->source);

		$this->width  = $image_info[0] ?? '';
		$this->height = $image_info[1] ?? '';
		$this->mime   = $image_info['mime'] ?? null;
	}

	private function setDimensions()
	{
		if ( ! $this->isInternal())
		{
			$this->attributes->resize = false;

			return;
		}

		if (empty($this->attributes->resize))
		{
			return;
		}

		// Width and height are already set
		if ( ! empty($this->attributes->width) && ! empty($this->attributes->height))
		{
			return;
		}

		// Width and height are both not set, so can't calculate resize
		if ( ! isset($this->attributes->height) && ! isset($this->attributes->width))
		{
			$this->attributes->resize = false;

			return;
		}

		$set_width  = $this->attributes->width ?? 0;
		$set_height = $this->attributes->height ?? 0;

		$orig_width  = $this->width;
		$orig_height = $this->height;

		if ($set_width)
		{
			$this->attributes->height = round($set_width / $orig_width * $orig_height);
		}

		if ($set_height)
		{
			$this->attributes->width = round($set_height / $orig_height * $orig_width);
		}
	}

	public function isResized()
	{
		if ( ! is_null($this->is_resized))
		{
			return $this->is_resized;
		}

		$this->is_resized = false;

		$parent_folder_name = File::getBaseName($this->dirname);
		$parent_folder      = File::getDirName($this->dirname);
		$resize_folder      = $this->attributes->{'resize-folder'} ?? 'resized';

		// Image is not inside the resize folder
		if ($parent_folder_name != $resize_folder)
		{
			return false;
		}

		$file_name = $this->basename;

		// Check if image with same name exists in parent folder
		if (file_exists(JPATH_SITE . '/' . $parent_folder . '/' . utf8_decode($file_name)))
		{
			$this->is_resized      = true;
			$this->main_image_path = $parent_folder . '/' . $file_name;

			return true;
		}

		// Remove any dimensions from the file
		// image_300x200.jpg => image.jpg
		$file_name = RegEx::replace(
			'_[0-9]+x[0-9]*(\.[^.]+)$',
			'\1',
			$this->basename
		);

		// Check again if image with same name (but without dimensions) exists in parent folder
		if (file_exists(JPATH_SITE . '/' . $parent_folder . '/' . utf8_decode($file_name)))
		{
			$this->is_resized      = true;
			$this->main_image_path = $parent_folder . '/' . $file_name;

			return true;
		}

		return false;
	}

	/**
	 * Method to create a resized version of the current image and save them to disk
	 *
	 * @param string $width
	 * @param string $height
	 * @param string $resizeFolder
	 *
	 * @return  \Intervention\Image\Image|null
	 */
	public function getResized($width, $height, $resizeFolder = null, $quality = 90)
	{
		// Make sure the resource handle is valid.
		if ( ! $this->isInternal())
		{
			return null;
		}

		// No thumbFolder set -> we will create a thumbs folder in the current image folder
		if (is_null($resizeFolder))
		{
			$resizeFolder = $this->getFolder() . '/resized';
		}

		// Check destination
		if ( ! is_dir($resizeFolder)
			&& ( ! is_dir(dirname($resizeFolder)) || ! @mkdir($resizeFolder)))
		{
			return null;
		}

		if ($width == $this->getWidth() && $height == $this->getHeight())
		{
			return null;
		}

		$imgAge = filemtime($this->getPath());
		$maxAge = time() - (60 * JFactory::getApplication()->get('cachetime'));

		$resizedFile = $resizeFolder . '/' . $this->getFileName() . '_' . $width . 'x' . $height . '.' . $this->getExtension();

		if (file_exists($resizedFile)
			&& filemtime($resizedFile) > $maxAge // not older that cache time setting
			&& filemtime($resizedFile) > $imgAge // not older that original image
		)
		{
			return ImageManager::make($resizedFile);
		}

		[$resizeWidth, $resizeHeight] = $this->getResizeDimensions($width, $height);

		try
		{
			$resized = ImageManager::make($this->getPath())
				->resize($resizeWidth, $resizeHeight, function ($constraint) {
					$constraint->aspectRatio();
				})
				->crop($width, $height)
				->save($resizedFile, $this->parseQuality($quality));
		}
		catch (NotReadableException $exception)
		{
			$resized = null;
		}

		if ( ! $resized)
		{
			return null;
		}

		return $resized;
	}

	public function getFolder()
	{
		return $this->dirname;
	}

	public function getWidth()
	{
		return $this->width;
	}

	public function getHeight()
	{
		return $this->height;
	}

	public function getPath()
	{
		return $this->basePath() ?: $this->source;
	}

	public function getFileName()
	{
		return $this->filename;
	}

	public function getExtension()
	{
		return $this->extension;
	}

	private function getResizeDimensions($width, $height)
	{
		if (($this->getWidth() / $width) > ($this->getHeight() / $height))
		{
			return [null, $height];
		}

		return [$width, null];
	}

	private function parseQuality($quality = 90)
	{
		if (is_int($quality))
		{
			return $quality;
		}

		switch ($quality)
		{
			case 'low':
				return 50;

			case 'medium':
				return 70;

			case 'high':
			default:
				return 90;
		}
	}

	/**
	 * Get fully qualified path
	 *
	 * @return string
	 */
	private function basePath()
	{
		if ($this->dirname && $this->basename)
		{
			return $this->dirname . '/' . $this->basename;
		}

		return null;
	}

	public function getMainImage()
	{
		if ( ! is_null($this->main_image))
		{
			return $this->main_image;
		}

		$this->main_image = false;

		if ( ! $this->isResized())
		{
			return false;
		}

		if ( ! $this->main_image_path)
		{
			return false;
		}

		$this->main_image = new Image($this->main_image_path, $this->attributes);

		return $this->main_image;
	}

	/**
	 * Method to create a resized version of the current image and save them to disk
	 *
	 * @param string $width
	 * @param string $height
	 * @param string $resizeFolder
	 *
	 * @return  string|false
	 */
	public function getResizedUrl($width, $height, $resizeFolder = null, $quality = 90)
	{
		$image = $this->getResized($width, $height, $resizeFolder, $quality);

		if ( ! $image)
		{
			return $this->getPath();
		}

		return $image->basePath();
	}

	public function renderTag()
	{
		$image_tag = '<img src="' . $this->getPath() . '" '
			. RL_HtmlTag::flattenAttributes($this->getTagAttributes()) . ' />';

		if ( ! isset($this->attributes->{'outer-class'}))
		{
			return $image_tag;
		}

		return '<div class="' . htmlspecialchars($this->attributes->{'outer-class'}) . '">'
			. $image_tag
			. '</div>';
	}

	private function getTagAttributes()
	{
		$remove = [
			'src',
			'resize',
			'resize-folder',
			'quality',
			'caption',
			'outer-class',
		];

		$attributes = array_diff_key((array) $this->attributes, array_flip($remove));

		$ordered_keys = [
			'alt',
			'title',
			'width',
			'height',
			'class',
		];

		krsort($ordered_keys);

		foreach ($ordered_keys as $key)
		{
			if ( ! key_exists($key, $attributes))
			{
				continue;
			}

			$value = $attributes[$key];
			unset($attributes[$key]);

			$attributes = array_merge([$key => $value], $attributes);
		}

		return $attributes;
	}

	/**
	 * Get file size
	 *
	 * @return mixed
	 */
	private function filesize()
	{
		$path = $this->basePath();

		if (file_exists($path) && is_file($path))
		{
			return filesize($path);
		}

		return false;
	}
}

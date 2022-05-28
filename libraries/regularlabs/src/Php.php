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

use Joomla\CMS\Application\CMSApplication as JCMSApplication;
use Joomla\CMS\Document\Document as JDocument;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Filesystem\File as JFile;
use Joomla\CMS\Version as JVersion;

class Php
{
	static $rl_variables;

	public static function execute($rl_string, $rl_article = null, $rl_module = null, $has_own_return = false)
	{
		self::prepareString($rl_string);

		$function_name = self::getFunctionName($rl_string);
		if ( ! $function_name)
		{
			// Something went wrong!
			return true;
		}

		if ( ! $rl_article && strpos($rl_string, '$article') !== false)
		{
			if (JFactory::getApplication()->input->get('option', '') == 'com_content' && JFactory::getApplication()->input->get('view', '') == 'article')
			{
				$rl_article = Article::get(JFactory::getApplication()->input->getInt('id'));
			}
		}

		$rl_pre_variables = array_keys(get_defined_vars());

		ob_start();
		$rl_post_variables = $function_name(self::$rl_variables, $rl_article, $rl_module);
		$rl_output         = ob_get_contents();
		ob_end_clean();

		if ($has_own_return)
		{
			return $rl_post_variables;
		}

		if ( ! is_array($rl_post_variables))
		{
			return $rl_output;
		}

		$rl_diff_variables = array_diff(array_keys($rl_post_variables), $rl_pre_variables);

		foreach ($rl_diff_variables as $rl_diff_key)
		{
			if (in_array($rl_diff_key, ['Itemid', 'mainframe', 'app', 'document', 'doc', 'database', 'db', 'user'], true)
				|| substr($rl_diff_key, 0, 4) == 'rl_'
			)
			{
				continue;
			}

			self::$rl_variables[$rl_diff_key] = $rl_post_variables[$rl_diff_key];
		}

		return $rl_output;
	}

	private static function prepareString(&$string)
	{
		$string = trim($string);
		$string = str_replace('?><?php', '', $string . '<?php ;');

		if (substr($string, 0, 5) !== '<?php')
		{
			$string = '?>' . $string;

			return;
		}

		$string = substr($string, 5);
	}

	private static function getFunctionName($string)
	{
		$function_name = 'regularlabs_php_' . md5($string);

		if (function_exists($function_name))
		{
			return $function_name;
		}

		$contents = self::generateFileContents($function_name, $string);
		self::createFunctionInMemory($contents);

		if ( ! function_exists($function_name))
		{
			// Something went wrong!
			return false;
		}

		return $function_name;
	}

	private static function generateFileContents($function_name = 'rl_function', $string = '')
	{
		$init_variables = self::getVarInits();

		$init_variables[] =
			'if (is_array($rl_variables)) {'
			. 'foreach ($rl_variables as $rl_key => $rl_value) {'
			. '${$rl_key} = $rl_value;'
			. '}'
			. '}';

		$contents = [
			'<?php',
			'defined(\'_JEXEC\') or die;',
			'function ' . $function_name . '($rl_variables, $article, $module){',
			implode("\n", $init_variables),
			$string . ';',
			'return get_defined_vars();',
			';}',
		];

		$contents = implode("\n", $contents);

		// Remove Zero Width spaces / (non-)joiners
		$contents = str_replace(
			[
				"\xE2\x80\x8B",
				"\xE2\x80\x8C",
				"\xE2\x80\x8D",
			],
			'',
			$contents
		);

		return $contents;
	}

	private static function createFunctionInMemory($string)
	{
		$file_name = getmypid() . '_' . md5($string);

		$tmp_path  = JFactory::getApplication()->get('tmp_path', JPATH_ROOT . '/tmp');
		$temp_file = $tmp_path . '/regularlabs' . '/' . $file_name;

		// Write file
		if ( ! file_exists($temp_file) || is_writable($temp_file))
		{
			JFile::write($temp_file, $string);
		}

		// Include file
		include_once $temp_file;

		// Delete file
		if ( ! JFactory::getApplication()->get('debug'))
		{
			@chmod($temp_file, 0777);
			@unlink($temp_file);
		}
	}

	private static function getVarInits()
	{
		return [
			'$app = $mainframe = RegularLabs\Library\Php::getApplication();',
			'$document = $doc = RegularLabs\Library\Php::getDocument();',
			'$database = $db = JFactory::getDbo();',
			'$user = $app->getIdentity() ?: JFactory::getUser();',
			'$Itemid = $app->input->getInt(\'Itemid\');',
		];
	}

	public static function getApplication()
	{
		if (JFactory::getApplication()->input->get('option', '') == 'com_finder')
		{
			return JFactory::getApplication();
		}

		return JCMSApplication::getInstance('site');
	}

	public static function getDocument()
	{
		if (JFactory::getApplication()->input->get('option', '') != 'com_finder')
		{
			return Document::get();
		}

		$lang    = JFactory::getLanguage();
		$version = new JVersion;

		$attributes = [
			'charset'      => 'utf-8',
			'lineend'      => 'unix',
			'tab'          => "\t",
			'language'     => $lang->getTag(),
			'direction'    => $lang->isRtl() ? 'rtl' : 'ltr',
			'mediaversion' => $version->getMediaVersion(),
		];

		return JDocument::getInstance('html', $attributes);
	}
}

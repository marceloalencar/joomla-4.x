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

namespace RegularLabs\Library\Form\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;

class DependencyFieldHelper
{
	public static function setMessage($file, $name)
	{
		if ( ! $file)
		{
			return;
		}

		$file = JPATH_SITE . '/' . trim($file, '/');

		if (file_exists($file))
		{
			return;
		}

		$msg          = JText::sprintf('RL_THIS_EXTENSION_NEEDS_THE_MAIN_EXTENSION_TO_FUNCTION', JText::_($name));
		$messageQueue = JFactory::getApplication()->getMessageQueue();

		foreach ($messageQueue as $queue_message)
		{
			if ($queue_message['type'] == 'error' && $queue_message['message'] == $msg)
			{
				return;
			}
		}

		JFactory::getApplication()->enqueueMessage($msg, 'error');
	}
}

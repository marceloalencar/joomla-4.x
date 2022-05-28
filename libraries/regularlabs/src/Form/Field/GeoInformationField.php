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

use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\Form\FormField as RL_FormField;
use RegularLabs\Library\GeoIp\GeoIp as RL_GeoIP;

class GeoInformationField extends RL_FormField
{
	protected function getInput()
	{
		return '';
	}

	protected function getLabel()
	{
		if ( ! class_exists('RegularLabs\\Library\\GeoIp\\GeoIp'))
		{
			return '';
		}

		$ip = '';

		$geo = new RL_GeoIP($ip);

		if (empty($geo))
		{
			return false;
		}

		$geo = $geo->get();

		if (empty($geo))
		{
			return false;
		}

		$details = [
			JText::_('CON_CONTINENT') . ': <strong>' . $geo->continent . '</strong>',
			JText::_('CON_COUNTRY') . ':  <strong>' . $geo->country . '</strong>',
			JText::_('CON_REGION') . ':  <strong>' . implode(', ', $geo->regions) . '</strong>',
			JText::_('CON_POSTAL_CODE') . ':  <strong>' . $geo->postalCode . '</strong>',
		];

		$html = '<div class="rl-alert alert alert-info rl-alert-light">'
			. JText::_('CON_GEO_CURRENT_DETAILS')
			. '<ul><li>' . implode('</li><li>', $details) . '</li></ul>'
			. '</div>';

		return '</div><div>' . $html;
	}

}

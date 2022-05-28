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

use JLoader;
use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin as JFieldsPlugin;

class FieldsPlugin extends JFieldsPlugin
{
	public function __construct(&$subject, $config = [])
	{
		parent::__construct($subject, $config);

		$path = JPATH_PLUGINS . '/fields/' . $this->_name . '/src/Form/Field';

		if ( ! file_exists($path))
		{
			return;
		}

		$name = str_replace('PlgFields', '', get_class($this));

		JLoader::registerAlias('JFormField' . $name, '\\RegularLabs\\Plugin\\Fields\\' . $name . '\\Form\\Field\\' . $name . 'Field');
	}
}

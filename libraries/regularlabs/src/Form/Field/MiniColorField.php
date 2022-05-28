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

use RegularLabs\Library\ArrayHelper as RL_Array;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Form\FormField as RL_FormField;

class MiniColorField extends RL_FormField
{
	public function getInput()
	{
		$class     = trim('rl-mini-colors ' . $this->get('class'));
		$table     = $this->get('table');
		$item_id   = $this->get('item_id');
		$id_column = $this->get('id_column') ?: 'id';
		$disabled  = $this->get('disabled') ? ' disabled="disabled"' : '';
		$colors    = $this->get('colors', 'none,#c0c6cf,#000000,#dc2a28,#fb6b14,#ffa813,#eac90a,#18a047,#0f9aa4,#115dda,#761bda,#d319a4');

		$colors = str_replace('none', 'transparent', $colors);

		RL_Document::scriptOptions(['swatches' => RL_Array::toArray($colors)], 'minicolors');

		RL_Document::script('regularlabs.script');
		RL_Document::script('regularlabs.mini-colors');
		RL_Document::style('regularlabs.mini-colors');

		return '<div class="rl-mini-colors">'
			. '<input type="text" name="' . $this->name . '" id="' . $this->id . '"'
			. ' class="' . $class . '" value="' . $this->value . '"' . $disabled
			. ' data-rl-mini-colors data-table="' . $table . '" data-item_id="' . $item_id . '" data-id_column="' . $id_column . '"'
			. '>'
			. '</div>';
	}
}

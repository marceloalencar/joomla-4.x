<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_menu
 * @copyright	Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Note. It is important to remove spaces between elements.
$title = $item->anchor_title ? 'title="'.$item->anchor_title.'" ' : '';
if ($item->menu_image) {
		$item->getParams()->get('menu_text', 1 ) ?
		$linktype = '<img src="'.$item->menu_image.'" alt="'.$item->title.'" /><span class="image-title">'.$item->title.'</span> ' :
		$linktype = '<img src="'.$item->menu_image.'" alt="'.$item->title.'" />';
}
else { $linktype = $item->title;
}

//
if ($item->note != '') {
	$linktype = '<span class="icon-li icon-stack"><i class="icon-circle icon-stack-base"><span class="hide">&nbsp;</span></i><i class="'.$item->note.' icon-light"><span class="hide">&nbsp;</span></i></span>' . "\n" . $linktype;
}
//

?><span class="separator"><?php echo $title; ?><?php echo $linktype; ?></span>
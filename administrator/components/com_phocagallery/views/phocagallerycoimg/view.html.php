<?php
/*
 * @package		Joomla.Framework
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @component Phoca Component
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License version 2 or later;
 */
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
jimport('joomla.application.component.view');


class PhocaGalleryCpViewPhocaGalleryCoImg extends HtmlView
{
	protected $item;
	protected $form;
	protected $state;
	protected $t;
	protected $r;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{


		$this->state	= $this->get('State');
		$this->item		= $this->get('Item');
		$this->form		= $this->get('Form');

		$this->t	= PhocaGalleryUtils::setVars('coimg');
		$this->r	= new PhocaGalleryRenderAdminview();

		$itemInfo	= $this->getInfoValues();

		if (isset($itemInfo->image_title)) {
			$this->form->setValue('imagetitle', '', $itemInfo->image_title);
		}
		if (isset($itemInfo->category_title)) {
			$this->form->setValue('cattitle', '', $itemInfo->category_title);
		}
		if (isset($itemInfo->username) && isset($itemInfo->usernameno)) {
			$this->form->setValue('usertitle', '', $itemInfo->usernameno . ' ('.$itemInfo->username.')');
		}


		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	protected function getInfoValues() {

		if (isset($this->item->id)) {
			$db		= Factory::getDbo();
			$query	= $db->getQuery(true);

			// Select the required fields from the table.
			$query->select('a.id');

			$query->from('`#__phocagallery_img_comments` AS a');

			$query->select('i.title AS image_title, i.id AS image_id');
			$query->join('LEFT', '#__phocagallery AS i ON i.id = a.imgid');

			$query->select('c.title AS category_title, c.id AS category_id');
			$query->join('LEFT', '#__phocagallery_categories AS c ON c.id = i.catid');

			$query->select('ua.username AS username, ua.name AS usernameno');
			$query->join('LEFT', '#__users AS ua ON ua.id=a.userid');

			$query->where('a.id = ' . (int) $this->item->id);

			$db->setQuery($query);
			$itemInfo = $db->loadObject();

			/*if ($db->getErrorNum()) {
				throw new Exception($db->getErrorMsg(), 500);
			}*/

			return $itemInfo;
		}
	}

	protected function addToolbar() {

		require_once JPATH_COMPONENT.'/helpers/phocagallerycoimgs.php';
		Factory::getApplication()->input->set('hidemainmenu', true);
		$bar 		= Toolbar::getInstance('toolbar');
		$user		= Factory::getUser();
		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo		= PhocaGalleryCoImgsHelper::getActions($this->state->get('filter.category_id'), $this->item->id);
		$paramsC 	= ComponentHelper::getParams('com_phocagallery');

		$text = $isNew ? Text::_( 'COM_PHOCAGALLERY_NEW' ) : Text::_('COM_PHOCAGALLERY_EDIT');
		ToolbarHelper::title(   Text::_( 'COM_PHOCAGALLERY_IMAGE_COMMENT' ).': <small><small>[ ' . $text.' ]</small></small>' , 'comment');

		// If not checked out, can save the item.
		if (!$checkedOut && $canDo->get('core.edit')){
			ToolbarHelper::apply('phocagallerycoimg.apply', 'JToolbar_APPLY');
			ToolbarHelper::save('phocagallerycoimg.save', 'JToolbar_SAVE');
		}

		ToolbarHelper::cancel('phocagallerycoimg.cancel', 'JToolbar_CLOSE');
		ToolbarHelper::divider();
		ToolbarHelper::help( 'screen.phocagallery', true );
	}

}

?>

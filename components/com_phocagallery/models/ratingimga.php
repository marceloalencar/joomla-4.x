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
defined('_JEXEC') or die();
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
jimport('joomla.application.component.model');


class PhocaGalleryModelRatingImgA extends BaseDatabaseModel
{

	function rate($data) {
		$row = $this->getTable('phocagalleryimgvotes');

		if (!$row->bind($data)) {
			$this->setError($row->getError());
			return false;
		}

		$row->date 		= gmdate('Y-m-d H:i:s');

		$row->published = 1;

		if (!$row->id) {
			$where = 'imgid = ' . (int) $row->imgid ;
			$row->ordering = $row->getNextOrder( $where );
		}

		if (!$row->check()) {
			$this->setError($row->getError());
			return false;
		}

		if (!$row->store()) {
			$this->setError($row->getError());
			return false;
		}

		// Update the Vote Statistics
		if (!PhocaGalleryRateImage::updateVoteStatistics( $data['imgid'])) {
			return false;
		}

		return true;
	}
}
?>

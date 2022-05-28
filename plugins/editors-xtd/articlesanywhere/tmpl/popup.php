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

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;

?>

<div class="container-fluid container-main">

	<div class="row">
		<div class="col-lg-6 border-end">
			<form action="index.php" id="adminForm" name="articlesAnywhereForm" method="post" class="rl-form labels-sm">
				<input type="hidden" name="type" id="type" value="url">
				<?php echo JHtml::_('uitab.startTabSet', 'main', ['active' => 'filters']); ?>

				<?php echo JHtml::_('uitab.addTab', 'main', 'filters', JText::_('RL_FILTERS')); ?>
				<?php echo $this->form->renderFieldset('filters'); ?>
				<?php echo JHtml::_('uitab.endTab'); ?>

				<?php echo JHtml::_('uitab.addTab', 'main', 'data_tags', JText::_('AA_DATA_TAGS')); ?>
				<?php echo $this->form->renderFieldset('data_tags'); ?>
				<?php echo JHtml::_('uitab.endTab'); ?>

				<?php echo JHtml::_('uitab.addTab', 'main', 'extra_attributes', JText::_('RL_OTHER_SETTINGS')); ?>
				<?php echo $this->form->renderFieldset('extra_attributes'); ?>
				<?php echo JHtml::_('uitab.endTab'); ?>

				<?php echo JHtml::_('uitab.endTabSet'); ?>
			</form>
		</div>
		<div class="col-lg-6">
			<div class="position-sticky" style="top:1.25rem;">
				<button type="button" class="btn btn-success mb-4 w-100 hidden d-lg-block"
				        onclick="RegularLabs.ArticlesAnywhere.Popup.insertText();window.parent.Joomla.Modal.getCurrent().close();">
					<span class="icon-file-import" aria-hidden="true"></span>
					<?php echo JText::_('RL_INSERT'); ?>
				</button>
				<fieldset class="options-form mt-2 position-relative">
					<legend class="mb-1"><?php echo JText::_('JGLOBAL_PREVIEW'); ?></legend>
					<span id="preview_spinner" class="rl-spinner hidden"></span>
					<span id="preview_message" class="text-muted fst-italic">
						<span class="icon-info-circle text-info" aria-hidden="true"></span>
						<?php echo JText::_('AA_MESSAGE_NO_PREVIEW'); ?>
					</span>
					<div id="preview_code" class="hidden"></div>
				</fieldset>
				<div class="alert alert-info">
					<?php echo JText::sprintf(
						'AA_POPUP_MORE_INFO',
						'<a href="https://docs4.regularlabs.com/articlesanywhere" target="_blank">',
						'</a>'
					); ?>
				</div>
			</div>
		</div>
	</div>
</div>

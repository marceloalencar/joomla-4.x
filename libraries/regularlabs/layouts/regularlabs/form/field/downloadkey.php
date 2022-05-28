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

use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Layout\FileLayout as JFileLayout;

defined('_JEXEC') or die;

/**
 * @var   array  $displayData
 * @var   int    $id
 * @var   string $extension
 * @var   int    $cloak_length
 * @var   bool   $use_modal
 * @var   bool   $hidden
 * @var   string $callback
 */

extract($displayData);

$extension    = $extension ?? 'all';
$cloak_length = $cloak_length ?? 4;
$use_modal    = $use_modal ?? false;
$hidden       = $hidden ?? false;
$callback     = htmlspecialchars($callback ?? '', ENT_QUOTES, 'UTF-8');
?>
<div id="downloadKeyWrapper_<?php echo $id; ?>" class="rl-download-key" data-callback="<?php echo $callback; ?>">
	<div class="rl-download-key-wrapper<?php echo $hidden ? ' hidden' : ''; ?>">
		<span class="rl-spinner"></span>
		<div class="input-group">
			<input type="text" id="<?php echo $id; ?>" data-key-extension="<?php echo $extension; ?>" data-key-cloak-length="<?php echo $cloak_length; ?>"
			       class="rl-download-key-field form-control inactive rl-code-field hidden">
			<button type="button" class="btn btn-primary button-edit hidden">
				<span class="icon-edit" aria-hidden="true"></span><span class="visually-hidden"><?php echo JText::_('JEDIT'); ?></span>
			</button>
			<button type="button" class="btn btn-success button-apply hidden">
				<span class="icon-checkmark" aria-hidden="true"></span><span class="visually-hidden"><?php echo JText::_('JAPPLY'); ?></span>
			</button>
			<button type="button" class="btn btn-danger button-cancel hidden">
				<span class="icon-times" aria-hidden="true"></span><span class="visually-hidden"><?php echo JText::_('JCANCEL'); ?></span>
			</button>
		</div>

		<?php
		echo (new JFileLayout(
			'regularlabs.form.field.downloadkey_errors',
			JPATH_SITE . '/libraries/regularlabs/layouts'
		))->render([
			'id'        => $id,
			'extension' => $extension,
		]);
		?>
	</div>

	<?php
	if ($use_modal)
	{
		echo (new JFileLayout(
			'regularlabs.form.field.downloadkey_modal',
			JPATH_SITE . '/libraries/regularlabs/layouts'
		))->render([
			'id'        => $id,
			'extension' => $extension,
		]);
	}
	?>
</div>

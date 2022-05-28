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
 */

extract($displayData);

$extension = $extension ?: 'all';

?>

<div class="content p-3">
	<?php
	echo (new JFileLayout(
		'regularlabs.form.field.downloadkey_errors',
		JPATH_SITE . '/libraries/regularlabs/layouts'
	))->render([
		'id'        => $id,
		'extension' => $extension,
	]);
	?>
	<p>
		<?php echo html_entity_decode(JText::_('RL_DOWNLOAD_KEY_ENTER')); ?>:
	</p>
	<div class="input-group">
		<input type="text" id="<?php echo $id; ?>_modal" placeholder="ABC123..." class="rl-download-key-field form-control rl-code-field">
	</div>
</div>

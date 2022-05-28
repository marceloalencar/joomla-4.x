<?php
/**
 * @package     
 * @subpackage  
 * @copyright   
 * @license     
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

$module  = $displayData['module'];
$params  = $displayData['params'];
$attribs = $displayData['attribs'];

$headerLevel = isset($attribs['headerLevel']) ? (int) $attribs['headerLevel'] : 2;
$content = $module->content;
if(! empty($content)):
?>
<div class="row-fluid">
	<section<?php if ($params->get('tag_id', '')  != ''): ?> id="<?php echo $params->get('tag_id'); ?>"<?php endif; ?><?php if ($params->get('class_sfx', '')  != ''): ?> class="<?php echo trim($params->get('class_sfx', '')); ?>"<?php endif; ?>>
		<?php if ($module->showtitle): ?>
		 <h<?php echo $headerLevel; ?> class="span2"><span><?php echo $module->title; ?></span></h<?php echo $headerLevel; ?>>
		<?php endif; ?>
		<?php echo $module->content; ?>
	</section>
</div>
<?php
endif;

?>
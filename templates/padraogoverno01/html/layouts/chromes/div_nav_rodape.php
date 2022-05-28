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
if(! empty($module->content) ):
?>

	<div class="<?php echo $params->get('class_sfx', ''); ?>">
		<nav class="row <?php echo $params->get('moduleclass_sfx'); ?> nav">
			<?php if ($module->showtitle): ?>
			<h<?php echo $headerLevel; ?>><?php echo $module->title; ?></h<?php echo $headerLevel; ?>>
			<?php endif; ?>
			<?php echo $module->content; ?>
		</nav>
	</div>

<?php
	endif;

?>
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

if(! empty($module->content) ):
?>
<div id="<?php echo $params->get('moduleclass_sfx'); ?>" class="row">
	<h2 class="hidden"><?php echo $module->title; ?></h2>
	<?php echo $module->content; ?>
</div>
<?php
endif;

?>
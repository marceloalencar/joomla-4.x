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

	if(strpos($params->get('moduleclass_sfx').' '.$params->get('class_sfx'), 'show-icon')===false)
		$mobile_classes = 'visible-phone visible-tablet';
	else
		$mobile_classes = '';
?>
<nav class="<?php echo $params->get('moduleclass_sfx'); ?> <?php echo $params->get('class_sfx', ''); ?>">
	<h<?php echo $headerLevel; ?> <?php if($params->get('moduleclass_sfx')=='menu-de-apoio'): ?>class="hide"<?php endif; ?>><?php echo $module->title; ?> <?php if($params->get('moduleclass_sfx')!='menu-de-apoio'): ?><i class="icon-chevron-down visible-phone visible-tablet pull-right"></i><?php endif; ?></h<?php echo $headerLevel; ?>>
	<?php echo $module->content; ?>
</nav>
<?php
endif;

?>
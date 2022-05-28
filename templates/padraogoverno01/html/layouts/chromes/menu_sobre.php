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

$content = $module->content;
if(! empty($content)):
?>
<nav<?php if ($params->get('class_sfx', '')  != ''): ?> class="<?php echo $params->get('class_sfx'); ?>"<?php endif; ?>>
<h2 class="hide"><?php echo $module->title; ?></h2>
<?php echo $module->content; ?>
</nav>
<?php
endif;

?>
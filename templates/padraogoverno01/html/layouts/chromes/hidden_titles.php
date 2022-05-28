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
	<h<?php echo $headerLevel; ?> class="hide"><?php echo $module->title; ?></h<?php echo $headerLevel; ?>>
	<?php echo $module->content; ?>
<?php
endif;

?>
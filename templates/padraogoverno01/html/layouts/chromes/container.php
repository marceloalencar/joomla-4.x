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
	if($module->module == 'mod_container') // || ($module->module == 'mod_chamadas' && $params->get('layout')=='padraogoverno01:listagem-box01'
	{
		echo $module->content;
	}
	else
	{
	$class = is_null($params->get('moduleclass_sfx')) ? '' : $params->get('moduleclass_sfx');

	$container_class = '';
	$container_class_pos = strpos($class, 'container-class-');
	if($container_class_pos !== false)
	{
		$container_class = substr($class, $container_class_pos);
		$container_class = str_replace(array('container-class-','--'), array('', ' '), $container_class);
		$class = str_replace( 'container-class-', '', $class);
	}

	$variacao = $params->get('variacao', 0);
	if( $variacao > 0 ){
		if ( $variacao < 10 ) {
			$variacao = '0'.$variacao;
		}
		$class = trim($class.' variacao-module-'.$variacao);
	}

	$title = ( $params->get('titulo_alternativo', '') != '' )? $params->get('titulo_alternativo') : $module->title;
	$layout = explode(':', $params->get('layout'));
	$module->showtitle = (@$layout[1]!='manchete-principal')? $module->showtitle : '';
	?>
	<div class="row-fluid module <?php echo $class; ?>">
		<?php if ($module->showtitle): ?>
			<?php $class_outstandingcheck = is_null($params->get('moduleclass_sfx')) ? '' : $params->get('moduleclass_sfx'); ?>
			<?php if(strpos($class_outstandingcheck, 'no-outstanding-title')===false): ?><div class="outstanding-header"><?php endif; ?>
			<h<?php echo $headerLevel; ?> <?php if(strpos($class_outstandingcheck, 'no-outstanding-title')===false): ?>class="outstanding-title"<?php endif; ?>><span><?php echo $title; ?></span></h<?php echo $headerLevel; ?>>
			<?php if(strpos($class_outstandingcheck, 'no-outstanding-title')===false): ?></div><?php endif; ?>
		<?php endif; ?>
		<?php if($container_class != ''): ?><div class="<?php echo $container_class; ?>"><?php endif; ?>
		<?php echo $module->content; ?>
		<?php if($container_class != ''): ?></div><?php endif; ?>
	</div>
	<?php
	}
	endif;

?>
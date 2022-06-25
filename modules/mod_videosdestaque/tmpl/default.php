<?php
/**
 * @package     Joomlagovbr
 * @subpackage  mod_agendadirigentes
 *
 * @copyright   Copyright (C) 2013 Comunidade Joomla Calango e Grupo de Trabalho de Ministérios
 * @license     GNU General Public License version 2
 */

defined('_JEXEC') or die;
?>
<div class="row-fluid chamadas-secundarias">
<?php
if (count($items) == 0)
{
	// Caso não existam vídeos...
}
else
{
	$span_unit = 12 / count($items);
	for ($i=0, $limit = count($items); $i < $limit; $i++):
		$class = 'module span' . $span_unit;
?>
	<div class="<?php echo $class ?>">
		<div class="video">
			<?php ModVideosDestaqueHelper::showPlayer( $items["list_videos$i"]->url, count($items) ); ?>
		</div>
		<h2><strong><?php echo $items["list_videos$i"]->name; ?></strong></h2>
		<p class="description"><?php echo $items["list_videos$i"]->description; ?></p>
	</div>
<?php
endfor;
}
?>
	
    <?php if( !empty($text_link_footer) && !empty($url_link_footer) ): ?>
    <div class="outstanding-footer">
        <a href="<?php echo $url_link_footer ?>" class="outstanding-link">
            <span class="text"><?php echo $text_link_footer; ?></span>
            <span class="icon-box">                                          
              <i class="icon-angle-right icon-light"><span class="hide">&nbsp;</span></i>
            </span>
        </a>
    </div>  
	<?php endif; ?>
</div>
<?php
/**
 * YoutubeGallery Joomla! Button
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/
 
 // no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class plgButtonYoutubeGalleryButton extends JPlugin
{
    public function onDisplay($name)
	{
        $css = ".icon-YoutubeGalleryButton {
                    background: transparent url('/plugins/editors-xtd/youtubegallerybutton/youtube-logo.png') no-repeat 0 4px !important;
                }";

        $doc = JFactory::getDocument();
        $doc->addStyleDeclaration($css);
        $button = new JObject();
        $button->set('modal', true);
        $button->set('text', JText::_('Youtube Gallery')); //PLG_YOUTUBEGALLERYBUTTON_BUTTON_YOUTUBEGALLERY - fix it
        $button->set('name', 'YoutubeGalleryButton');
        $button->set('id', 'YoutubeGalleryButton');
        
        $app = JFactory::getApplication();

		$link='/index.php?option=com_youtubegallery&amp;view=listandthemeselection&amp;tmpl=component&amp;e_name='.$name;
		
		if($app->isClient('site'))
            $button->set('link', $link);
        else
			$button->set('link', '/administrator'.$link);
            
        $button->options = "{handler: 'iframe', size: {x: 800, y: 500}, id:'ygb', name:'ygbn'}";
        
        return $button;
	}
}

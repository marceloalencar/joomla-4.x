<?php
/**
 * Youtube Gallery Joomla! Module
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/


defined('_JEXEC') or die('Restricted access');

$path=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_youtubegallery'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'youtubegallery'.DIRECTORY_SEPARATOR;
require_once($path.'loader.php');
YGLoadClasses();

use YouTubeGallery\Helper;

$listid=(int)$params->get( 'listid' );

//Get Theme
if(Helper::check_user_agent('mobile'))
{
	//Use Mobile Theme if set.
	$themeid=(int)$params->get( 'mobilethemeid' );
	if($themeid==0)
	    $themeid=(int)$params->get( 'themeid' );
}
else
	$themeid=(int)$params->get( 'themeid' );

$align='';

if($listid!=0 and $themeid!=0)
{
	$ygDB=new YouTubeGalleryDB;

	$videolist_and_theme_found=true;

	if(!$ygDB->getVideoListTableRow($listid))
	{
		JFactory::getApplication()->enqueueMessage(JText::_( 'MOD_YOUTUBEGALLERY_ERROR_VIDEOLIST_NOT_SET' ), 'error');
		$videolist_and_theme_found=false;
	}

	if(!$ygDB->getThemeTableRow($themeid))
	{
		JFactory::getApplication()->enqueueMessage(JText::_( 'MOD_YOUTUBEGALLERY_ERROR_THEME_NOT_SET' ), 'error');
		$videolist_and_theme_found=false;
	}
	
	$youtubegallerycode = '';

	if($videolist_and_theme_found)
	{

		$firstvideo='';
		$youtubegallerycode='';
		$total_number_of_rows=0;

		$ygDB->update_playlist();

		$videoid=JFactory::getApplication()->input->getCmd('videoid','');
		if(!isset($videoid) or $videoid=='')
		{
			$video=JFactory::getApplication()->input->getVar('video','');
			$video=preg_replace('/[^a-zA-Z0-9-_]+/', '', $video);

			if($video!='')
				$videoid=YouTubeGalleryDB::getVideoIDbyAlias($video);
		}

		if($ygDB->theme_row->es_playvideo==1 and $videoid!='')
			$ygDB->theme_row->es_autoplay=1;

		$videoid_new=$videoid;
		$jinput=JFactory::getApplication()->input;
		if($jinput->getInt('yg_api')==1)
        {
			$videolist=$ygDB->getVideoList_FromCache_From_Table($videoid_new,$total_number_of_rows,false);
            $result=json_encode($videolist);

            if (ob_get_contents())
				ob_end_clean();

            header('Content-Disposition: attachment; filename="youtubegallery_api.json"');
            header('Content-Type: application/json; charset=utf-8');
            header("Pragma: no-cache");
            header("Expires: 0");

            echo $result;
            die;

            return '';
		}
        else
		{
			$videolist=$ygDB->getVideoList_FromCache_From_Table($videoid_new,$total_number_of_rows);
		}
		

		if($videoid=='')
		{
			if($ygDB->theme_row->es_playvideo==1 and $videoid_new!='')
				$videoid=$videoid_new;
		}

		$custom_itemid=(int)$params->get( 'customitemid' );

		$renderer= new YouTubeGalleryRenderer;

		$gallerymodule=$renderer->render(
			$videolist,
			$ygDB->videolist_row,
			$ygDB->theme_row,
			$total_number_of_rows,
			$videoid,
			$custom_itemid
		);

		if($params->get( 'allowcontentplugins' ))
		{
			$o = new stdClass();
			$o->text=$gallerymodule;

			$dispatcher	= JDispatcher::getInstance();

			JPluginHelper::importPlugin('content');

			$r = $dispatcher->trigger('onContentPrepare', array ('com_content.article', &$o, &$params_, 0));

			$gallerymodule=$o->text;
		}

		$align=$params->get( 'galleryalign' );

		switch($align)
		{
		   	case 'left' :
		   		$youtubegallerycode.= '<div style="float:left;position:relative;">'.$gallerymodule.'</div>';
			break;

			case 'center' :
		   		$youtubegallerycode.= '<div style="width:'.$ygDB->theme_row->width.'px;margin-left:auto;margin-right:auto;position:relative;">'.$gallerymodule.'</div>';
			break;

		   	case 'right' :
		  		$youtubegallerycode.= '<div style="float:right;position:relative;">'.$gallerymodule.'</div>';
			break;

		   	default :
		   		$youtubegallerycode.= $gallerymodule;
			break;

		}//switch($align)
	}
	else
	{
		//JFactory::getApplication()->enqueueMessage('Youtube Gallery: Video List and Theme not found.', 'error');
		//echo '<p style="background-color:red;color:white;">Youtube Gallery: Video List and Theme not found.</p>';
	}

	echo $youtubegallerycode;

}
else
	echo '<p>Video list or Theme not selected</p>';

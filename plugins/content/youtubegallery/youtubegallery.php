<?php
/**
 * YoutubeGallery Joomla! Plugin
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

defined('_JEXEC') or die('Restricted access');

/*

	function plgContentYouTubeGallery(&$row, &$params, $page=0)
	{
		if (is_object($row)) {
			if(strpos($row->text,'youtubegallery')!==false)
			{
				plgContentYoutubeGallery::plgYoutubeGallery($row->text, false);
				plgContentYoutubeGallery::plgYoutubeGallery($row->text, true);
			}
		}
		else
		{
			if(strpos($row,'youtubegallery')!==false)
			{
				plgContentYoutubeGallery::plgYoutubeGallery($row, false);
				plgContentYoutubeGallery::plgYoutubeGallery($row, true);
			}
		}
	
	
	}
*/

use YouTubeGallery\Helper;

//jimport('joomla.plugin.plugin');
class plgContentYoutubeGallery extends JPlugin
{

	public function onContentPrepare($context, &$article, &$params, $limitstart=0) {

		$count=0;
		$count+=plgContentYoutubeGallery::plgYoutubeGallery($article->text,true);
		$count+=plgContentYoutubeGallery::plgYoutubeGallery($article->text,false);

	}

	public static function strip_html_tags_textarea( $text )
	{
	    $text = preg_replace(
        array(
          // Remove invisible content
            '@<textarea[^>]*?>.*?</textarea>@siu',
        ),
        array(
            ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',"$0", "$0", "$0", "$0", "$0", "$0","$0", "$0",), $text );

		return $text ;
	}


	public static function plgYoutubeGallery(&$text_original, $byId)
	{
		$path=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_youtubegallery'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'youtubegallery'.DIRECTORY_SEPARATOR;
		require_once($path.'loader.php');
		YGLoadClasses();
		
		$text=plgContentYoutubeGallery::strip_html_tags_textarea($text_original);

		$options=array();
		if($byId)
			$fList=plgContentYoutubeGallery::getListToReplace('youtubegalleryid',$options,$text);
		else
			$fList=plgContentYoutubeGallery::getListToReplace('youtubegallery',$options,$text);


		if(count($fList)==0)
			return 0;

		for($i=0; $i<count($fList);$i++)
		{
			$replaceWith=plgContentYoutubeGallery::getYoutubeGallery($options[$i],$i,$byId);
			$text_original=str_replace($fList[$i],$replaceWith,$text_original);
		}

		return count($fList);
	}

	public static function getYoutubeGallery($galleryparams,$count,$byId)
	{
		$result='';

		$opt=explode(',',$galleryparams);
		if(count($opt)<2)
		{
			JFactory::getApplication()->enqueueMessage(JText::_( 'PLG_CONTENT_YOUTUBEGALLERY_ERROR_THEME_NOT_SET' ), 'error');
			return '';
		}

		$db = JFactory::getDBO();

		if($byId)
		{
			$listid=(int)$opt[0];
			if(isset($opt[3]) and (int)$opt[3]!=0)
			{
				$isMobile=Helper::check_user_agent('mobile');
				if($isMobile)
					$themeid=(int)$opt[3];
				else
					$themeid=(int)$opt[1];
			}
			else
				$themeid=(int)$opt[1];

			$query_list = 'SELECT * FROM #__customtables_table_youtubegalleryvideolists WHERE id='.$listid.' LIMIT 1';
			$query_theme = 'SELECT * FROM #__customtables_table_youtubegallerythemes WHERE id='.$themeid.' LIMIT 1';
		}
		else
		{
			$listname=trim($opt[0]);
			if(isset($opt[3]) and trim($opt[3])!='')
			{
				$isMobile=Helper::check_user_agent('mobile');
				if($isMobile)
					$themename=trim($opt[3]);
				else
					$themename=trim($opt[1]);
			}
			else
				$themename=trim($opt[1]);

			$query_list = 'SELECT * FROM #__customtables_table_youtubegalleryvideolists WHERE es_listname="'.$listname.'" LIMIT 1';
			$query_theme = 'SELECT * FROM #__customtables_table_youtubegallerythemes WHERE es_themename="'.$themename.'" LIMIT 1';
		}

		//Video List data
		$db->setQuery($query_list);

		$videolist_rows = $db->loadObjectList();
		if(count($videolist_rows)==0)
		{
			JFactory::getApplication()->enqueueMessage(JText::_( 'PLG_CONTENT_YOUTUBEGALLERY_ERROR_VIDEOLIST_NOT_SET' ), 'error');
			return '';
		}
		$videolist_row=$videolist_rows[0];

		//Theme data
		$db->setQuery($query_theme);

		$theme_rows = $db->loadObjectList();
		if(count($theme_rows)==0)
		{
			JFactory::getApplication()->enqueueMessage(JText::_( 'PLG_CONTENT_YOUTUBEGALLERY_ERROR_THEME_NOT_SET' ), 'error');
			return '';
		}
		$theme_row=$theme_rows[0];


		$custom_itemid=0;
		if(isset($opt[2]))
			$custom_itemid=(int)$opt[2];

		$ygDB=new YouTubeGalleryDB;
		$ygDB->videolist_row = $videolist_row;
		
		$ygDB->theme_row = $theme_row;

		$total_number_of_rows=0;

		$ygDB->update_playlist();

		$videoid=JFactory::getApplication()->input->getCmd('videoid','');
		if(!isset($videoid) or $videoid=='')
		{
			$video=JFactory::getApplication()->input->getVar('video','');
			$video=preg_replace('/[^a-zA-Z0-9-_]+/', '', $video);
			
			if($video!='')
			{
				$videoid=YouTubeGalleryDB::getVideoIDbyAlias($video);
				JFactory::getApplication()->input->set('videoid',$videoid);
			}
			
		}

		if($theme_row->es_playvideo==1 and $videoid!='')
			$theme_row->es_autoplay=1;

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
			$videolist=$ygDB->getVideoList_FromCache_From_Table($videoid_new,$total_number_of_rows,false);
		}

		if($videoid=='')
		{
			if($theme_row->es_playvideo==1 and $videoid_new!='')
				$videoid=$videoid_new;
		}

		$renderer= new YouTubeGalleryRenderer;

		$result.=$renderer->render(
									$videolist,
									$videolist_row,
									$theme_row,
									$total_number_of_rows,
									$videoid,
									$custom_itemid
								 );

		return $result;

	}

	public static function getListToReplace($par,&$options,&$text)
	{
		$temp_text=preg_replace("/<textarea\b[^>]*>(.*?)<\/textarea>/i", "", $text);

		$fList=array();
		$l=strlen($par)+2;

		$offset=0;
		do{
			if($offset>=strlen($temp_text))
				break;

			$ps=strpos($text, '{'.$par.'=', $offset);
			if($ps===false)
				break;


			if($ps+$l>=strlen($temp_text))
				break;

		$pe=strpos($text, '}', $ps+$l);

		if($pe===false)
			break;

		$notestr=substr($temp_text,$ps,$pe-$ps+1);
		$fList[]=$notestr;

		$opt_string=substr($temp_text,$ps+$l,$pe-$ps-$l);
		$options[]=Helper::html2txt($opt_string);

		$offset=$ps+$l;

		}while(!($pe===false));

		return $fList;
	}

}//class

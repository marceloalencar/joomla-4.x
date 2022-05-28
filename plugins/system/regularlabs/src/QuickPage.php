<?php
/**
 * @package         Regular Labs Library
 * @version         22.5.9993
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Plugin\System\RegularLabs;

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Http as RL_Http;
use RegularLabs\Library\RegEx as RL_RegEx;

class QuickPage
{
	public static function render()
	{
		if ( ! JFactory::getApplication()->input->getInt('rl_qp', 0))
		{
			return;
		}

		$url = JFactory::getApplication()->input->getString('url', '');

		if ($url)
		{
			echo RL_Http::getFromServer($url, JFactory::getApplication()->input->getInt('timeout', ''));

			die;
		}

		$class  = JFactory::getApplication()->input->getString('class', '');
		$method = JFactory::getApplication()->input->getString('method', 'render');

		$allowed = [
			'Plugin.EditorButton.ArticlesAnywhere.Popup',
			'Plugin.EditorButton.ConditionalContent.Popup',
			'Plugin.EditorButton.ContentTemplater.Data',
			'Plugin.EditorButton.ContentTemplater.Popup',
			'Plugin.EditorButton.DummyContent.Popup',
			'Plugin.EditorButton.Modals.Popup',
			'Plugin.EditorButton.ModulesAnywhere.Popup',
			'Plugin.EditorButton.Sliders.data.php',
			'Plugin.EditorButton.Sliders.Popup',
			'Plugin.EditorButton.Snippets.Popup',
			'Plugin.EditorButton.Sourcerer.Popup',
			'Plugin.EditorButton.Tabs/data.php',
			'Plugin.EditorButton.Tabs.Popup',
			'Plugin.EditorButton.Tooltips.Popup',
		];

		if ( ! $class || in_array($class, $allowed) === false)
		{
			die;
		}

		$app = JFactory::getApplication();

//		if (RL_Document::isClient('site'))
//		{
//			$app->setTemplate('../administrator/templates/isis');
//		}

		$_REQUEST['tmpl'] = 'component';
		$app->input->set('option', 'com_content');

		switch ($app->input->getCmd('format', 'html'))
		{
			case 'json' :
				$format = 'application/json';
				break;

			default:
			case 'html' :
				$format = 'text/html';
				break;
		}

		header('Content-Type: ' . $format . '; charset=utf-8');
//		$doc->addScript(
//			JUri::root(true) . '/administrator/templates/isis/js/template.js'
//		);
//		$doc->addStylesheet(
//			JUri::root(true) . '/administrator/templates/isis/css/template' . ($doc->direction === 'rtl' ? '-rtl' : '') . '.css'
//		);'

//		RL_Document::style('regularlabs.popup');

		$class = '\\RegularLabs\\' . str_replace('.', '\\', $class);

		ob_start();
		(new $class)->$method();
		$html = ob_get_contents();
		ob_end_clean();

		RL_Document::setComponentBuffer($html);

		$app = new Application;
		$app->render();

		$html = JFactory::getApplication()->getBody();

		$html = RL_RegEx::replace('\s*<link [^>]*href="[^"]*templates/system/[^"]*\.css[^"]*"[^>]*( /)?>', '', $html);
		$html = RL_RegEx::replace('(<body [^>]*class=")', '\1rl-popup ', $html);
		$html = str_replace('<body>', '<body class="rl-popup"', $html);

		// Move the template css down to last
		/*		$html = RL_RegEx::replace('(<link [^>]*href="[^"]*templates/isis/[^"]*\.css[^"]*"[^>]*(?: /)?>\s*)(.*?)(<script)', '\2\1\3', $html);*/

		echo $html;

		die;
	}
}

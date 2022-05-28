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

namespace RegularLabs\Library;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication as JCMSApplication;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Form\Form as JForm;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Plugin\CMSPlugin as JCMSPlugin;
use Joomla\CMS\Plugin\PluginHelper as JPluginHelper;
use Joomla\Database\DatabaseDriver as JDatabaseDriver;
use Joomla\Registry\Registry as JRegistry;

class SystemPlugin extends JCMSPlugin
{
	public    $_alias              = '';
	public    $_lang_prefix        = '';
	public    $_title              = '';
	protected $_can_disable_by_url = true;
	protected $_doc_ready          = false;
	protected $_enable_in_admin    = false;
	protected $_enable_in_frontend = true;
	protected $_id                 = 0;
	protected $_jversion           = 4;
	protected $_page_types         = ['html', 'feed', 'pdf', 'xml', 'ajax', 'json', 'raw'];
	protected $_pass               = null;
	protected $_protected_formats  = [];
	/**
	 * @var    JCMSApplication
	 */
	protected $app;
	protected $autoloadLanguage = true;
	/**
	 * @var    JDatabaseDriver
	 */
	protected $db;

	/**
	 * @param object  &$subject    The object to observe
	 * @param array    $config     An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 */
	public function __construct(&$subject, $config = [])
	{
		if (isset($config['id']))
		{
			$this->_id = $config['id'];
		}

		parent::__construct($subject, $config);

		$this->app = JFactory::getApplication();
		$this->db  = JFactory::getDbo();

		if (empty($this->_alias))
		{
			$this->_alias = $this->_name;
		}

		if (empty($this->_title))
		{
			$this->_title = strtoupper($this->_alias);
		}
	}

	/**
	 * @param string $extension The extension for which a language file should be loaded
	 * @param string $basePath  The basepath to use
	 *
	 * @return  bool  True, if the file has successfully loaded.
	 */
	public function loadLanguage($extension = '', $basePath = JPATH_ADMINISTRATOR)
	{
		parent::loadLanguage('plg_system_regularlabs', JPATH_LIBRARIES . '/regularlabs');

		return parent::loadLanguage();
	}

	/**
	 * @return  void
	 */
	public function onAfterDispatch(): void
	{
		if ( ! $this->passChecks())
		{
			return;
		}

		$this->handleOnAfterDispatch();

		$buffer = Document::getComponentBuffer();

		$this->loadStylesAndScripts($buffer);

		if ( ! $buffer)
		{
			return;
		}

		$this->changeDocumentBuffer($buffer);

		Document::setComponentBuffer($buffer);
	}

	/**
	 * @return  bool
	 */
	protected function passChecks()
	{
		if ( ! is_null($this->_pass))
		{
			return $this->_pass;
		}

		$this->setPass(false);

		if ( ! $this->isFrameworkEnabled())
		{
			return false;
		}

		if ($this->_doc_ready && ! $this->passPageTypes())
		{
			return false;
		}

		if ( ! $this->_enable_in_frontend && $this->app->isClient('site'))
		{
			return false;
		}

		if ( ! $this->_enable_in_admin && ! $this->app->isClient('site'))
		{
			return false;
		}

		// disabled by url?
		if ($this->_can_disable_by_url
			&& Protect::isDisabledByUrl($this->_alias))
		{
			return false;
		}

		if ( ! $this->extraChecks())
		{
			return false;
		}

		$this->setPass(true);

		return true;
	}

	/**
	 * @return  void
	 */
	protected function handleOnAfterDispatch()
	{
		$this->handleFeedArticles();
	}

	/**
	 * @param string $buffer
	 *
	 * @return  void
	 */
	protected function loadStylesAndScripts(&$buffer)
	{
	}

	/**
	 * @param string $buffer
	 *
	 * @return  bool
	 */
	protected function changeDocumentBuffer(&$buffer)
	{
		return false;
	}

	/**
	 * @param bool $pass
	 *
	 * @return  void
	 */
	private function setPass($pass)
	{
		if ( ! $this->_doc_ready)
		{
			return;
		}

		$this->_pass = (bool) $pass;
	}

	/**
	 * Check if the Regular Labs Library is enabled
	 *
	 * @return bool
	 */
	private function isFrameworkEnabled()
	{
		if ( ! defined('REGULAR_LABS_LIBRARY_ENABLED'))
		{
			$this->setIsFrameworkEnabled();
		}

		if ( ! REGULAR_LABS_LIBRARY_ENABLED)
		{
			$this->throwError('REGULAR_LABS_LIBRARY_NOT_ENABLED');
		}

		return REGULAR_LABS_LIBRARY_ENABLED;
	}

	protected function passPageTypes()
	{
		if (empty($this->_page_types))
		{
			return true;
		}

		if (in_array('*', $this->_page_types, true))
		{
			return true;
		}

		if (empty(JFactory::$document))
		{
			return true;
		}

		if (Document::isFeed())
		{
			return in_array('feed', $this->_page_types, true);
		}

		if (Document::isPDF())
		{
			return in_array('pdf', $this->_page_types, true);
		}

		$page_type = Document::get()->getType();

		if (in_array($page_type, $this->_page_types, true))
		{
			return true;
		}

		return false;
	}

	protected function extraChecks()
	{
//		$input = JFactory::getApplication()->input;
//
//		// Disable on Gridbox edit form: option=com_gridbox&view=gridbox
//		if ($input->get('option', '') == 'com_gridbox' && $input->get('view', '') == 'gridbox')
//		{
//			return false;
//		}
//
//		// Disable on SP PageBuilder edit form: option=com_sppagebuilder&view=form
//		if ($input->get('option', '') == 'com_sppagebuilder' && $input->get('view', '') == 'form')
//		{
//			return false;
//		}

		return true;
	}

	/**
	 * @return  void
	 */
	protected function handleFeedArticles()
	{
		if ( ! empty($this->_page_types)
			&& ! in_array('feed', $this->_page_types, true)
		)
		{
			return;
		}

		if ( ! Document::isFeed()
			&& JFactory::getApplication()->input->get('option', '') != 'com_acymailing'
		)
		{
			return;
		}

		if ( ! isset(Document::get()->items))
		{
			return;
		}

		$context = 'feed';
		$items   = Document::get()->items;
		$params  = null;

		foreach ($items as $item)
		{
			$this->handleOnContentPrepare('article', $context, $item, $params);
		}
	}

	/**
	 * Set the define with whether the Regular Labs Library is enabled
	 */
	private function setIsFrameworkEnabled()
	{
		if ( ! JPluginHelper::isEnabled('system', 'regularlabs'))
		{
			$this->throwError('REGULAR_LABS_LIBRARY_NOT_ENABLED');

			define('REGULAR_LABS_LIBRARY_ENABLED', false);

			return;
		}

		define('REGULAR_LABS_LIBRARY_ENABLED', true);
	}

	/**
	 * Place an error in the message queue
	 */
	protected function throwError($error)
	{
		$user = JFactory::getApplication()->getIdentity() ?: JFactory::getUser();

		// Return if page is not an admin page or the admin login page
		if (
			! JFactory::getApplication()->isClient('administrator')
			|| $user->get('guest')
		)
		{
			return;
		}

		// load the admin language file
		JFactory::getLanguage()->load('plg_' . $this->_type . '_' . $this->_name, JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name);

		$text = JText::sprintf($this->_lang_prefix . '_' . $error, JText::_($this->_title));
		$text = JText::_($text) . ' ' . JText::sprintf($this->_lang_prefix . '_EXTENSION_CAN_NOT_FUNCTION', JText::_($this->_title));

		// Check if message is not already in queue
		$messagequeue = JFactory::getApplication()->getMessageQueue();
		foreach ($messagequeue as $message)
		{
			if ($message['message'] == $text)
			{
				return;
			}
		}

		JFactory::getApplication()->enqueueMessage($text, 'error');
	}

	/**
	 * @param string    $area
	 * @param string    $context The context of the content being passed to the plugin.
	 * @param mixed     $article An object with a "text" property
	 * @param mixed    &$params  Additional parameters. See {@see PlgContentContent()}.
	 * @param int       $page    Optional page number. Unused. Defaults to zero.
	 *
	 * @return  bool
	 */
	protected function handleOnContentPrepare($area, $context, &$article, &$params, $page = 0)
	{
		return true;
	}

	/**
	 * @return  void
	 */
	public function onAfterInitialise(): void
	{
		if ( ! $this->passChecks())
		{
			return;
		}

		$this->handleOnAfterInitialise();
	}

	/**
	 * @return  void
	 */
	protected function handleOnAfterInitialise()
	{
	}

	/**
	 * @return  void
	 */
	public function onAfterRender(): void
	{
		if ( ! $this->passChecks())
		{
			return;
		}

		$this->handleOnAfterRender();

		$html = $this->app->getBody();

		if ($html == '')
		{
			return;
		}

		if ( ! $this->changeFinalHtmlOutput($html))
		{
			return;
		}

		$this->cleanFinalHtmlOutput($html);

		$this->app->setBody($html);
	}

	/**
	 * @return  void
	 *
	 * Consider using changeFinalHtmlOutput instead
	 */
	protected function handleOnAfterRender()
	{
	}

	/**
	 * @param string $html
	 *
	 * @return  bool
	 */
	protected function changeFinalHtmlOutput(&$html)
	{
		return false;
	}

	/**
	 * @param string $html
	 *
	 * @return  void
	 */
	protected function cleanFinalHtmlOutput(&$html)
	{
	}

	/**
	 * @param object $module
	 * @param array  $params
	 *
	 * @return  void
	 */
	public function onAfterRenderModule(&$module, &$params): void
	{
		if ( ! $this->passChecks())
		{
			return;
		}

		$this->handleOnAfterRenderModule($module, $params);
	}

	/**
	 * @param object $module
	 * @param array  $params
	 *
	 * @return  void
	 */
	protected function handleOnAfterRenderModule(&$module, &$params)
	{
	}

	/**
	 * @param string $buffer
	 * @param array  $params
	 *
	 * @return  void
	 */
	public function onAfterRenderModules(&$buffer, &$params): void
	{
		if ( ! $this->passChecks())
		{
			return;
		}

		$this->handleOnAfterRenderModules($buffer, $params);

		if (empty($buffer))
		{
			return;
		}

		$this->changeModulePositionOutput($buffer, $params);
	}

	/**
	 * @param string $buffer
	 * @param array  $params
	 *
	 * @return  void
	 */
	protected function handleOnAfterRenderModules(&$buffer, &$params)
	{
	}

	/**
	 * @param string $buffer
	 * @param string $params
	 *
	 * @return  void
	 */
	protected function changeModulePositionOutput(&$buffer, &$params)
	{
	}

	/**
	 * @return  void
	 */
	public function onAfterRoute(): void
	{
		$this->_doc_ready = true;

		if ( ! $this->passChecks())
		{
			return;
		}

		$this->handleOnAfterRoute();
	}

	/**
	 * @return  void
	 */
	protected function handleOnAfterRoute()
	{
	}

	/**
	 * @return  void
	 */
	public function onBeforeCompileHead(): void
	{
		if ( ! $this->passChecks())
		{
			return;
		}

		$this->handleOnBeforeCompileHead();
	}

	/**
	 * @return  void
	 */
	protected function handleOnBeforeCompileHead()
	{
	}

	/**
	 * @param string    $context The context of the content being passed to the plugin.
	 * @param mixed    &$row     An object with a "text" property
	 * @param mixed    &$params  Additional parameters. See {@see PlgContentContent()}.
	 * @param integer   $page    Optional page number. Unused. Defaults to zero.
	 *
	 * @return  void
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0): void
	{
		if ( ! $this->passChecks())
		{
			return;
		}

		$area    = isset($article->created_by) ? 'article' : 'other';
		$context = (($params instanceof JRegistry) && $params->get('rl_search')) ? 'com_search.' . $params->get('readmore_limit') : $context;

		if ( ! $this->handleOnContentPrepare($area, $context, $article, $params, $page))
		{
			return;
		}

		Article::process($article, $context, $this, 'processArticle', [$area, $context, $article]);
	}

	/**
	 * @param JForm    $form The form
	 * @param stdClass $data The data
	 *
	 * @return  bool
	 */
	public function onContentPrepareForm(JForm $form, $data): bool
	{
		if ( ! $this->passChecks())
		{
			return true;
		}

		return $this->handleOnContentPrepareForm($form, $data);
	}

	/**
	 * @param JForm    $form The form
	 * @param stdClass $data The data
	 *
	 * @return  bool
	 */
	protected function handleOnContentPrepareForm(JForm $form, $data)
	{
		return true;
	}

	/**
	 * @param string &$string
	 * @param string  $area
	 * @param string  $context The context of the content being passed to the plugin.
	 * @param mixed   $article An object with a "text" property
	 * @param int     $page    Optional page number. Unused. Defaults to zero.
	 *
	 * @return  void
	 */
	public function processArticle(&$string, $area = 'article', $context = '', $article = null, $page = 0)
	{
	}
}

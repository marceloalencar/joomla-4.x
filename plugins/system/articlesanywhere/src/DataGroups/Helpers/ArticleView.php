<?php
/**
 * @package         Articles Anywhere
 * @version         12.3.1
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\AssociationHelper;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Component\Content\Site\Model\ArticleModel as JArticleModel;
use Joomla\Component\Content\Site\View\Article\HtmlView as JArticleView;
use RegularLabs\Library\StringHelper as RL_String;

class ArticleView extends JArticleView
{
	private $plugin_params;

	/**
	 * Execute and display a template script.
	 *
	 * @param string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
// Articles Anywhere: Do we need this?
//		if ($this->getLayout() === 'pagebreak')
//		{
//			return parent::display($tpl);
//		}

		// Create a shortcut for $item.
		$item = $this->item;

		if (empty($item))
		{
			return false;
		}

		$app  = Factory::getApplication();
		$user = Factory::getApplication()->getIdentity() ?: JFactory::getUser();

		// Articles Anywhere: Nope, already set via setParams
		// $this->item  = $this->get('Item');
		$this->print = $app->input->getBool('print', false);
		// Articles Anywhere: Nope, already set via setParams
		//$this->state = $this->get('State');
		$this->user = $user;

		// Check for errors.
		$errors = $this->get('Errors');
		if ( ! empty($errors) && count($errors))
		{
			return false;
		}

		$item->tagLayout = new FileLayout('joomla.content.tags');

		// Add router helpers.
		$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

		// No link for ROOT category
		if ($item->parent_alias === 'root')
		{
			$item->parent_id = null;
		}

		// TODO: Change based on shownoauth
		$item->readmore_link = Route::_(RouteHelper::getArticleRoute($item->slug, $item->catid, $item->language));

		// Merge article params. If this is single-article view, menu params override article params
		// Otherwise, article params override menu item params
		$this->params = $this->state->get('params');
		$active       = $app->getMenu()->getActive();
		$temp         = clone $this->params;

		// TODO: Articles Anywhere: Should access check be here? Or before calling Layout?
		// Check ignore access level in settings/tag
		$item->params->set('access-view', true);

//      Articles Anywhere: We already have set the template. So we don't need this
//		// Check to see which parameters should take priority
//		if ($active)
//		{
//			$currentLink = $active->link;
//
//			// If the current view is the active item and an article view for this article, then the menu item params take priority
//			if (strpos($currentLink, 'view=article') && strpos($currentLink, '&id=' . (string) $item->id))
//			{
//				// Load layout from active query (in case it is an alternative menu item)
//				if (isset($active->query['layout']))
//				{
//					$this->setLayout($active->query['layout']);
//				}
//				// Check for alternative layout of article
//				elseif ($layout = $item->params->get('article_layout'))
//				{
//					$this->setLayout($layout);
//				}
//
//				// $item->params are the article params, $temp are the menu item params
//				// Merge so that the menu item params take priority
//				$item->params->merge($temp);
//			}
//			else
//			{
//				// Current view is not a single article, so the article params take priority here
//				// Merge the menu item params with the article params so that the article params take priority
//				$temp->merge($item->params);
//				$item->params = $temp;
//
//				// Check for alternative layouts (since we are not in a single-article menu item)
//				// Single-article menu item layout takes priority over alt layout for an article
//				if ($layout = $item->params->get('article_layout'))
//				{
//					$this->setLayout($layout);
//				}
//			}
//		}
//		else
//		{
//			// Merge so that article params take priority
//			$temp->merge($item->params);
//			$item->params = $temp;
//
//			// Check for alternative layouts (since we are not in a single-article menu item)
//			// Single-article menu item layout takes priority over alt layout for an article
//			if ($layout = $item->params->get('article_layout'))
//			{
//				$this->setLayout($layout);
//			}
//		}

		$offset = $this->state->get('list.offset');

		// Articles Anywhere: Do we nee this? Or handle access a level higher?
//		// Check the view access to the article (the model has already computed the values).
//		if ($item->params->get('access-view') == false && ($item->params->get('show_noauth', '0') == '0'))
//		{
//			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
//			$app->setHeader('status', 403, true);
//
//			return;
//		}

		// Articles Anywhere: Do we nee this? Or handle access a level higher?
		/**
		 * Check for no 'access-view' and empty fulltext,
		 * - Redirect guest users to login
		 * - Deny access to logged users with 403 code
		 * NOTE: we do not recheck for no access-view + show_noauth disabled ... since it was checked above
		 */
//		if ($item->params->get('access-view') == false && ! strlen($item->fulltext))
//		{
//			if ($this->user->get('guest'))
//			{
//				$return                = base64_encode(Uri::getInstance());
//				$login_url_with_return = Route::_('index.php?option=com_users&view=login&return=' . $return);
//				$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'notice');
//				$app->redirect($login_url_with_return, 403);
//			}
//			else
//			{
//				$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
//				$app->setHeader('status', 403, true);
//
//				return;
//			}
//		}

		/**
		 * NOTE: The following code (usually) sets the text to contain the fulltext, but it is the
		 * responsibility of the layout to check 'access-view' and only use "introtext" for guests
		 */
		if ($item->params->get('show_intro', '1') === '1')
		{
			$item->text = $item->introtext . ' ' . $item->fulltext;
		}
		elseif ($item->fulltext)
		{
			$item->text = $item->fulltext;
		}
		else
		{
			$item->text = $item->introtext;
		}

		$item->tags = new TagsHelper;
		$item->tags->getItemTags('com_content.article', $this->item->id);

		if (Associations::isEnabled() && $item->params->get('show_associations'))
		{
			$item->associations = AssociationHelper::displayAssociations($item->id);
		}

		$item->event                       = (object) [];
		$item->event->afterDisplayTitle    = '';
		$item->event->beforeDisplayContent = '';
		$item->event->afterDisplayContent  = '';

		// Articles Anywhere: Only trigger content events if the settings allow it
		if ($this->plugin_params->force_content_triggers)
		{
			// Process the content plugins.
			PluginHelper::importPlugin('content');
			Factory::getApplication()->triggerEvent('onContentPrepare', ['com_content.article', &$item, &$item->params, $offset]);

			$results = Factory::getApplication()->triggerEvent(
				'onContentAfterTitle',
				['com_content.article', &$item, &$item->params, $offset,]
			);

			$item->event->afterDisplayTitle = trim(implode("\n", $results));

			$results = Factory::getApplication()->triggerEvent(
				'onContentBeforeDisplay',
				['com_content.article', &$item, &$item->params, $offset,]
			);

			$item->event->beforeDisplayContent = trim(implode("\n", $results));

			$results = Factory::getApplication()->triggerEvent(
				'onContentAfterDisplay',
				['com_content.article', &$item, &$item->params, $offset,]
			);

			$item->event->afterDisplayContent = trim(implode("\n", $results));
		}

		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($this->item->params->get('pageclass_sfx'));

		// Articles Anywhere: No, we just do the loadTemplate directly
		//$this->_prepareDocument();
		//parent::display($tpl);

		return $this->loadTemplate($tpl);
	}

	public function setParams($id, $template, $layout, $params, $attributes)
	{
		$model = new JArticleModel;

		$this->plugin_params = $params;

		$this->item  = $model->getItem($id);
		$this->state = $model->getState();

		$this->setLayout($template . ':' . $layout);

		$this->item->article_layout = $template . ':' . $layout;

		$this->overrideItemParams($attributes);

		$this->_addPath('template', JPATH_SITE . '/components/com_content/tmpl/article/');
		$this->_addPath('template', JPATH_SITE . '/templates/' . $template . '/html/com_content/article/');
	}

	private function overrideItemParams($attributes)
	{
		foreach ($attributes as $key => $value)
		{
			$key = RL_String::toUnderscoreCase($key);

			if ( ! $this->item->params->exists($key))
			{
				continue;
			}

			$this->item->params->set($key, $value);
		}
	}
}

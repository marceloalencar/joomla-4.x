/**
 * @package         Articles Anywhere
 * @version         12.3.1
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

(function() {
	'use strict';

	window.RegularLabs                  = window.RegularLabs || {};
	window.RegularLabs.ArticlesAnywhere = window.RegularLabs.ArticlesAnywhere || {};

	window.RegularLabs.ArticlesAnywhere.Button = window.RegularLabs.ArticlesAnywhere.Button || {
		code: '',

		insertText: function(editor_name) {
			Joomla.editors.instances[editor_name].replaceSelection(this.code);
		},

		setCode: function(code) {
			this.code = code;
		},
	};
})();

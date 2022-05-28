/**
 * @package         Regular Labs Library
 * @version         22.5.9993
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

(function() {
	'use strict';

	window.RegularLabs = window.RegularLabs || {};

	window.RegularLabs.AdminForm = window.RegularLabs.AdminForm || {
		setToggleTitleClass: function(input, value) {
			const panel = input.closest('.rl-panel');

			if ( ! panel) {
				return;
			}

			panel.classList.remove('rl-panel-info');
			panel.classList.remove('rl-panel-success');
			panel.classList.remove('rl-panel-error');

			switch (value) {
				case 2:
					panel.classList.add('rl-panel-error');
					break;
				case 1:
					panel.classList.add('rl-panel-success');
					break;
				default:
					panel.classList.add('rl-panel-info');
					break;
			}
		},

		loadAjaxButton: function(id, url) {
			const button  = document.querySelector(`#${id}`);
			const icon    = button.querySelector("span:nth-child(1)");
			const message = document.querySelector(`#message_${id}`);

			icon.className    = "icon-refresh icon-spin";
			message.className = "";
			message.innerHTML = "";

			const constants = `
				const button = document.querySelector("#${id}");
				const icon = button.querySelector("span:nth-child(1)");
				const message = document.querySelector("#message_${id}");
			`;

			let success = `${constants}
				Regular.removeClass(button, "btn-warning");
				Regular.addClass(button, "btn-success");
				button.querySelector("span:nth-child(1)").className = "icon-ok";
				if (data) {
					Regular.addClass(message, "alert alert-success alert-noclose alert-inline");
					message.innerHTML = data;
				}
			`;

			let error = `${constants}
				Regular.removeClass(button, "btn-success");
				Regular.addClass(button, "btn-warning");
				button.querySelector("span:nth-child(1)").className = "icon-warning";
				
				if(data){
					let error = data;
					if(data.statusText) { 
						error = data.statusText;
						if(data.responseText.test(/<blockquote>/)) {
							error = data.responseText.replace(/^[.\\s\\S]*?<blockquote>([.\\s\\S]*?)<\\/blockquote>[.\\s\\S]*$/gm, "$1");
						}
					}
					Regular.addClass(message, "alert alert-danger alert-noclose alert-inline");
					message.innerHTML = error;
				}
			`;

			success = `
				if(data == "" || data.substring(0,1) == "+") {
					data = data.trim().replace(/^[+]/, "");
					${success}
				} else {
					data = data.trim().replace(/^[-]/, "");
					${error}
				}
			`;

			RegularLabs.Scripts.loadAjax(url, success, error);
		},

		loadAjaxFields: function() {
			document.querySelectorAll('textarea[data-rl-ajax]').forEach((el) => {
				if (el.dataset['rlAjaxDone'] === el.id) {
					return;
				}

				const parent = el.closest('div[data-showon]');

				if (parent) {
					if (parent.classList.contains('hidden')) {
						return;
					}

					const grand_parent = el.closest('div[data-showon]');

					if (grand_parent && grand_parent.classList.contains('hidden')) {
						return;
					}
				}

				let attributes = JSON.parse(el.dataset['rlAjax']);

				attributes.id   = el.id;
				attributes.name = el.name;

				const query_attributes = createCompressedAttributes(attributes);

				const url = `index.php?option=com_ajax&plugin=regularlabs&format=raw&fieldid=${el.id}&${query_attributes}`;

				const set_field      = `const field = document.querySelector("#${el.id}");`;
				const replace_field  = `if(field && '${el.id}'.indexOf('X__') < 0){`
					+ 'field.parentNode.replaceChild('
					+ 'Regular.createElementFromHTML(data),'
					+ `document.querySelector("#${el.id}")`
					+ ');'
					+ '}';
				const remove_spinner = `if(field && '${el.id}'.indexOf('X__') < 0){`
					+ 'field.parentNode.querySelectorAll(`.rl-spinner`).forEach((el) => {'
					+ 'el.remove();'
					+ '})'
					+ '}';

				let success = replace_field;

				if (attributes.treeselect) {
					success += `if(data.indexOf('rl-treeselect-') > -1){RegularLabs.TreeSelect.init('${el.id}');}`;
				}

				const error = `${set_field}${remove_spinner}`;
				success     = `if(data){${set_field}${remove_spinner}${success}}`;

				el.dataset['rlAjaxDone'] = el.id;

				if (typeof RegularLabs.Scripts === 'undefined') {
					document.addEventListener('DOMContentLoaded', function() {
						RegularLabs.Scripts.addToLoadAjaxList(url, success, error);
					});
					return;
				}

				RegularLabs.Scripts.addToLoadAjaxList(url, success, error);
			});

			function createCompressedAttributes(object) {
				const string = JSON.stringify(object);

				const compressed   = btoa(string);
				const chunk_length = Math.ceil(compressed.length / 10);
				const chunks       = compressed.match(new RegExp('.{1,' + chunk_length + '}', 'g'));

				const attributes = [];

				chunks.forEach((chunk, i) => {
					attributes.push(`rlatt_${i}=${encodeURIComponent(chunk)}`);
				});

				return attributes.join('&');
			}
		},

		removeEmptyControlGroups: function() {
			// remove all empty control groups
			document.querySelectorAll('div.control-group > div.control-label label').forEach((el) => {
				if (el.innerHTML.trim() === '') {
					el.remove();
				}
			});
			document.querySelectorAll('div.control-group > div.control-label,div.control-group > div.controls').forEach((el) => {
				if (el.innerHTML.trim() === '') {
					el.remove();
				}
			});
			document.querySelectorAll('div.control-group').forEach((el) => {
				if (el.innerHTML.trim() === '') {
					el.remove();
				}
			});
		},

		setParentClassOnCheckboxes: function() {
			document.querySelectorAll('fieldset.rl-form-checkboxes-set-parent-classes input').forEach((el) => {
				this.setParentClassOnCheckbox(el);
				document.addEventListener('change', (event) => {
					this.setParentClassOnCheckbox(el);
				});
			});
		},

		setParentClassOnCheckbox: function(el) {
			el.parentElement.classList.toggle('rl-checkbox-checked', el.checked);
		},

		updateForm: function() {
			this.loadAjaxFields();
			this.removeEmptyControlGroups();
		}
	};

	RegularLabs.AdminForm.updateForm();
	RegularLabs.AdminForm.setParentClassOnCheckboxes();

	document.addEventListener('subform-row-add', () => {
		RegularLabs.AdminForm.updateForm();
	});

	document.addEventListener('joomla:showon-show', (event) => {
		event.target.querySelectorAll('.CodeMirror').forEach((editor) => {
			editor.CodeMirror.refresh();
		});
	});
	document.addEventListener('joomla.tab.show', (event) => {
		document.querySelectorAll('.CodeMirror').forEach((editor) => {
			editor.CodeMirror.refresh();
		});
	});

	setInterval(() => {
		RegularLabs.AdminForm.updateForm();
	}, 1000);
})();

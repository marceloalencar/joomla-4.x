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

	window.RegularLabs.ArticlesAnywhere.Popup = window.RegularLabs.ArticlesAnywhere.Popup || {
		form          : null,
		options       : {},
		tag_characters: {},
		group         : null,
		do_update     : false,
		tag_type      : '',

		init: function() {
			if ( ! parent.RegularLabs.ArticlesAnywhere.Button) {
				document.querySelector('body').innerHTML = '<div class="alert alert-error">This page cannot function on its own.</div>';
				return;
			}

			this.options = Joomla.getOptions ? Joomla.getOptions('rl_articlesanywhere', {}) : Joomla.optionsStorage.rl_articlesanywhere || {};
			this.options.editor_name;

			if ( ! this.options.editor_name) {
				document.querySelector('body').innerHTML = 'No editor name found.';
				return;
			}

			this.form = document.querySelector('[name="articlesAnywhereForm"]');

			this.tag_characters.start      = this.options.tag_characters[0];
			this.tag_characters.end        = this.options.tag_characters[1];
			this.tag_characters.data_start = this.options.tag_characters_data[0];
			this.tag_characters.data_end   = this.options.tag_characters_data[1];

			setInterval(() => {
				if ( ! this.do_update) {
					return;
				}

				this.do_update = false;
				this.updatePreview();

			}, 100);

			this.form.addEventListener('DOMSubtreeModified', () => {
				this.do_update = true;
			});

			this.do_update = true;
		},

		insertText: function() {
			parent.RegularLabs.ArticlesAnywhere.Button.insertText(this.options.editor_name);
		},

		updatePreview: function() {
			const self = this;

			const preview_message = document.querySelector('#preview_message');
			const preview_code    = document.querySelector('#preview_code');
			const preview_spinner = document.querySelector('#preview_spinner');

			Regular.addClass(preview_message, 'hidden');
			Regular.addClass(preview_code, 'hidden');
			Regular.removeClass(preview_spinner, 'hidden');

			const code             = this.generateCode();
			preview_code.innerHTML = code;

			parent.RegularLabs.ArticlesAnywhere.Button.setCode(code);

			Regular.removeClass(preview_message, 'hidden');
			Regular.addClass(preview_spinner, 'hidden');

			if (code) {
				Regular.addClass(preview_message, 'hidden');
				Regular.removeClass(preview_code, 'hidden');
			}


			if (document.querySelectorAll('joomla-field-subform[name="data_tags"] div.subform-repeatable-group').length < 1) {
				document.querySelector('joomla-field-subform[name="data_tags"] .group-add').click();
			}

			setTimeout(() => {
				addEventListeners();
			}, 10);

			function addEventListeners() {
				// Fix broken references to fields in subform (stupid Joomla!)
				self.form.querySelectorAll('.subform-repeatable-group').forEach((group) => {
					const group_name = group.dataset['group'];
					const x_name     = group.dataset['baseName'] + 'X';

					const regex = new RegExp(x_name, 'g');

					const sub_elements = group.querySelectorAll(
						`[id*="${group_name}_"],`
						+ `[id*="${x_name}_"],`
						+ `[data-for*="${x_name}_"],`
						+ `[data-for*="${x_name}]"]`
					);

					sub_elements.forEach((el) => {
						if (el.dataset['for']) {
							el.dataset['for'] = el.dataset['for'].replace(regex, group_name);
						}
						if (el.getAttribute('oninput')) {
							el.setAttribute('oninput', el.getAttribute('oninput').replace(regex, group_name));
						}
						if (el.id) {
							el.id = el.id.replace(regex, group_name);
						}
					});
				});

				self.form.querySelectorAll('select:not(.has-listener), input:not(.has-listener), button:not(.has-listener)').forEach((el) => {
					el.addEventListener('change', () => {
						self.do_update = true;
					});
					el.addEventListener('click', () => {
						self.do_update = true;
					});
					el.addEventListener('keyup', () => {
						self.do_update = true;
					});
					Regular.addClass(el, 'has-listener');
				});
			}
		},

		generateCode: function() {
			const self = this;

			setSingleTag();

			const filters = getFilters();

			let attributes = convertToAttributes(filters);

			if (attributes === '') {
				return '';
			}

			const data_tags = getDataTags();

			let content = data_tags.join('');


			if (attributes === 'article="current"') {
				attributes = '';
			}

			return wrapTag(this.tag_type + ' ' + attributes)
				+ content
				+ wrapTag('/' + this.tag_type);

			function setSingleTag() {
				self.tag_type = self.options.article_tag;
			}

			function setMultipleTag() {
				self.tag_type = self.options.articles_tag;
			}

			function wrapTag(string) {
				return self.tag_characters.start
					+ string.trim()
					+ self.tag_characters.end;
			}


			function getFilters() {
				self.group = '';
				return getArticle();


				function getArticle() {
					const type = getData('article_type');

					if (type === 'current') {
						return {'article': 'current'};
					}

					const key     = getData('article_key');
					const article = getData('article', key === 'title');

					if ( ! article) {
						return false;
					}

					return {
						[key]: article
					};
				}


				function getData(id, use_text = false) {
					return getDataByType('filters', id, use_text);
				}

				function hideElementsBasedOnFilterType(type, group) {
					if ( ! type) {
						return;
					}

					if (type === 'article') {
						Regular.addClass(getFilterGroupsElements(), 'hidden');
						Regular.removeClass(group, 'hidden');
						Regular.addClass(getFilterAddButtonsElements(), 'hidden');
						return;
					}

					Regular.addClass(`.filter-type.type-article, .filter-type.type-${type}`, 'hidden');
				}

				function getFilterGroupsElements() {
					return document.querySelectorAll('joomla-field-subform[name="filters"] div.subform-repeatable-group');
				}

				function getFilterAddButtonsElements() {
					return document.querySelectorAll('joomla-field-subform[name="filters"] .group-add, joomla-field-subform[name="filters"] .group-move');
				}
			}

			function getDataTags() {
				let data_tags = [];

				document.querySelectorAll('joomla-field-subform[name="data_tags"] div.subform-repeatable-group').forEach((group) => {
					self.group     = group.dataset['group'];
					const data_tag = getDataTag('type');

					if ( ! data_tag) {
						return;
					}

					data_tags.push(data_tag);
				});

				return data_tags;

				function getDataTag() {
					const type = getData('type');

					if ( ! type || ! type.length) {
						return false;
					}

					switch (type) {
						case 'newline':
							return '<br>';

						case 'article':
							return getArticle();

						case 'title':
							return getTitle();

						case 'text':
							return getText();

						case 'readmore':
							return getReadmore();

						case 'image':
							return getImage();

						case 'category':
						case 'parent-category':
							return getCategory(type);

						case 'date':
							return getDate();


						default:
							return wrapTag(type);
					}

					function getArticle() {
						const layout = getData('article_layout');

						let attributes = {};

						if (layout) {
							attributes.layout = layout;
						}

						return wrapTag('article ' + convertToAttributes(attributes));
					}

					function getTitle() {
						const heading = getData('title_heading');

						let tag = wrapTag('title');

						if (getData('title_add_link')) {
							tag = wrapTag('link')
								+ tag
								+ wrapTag('/link');
						}

						if (heading) {
							tag = `</p><${heading}>${tag}</${heading}><p>`;
						}

						return tag;
					}

					function getCategory(key) {
						const prefix = key.replace('-', '_');

						let tag = wrapTag(key);

						if (getData(`${prefix}_add_link`)) {
							tag = wrapTag(`${key}:link`)
								+ tag
								+ wrapTag(`/${key}:link`);
						}

						return tag;
					}

					function getDate() {
						const key         = getData('date_key');
						const date_format = getData('date_format');

						let attributes = {};

						if (date_format) {
							attributes.format = date_format === 'other' ? getData('date_format_custom') : date_format;
						}

						return wrapTag(key + ' ' + convertToAttributes(attributes));

					}

					function getText() {
						const key          = getData('text_key');
						const limit_by     = getData('text_limit_by');
						const use_ellipsis = getData('use_ellipsis');
						const strip        = getData('text_strip');

						let attributes = {};

						if (limit_by) {
							attributes[limit_by] = parseInt(getData(`text_max_length_${limit_by}`));
						}

						if (use_ellipsis !== '') {
							attributes['use-ellipsis'] = use_ellipsis ? 'true' : 'false';
						}

						if (strip) {
							attributes.html = 'false';
						}

						return wrapTag(key + ' ' + convertToAttributes(attributes));
					}

					function getReadmore() {
						const text      = getData('readmore_text');
						const classname = getData('readmore_class');

						let attributes = {};

						if (text) {
							attributes.text = text;
						}

						if (classname) {
							attributes.class = classname;
						}

						return wrapTag('readmore ' + convertToAttributes(attributes));
					}

					function getImage() {
						let key          = getData('image_key');
						let content_type = getData('image_content_type');
						let number       = getData('image_number');
						let width        = getData('image_width');
						let height       = getData('image_height');

						number = Math.max(1, parseInt(number) ? parseInt(number) : 1);
						width  = Math.max(0, parseInt(width) ? parseInt(width) : 0);
						height = Math.max(0, parseInt(height) ? parseInt(height) : 0);

						if (key === 'content') {
							key = 'image-' + (content_type === 'select' ? number : 'random');
						}

						let attributes = {};

						if (width) {
							attributes.width = width;
						}

						if (height) {
							attributes.height = height;
						}

						let tag = wrapTag(key + ' ' + convertToAttributes(attributes));

						if (getData('image_add_link')) {
							tag = wrapTag('link')
								+ tag
								+ wrapTag('/link');
						}

						return tag;
					}

					function getField() {
						const field = getData('field_name');

						if ( ! field) {
							return false;
						}

						let attributes = {};

						if (getData('field_show_label')) {
							attributes.showlabel = 'true';
						}

						return wrapTag(field + ' ' + convertToAttributes(attributes));
					}
				}

				function getData(id, use_text = false) {
					return getDataByType('data_tags', id, use_text);
				}

				function wrapTag(string) {
					return self.tag_characters.data_start
						+ string.trim()
						+ self.tag_characters.data_end;
				}
			}

			function convertToAttributes(groups) {
				const attributes = [];

				for (let key in groups) {
					const value = groups[key];

					if (typeof value !== 'object') {
						attributes.push(key + '="' + value + '"');
						continue;
					}

					attributes.push(
						Object.entries(value).map(
							(key_value) => key_value[0] + '="' + key_value[1] + '"'
						).join(' ')
					);
				}

				return attributes.join(' ');
			}

			function setFormTagType(value = '') {
				const element = getFormElement('tag_type');
				element.value = value;
				element.dispatchEvent(new Event('change'));
			}

			function getFormElement(id, type = '') {
				const group  = self.group ? `[${self.group}]` : '';
				const prefix = type ? `${type}${group}` : '';

				let element = prefix ? `${prefix}[${id}]` : id;

				if ( ! self.form[element]) {
					element += '[]';
				}

				if ( ! self.form[element] && group) {
					// keep space between groups separate, otherwise the js minifier will remove it
					element = document.querySelector(`div[data-group="${self.group}"]` + ' ' + `[name="${id}"]`);
				}

				return typeof element !== 'string' ? element : self.form[element];
			}

			function getDataByType(type, id, use_text = false) {
				let element = getFormElement(id, type);

				if ( ! element) {
					return '';
				}

				if (element.options === undefined) {
					return parseValue(element.value);
				}

				let selected = [];
				for (let option of element.options) {
					if ( ! option.selected || ! option.value.length || option.value === '-') {
						continue;
					}

					if (use_text) {
						const text = option.innerText
							.replace(/^[ -]*/, '')
							.replace(/ \[.*$/, '')
							.replace('<', '&lt;')
							.replace('>', '&gt;');

						selected.push(text);
						continue;
					}

					selected.push(parseValue(option.value));
				}

				if (element.type !== 'select-multiple') {
					return selected.length ? selected[0] : '';
				}

				return selected;

				function parseValue(string) {
					string = string.toString().valueOf();

					if (string === '1' || string === 'true') {
						return 1;
					}

					if (string === '0' || string === 'false') {
						return 0;
					}

					return string;
				}
			}
		},
	};
})();

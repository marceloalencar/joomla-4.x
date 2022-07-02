(function(window, document, Math) {
	var picker, currentEl, oldColor;

	// Default settings
	var settings = {
		el          : '[data-rl-mini-colors]',
		parent      : null,
		wrap        : true,
		margin      : 2,
		swatches    : [],
		swatchesOnly: false,
		alpha       : true,
		autoClose   : false,

		a11y: {
			open       : 'Open color picker',
			swatch     : 'Color swatch',
		}
	};

	/**
	 * Configure the color picker.
	 * @param {object} options Configuration options.
	 */
	function configure(options) {
		if (typeof options !== 'object') {
			return;
		}

		for (var key in options) {
			switch (key) {
				case 'el':
					bindFields(options.el);
					if (options.wrap !== false) {
						wrapFields(options.el);
					}
					break;
				case 'parent':
					settings.parent = document.querySelector(options.parent);
					if (settings.parent) {
						settings.parent.appendChild(picker);
					}
					break;
				case 'margin':
					options.margin *= 1;
					settings.margin = ! isNaN(options.margin) ? options.margin : settings.margin;
					break;
				case 'wrap':
					if (options.el && options.wrap) {
						wrapFields(options.el);
					}
					break;
				case 'swatches':
					if (Array.isArray(options.swatches)) {
						(function() {
							var swatches = [];

							options.swatches.forEach(function(swatch, i) {
								swatches.push("<button id=\"rl-mini-colors-swatch-" + i + "\" class=\"rl-mini-colors-button\" aria-labelledby=\"rl-mini-colors-swatch-label rl-mini-colors-swatch-" + i + "\" style=\"color: " + swatch + ";\">" + swatch + "</button>");
							});

							if (swatches.length) {
								getEl('rl-mini-colors-swatches').innerHTML = "<div>" + swatches.join('') + "</div>";
							}
						})();
					}
					break;
				case 'swatchesOnly':
					settings.swatchesOnly = !! options.swatchesOnly;
					picker.setAttribute('data-minimal', settings.swatchesOnly);

					if (settings.swatchesOnly) {
						settings.autoClose = true;
					}
					break;
				case 'a11y':
					var labels = options.a11y;
					var update = false;

					if (typeof labels === 'object') {
						for (var label in labels) {
							if (labels[label] && settings.a11y[label]) {
								settings.a11y[label] = labels[label];
								update               = true;
							}
						}
					}

					if (update) {
						var openLabel   = getEl('rl-mini-colors-open-label');
						var swatchLabel = getEl('rl-mini-colors-swatch-label');

						openLabel.innerHTML   = settings.a11y.open;
						swatchLabel.innerHTML = settings.a11y.swatch;
					}
				default:
					settings[key] = options[key];
			}

		}
	}

	/**
	 * Bind the color picker to input fields that match the selector.
	 * @param {string} selector One or more selectors pointing to input fields.
	 */
	function bindFields(selector) {
		// Show the color picker on click on the input fields that match the selector
		addListener(document, 'click', selector, function(event) {

			var parent     = settings.parent;
			var coords     = event.target.getBoundingClientRect();
			var scrollY    = window.scrollY;
			var reposition = {left: false, top: false};
			var offset     = {x: 0, y: 0};
			var left       = coords.x;
			var top        = scrollY + coords.y + coords.height + settings.margin;

			currentEl = event.target;
			picker.classList.add('rl-mini-colors-open');

			var pickerWidth  = picker.offsetWidth;
			var pickerHeight = picker.offsetHeight;

			// If the color picker is inside a custom container
			// set the position relative to it
			if (parent) {
				var style     = window.getComputedStyle(parent);
				var marginTop = parseFloat(style.marginTop);
				var borderTop = parseFloat(style.borderTopWidth);

				offset = parent.getBoundingClientRect();
				offset.y += borderTop + scrollY;
				left -= offset.x;
				top -= offset.y;

				if (left + pickerWidth > parent.clientWidth) {
					left += coords.width - pickerWidth;
					reposition.left = true;
				}

				if (top + pickerHeight > parent.clientHeight - marginTop) {
					top -= coords.height + pickerHeight + settings.margin * 2;
					reposition.top = true;
				}

				top += parent.scrollTop;

				// Otherwise set the position relative to the whole document
			} else {
				if (left + pickerWidth > document.documentElement.clientWidth) {
					left += coords.width - pickerWidth;
					reposition.left = true;
				}

				if (top + pickerHeight - scrollY > document.documentElement.clientHeight) {
					top            = scrollY + coords.y - pickerHeight - settings.margin;
					reposition.top = true;
				}
			}

			picker.classList.toggle('rl-mini-colors-left', reposition.left);
			picker.classList.toggle('rl-mini-colors-top', reposition.top);
			picker.style.left = left + "px";
			picker.style.top  = top + "px";

			deselectRow(currentEl);
		});
	}

	function deselectRow(el) {
		const tr = el.closest('tr');

		if ( ! tr) {
			return;
		}

		const input = tr.querySelector('.form-check-input');

		if ( ! input) {
			return;
		}

		input.checked = false;
	}

	/**
	 * Wrap the linked input fields in a div that adds a color preview.
	 * @param {string} selector One or more selectors pointing to input fields.
	 */
	function wrapFields(selector) {
		document.querySelectorAll(selector).forEach(function(field) {
			var parentNode = field.parentNode;

			if ( ! parentNode.classList.contains('rl-mini-colors-field')) {
				var wrapper = document.createElement('div');

				wrapper.innerHTML = "<button class=\"rl-mini-colors-button\" aria-labelledby=\"rl-mini-colors-open-label\"></button>";
				parentNode.insertBefore(wrapper, field);
				wrapper.setAttribute('class', 'rl-mini-colors-field');
				wrapper.style.color = field.value;
				wrapper.appendChild(field);
			}
		});
	}

	/**
	 * Close the color picker.
	 * @param {boolean} [revert] If true, revert the color to the original value.
	 */
	function closePicker(color) {
		if ( ! currentEl) {
			return;
		}

		picker.classList.remove('rl-mini-colors-open');

		currentEl = null;
	}

	/**
	 * Copy the active color to the linked input field.
	 * @param {number} [color] Color value to override the active color.
	 */
	function pickColor(color) {
		if ( ! currentEl) {
			return;
		}
		currentEl.value = color;
		currentEl.dispatchEvent(new Event('input', {bubbles: true}));
		currentEl.dispatchEvent(new Event('change', {bubbles: true}));

		const parent = currentEl.parentNode;

		if (parent.classList.contains('rl-mini-colors-field')) {
			parent.style.color = color;
		}
	}

	/**
	 * Init the color picker.
	 */
	function init() {
		// Render the UI
		picker = document.createElement('div');
		picker.setAttribute('id', 'rl-mini-colors-picker');
		picker.className = 'rl-mini-colors-picker';
		picker.innerHTML =
			'<div id="rl-mini-colors-swatches" class="rl-mini-colors-swatches"></div>'
			+ ("<span id=\"rl-mini-colors-open-label\" hidden>" + settings.a11y.open + "</span>")
			+ ("<span id=\"rl-mini-colors-swatch-label\" hidden>" + settings.a11y.swatch + "</span>");

		// Append the color picker to the DOM
		document.body.appendChild(picker);

		// Bind the picker to the default selector
		bindFields(settings.el);
		wrapFields(settings.el);

		addListener(picker, 'click', '.rl-mini-colors-swatches .rl-mini-colors-button', (event) => {
			pickColor(event.target.textContent);
			closePicker(event.target.textContent);
		});

		addListener(document, 'click', '.rl-mini-colors-field .rl-mini-colors-button', (event) => {
			event.target.nextElementSibling.dispatchEvent(new Event('click', {bubbles: true}));
		});

		addListener(document, 'mousedown', (event) => {
			if (event.target.classList.contains('rl-mini-colors')
				|| event.target.classList.contains('rl-mini-colors-picker')
				|| event.target.closest('.rl-mini-colors-picker')) {
				return;
			}

			closePicker();
		});
	}

	/**
	 * Shortcut for getElementById to optimize the minified JS.
	 * @param {string} id The element id.
	 * @return {object} The DOM element with the provided id.
	 */
	function getEl(id) {
		return document.getElementById(id);
	}

	/**
	 * Shortcut for addEventListener to optimize the minified JS.
	 * @param {object} context The context to which the listener is attached.
	 * @param {string} type Event type.
	 * @param {(string|function)} selector Event target if delegation is used, event handler if not.
	 * @param {function} [fn] Event handler if delegation is used.
	 */
	function addListener(context, type, selector, fn) {
		var matches = Element.prototype.matches || Element.prototype.msMatchesSelector;

		// Delegate event to the target of the selector
		if (typeof selector === 'string') {
			context.addEventListener(type, function(event) {
				if (matches.call(event.target, selector)) {
					fn.call(event.target, event);
				}
			});

			// If the selector is not a string then it's a function
			// in which case we need regular event listener
		} else {
			fn = selector;
			context.addEventListener(type, fn);
		}
	}

	/**
	 * Call a function only when the DOM is ready.
	 * @param {function} fn The function to call.
	 * @param {array} [args] Arguments to pass to the function.
	 */
	function DOMReady(fn, args) {
		args = args !== undefined ? args : [];

		if (document.readyState !== 'loading') {
			fn.apply(void 0, args);
		} else {
			document.addEventListener('DOMContentLoaded', function() {
				fn.apply(void 0, args);
			});
		}
	}

	// Polyfill for Nodelist.forEach
	if (NodeList !== undefined && NodeList.prototype && ! NodeList.prototype.forEach) {
		NodeList.prototype.forEach = Array.prototype.forEach;
	}

	// Expose the color picker to the global scope
	window.RegularLabs_MiniColors = function() {
		var methods = {
			set  : configure,
			wrap : wrapFields,
			close: closePicker
		};

		function RegularLabs_MiniColors(options) {
			DOMReady(function() {
				if (options) {
					if (typeof options === 'string') {
						bindFields(options);
					} else {
						configure(options);
					}
				}
			});
		}

		var _loop = function _loop(key) {
			RegularLabs_MiniColors[key] = function() {
				for (var _len = arguments.length, args = new Array(_len), _key2 = 0; _key2 < _len; _key2++) {
					args[_key2] = arguments[_key2];
				}
				DOMReady(methods[key], args);
			};
		};
		for (var key in methods) {
			_loop(key);
		}

		return RegularLabs_MiniColors;
	}();

	// Init the color picker when the DOM is ready
	DOMReady(init);

})(window, document, Math);

(function() {
	'use strict';

	window.RegularLabs = window.RegularLabs || {};

	window.RegularLabs.MiniColors = window.RegularLabs.MiniColors || {
		init: function() {
			const minicolors = document.querySelectorAll('div.rl-mini-colors');
			const options    = Joomla.getOptions ? Joomla.getOptions('rl_minicolors', {}) : Joomla.optionsStorage.rl_minicolors || {};

			minicolors.forEach((minicolor) => {
				const field = minicolor.querySelector('input');
				RegularLabs_MiniColors({
					el          : `#${field.id}`,
					theme       : 'default',
					alpha       : false,
					swatchesOnly: true,
					swatches    : options.swatches
				});

				if ( ! field.dataset['table'] || ! field.dataset['item_id']) {
					return;
				}

				field.addEventListener('change', () => {
					RegularLabs.MiniColors.save(field.dataset['table'], field.dataset['item_id'], field.dataset['id_column'], field.value, field);
				});

				RegularLabs.MiniColors.setTableRowBackground(field, field.value);
			});
		},

		setTableRowBackground: async function(element, color, opacity = .1) {

			if ( ! element) {
				return;
			}

			const table_row = element.closest('tr');

			if ( ! table_row) {
				return;
			}
			const table_cells = table_row.querySelectorAll('td, th');

			if ( ! table_cells.length) {
				return;
			}

			const bg_color = RegularLabs.MiniColors.getColorWithOpacity(color, opacity);

			if (color[0] === '#') {
				table_cells[0].style.borderLeft = `4px solid ${color}`;
			}

			table_cells.forEach((table_cell) => {
				table_cell.style.backgroundColor = bg_color;
			});
		},

		save: async function(table, item_id, id_column, color, element) {
			let spinner = null;
			id_column = id_column ? id_column : 'id';

			if (element) {
				spinner = document.createElement('div');
				spinner.classList.add('rl-spinner');

				element.closest('div.rl-mini-colors-field').append(spinner);

				RegularLabs.MiniColors.setTableRowBackground(element, color);
			}

			const url = 'index.php?option=com_ajax&plugin=regularlabs&format=raw&saveColor=1'
				+ '&table=' + table
				+ '&item_id=' + item_id
				+ '&id_column=' + id_column
				+ '&color=' + encodeURIComponent(color);

			await RegularLabs.Scripts.loadUrl(url);

			RegularLabs.MiniColors.saved(spinner);

		},

		saved: function(spinner = null) {
			if ( ! spinner) {
				return;
			}

			spinner.remove();
		},

		getColorWithOpacity: function(hex, opacity) {
			const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);

			if ( ! result) {
				return 'var(--table-bg)';
			}

			return 'rgba('
				+ parseInt(result[1], 16) + ','
				+ parseInt(result[2], 16) + ','
				+ parseInt(result[3], 16) + ','
				+ opacity
				+ ')';
		}

	};

	RegularLabs.MiniColors.init();
})();

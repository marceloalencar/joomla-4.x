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

	window.RegularLabs.AdminFormDescriptions = window.RegularLabs.AdminFormDescriptions || {
		moveLabelDescriptions: function() {
			document.querySelectorAll('div[id$="-desc"]:not(.rl-moved)').forEach((description) => {
				const control_group = description.closest('.control-group');

				if ( ! control_group) {
					return;
				}

				const label = control_group.querySelector('label');

				if ( ! label) {
					return;
				}

				const controls = control_group.querySelector('.controls');

				this.create(label, controls, description);
			});
		},

		createFromClasses: function() {
			document.querySelectorAll('div.rl-popover:not(.rl-moved)').forEach((description) => {
				const label = description.previousElementSibling;

				if ( ! label) {
					return;
				}

				let parent   = description.closest('.rl-popover-parent');
				let position = 'after';

				if ( ! parent) {
					parent   = description.parentElement;
					position = 'end';
				}

				this.create(label, parent, description, position);
			});
		},

		create: function(label, controls, description, position = 'start') {
			if ( ! label) {
				return;
			}

			description.classList.add('hidden');
			description.classList.add('rl-moved');

			const popover       = document.createElement('div');
			const popover_inner = document.createElement('div');

			popover.classList.add('rl-admin-popover-container');

			if (description.classList.contains('rl-popover-full')) {
				popover.classList.add('rl-admin-popover-full');
			}

			popover_inner.classList.add('rl-admin-popover');
			popover_inner.innerHTML = description.querySelector('small').innerHTML;

			popover.append(popover_inner);

			const button = document.createElement('span');
			button.classList.add('icon-info-circle', 'text-muted', 'fs-6', 'ms-1', 'align-text-top');

			label.setAttribute('role', 'button');
			label.setAttribute('tabindex', '0');

			const action_show = function() {
				popover.classList.add('show');
			};
			const action_hide = function() {
				popover.classList.remove('show');
			};

			label.addEventListener('mouseenter', action_show);
			label.addEventListener('mouseleave', action_hide);
			label.addEventListener('focus', action_show);
			label.addEventListener('blur', action_hide);

			label.append(button);

			switch (position) {
				case 'start':
					controls.prepend(popover);
					break;
				case 'end':
					controls.append(popover);
					break;
				case 'after':
				default:
					controls.parentNode.insertBefore(popover, controls.nextSibling);
					break;
			}
		}
	};

	RegularLabs.AdminFormDescriptions.moveLabelDescriptions();
	RegularLabs.AdminFormDescriptions.createFromClasses();

	document.addEventListener('subform-row-add', () => {
		document.dispatchEvent(new Event('rl-update-form-descriptions'));
	});

	document.addEventListener('rl-update-form-descriptions', () => {
		RegularLabs.AdminFormDescriptions.moveLabelDescriptions();
		RegularLabs.AdminFormDescriptions.createFromClasses();
	});
})();

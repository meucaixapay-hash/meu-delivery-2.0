(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-md2-app]').forEach(function (app) {
			var sidebar = app.querySelector('.md2-sidebar');
			var backdrop = app.querySelector('.md2-backdrop');
			var openButton = app.querySelector('[data-md2-open]');
			var closeButtons = app.querySelectorAll('[data-md2-close]');

			function closeMenu() {
				app.classList.remove('md2-menu-open');
				backdrop.hidden = true;
				openButton.setAttribute('aria-expanded', 'false');
			}

			openButton.addEventListener('click', function () {
				app.classList.add('md2-menu-open');
				backdrop.hidden = false;
				openButton.setAttribute('aria-expanded', 'true');
				sidebar.querySelector('a')?.focus();
			});

			closeButtons.forEach(function (button) {
				button.addEventListener('click', closeMenu);
			});

			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && app.classList.contains('md2-menu-open')) {
					closeMenu();
					openButton.focus();
				}
			});
		});
	});
})();

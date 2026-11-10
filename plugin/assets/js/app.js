(function () {
	'use strict';

	function initApp(app) {
		var sidebar = app.querySelector('.md2-sidebar');
		var backdrop = app.querySelector('.md2-backdrop');
		var openButton = app.querySelector('[data-md2-open]');
		var closeButtons = app.querySelectorAll('[data-md2-close]');
		var media = window.matchMedia('(max-width: 720px)');
		var bodyOverflow = '';

		if (!sidebar || !openButton) {
			return;
		}

		function focusableItems() {
			return Array.prototype.slice.call(sidebar.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
			)).filter(function (element) {
				return element.getAttribute('aria-hidden') !== 'true' && element.getClientRects().length > 0;
			});
		}

		function setDesktopState() {
			if (app.classList.contains('md2-menu-open')) {
				document.body.style.overflow = bodyOverflow;
			}
			app.classList.remove('md2-menu-open');
			if (backdrop) {
				backdrop.hidden = true;
			}
			openButton.setAttribute('aria-expanded', 'false');
			sidebar.removeAttribute('aria-hidden');
			sidebar.removeAttribute('role');
			sidebar.removeAttribute('aria-modal');
		}

		function closeMenu(restoreFocus) {
			if (!app.classList.contains('md2-menu-open')) {
				return;
			}
			app.classList.remove('md2-menu-open');
			if (backdrop) {
				backdrop.hidden = true;
			}
			openButton.setAttribute('aria-expanded', 'false');
			sidebar.setAttribute('aria-hidden', 'true');
			sidebar.removeAttribute('aria-modal');
			sidebar.removeAttribute('role');
			document.body.style.overflow = bodyOverflow;
			if (restoreFocus) {
				openButton.focus();
			}
		}

		function openMenu() {
			if (!media.matches) {
				return;
			}
			bodyOverflow = document.body.style.overflow;
			app.classList.add('md2-menu-open');
			if (backdrop) {
				backdrop.hidden = false;
			}
			openButton.setAttribute('aria-expanded', 'true');
			sidebar.setAttribute('aria-hidden', 'false');
			sidebar.setAttribute('role', 'dialog');
			sidebar.setAttribute('aria-modal', 'true');
			var items = focusableItems();
			if (items.length) {
				items[0].focus();
			}
			document.body.style.overflow = 'hidden';
		}

		openButton.addEventListener('click', openMenu);
		Array.prototype.forEach.call(closeButtons, function (button) {
			button.addEventListener('click', function () { closeMenu(true); });
		});
		Array.prototype.forEach.call(sidebar.querySelectorAll('a[href]'), function (link) {
			link.addEventListener('click', function () { closeMenu(false); });
		});

		document.addEventListener('keydown', function (event) {
			if (!app.classList.contains('md2-menu-open')) {
				return;
			}
			if (event.key === 'Escape') {
				event.preventDefault();
				closeMenu(true);
				return;
			}
			if (event.key === 'Tab') {
				var items = focusableItems();
				if (!items.length) {
					event.preventDefault();
					return;
				}
				var first = items[0];
				var last = items[items.length - 1];
				if (event.shiftKey && (document.activeElement === first || !sidebar.contains(document.activeElement))) {
					event.preventDefault();
					last.focus();
				} else if (!event.shiftKey && (document.activeElement === last || !sidebar.contains(document.activeElement))) {
					event.preventDefault();
					first.focus();
				}
			}
		});

		if (typeof media.addEventListener === 'function') {
			media.addEventListener('change', function (event) {
				if (!event.matches) {
					setDesktopState();
				} else if (!app.classList.contains('md2-menu-open')) {
					sidebar.setAttribute('aria-hidden', 'true');
				}
			});
		} else if (typeof media.addListener === 'function') {
			media.addListener(function (event) {
				if (!event.matches) {
					setDesktopState();
				} else if (!app.classList.contains('md2-menu-open')) {
					sidebar.setAttribute('aria-hidden', 'true');
				}
			});
		}

		if (media.matches) {
			sidebar.setAttribute('aria-hidden', 'true');
		}
	}

	function init() {
		document.querySelectorAll('[data-md2-app]').forEach(initApp);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

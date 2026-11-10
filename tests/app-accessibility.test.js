const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../plugin/assets/js/app.js'), 'utf8');

class EventTarget {
	constructor() { this.listeners = {}; }
	addEventListener(type, callback) {
		(this.listeners[type] ||= []).push(callback);
	}
	dispatch(type, event = {}) {
		(this.listeners[type] || []).forEach((callback) => callback(event));
	}
}

class Element extends EventTarget {
	constructor(document) {
		super();
		this.document = document;
		this.attributes = {};
		this.style = {};
		this.hidden = false;
		this.classList = {
			values: new Set(),
			add(value) { this.values.add(value); },
			remove(value) { this.values.delete(value); },
			contains(value) { return this.values.has(value); },
		};
	}
	setAttribute(name, value) { this.attributes[name] = String(value); }
	getAttribute(name) { return this.attributes[name] ?? null; }
	removeAttribute(name) { delete this.attributes[name]; }
	focus() { this.document.activeElement = this; }
	getClientRects() { return [{}]; }
	contains(element) { return this.focusable.includes(element); }
}

function fixture(mobile = true) {
	const document = new EventTarget();
	document.body = { style: { overflow: 'auto' } };
	document.activeElement = null;
	const app = new Element(document);
	const sidebar = new Element(document);
	const openButton = new Element(document);
	const closeButton = new Element(document);
	const backdrop = new Element(document);
	backdrop.hidden = true;
	const links = [new Element(document), new Element(document)];
	sidebar.focusable = [closeButton, ...links];
	sidebar.querySelectorAll = (selector) => selector === 'a[href]' ? links : sidebar.focusable;
	app.querySelector = (selector) => ({
		'.md2-sidebar': sidebar,
		'.md2-backdrop': backdrop,
		'[data-md2-open]': openButton,
	}[selector] || null);
	app.querySelectorAll = (selector) => selector === '[data-md2-close]' ? [closeButton, backdrop] : [];
	app.contains = (element) => element === sidebar || element === openButton;
	const media = new EventTarget();
	media.matches = mobile;
	media.change = (matches) => {
		media.matches = matches;
		media.dispatch('change', { matches });
	};
	const window = { matchMedia: () => media };
	document.querySelectorAll = () => [app];
	vm.runInNewContext(source, { document, window });
	return { app, sidebar, openButton, closeButton, backdrop, links, document, media };
}

test('mobile drawer exposes a modal, traps focus, restores focus and unlocks scrolling', () => {
	const { app, sidebar, openButton, closeButton, links, backdrop, document } = fixture();
	sidebar.setAttribute('aria-hidden', 'true');
	openButton.dispatch('click');
	assert.equal(app.classList.contains('md2-menu-open'), true);
	assert.equal(openButton.getAttribute('aria-expanded'), 'true');
	assert.equal(sidebar.getAttribute('role'), 'dialog');
	assert.equal(sidebar.getAttribute('aria-modal'), 'true');
	assert.equal(sidebar.getAttribute('aria-hidden'), 'false');
	assert.equal(document.activeElement, closeButton);
	assert.equal(document.body.style.overflow, 'hidden');
	assert.equal(backdrop.hidden, false);

	document.dispatch('keydown', { key: 'Tab', shiftKey: false, preventDefault() { this.prevented = true; } });
	assert.equal(document.activeElement, closeButton);
	document.dispatch('keydown', { key: 'Tab', shiftKey: true, preventDefault() { this.prevented = true; } });
	assert.equal(document.activeElement, links[1]);
	document.activeElement = links[1];
	document.dispatch('keydown', { key: 'Tab', shiftKey: false, preventDefault() { this.prevented = true; } });
	assert.equal(document.activeElement, closeButton);

	document.dispatch('keydown', { key: 'Escape', preventDefault() {} });
	assert.equal(app.classList.contains('md2-menu-open'), false);
	assert.equal(openButton.getAttribute('aria-expanded'), 'false');
	assert.equal(sidebar.getAttribute('aria-hidden'), 'true');
	assert.equal(sidebar.getAttribute('aria-modal'), null);
	assert.equal(document.body.style.overflow, 'auto');
	assert.equal(backdrop.hidden, true);
	assert.equal(document.activeElement, openButton);
});

test('close controls and desktop resize remove modal state', () => {
	const { app, sidebar, openButton, closeButton, backdrop, document, media } = fixture();
	sidebar.setAttribute('aria-hidden', 'true');
	openButton.dispatch('click');
	closeButton.dispatch('click');
	assert.equal(app.classList.contains('md2-menu-open'), false);
	assert.equal(document.activeElement, openButton);

	openButton.dispatch('click');
	backdrop.dispatch('click');
	assert.equal(app.classList.contains('md2-menu-open'), false);

	openButton.dispatch('click');
	media.change(false);
	assert.equal(app.classList.contains('md2-menu-open'), false);
	assert.equal(sidebar.getAttribute('role'), null);
	assert.equal(sidebar.getAttribute('aria-modal'), null);
	assert.equal(sidebar.getAttribute('aria-hidden'), null);
	assert.equal(document.body.style.overflow, 'auto');
});

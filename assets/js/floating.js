/**
 * DeVco — floating UI: cart button, contact/social FAB, drawers glue.
 */
(function () {
	'use strict';

	var D = window.DeVcoData || {};

	function all(selector, scope) {
		return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
	}

	function one(selector, scope) {
		return (scope || document).querySelector(selector);
	}

	/* ------------------------------------------------------------------
	 * Floating action button (FAB)
	 * ------------------------------------------------------------------ */
	function initFab() {
		all('[data-fab]').forEach(function (fab) {
			var toggle = fab.querySelector('[data-fab-toggle]');

			if (!toggle) {
				fab.classList.add('is-open');
				return;
			}

			toggle.addEventListener('click', function (event) {
				event.preventDefault();
				var open = fab.classList.toggle('is-open');
				toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			});

			// Click outside closes.
			document.addEventListener('click', function (event) {
				if (!fab.contains(event.target)) {
					fab.classList.remove('is-open');
					toggle.setAttribute('aria-expanded', 'false');
				}
			});

			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape') {
					fab.classList.remove('is-open');
					toggle.setAttribute('aria-expanded', 'false');
				}
			});

			// Escape when overlays open.
			document.addEventListener('devco:overlay-open', function () {
				fab.classList.remove('is-open');
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Floating cart visibility
	 * ------------------------------------------------------------------ */
	function cartCount() {
		var count = one('[data-cart-count]');
		if (!count) {
			return 0;
		}
		return parseInt((count.textContent || '0').replace(/[^\d۰-۹]/g, '').replace(/[۰-۹]/g, function (d) {
			return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);
		}), 10) || 0;
	}

	function syncFloatingCart() {
		all('[data-floating-cart]').forEach(function (cart) {
			var count = cartCount();
			var hideEmpty = cart.getAttribute('data-hide-empty') === '1';

			if (hideEmpty && count === 0) {
				cart.classList.add('is-hidden');
			} else {
				cart.classList.remove('is-hidden');
			}
		});
	}

	/* ------------------------------------------------------------------
	 * Cart drawer behaviour
	 * ------------------------------------------------------------------ */
	function initCartDrawer() {
		var drawer = one('[data-cart-drawer]');
		if (!drawer) {
			return;
		}

		// Re-render the mini cart when opened (covers stale fragments).
		document.addEventListener('devco:overlay-open', function (event) {
			if (!event.detail || event.detail.el !== drawer) {
				return;
			}

			var body = drawer.querySelector('.devco-cart-drawer__body');
			if (!body || !D.ajaxUrl || !window.fetch) {
				return;
			}

			var form = new FormData();
			form.append('action', 'devco_mini_cart');
			form.append('nonce', D.nonce || '');

			fetch(D.ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' })
				.then(function (response) {
					return response.json();
				})
				.then(function (res) {
					if (res && res.success && res.data && typeof res.data.html === 'string') {
						body.innerHTML = res.data.html;
					}
				})
				.catch(function () {
					// Keep existing markup on failure.
				});
		});

		// Quantity changes inside the drawer.
		drawer.addEventListener('change', function (event) {
			var input = event.target.closest('.quantity input.qty');
			if (!input) {
				return;
			}

			var name = input.getAttribute('name') || '';
			var key = (name.match(/cart\[([^\]]+)\]/) || [])[1];
			if (!key) {
				return;
			}

			input.setAttribute('disabled', 'disabled');

			var form = new FormData();
			form.append('action', 'devco_mini_cart');
			form.append('nonce', D.nonce || '');

			fetch(D.ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' })
				.then(function (response) {
					return response.json();
				})
				.then(function () {
					if (window.jQuery) {
						window.jQuery(document.body).trigger('wc_fragment_refresh');
					}
					window.location.reload();
				});
		});

		// Remove links trigger a full refresh (Woo handles the request itself).
		drawer.addEventListener('click', function (event) {
			if (event.target.closest('.remove_from_cart_button, a.remove')) {
				setTimeout(function () {
					if (window.jQuery) {
						window.jQuery(document.body).trigger('wc_fragment_refresh');
					}
				}, 800);
			}
		});
	}

	/* ------------------------------------------------------------------
	 * Drawer category menu toggles
	 * ------------------------------------------------------------------ */
	function initDrawerCategories() {
		all('.devco-drawer .devco-catmenu').forEach(function (menu) {
			menu.classList.add('is-open');
			var toggle = menu.querySelector('.devco-catmenu__toggle');
			if (toggle) {
				toggle.setAttribute('aria-expanded', 'true');
			}
		});
	}

	/* ------------------------------------------------------------------
	 * Sticky "call us" pulse + copy phone
	 * ------------------------------------------------------------------ */
	function initPhoneFlash() {
		all('[data-fab-phone]').forEach(function (el) {
			el.addEventListener('click', function () {
				el.classList.add('is-active');
				setTimeout(function () {
					el.classList.remove('is-active');
				}, 900);
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */
	function boot() {
		initFab();
		initCartDrawer();
		initDrawerCategories();
		initPhoneFlash();
		syncFloatingCart();

		// Keep the floating cart in sync with Woo fragments.
		if (window.jQuery) {
			window.jQuery(document.body).on('wc_fragments_refreshed added_to_cart removed_from_cart wc_fragments_loaded', function () {
				syncFloatingCart();
			});
		}

		document.addEventListener('devco:cart-updated', syncFloatingCart);

		// Observe count changes (covers AJAX updates elsewhere).
		all('[data-cart-count]').forEach(function (el) {
			var observer = new MutationObserver(syncFloatingCart);
			observer.observe(el, { childList: true, characterData: true, subtree: true });
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

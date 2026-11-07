/**
 * DeVco — core front-end behaviour.
 *
 * Sticky header, mobile drawer, menus, search + live search, modals,
 * quick view, mini-cart, fly-to-cart, quantity steppers, to-top, scroll
 * progress, newsletter and copy helpers. Vanilla JS, no build step.
 */
(function () {
	'use strict';

	var D = window.DeVcoData || {};
	var W = window.DeVcoWoo || {};
	var i18n = D.i18n || {};

	/* ------------------------------------------------------------------
	 * Utilities
	 * ------------------------------------------------------------------ */
	var Devco = {
		select: function (selector, scope) {
			return (scope || document).querySelector(selector);
		},
		selectAll: function (selector, scope) {
			return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
		},
		on: function (el, type, handler, options) {
			if (el) {
				el.addEventListener(type, handler, options || false);
			}
		},
		debounce: function (fn, wait) {
			var timer;
			return function () {
				var ctx = this;
				var args = arguments;
				clearTimeout(timer);
				timer = setTimeout(function () {
					fn.apply(ctx, args);
				}, wait || 250);
			};
		},
		toLatin: function (value) {
			return String(value == null ? '' : value)
				.replace(/[۰-۹]/g, function (d) {
					return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);
				})
				.replace(/[٠-٩]/g, function (d) {
					return '٠١٢٣٤٥٦٧٨٩'.indexOf(d);
				});
		},
		toPersian: function (value) {
			return String(value == null ? '' : value).replace(/[0-9]/g, function (d) {
				return '۰۱۲۳۴۵۶۷۸۹'[d];
			});
		},
		/**
		 * POST helper returning a promise resolved with the JSON body.
		 */
		post: function (action, data, opts) {
			opts = opts || {};
			var body = new FormData();
			body.append('action', action);
			body.append('nonce', opts.nonce || D.nonce || '');

			Object.keys(data || {}).forEach(function (key) {
				var value = data[key];
				if (value instanceof File || value instanceof Blob) {
					body.append(key, value);
				} else if (Array.isArray(value)) {
					value.forEach(function (item, i) {
						if (item && typeof item === 'object') {
							Object.keys(item).forEach(function (sub) {
								body.append(key + '[' + i + '][' + sub + ']', item[sub]);
							});
						} else {
							body.append(key + '[]', item);
						}
					});
				} else if (value && typeof value === 'object') {
					Object.keys(value).forEach(function (sub) {
						body.append(key + '[' + sub + ']', value[sub]);
					});
				} else if (value !== undefined && value !== null) {
					body.append(key, value);
				}
			});

			return fetch(opts.url || D.ajaxUrl || '', {
				method: 'POST',
				body: body,
				credentials: 'same-origin'
			}).then(function (response) {
				return response.json().catch(function () {
					return { success: false, data: { message: i18n.error || 'خطا' } };
				});
			});
		},
		lock: function (state) {
			document.body.classList.toggle('devco-locked', !!state);
		},
		/**
		 * Lightweight toast notification.
		 */
		toast: function (message, type) {
			if (!message) {
				return;
			}

			var node = document.createElement('div');
			node.className = 'devco-toast' + (type === 'error' ? ' devco-toast--error' : '');
			node.innerHTML = '<span class="devco-toast__icon">' + (type === 'error' ? '!' : '✓') + '</span><span></span>';
			node.lastChild.textContent = message;
			document.body.appendChild(node);

			requestAnimationFrame(function () {
				node.classList.add('is-visible');
			});

			setTimeout(function () {
				node.classList.remove('is-visible');
				setTimeout(function () {
					if (node.parentNode) {
						node.parentNode.removeChild(node);
					}
				}, 320);
			}, type === 'error' ? 4200 : 3000);
		},
		setLoading: function (el, state) {
			if (!el) {
				return;
			}
			el.classList.toggle('is-loading', !!state);
			if (state) {
				el.setAttribute('disabled', 'disabled');
			} else {
				el.removeAttribute('disabled');
			}
		}
	};

	window.Devco = Devco;

	/* ------------------------------------------------------------------
	 * Cart helpers
	 * ------------------------------------------------------------------ */
	function setCartCount(count) {
		if (typeof count === 'undefined' || count === null) {
			return;
		}

		var value = parseInt(count, 10);
		if (isNaN(value)) {
			return;
		}

		Devco.selectAll('[data-cart-count]').forEach(function (el) {
			el.textContent = D.persianNums ? Devco.toPersian(value) : String(value);
			el.classList.add('devco-cart-bump');
			setTimeout(function () {
				el.classList.remove('devco-cart-bump');
			}, 520);
		});

		Devco.selectAll('.js-devco-cart-open[data-count]').forEach(function (el) {
			el.setAttribute('data-count', value);
		});

		if (W && typeof W.cartCount !== 'undefined') {
			W.cartCount = value;
		}
	}

	function refreshCartDrawer() {
		var drawer = Devco.select('[data-cart-drawer]');
		if (!drawer || !D.ajaxUrl) {
			return Promise.resolve();
		}

		var body = drawer.querySelector('.devco-cart-drawer__body .widget_shopping_cart_content') || drawer.querySelector('.devco-cart-drawer__body');
		if (!body) {
			return Promise.resolve();
		}

		return Devco.post('devco_mini_cart', {}).then(function (res) {
			if (res && res.success && res.data) {
				if (typeof res.data.html === 'string') {
					body.innerHTML = res.data.html;
				}
				setCartCount(res.data.count);
			}
		});
	}

	function refreshFloatingCart() {
		if (!D.ajaxUrl) {
			return;
		}

		Devco.post('devco_mini_cart', {}).then(function (res) {
			if (res && res.success && res.data) {
				setCartCount(res.data.count);
			}
		});
	}

	window.devcoRefreshCart = function () {
		setCartCount(typeof W !== 'undefined' ? W.cartCount : null);
		refreshCartDrawer();
		refreshFloatingCart();
	};

	/* ------------------------------------------------------------------
	 * Overlay manager (drawer / cart drawer / modal / fitting)
	 * ------------------------------------------------------------------ */
	var activeOverlays = [];

	function openOverlay(el) {
		if (!el) {
			return;
		}

		el.classList.add('is-open');
		el.setAttribute('aria-hidden', 'false');
		activeOverlays.push(el);
		Devco.lock(true);
		document.dispatchEvent(new CustomEvent('devco:overlay-open', { detail: { el: el } }));
	}

	function closeOverlay(el) {
		if (!el) {
			return;
		}

		el.classList.remove('is-open');
		el.setAttribute('aria-hidden', 'true');
		activeOverlays = activeOverlays.filter(function (item) {
			return item !== el;
		});

		if (!activeOverlays.length) {
			Devco.lock(false);
		}

		document.dispatchEvent(new CustomEvent('devco:overlay-close', { detail: { el: el } }));
	}

	function closeAllOverlays() {
		activeOverlays.slice().forEach(closeOverlay);
		Devco.selectAll('.devco-search.is-open').forEach(function (el) {
			el.classList.remove('is-open');
		});
	}

	window.devcoOpenOverlay = openOverlay;
	window.devcoCloseOverlay = closeOverlay;

	function openDrawer() {
		var drawer = Devco.select('[data-drawer]');
		if (drawer) {
			openOverlay(drawer);
			var focusable = drawer.querySelector('input, a, button');
			if (focusable) {
				setTimeout(function () {
					focusable.focus({ preventScroll: true });
				}, 320);
			}
		}
	}

	function openCartDrawer() {
		var drawer = Devco.select('[data-cart-drawer]');
		if (drawer) {
			openOverlay(drawer);
			refreshCartDrawer();
			return;
		}

		if (D.cartUrl) {
			window.location.href = D.cartUrl;
		}
	}

	window.devcoOpenCart = openCartDrawer;

	/* ------------------------------------------------------------------
	 * Global click delegation
	 * ------------------------------------------------------------------ */
	document.addEventListener('click', function (event) {
		var target = event.target;

		// Drawer.
		var drawerOpen = target.closest('.js-devco-drawer-open');
		if (drawerOpen) {
			event.preventDefault();
			openDrawer();
			return;
		}

		if (target.closest('[data-drawer-close]')) {
			closeOverlay(target.closest('[data-drawer]'));
			return;
		}

		// Cart drawer.
		if (target.closest('.js-devco-cart-open, [data-open-cart]')) {
			event.preventDefault();
			openCartDrawer();
			return;
		}

		if (target.closest('[data-cart-close]')) {
			closeOverlay(target.closest('[data-cart-drawer]'));
			return;
		}

		// Modals.
		if (target.closest('[data-modal-close]')) {
			closeOverlay(target.closest('.devco-modal'));
			return;
		}

		// Size chart.
		if (target.closest('.js-devco-size-chart')) {
			event.preventDefault();
			openOverlay(Devco.select('[data-size-chart]'));
			return;
		}

		// Quick view.
		var quick = target.closest('.js-devco-quick-view, [data-quick-view]');
		if (quick && quick.getAttribute('data-product-id')) {
			event.preventDefault();
			openQuickView(quick.getAttribute('data-product-id'));
			return;
		}

		// Quick add.
		var add = target.closest('.js-devco-quick-add');
		if (add && !add.closest('.devco-modal')) {
			event.preventDefault();
			quickAdd(add);
			return;
		}

		// Buy now.
		var buy = target.closest('.js-devco-buy-now');
		if (buy) {
			event.preventDefault();
			buyNow(buy);
			return;
		}

		// Wishlist.
		var wish = target.closest('.js-devco-wishlist');
		if (wish) {
			event.preventDefault();
			toggleWishlist(wish);
			return;
		}

		// Quantity steppers.
		var step = target.closest('[data-qty]');
		if (step) {
			event.preventDefault();
			stepQuantity(step);
			return;
		}

		// Filters drawer (shop sidebar).
		if (target.closest('.js-devco-filters-open')) {
			event.preventDefault();
			var sidebar = Devco.select('.devco-shop-sidebar');
			if (sidebar) {
				sidebar.classList.add('is-open');
				Devco.lock(true);
			}
			return;
		}

		if (target.closest('[data-filters-close]')) {
			var sb = Devco.select('.devco-shop-sidebar');
			if (sb) {
				sb.classList.remove('is-open');
				Devco.lock(activeOverlays.length > 0);
			}
			return;
		}

		// Copy shortcode / text.
		var copy = target.closest('[data-copy]');
		if (copy) {
			event.preventDefault();
			copyText(copy.getAttribute('data-copy'));
			return;
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' || event.keyCode === 27) {
			closeAllOverlays();
		}
	});

	/* ------------------------------------------------------------------
	 * Menus
	 * ------------------------------------------------------------------ */
	function initMenus() {
		Devco.selectAll('.devco-nav[data-mega-cols]').forEach(function (nav) {
			nav.style.setProperty('--devco-mega-cols', nav.getAttribute('data-mega-cols') || 4);
		});

		// Ensure mega columns flow through the CSS variable on each mega panel.
		Devco.selectAll('.devco-nav--mode-mega .devco-menu > li.menu-item-has-children > .sub-menu').forEach(function (sub) {
			var cols = sub.children.length > 6 ? 4 : 3;
			sub.style.setProperty('--devco-mega-cols', sub.closest('[data-mega-cols]') ? sub.closest('[data-mega-cols]').getAttribute('data-mega-cols') : cols);
		});

		// Touch / click toggles for drawer + flyout menus.
		Devco.selectAll('.devco-menu--drawer li.menu-item-has-children > a, .devco-nav--mode-flyout .devco-menu li.menu-item-has-children > a').forEach(function (link) {
			link.addEventListener('click', function (event) {
				var li = link.parentNode;
				var sub = li.querySelector('.sub-menu');

				if (sub && !li.classList.contains('is-open')) {
					event.preventDefault();
					li.classList.add('is-open');
				} else if (sub) {
					li.classList.remove('is-open');
				}
			});
		});

		// Flyout toggle.
		Devco.selectAll('.js-devco-menu-toggle').forEach(function (btn) {
			btn.addEventListener('click', function (event) {
				event.preventDefault();
				var nav = Devco.select('.devco-nav--mode-flyout');
				if (nav) {
					nav.classList.toggle('is-open');
					btn.setAttribute('aria-expanded', nav.classList.contains('is-open') ? 'true' : 'false');
				}
				var cm = Devco.select('.devco-catmenu');
				if (cm) {
					cm.classList.toggle('is-open');
				}
			});
		});

		// Category menu.
		Devco.selectAll('[data-catmenu]').forEach(function (menu) {
			var toggle = menu.querySelector('.devco-catmenu__toggle');
			Devco.on(toggle, 'click', function (event) {
				event.preventDefault();
				menu.classList.toggle('is-open');
				toggle.setAttribute('aria-expanded', menu.classList.contains('is-open') ? 'true' : 'false');
			});

			menu.addEventListener('mouseleave', function () {
				if (window.innerWidth > 991) {
					menu.classList.remove('is-open');
				}
			});
		});

		// Account dropdown.
		Devco.selectAll('.devco-account').forEach(function (account) {
			var trigger = account.querySelector('[data-account-toggle]') || account.querySelector('button');
			Devco.on(trigger, 'click', function (event) {
				if (!account.querySelector('.devco-account__dropdown')) {
					return;
				}
				event.preventDefault();
				account.classList.toggle('is-open');
			});

			document.addEventListener('click', function (event) {
				if (!account.contains(event.target)) {
					account.classList.remove('is-open');
				}
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Search + live search
	 * ------------------------------------------------------------------ */
	function initSearch() {
		Devco.selectAll('.devco-search').forEach(function (root) {
			var toggle = root.querySelector('.devco-search__toggle');
			var input = root.querySelector('.devco-search__input');

			Devco.on(toggle, 'click', function (event) {
				event.preventDefault();
				root.classList.toggle('is-open');
				toggle.setAttribute('aria-expanded', root.classList.contains('is-open') ? 'true' : 'false');
				if (root.classList.contains('is-open') && input) {
					setTimeout(function () {
						input.focus({ preventScroll: true });
					}, 60);
				}
			});

			if (!input || root.getAttribute('data-ajax') === 'no' || !D.isWoo) {
				return;
			}

			var wrap = root.querySelector('.devco-search__results');
			if (!wrap) {
				return;
			}

			var run = Devco.debounce(function () {
				var term = input.value.trim();
				if (term.length < 2) {
					wrap.innerHTML = '';
					wrap.classList.remove('is-open');
					return;
				}

				wrap.classList.add('is-open');
				wrap.innerHTML = '<div class="devco-search__loading"><span class="devco-spinner"></span></div>';

				Devco.post('devco_live_search', { term: term }).then(function (res) {
					if (res && res.success && res.data) {
						wrap.innerHTML = res.data.html || '';
						wrap.classList.toggle('is-open', !!res.data.html);
					} else {
						wrap.innerHTML = '';
					}
				});
			}, 320);

			input.addEventListener('input', run);

			input.addEventListener('keydown', function (event) {
				if (event.key === 'Escape') {
					wrap.classList.remove('is-open');
					input.blur();
				}
			});

			document.addEventListener('click', function (event) {
				if (!root.contains(event.target)) {
					wrap.classList.remove('is-open');
				}
			});
		});

		// Mobile search bar toggle.
		Devco.selectAll('.js-devco-mobile-search').forEach(function (btn) {
			btn.addEventListener('click', function (event) {
				event.preventDefault();
				var bar = Devco.select('.devco-mobile-search');
				if (bar) {
					bar.classList.toggle('is-open');
					var field = bar.querySelector('input[type="search"]');
					if (field && bar.classList.contains('is-open')) {
						field.focus({ preventScroll: true });
					}
					return;
				}

				var toggle = Devco.select('[data-search-style="popup"] .devco-search__toggle, [data-search-style="icon"] .devco-search__toggle');
				if (toggle) {
					toggle.click();
				}
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Quick view
	 * ------------------------------------------------------------------ */
	function openQuickView(productId) {
		var modal = Devco.select('[data-quick-view]');
		if (!modal || !D.ajaxUrl) {
			return;
		}

		var content = modal.querySelector('[data-quick-view-content]');
		if (content) {
			content.innerHTML = '<div class="devco-modal__loading"><span class="devco-spinner"></span></div>';
		}

		openOverlay(modal);

		Devco.post('devco_quick_view', { product_id: productId }).then(function (res) {
			if (!content) {
				return;
			}

			if (res && res.success && res.data && res.data.html) {
				content.innerHTML = res.data.html;
				if (window.jQuery) {
					window.jQuery(document.body).trigger('wc_variation_form');
					window.jQuery(document.body).trigger('devco_quick_view_loaded');
				}
			} else {
				content.innerHTML = '<p class="devco-modal__hint">' + (res && res.data && res.data.message ? res.data.message : i18n.error) + '</p>';
			}
		});
	}

	/* ------------------------------------------------------------------
	 * Add to cart
	 * ------------------------------------------------------------------ */
	function flyToCart(source) {
		var target = Devco.select('[data-cart-count]') || Devco.select('.devco-fc__icon');
		if (!target || !source) {
			return;
		}

		var img = source.closest('.devco-product-card, .devco-showcase, .product, .devco-quickview') || source;
		var thumb = img.querySelector('img');
		if (!thumb) {
			return;
		}

		var from = thumb.getBoundingClientRect();
		var to = target.getBoundingClientRect();

		var layer = document.createElement('div');
		layer.className = 'devco-fly-layer';
		document.body.appendChild(layer);

		var clone = document.createElement('img');
		clone.src = thumb.currentSrc || thumb.src;
		clone.className = 'devco-fly-img';
		clone.style.left = from.left + 'px';
		clone.style.top = from.top + 'px';
		clone.style.width = Math.min(from.width, 120) + 'px';
		clone.style.height = Math.min(from.height, 120) + 'px';
		layer.appendChild(clone);

		var dx = to.left + to.width / 2 - (from.left + Math.min(from.width, 120) / 2);
		var dy = to.top + to.height / 2 - (from.top + Math.min(from.height, 120) / 2);

		requestAnimationFrame(function () {
			clone.style.transform = 'translate(' + dx + 'px,' + dy + 'px) scale(.18) rotate(12deg)';
			clone.style.opacity = '0.2';
		});

		for (var i = 0; i < 6; i++) {
			var dot = document.createElement('span');
			dot.className = 'devco-fly-burst';
			dot.style.left = from.left + from.width / 2 + 'px';
			dot.style.top = from.top + from.height / 2 + 'px';
			dot.style.setProperty('--dx', (Math.random() * 80 - 40).toFixed(0) + 'px');
			dot.style.setProperty('--dy', (Math.random() * 80 - 40).toFixed(0) + 'px');
			layer.appendChild(dot);
		}

		setTimeout(function () {
			if (layer.parentNode) {
				layer.parentNode.removeChild(layer);
			}
		}, 900);
	}

	function quickAdd(button) {
		var id = button.getAttribute('data-product-id');
		if (!id || !D.ajaxUrl) {
			return;
		}

		button.classList.add('is-loading');
		flyToCart(button);

		Devco.post('devco_buy_now', { product_id: id, quantity: 1, skip_cart: 0, ajax_add: 1 }).then(function (res) {
			button.classList.remove('is-loading');

			if (res && res.success) {
				var count = res.data && res.data.count ? res.data.count : null;
				setCartCount(count);
				refreshCartDrawer();
				Devco.toast(i18n.added || 'به سبد اضافه شد');
				if (window.jQuery && W && W.ajaxUrl) {
					window.jQuery(document.body).trigger('wc_fragment_refresh');
				}
			} else {
				Devco.toast(res && res.data && res.data.message ? res.data.message : i18n.error, 'error');
				if (res && res.data && res.data.redirect) {
					window.location.href = res.data.redirect;
				}
			}
		});
	}

	function buyNow(button) {
		var id = button.getAttribute('data-product-id');
		if (!id || !D.ajaxUrl) {
			return;
		}

		var qtyField = document.querySelector('form.cart input.qty, .quantity input.qty');
		var qty = qtyField ? parseInt(Devco.toLatin(qtyField.value), 10) || 1 : 1;
		var skip = button.getAttribute('data-skip-cart') === '1' ? 1 : 0;

		flyToCart(button);

		Devco.post('devco_buy_now', { product_id: id, quantity: qty, skip_cart: skip }).then(function (res) {
			if (res && res.success && res.data && res.data.redirect) {
				window.location.href = res.data.redirect;
			} else {
				Devco.toast(res && res.data && res.data.message ? res.data.message : i18n.error, 'error');
			}
		});
	}

	// Native WooCommerce add-to-cart forms: fire the fly animation + refresh drawer.
	if (window.jQuery) {
		window.jQuery(document.body).on('added_to_cart', function (event, fragments, hash, $button) {
			if ($button && $button.length) {
				flyToCart($button[0]);
			}
			refreshCartDrawer();
			refreshFloatingCart();
			Devco.toast(i18n.added || 'به سبد اضافه شد');
		});

		window.jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function () {
			refreshFloatingCart();
		});
	}

	/* ------------------------------------------------------------------
	 * Wishlist (stored in localStorage)
	 * ------------------------------------------------------------------ */
	function wishlist() {
		try {
			return JSON.parse(window.localStorage.getItem('devco_wishlist') || '[]');
		} catch (e) {
			return [];
		}
	}

	function toggleWishlist(button) {
		var id = String(button.getAttribute('data-product-id') || '');
		if (!id) {
			return;
		}

		var list = wishlist();
		var index = list.indexOf(id);

		if (index > -1) {
			list.splice(index, 1);
			button.classList.remove('is-active');
		} else {
			list.push(id);
			button.classList.add('is-active');
		}

		try {
			window.localStorage.setItem('devco_wishlist', JSON.stringify(list));
		} catch (e) {
			// Storage unavailable — ignore silently.
		}

		Devco.selectAll('[data-wishlist-count]').forEach(function (el) {
			el.textContent = D.persianNums ? Devco.toPersian(list.length) : String(list.length);
		});
	}

	function initWishlist() {
		var list = wishlist();

		Devco.selectAll('.js-devco-wishlist[data-product-id]').forEach(function (button) {
			if (list.indexOf(String(button.getAttribute('data-product-id'))) > -1) {
				button.classList.add('is-active');
			}
		});

		Devco.selectAll('[data-wishlist-count]').forEach(function (el) {
			el.textContent = D.persianNums ? Devco.toPersian(list.length) : String(list.length);
		});
	}

	/* ------------------------------------------------------------------
	 * Quantity steppers
	 * ------------------------------------------------------------------ */
	function stepQuantity(button) {
		var input = document.querySelector('.quantity input.qty, form.cart input.qty, [data-qty-input]');
		if (!input) {
			input = button.parentNode.querySelector('input');
		}
		if (!input) {
			return;
		}

		var current = parseFloat(Devco.toLatin(input.value)) || 1;
		var step = parseFloat(input.getAttribute('step')) || 1;
		var min = parseFloat(input.getAttribute('min')) || 1;
		var max = parseFloat(input.getAttribute('max')) || 0;
		var next = button.getAttribute('data-qty') === 'plus' ? current + step : current - step;

		if (next < min) {
			next = min;
		}
		if (max && next > max) {
			next = max;
		}

		input.value = next;
		input.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function initQuantityControls() {
		Devco.selectAll('.quantity, form.cart .quantity').forEach(function (wrap) {
			if (wrap.querySelector('.devco-qty-btn')) {
				return;
			}

			var input = wrap.querySelector('input.qty');
			if (!input) {
				return;
			}

			var minus = document.createElement('button');
			minus.type = 'button';
			minus.className = 'devco-qty-btn';
			minus.setAttribute('data-qty', 'minus');
			minus.setAttribute('aria-label', 'کاهش');
			minus.innerHTML = '−';

			var plus = document.createElement('button');
			plus.type = 'button';
			plus.className = 'devco-qty-btn';
			plus.setAttribute('data-qty', 'plus');
			plus.setAttribute('aria-label', 'افزایش');
			plus.innerHTML = '+';

			wrap.insertBefore(minus, input);
			wrap.appendChild(plus);
		});
	}

	/* ------------------------------------------------------------------
	 * Copy to clipboard
	 * ------------------------------------------------------------------ */
	function copyText(text) {
		if (!text) {
			return;
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(function () {
				Devco.toast(i18n.copy || 'کپی شد');
			});
			return;
		}

		var helper = document.createElement('textarea');
		helper.value = text;
		helper.style.position = 'fixed';
		helper.style.opacity = '0';
		document.body.appendChild(helper);
		helper.select();
		try {
			document.execCommand('copy');
			Devco.toast(i18n.copy || 'کپی شد');
		} catch (e) {
			// Ignore.
		}
		document.body.removeChild(helper);
	}

	/* ------------------------------------------------------------------
	 * Newsletter
	 * ------------------------------------------------------------------ */
	function initNewsletter() {
		Devco.selectAll('[data-newsletter]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();

				var input = form.querySelector('input[type="email"]');
				var message = form.querySelector('[data-newsletter-message]');
				var button = form.querySelector('button[type="submit"], button');

				if (!input || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(input.value)) {
					if (message) {
						message.textContent = 'ایمیل را درست وارد کنید.';
					}
					return;
				}

				Devco.setLoading(button, true);

				Devco.post('devco_newsletter', { email: input.value }).then(function (res) {
					Devco.setLoading(button, false);

					if (res && res.success) {
						if (message) {
							message.textContent = res.data.message || 'سپاس!';
							message.classList.add('is-success');
						}
						form.reset();
					} else if (message) {
						message.textContent = res && res.data && res.data.message ? res.data.message : i18n.error;
						message.classList.add('is-error');
					}
				});
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Header / scroll effects
	 * ------------------------------------------------------------------ */
	function initHeader() {
		var header = Devco.select('[data-header]');
		var progress = Devco.select('[data-scroll-progress]');
		var toTop = Devco.select('.js-devco-to-top');
		var lastY = window.pageYOffset;
		var ticking = false;

		function update() {
			var y = window.pageYOffset;

			// Sticky state.
			if (header) {
				header.classList.toggle('is-scrolled', y > 10);

				var hide = Devco.select('.devco-mobile-header');
				var mobileHide = hide && hide.classList.contains('is-sticky-hide');

				if (header.classList.contains('is-sticky')) {
					if (window.innerWidth > 991) {
						header.classList.toggle('is-compact', y > 120);
					}
				}

				if (mobileHide && window.innerWidth <= 991) {
					hide.classList.toggle('is-hidden', y > lastY && y > 140);
				}
			}

			// Scroll progress.
			if (progress) {
				var height = document.documentElement.scrollHeight - window.innerHeight;
				var ratio = height > 0 ? (y / height) * 100 : 0;
				progress.style.width = Math.min(100, Math.max(0, ratio)) + '%';
			}

			// To top.
			if (toTop) {
				toTop.classList.toggle('is-visible', y > 420);
			}

			lastY = y;
			ticking = false;
		}

		window.addEventListener('scroll', function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(update);
			}
		}, { passive: true });

		Devco.on(toTop, 'click', function (event) {
			event.preventDefault();
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});

		update();
	}

	/* ------------------------------------------------------------------
	 * Preloader
	 * ------------------------------------------------------------------ */
	function initPreloader() {
		var preloader = Devco.select('[data-preloader]');
		if (!preloader) {
			return;
		}

		var hide = function () {
			preloader.classList.add('is-hidden');
			setTimeout(function () {
				if (preloader.parentNode) {
					preloader.parentNode.removeChild(preloader);
				}
			}, 620);
		};

		if (document.readyState === 'complete') {
			setTimeout(hide, 240);
		} else {
			window.addEventListener('load', function () {
				setTimeout(hide, 240);
			});
		}

		// Safety net.
		setTimeout(hide, 4200);
	}

	/* ------------------------------------------------------------------
	 * Reveal on scroll
	 * ------------------------------------------------------------------ */
	function initReveal() {
		var items = Devco.selectAll('[data-reveal], .devco-reveal');
		if (!items.length) {
			return;
		}

		if (!('IntersectionObserver' in window)) {
			items.forEach(function (el) {
				el.classList.add('is-visible');
			});
			return;
		}

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

		items.forEach(function (el) {
			observer.observe(el);
		});
	}

	/* ------------------------------------------------------------------
	 * Carousels
	 * ------------------------------------------------------------------ */
	function initCarousels() {
		Devco.selectAll('[data-carousel]').forEach(function (root) {
			var track = root.querySelector('.devco-carousel__track');
			if (!track) {
				return;
			}

			var slides = Array.prototype.slice.call(track.children);
			if (slides.length < 2) {
				return;
			}

			var prev = root.querySelector('[data-carousel-prev]');
			var next = root.querySelector('[data-carousel-next]');
			var dotsWrap = root.querySelector('.devco-carousel__dots');
			var index = 0;

			function perView() {
				return Math.max(1, parseInt(root.getAttribute('data-cols'), 10) || 4);
			}

			function maxIndex() {
				return Math.max(0, slides.length - perView());
			}

			function dots() {
				if (!dotsWrap) {
					return;
				}
				dotsWrap.innerHTML = '';
				for (var i = 0; i <= maxIndex(); i++) {
					var dot = document.createElement('button');
					dot.type = 'button';
					dot.setAttribute('aria-label', 'اسلاید ' + (i + 1));
					dot.addEventListener('click', function (idx) {
						return function () {
							index = idx;
							render();
						};
					}(i));
					dotsWrap.appendChild(dot);
				}
			}

			function render() {
				index = Math.min(Math.max(0, index), maxIndex());
				var slide = slides[0].getBoundingClientRect().width + 16;
				track.style.transform = 'translateX(' + (index * slide * (document.dir === 'rtl' ? 1 : -1)) + 'px)';

				if (prev) {
					prev.disabled = index <= 0;
				}
				if (next) {
					next.disabled = index >= maxIndex();
				}

				Devco.selectAll('button', dotsWrap).forEach(function (dot, i) {
					dot.classList.toggle('is-active', i === index);
				});
			}

			Devco.on(prev, 'click', function () {
				index -= 1;
				render();
			});

			Devco.on(next, 'click', function () {
				index += 1;
				render();
			});

			window.addEventListener('resize', Devco.debounce(function () {
				dots();
				render();
			}, 220));

			dots();
			render();

			// Autoplay.
			var autoplay = parseInt(root.getAttribute('data-autoplay'), 10);
			if (autoplay > 0) {
				setInterval(function () {
					index = index >= maxIndex() ? 0 : index + 1;
					render();
				}, Math.max(1500, autoplay));
			}
		});
	}

	/* ------------------------------------------------------------------
	 * Smooth anchors
	 * ------------------------------------------------------------------ */
	function initAnchors() {
		Devco.selectAll('a[href^="#"]').forEach(function (link) {
			link.addEventListener('click', function (event) {
				var id = link.getAttribute('href');
				if (!id || id === '#' || id.length < 2) {
					return;
				}

				var target = document.getElementById(id.slice(1));
				if (!target) {
					return;
				}

				event.preventDefault();
				target.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */
	function boot() {
		initPreloader();
		initHeader();
		initMenus();
		initSearch();
		initQuantityControls();
		initWishlist();
		initNewsletter();
		initReveal();
		initCarousels();
		initAnchors();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	// Re-init dynamic markup (Elementor editor / AJAX content).
	document.addEventListener('devco:refresh', function () {
		initQuantityControls();
		initWishlist();
		initCarousels();
	});
})();

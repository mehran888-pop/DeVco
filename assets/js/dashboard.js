/**
 * DeVco — customer dashboard: order tracking, measurements, wishlist.
 */
(function () {
	'use strict';

	var D = window.DeVcoData || {};
	var i18n = D.i18n || {};

	function all(sel, scope) {
		return Array.prototype.slice.call((scope || document).querySelectorAll(sel));
	}

	function one(sel, scope) {
		return (scope || document).querySelector(sel);
	}

	function toast(message, type) {
		if (window.Devco && window.Devco.toast) {
			window.Devco.toast(message, type);
		}
	}

	function setBusy(button, state) {
		if (!button) {
			return;
		}
		button.classList.toggle('is-loading', state);
		button.disabled = state;
	}

	/* ------------------------------------------------------------------
	 * Order tracking
	 * ------------------------------------------------------------------ */
	function initTracking() {
		all('[data-track-form]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();

				var orderInput = one('input[name="order_id"]', form);
				var contactInput = one('input[name="contact"]', form);
				var message = one('[data-track-message]', form);
				var button = one('button[type="submit"]', form);
				var resultBox = one('[data-track-result]', form.parentNode);

				var orderId = orderInput ? orderInput.value.replace(/[^\d۰-۹]/g, '').replace(/[۰-۹]/g, function (d) {
					return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);
				}) : '';

				var contact = contactInput ? contactInput.value.trim() : '';

				if (!orderId || !contact) {
					if (message) {
						message.textContent = 'شماره سفارش و اطلاعات تماس را وارد کنید.';
						message.className = 'devco-track-form__message is-error';
					}
					return;
				}

				setBusy(button, true);
				if (message) {
					message.textContent = '';
					message.className = 'devco-track-form__message';
				}

				window.Devco.post('devco_track_order', { order_id: orderId, contact: contact }).then(function (res) {
					setBusy(button, false);

					if (res && res.success && res.data) {
						if (!resultBox) {
							resultBox = document.createElement('div');
							resultBox.setAttribute('data-track-result', '');
							form.parentNode.insertBefore(resultBox, form.nextSibling);
						}
						resultBox.innerHTML = res.data.html || '';
					} else if (message) {
						message.textContent = res && res.data && res.data.message ? res.data.message : i18n.error;
						message.className = 'devco-track-form__message is-error';
					}
				}).catch(function () {
					setBusy(button, false);
					if (message) {
						message.textContent = i18n.error;
						message.className = 'devco-track-form__message is-error';
					}
				});
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Measurements
	 * ------------------------------------------------------------------ */
	function initMeasurements() {
		all('[data-measure-form]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();

				var message = one('[data-measure-message]', form);
				var button = one('button[type="submit"]', form);
				var payload = {};

				all('[data-bm-input]', form).forEach(function (input) {
					var key = input.getAttribute('data-bm-input');
					var value = input.value.replace(/[^\d.]/g, '');
					if (value) {
						payload[key] = value;
					}
				});

				if (!Object.keys(payload).length) {
					if (message) {
						message.textContent = 'حداقل یکی از اندازه‌ها را وارد کنید.';
						message.className = 'devco-measure-form__message is-error';
					}
					return;
				}

				setBusy(button, true);

				window.Devco.post('devco_save_measurements', { measurements: payload }).then(function (res) {
					setBusy(button, false);

					if (message) {
						message.textContent = res && res.success
							? (res.data && res.data.message) || 'ذخیره شد.'
							: (res && res.data && res.data.message) || i18n.error;
						message.className = 'devco-measure-form__message ' + (res && res.success ? 'is-success' : 'is-error');
					}

					if (res && res.success) {
						toast((res.data && res.data.message) || 'اندازه‌ها ذخیره شد.');
					}
				}).catch(function () {
					setBusy(button, false);
					if (message) {
						message.textContent = i18n.error;
						message.className = 'devco-measure-form__message is-error';
					}
				});
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Wishlist page
	 * ------------------------------------------------------------------ */
	function renderWishlist() {
		var container = one('[data-wishlist-page]');
		if (!container) {
			return;
		}

		var ids = [];

		try {
			ids = JSON.parse(window.localStorage.getItem('devco_wishlist') || '[]');
		} catch (e) {
			ids = [];
		}

		ids = ids.filter(function (id) {
			return /^\d+$/.test(String(id));
		}).slice(0, 12);

		if (!ids.length) {
			return;
		}

		container.innerHTML = '<div class="devco-panel__empty"><span class="devco-spinner"></span></div>';

		var cards = [];

		var requests = ids.map(function (id) {
			return window.Devco.post('devco_quick_view', { product_id: id }).then(function (res) {
				if (!res || !res.success || !res.data || !res.data.html) {
					return null;
				}

				var temp = document.createElement('div');
				temp.innerHTML = res.data.html;

				var title = one('.devco-quickview__title', temp);
				var image = one('img', temp);
				var price = one('.devco-quickview__price', temp);
				var link = one('.devco-quickview__link', temp);

				if (!title) {
					return null;
				}

				return {
					id: id,
					title: title.textContent,
					image: image ? image.getAttribute('src') : '',
					price: price ? price.innerHTML : '',
					url: link ? link.getAttribute('href') : '#'
				};
			}).catch(function () {
				return null;
			});
		});

		Promise.all(requests).then(function (results) {
			results.forEach(function (item) {
				if (item) {
					cards.push(item);
				}
			});

			if (!cards.length) {
				container.innerHTML = '<p class="devco-panel__empty" data-wishlist-empty>لیست علاقه‌مندی‌های شما خالی است.</p>';
				return;
			}

			var html = cards.map(function (item) {
				return '<article class="devco-product-card">' +
					'<div class="devco-product-card__inner">' +
					'<div class="devco-product-card__media"><a class="devco-product-card__link" href="' + item.url + '">' +
					(item.image ? '<img class="devco-product-card__img" src="' + item.image + '" alt="" loading="lazy" />' : '') +
					'</a>' +
					'<div class="devco-product-card__actions">' +
					'<button type="button" class="devco-card-action js-devco-wishlist is-active" data-product-id="' + item.id + '" aria-label="حذف از علاقه‌مندی‌ها">×</button>' +
					'<button type="button" class="devco-card-action devco-card-action--cart js-devco-quick-add" data-product-id="' + item.id + '" aria-label="افزودن به سبد">＋</button>' +
					'</div></div>' +
					'<div class="devco-product-card__body">' +
					'<h3 class="devco-product-card__title"><a href="' + item.url + '">' + item.title + '</a></h3>' +
					'<div class="devco-product-card__price">' + item.price + '</div>' +
					'</div></div></article>';
			}).join('');

			container.innerHTML = html;

			// Removing an item updates the page immediately.
			all('.js-devco-wishlist', container).forEach(function (button) {
				button.addEventListener('click', function () {
					var card = button.closest('.devco-product-card');
					if (card) {
						card.remove();
					}
					if (!one('.devco-product-card', container)) {
						container.innerHTML = '<p class="devco-panel__empty" data-wishlist-empty>لیست علاقه‌مندی‌های شما خالی است.</p>';
					}
				});
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Mobile tab bar: mark active tab from the query string.
	 * ------------------------------------------------------------------ */
	function initTabs() {
		var params = new URLSearchParams(window.location.search);
		var tab = params.get('devco_tab');

		if (!tab) {
			return;
		}

		all('.devco-dash__mobile-item, .devco-dash__nav-item').forEach(function (item) {
			var href = item.getAttribute('href') || '';
			item.classList.toggle('is-active', href.indexOf('devco_tab=' + tab) > -1);
		});
	}

	/* ------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */
	function init() {
		if (!window.Devco) {
			return;
		}

		initTracking();
		initMeasurements();
		initTabs();
		renderWishlist();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	document.addEventListener('devco:refresh', init);
})();

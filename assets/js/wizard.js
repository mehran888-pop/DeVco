/**
 * DeVco — first-run setup wizard (plugins, demo import, finish).
 */
(function () {
	'use strict';

	var A = window.DeVcoAdmin || {};
	var nonce = A.nonce || '';

	function all(sel, scope) {
		return Array.prototype.slice.call((scope || document).querySelectorAll(sel));
	}

	function one(sel, scope) {
		return (scope || document).querySelector(sel);
	}

	function post(action, data) {
		var body = new FormData();
		body.append('action', action);
		body.append('nonce', nonce);

		Object.keys(data || {}).forEach(function (key) {
			body.append(key, data[key]);
		});

		return fetch(A.ajaxUrl || window.ajaxurl || '', {
			method: 'POST',
			body: body,
			credentials: 'same-origin'
		}).then(function (response) {
			return response.json().catch(function () {
				return { success: false, data: { message: 'پاسخ نامعتبر از سرور.' } };
			});
		});
	}

	function say(text, ok) {
		all('[data-wizard-message]').forEach(function (box) {
			box.textContent = text || '';
			box.classList.remove('is-error', 'is-success');
			if (text) {
				box.classList.add(ok ? 'is-success' : 'is-error');
			}
		});
	}

	function busy(button, state) {
		if (!button) {
			return;
		}
		button.classList.toggle('is-loading', state);
		button.disabled = state;
		var spinner = one('.devco-spinner', button);
		if (spinner) {
			spinner.hidden = !state;
		}
	}

	/* ------------------------------------------------------------------
	 * Steps
	 * ------------------------------------------------------------------ */
	function go(step) {
		all('[data-wpanel]').forEach(function (panel) {
			var match = parseInt(panel.getAttribute('data-wpanel'), 10) === step;
			panel.hidden = !match;
			panel.classList.toggle('is-active', match);
		});

		all('[data-wstep]').forEach(function (chip) {
			var index = parseInt(chip.getAttribute('data-wstep'), 10);
			chip.classList.toggle('is-active', index === step);
			chip.classList.toggle('is-done', index < step);
		});

		window.scrollTo({ top: 0, behavior: 'smooth' });
	}

	/* ------------------------------------------------------------------
	 * Plugin install
	 * ------------------------------------------------------------------ */
	function initPlugins() {
		all('.js-wizard-install').forEach(function (button) {
			button.addEventListener('click', function (event) {
				event.preventDefault();

				var slug = button.getAttribute('data-plugin');
				var card = button.closest('[data-plugin]');
				var statusEl = card ? one('.devco-wizard__plugin-status', card) : null;

				if (!slug) {
					return;
				}

				busy(button, true);
				say('در حال نصب ' + slug + ' …', true);

				post('devco_wizard_install', { plugin: slug }).then(function (res) {
					busy(button, false);

					if (res && res.success && res.data) {
						say(res.data.message || 'افزونه نصب و فعال شد.', true);

						if (card) {
							card.setAttribute('data-status', 'active');
						}

						if (statusEl) {
							statusEl.textContent = 'فعال';
							statusEl.classList.add('is-active');
						}

						button.classList.remove('devco-btn--primary');
						button.classList.add('devco-btn--ghost');
						button.textContent = 'نصب شد';
						button.disabled = true;
					} else {
						say(res && res.data && res.data.message ? res.data.message : 'نصب افزونه انجام نشد.', false);
					}
				}).catch(function () {
					busy(button, false);
					say('خطای شبکه در نصب افزونه.', false);
				});
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Demo import
	 * ------------------------------------------------------------------ */
	function initImport() {
		var runBtn = one('.js-wizard-import');
		var progress = one('[data-demo-progress]');
		var bar = one('[data-demo-progress-bar]');
		var tasks = all('[data-task]');

		// "Next" is enabled once everything is imported or skipped.
		function markTask(key, state, label) {
			var row = one('[data-task="' + key + '"]');
			if (!row) {
				return;
			}

			var stateEl = one('[data-task-state]', row);
			var check = one('.devco-wizard__task-check', row);

			if (stateEl) {
				stateEl.textContent = label || (state === 'done' ? 'انجام شد' : state === 'error' ? 'خطا' : 'در حال اجرا…');
			}

			row.classList.toggle('is-done', state === 'done');
			row.classList.toggle('is-error', state === 'error');

			if (check) {
				check.textContent = state === 'done' ? '✓' : state === 'error' ? '!' : '…';
			}
		}

		function setProgress(value) {
			if (progress) {
				progress.hidden = false;
			}
			if (bar) {
				bar.style.width = Math.min(100, Math.max(0, value)) + '%';
			}
		}

		if (!runBtn) {
			return;
		}

		runBtn.addEventListener('click', function (event) {
			event.preventDefault();

			var order = tasks.map(function (row) {
				return row.getAttribute('data-task');
			}).filter(Boolean);

			if (!order.length) {
				return;
			}

			busy(runBtn, true);
			say('ایمپورت دمو آغاز شد…', true);

			var index = 0;

			function next() {
				if (index >= order.length) {
					setProgress(100);
					busy(runBtn, false);
					runBtn.textContent = 'ایمپورت کامل شد';
					runBtn.disabled = true;
					say('همه بخش‌ها با موفقیت ساخته شدند. مرحله بعد را بزنید.', true);
					return;
				}

				var key = order[index];
				markTask(key, 'running');
				setProgress((index / order.length) * 100);

				post('devco_wizard_import', { task: key }).then(function (res) {
					if (res && res.success) {
						markTask(key, 'done', (res.data && res.data.message) ? res.data.message : 'انجام شد');
					} else {
						markTask(key, 'error', (res && res.data && res.data.message) ? res.data.message : 'خطا');
					}

					index += 1;
					setProgress((index / order.length) * 100);
					setTimeout(next, 220);
				}).catch(function () {
					markTask(key, 'error', 'خطای شبکه');
					index += 1;
					setTimeout(next, 220);
				});
			}

			next();
		});

		var skip = one('[data-skip-import]');
		if (skip) {
			skip.addEventListener('click', function (event) {
				event.preventDefault();
				say('مرحله ایمپورت رد شد. می‌توانید بعداً از پنل قالب آن را اجرا کنید.', true);
				var panel = skip.closest('[data-wpanel]');
				if (panel) {
					var number = parseInt(panel.getAttribute('data-wpanel'), 10) || 2;
					go(number + 1);
				}
			});
		}
	}

	/* ------------------------------------------------------------------
	 * Finish
	 * ------------------------------------------------------------------ */
	function initFinish() {
		var finish = one('.js-wizard-finish');
		if (!finish) {
			return;
		}

		finish.addEventListener('click', function (event) {
			event.preventDefault();

			busy(finish, true);

			post('devco_wizard_finish', {}).then(function (res) {
				busy(finish, false);

				var redirect = res && res.success && res.data ? res.data.redirect : '';

				if (redirect) {
					window.location.href = redirect;
				} else {
					window.location.href = A.ajaxUrl ? A.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=devco-settings') : window.location.href;
				}
			}).catch(function () {
				busy(finish, false);
				say('پایان راه‌اندازی ثبت نشد، یک بار دیگر تلاش کنید.', false);
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */
	function boot() {
		all('[data-goto]').forEach(function (button) {
			button.addEventListener('click', function (event) {
				event.preventDefault();
				go(parseInt(button.getAttribute('data-goto'), 10) || 1);
			});
		});

		all('[data-wstep]').forEach(function (chip) {
			chip.addEventListener('click', function () {
				var index = parseInt(chip.getAttribute('data-wstep'), 10);
				if (index) {
					go(index);
				}
			});
		});

		initPlugins();
		initImport();
		initFinish();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

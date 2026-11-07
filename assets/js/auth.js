/**
 * DeVco — authentication: SMS OTP, password login, registration, modal.
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

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */
	function normalizeMobile(value) {
		var digits = String(value || '').replace(/[۰-۹]/g, function (d) {
			return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);
		}).replace(/[^\d+]/g, '');

		if (digits.indexOf('+98') === 0) {
			digits = '0' + digits.slice(3);
		} else if (digits.indexOf('0098') === 0) {
			digits = '0' + digits.slice(4);
		} else if (digits.indexOf('98') === 0 && digits.length === 12) {
			digits = '0' + digits.slice(2);
		} else if (digits.indexOf('9') === 0 && digits.length === 10) {
			digits = '0' + digits;
		}

		return digits;
	}

	function setMessage(form, text, type) {
		var box = one('[data-auth-message]', form);
		if (!box) {
			toast(text, type);
			return;
		}

		box.textContent = text || '';
		box.classList.remove('is-error', 'is-success');
		if (text && type) {
			box.classList.add(type === 'error' ? 'is-error' : 'is-success');
		}
	}

	function setBusy(form, state) {
		var button = one('[data-auth-submit], button[type="submit"]', form);
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
	 * OTP timer
	 * ------------------------------------------------------------------ */
	function startTimer(auth, seconds) {
		var el = one('[data-auth-timer]', auth);
		var resend = one('[data-auth-resend]', auth);
		var remaining = Math.max(0, parseInt(seconds, 10) || 60);

		if (auth._timer) {
			clearInterval(auth._timer);
		}

		function tick() {
			if (remaining <= 0) {
				clearInterval(auth._timer);
				if (el) {
					el.textContent = 'کد منقضی شد.';
				}
				if (resend) {
					resend.disabled = false;
				}
				return;
			}

			if (el) {
				var mm = Math.floor(remaining / 60);
				var ss = remaining % 60;
				el.textContent = 'ارسال دوباره تا ' + (mm > 0 ? mm + ':' : '') + (ss < 10 ? '0' + ss : ss) + ' ثانیه';
			}

			if (resend) {
				resend.disabled = true;
			}

			remaining -= 1;
		}

		tick();
		auth._timer = setInterval(tick, 1000);
	}

	/* ------------------------------------------------------------------
	 * OTP flow
	 * ------------------------------------------------------------------ */
	function bindOtp(auth) {
		var form = one('[data-auth-form="otp"]', auth);
		if (!form) {
			return;
		}

		var mobileField = one('[data-step="mobile"]', form);
		var codeField = one('[data-step="code"]', form);
		var nameField = one('[data-step="name"]', form);
		var mobileInput = one('input[name="mobile"]', form);
		var codeInput = one('input[name="code"]', form);
		var nameInput = one('input[name="name"]', form);
		var submit = one('button[type="submit"]', form);
		var submitText = one('.devco-auth__submit-text', form);

		// Numeric-only mobile.
		if (mobileInput) {
			mobileInput.addEventListener('input', function () {
				mobileInput.value = mobileInput.value.replace(/[^\d۰-۹]/g, '');
			});
		}

		// Auto-submit when the code is complete.
		if (codeInput) {
			codeInput.addEventListener('input', function () {
				codeInput.value = codeInput.value.replace(/[^\d۰-۹]/g, '');
				var length = parseInt(codeInput.getAttribute('data-otp-length'), 10) || D.otpLength || 5;
				if (codeInput.value.length >= length) {
					form.dispatchEvent(new Event('submit', { cancelable: true }));
				}
			});
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			var mobile = normalizeMobile(mobileInput ? mobileInput.value : '');

			if (!/^09\d{9}$/.test(mobile)) {
				setMessage(form, 'شماره موبایل را درست وارد کنید (۰۹۱۲۱۲۳۴۵۶۷).', 'error');
				return;
			}

			var inCodeStep = codeField && !codeField.hidden;
			var payload = { mobile: mobile };

			if (inCodeStep) {
				var code = String(codeInput ? codeInput.value : '').replace(/[۰-۹]/g, function (d) {
					return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);
				});

				if (code.length < 4) {
					setMessage(form, 'کد تایید را کامل وارد کنید.', 'error');
					return;
				}

				payload.code = code;
				if (nameInput && nameInput.value) {
					payload.name = nameInput.value;
				}

				setBusy(form, true);
				setMessage(form, '', '');

				window.Devco.post('devco_verify_otp', payload).then(function (res) {
					setBusy(form, false);

					if (res && res.success) {
						setMessage(form, (res.data && res.data.message) || 'خوش آمدید!', 'success');
						var redirect = res.data && res.data.redirect ? res.data.redirect : D.dashboard;
						setTimeout(function () {
							window.location.href = redirect || window.location.href;
						}, 700);
					} else {
						setMessage(form, res && res.data && res.data.message ? res.data.message : i18n.error, 'error');
					}
				});
				return;
			}

			setBusy(form, true);
			setMessage(form, '', '');

			window.Devco.post('devco_send_otp', payload).then(function (res) {
				setBusy(form, false);

				if (res && res.success) {
					if (mobileField) {
						mobileField.hidden = true;
					}
					if (codeField) {
						codeField.hidden = false;
					}
					if (nameField) {
						nameField.hidden = false;
					}
					if (submitText) {
						submitText.textContent = 'ورود به حساب';
					}
					if (codeInput) {
						codeInput.focus({ preventScroll: true });
					}
					setMessage(form, (res.data && res.data.message) || i18n.codeSent || 'کد ارسال شد.', 'success');
					startTimer(auth, (res.data && res.data.expiry) || 180);
				} else {
					setMessage(form, res && res.data && res.data.message ? res.data.message : i18n.error, 'error');
				}
			});
		});

		// Resend.
		var resend = one('[data-auth-resend]', auth);
		if (resend) {
			resend.addEventListener('click', function () {
				var mobile = normalizeMobile(mobileInput ? mobileInput.value : '');
				if (!mobile) {
					return;
				}

				resend.disabled = true;
				setMessage(form, i18n.sending || 'در حال ارسال…', '');

				window.Devco.post('devco_resend', { mobile: mobile }).then(function (res) {
					if (res && res.success) {
						setMessage(form, (res.data && res.data.message) || i18n.codeSent, 'success');
						startTimer(auth, (res.data && res.data.expiry) || 180);
					} else {
						setMessage(form, res && res.data && res.data.message ? res.data.message : i18n.error, 'error');
						resend.disabled = false;
					}
				});
			});
		}
	}

	/* ------------------------------------------------------------------
	 * Password login
	 * ------------------------------------------------------------------ */
	function bindLogin(auth) {
		var form = one('[data-auth-form="login"]', auth);
		if (!form) {
			return;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			var login = one('input[name="login"]', form);
			var password = one('input[name="password"]', form);
			var remember = one('input[name="remember"]', form);

			if (!login || !login.value || !password || !password.value) {
				setMessage(form, 'نام کاربری و رمز عبور را وارد کنید.', 'error');
				return;
			}

			setBusy(form, true);
			setMessage(form, '', '');

			window.Devco.post('devco_login', {
				login: login.value,
				password: password.value,
				remember: remember && remember.checked ? 1 : 0
			}).then(function (res) {
				setBusy(form, false);

				if (res && res.success) {
					setMessage(form, (res.data && res.data.message) || 'خوش آمدید!', 'success');
					setTimeout(function () {
						window.location.href = (res.data && res.data.redirect) || D.dashboard || window.location.href;
					}, 600);
				} else {
					setMessage(form, res && res.data && res.data.message ? res.data.message : i18n.error, 'error');
				}
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Registration
	 * ------------------------------------------------------------------ */
	function bindRegister(auth) {
		var form = one('[data-auth-form="register"]', auth);
		if (!form) {
			return;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			var name = one('input[name="name"]', form);
			var mobile = one('input[name="mobile"]', form);
			var password = one('input[name="password"]', form);
			var phone = normalizeMobile(mobile ? mobile.value : '');

			if (!name || !name.value) {
				setMessage(form, 'نام خود را وارد کنید.', 'error');
				return;
			}

			if (!/^09\d{9}$/.test(phone)) {
				setMessage(form, 'شماره موبایل را درست وارد کنید.', 'error');
				return;
			}

			if (!password || password.value.length < 6) {
				setMessage(form, 'رمز عبور باید حداقل ۶ کاراکتر باشد.', 'error');
				return;
			}

			setBusy(form, true);
			setMessage(form, '', '');

			window.Devco.post('devco_register', {
				name: name.value,
				mobile: phone,
				password: password.value
			}).then(function (res) {
				setBusy(form, false);

				if (res && res.success) {
					setMessage(form, (res.data && res.data.message) || 'حساب ساخته شد.', 'success');
					setTimeout(function () {
						window.location.href = (res.data && res.data.redirect) || D.dashboard || window.location.href;
					}, 700);
				} else {
					setMessage(form, res && res.data && res.data.message ? res.data.message : i18n.error, 'error');
				}
			});
		});

		var switchBtn = one('[data-auth-switch="register"]', auth);
		if (switchBtn) {
			switchBtn.addEventListener('click', function () {
				var login = one('[data-auth-form="login"]', auth);
				if (login) {
					login.hidden = true;
				}
				form.hidden = false;
				setMessage(form, '', '');
			});
		}
	}

	/* ------------------------------------------------------------------
	 * Tabs / panels
	 * ------------------------------------------------------------------ */
	function setView(auth, view) {
		auth.setAttribute('data-view', view);

		all('[data-auth-tab]', auth).forEach(function (tab) {
			tab.classList.toggle('is-active', tab.getAttribute('data-auth-tab') === view);
		});

		all('[data-auth-panel]', auth).forEach(function (panel) {
			var match = panel.getAttribute('data-auth-panel') === view;
			panel.hidden = !match;
			panel.classList.toggle('is-active', match);
		});

		var first = one('[data-auth-panel="' + view + '"] input:not([type="hidden"])', auth);
		if (first && auth.closest('.devco-modal')) {
			setTimeout(function () {
				first.focus({ preventScroll: true });
			}, 120);
		}
	}

	function bindTabs(auth) {
		all('[data-auth-tab]', auth).forEach(function (tab) {
			tab.addEventListener('click', function () {
				setView(auth, tab.getAttribute('data-auth-tab'));
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Modal glue
	 * ------------------------------------------------------------------ */
	function openAuthModal(view, mode) {
		var modal = one('[data-auth-modal]');
		if (!modal) {
			var fallback = D.accountUrl || '/wp-login.php';
			window.location.href = fallback;
			return;
		}

		if (typeof window.devcoOpenOverlay === 'function') {
			window.devcoOpenOverlay(modal);
		} else {
			modal.classList.add('is-open');
			document.body.classList.add('devco-locked');
		}

		var auth = one('[data-auth]', modal);
		if (auth) {
			if (view) {
				setView(auth, view);
			}

			if (mode === 'register') {
				var panel = one('[data-auth-panel="password"]', auth);
				var register = one('[data-auth-form="register"]', auth);
				var login = one('[data-auth-form="login"]', auth);

				if (register) {
					if (panel) {
						setView(auth, 'password');
					}
					if (login) {
						login.hidden = true;
					}
					register.hidden = false;
				}
			}
		}
	}

	function initAuthOpeners() {
		all('.js-devco-auth-open, [data-auth-open]').forEach(function (btn) {
			btn.addEventListener('click', function (event) {
				if (D.loggedIn) {
					return;
				}
				event.preventDefault();
				openAuthModal(btn.getAttribute('data-auth-view') || 'otp', btn.getAttribute('data-auth-mode') || '');
			});
		});

		// Fallback auth buttons elsewhere in the theme.
		all('[data-login-required]').forEach(function (link) {
			if (D.loggedIn) {
				return;
			}
			link.addEventListener('click', function (event) {
				event.preventDefault();
				openAuthModal('otp', '');
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */
	function init() {
		all('[data-auth]').forEach(function (auth) {
			if (auth.getAttribute('data-ready') === '1') {
				return;
			}
			auth.setAttribute('data-ready', '1');

			bindTabs(auth);
			bindOtp(auth);
			bindLogin(auth);
			bindRegister(auth);

			var view = auth.getAttribute('data-view');
			if (view) {
				setView(auth, view);
			}
		});

		initAuthOpeners();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	document.addEventListener('devco:refresh', init);
})();

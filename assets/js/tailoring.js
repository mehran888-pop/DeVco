/**
 * DeVco — tailoring: 3D body map, multi-step form, uploads, submission.
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

	// Measurement key → mannequin body part class.
	var MEASURE_PARTS = {
		neck: 'neck',
		shoulder: 'torso',
		chest: 'torso',
		bust: 'torso',
		waist: 'waist',
		stomach: 'waist',
		weight: 'waist',
		hip: 'hips',
		hips: 'hips',
		sleeve: 'arm-l',
		wrist: 'arm-l',
		arm_length: 'arm-l',
		shirt_length: 'torso',
		height: 'torso',
		thigh: 'leg-l',
		knee: 'leg-l',
		trouser_length: 'leg-l',
		inseam: 'leg-l',
		ankle: 'foot-l',
		calf: 'leg-l'
	};

	function partFor(key) {
		return MEASURE_PARTS[key] || key;
	}

	/* ------------------------------------------------------------------
	 * Body map (rotatable mannequin)
	 * ------------------------------------------------------------------ */
	function BodyMap(root) {
		this.root = root;
		this.figure = one('[data-figure]', root);
		this.rotation = 0;
		this.auto = false;
		this.autoTimer = null;
		this.init();
	}

	BodyMap.prototype.init = function () {
		var self = this;

		// Drag to rotate.
		if (this.figure) {
			var dragging = false;
			var startX = 0;
			var startRot = 0;

			var down = function (event) {
				var p = event.touches ? event.touches[0] : event;
				dragging = true;
				startX = p.clientX;
				startRot = self.rotation;
				var stage = self.figure.closest('.devco-bodymap__stage');
				if (stage) {
					stage.classList.add('is-dragging');
				}
			};

			var move = function (event) {
				if (!dragging) {
					return;
				}
				var p = event.touches ? event.touches[0] : event;
				var delta = (p.clientX - startX) * 0.6;
				self.setRotation(startRot + delta);

				if (event.cancelable && event.touches) {
					event.preventDefault();
				}
			};

			var up = function () {
				dragging = false;
				var stage = self.figure.closest('.devco-bodymap__stage');
				if (stage) {
					stage.classList.remove('is-dragging');
				}
			};

			var stage = this.figure.closest('.devco-bodymap__stage') || this.figure;
			stage.addEventListener('mousedown', down);
			window.addEventListener('mousemove', move);
			window.addEventListener('mouseup', up);
			stage.addEventListener('touchstart', down, { passive: true });
			stage.addEventListener('touchmove', move, { passive: false });
			stage.addEventListener('touchend', up);
		}

		// Arrow buttons.
		all('[data-rotate]', this.root).forEach(function (btn) {
			btn.addEventListener('click', function () {
				self.autoOff();
				self.setRotation(self.rotation + (parseFloat(btn.getAttribute('data-rotate')) || 0));
			});
		});

		// Auto spin.
		var autoBtn = one('[data-rotate-auto]', this.root);
		if (autoBtn) {
			autoBtn.addEventListener('click', function () {
				if (self.auto) {
					self.autoOff();
				} else {
					self.autoOn(autoBtn);
				}
			});
		}

		// Field ↔ hotspot ↔ mannequin sync.
		all('[data-bm-input]', this.root).forEach(function (input) {
			var key = input.getAttribute('data-bm-input');

			input.addEventListener('input', function () {
				input.value = input.value.replace(/[^\d.]/g, '');
				self.sync(key);
			});

			input.addEventListener('focus', function () {
				self.highlight(key);
			});

			input.addEventListener('blur', function () {
				self.highlight('');
			});
		});

		all('[data-measure]', this.root).forEach(function (spot) {
			spot.addEventListener('click', function () {
				var key = spot.getAttribute('data-measure');
				var input = one('[data-bm-input="' + key + '"]', self.root);
				if (input) {
					input.focus({ preventScroll: false });
					input.classList.add('is-active');
					setTimeout(function () {
						input.classList.remove('is-active');
					}, 1400);
				}
				self.highlight(key);
			});
		});

		this.sync();
	};

	BodyMap.prototype.setRotation = function (deg) {
		this.rotation = Math.max(-70, Math.min(70, deg));
		if (this.figure) {
			this.figure.style.setProperty('--bm-rot', this.rotation.toFixed(1) + 'deg');
			this.figure.style.transform = 'rotateY(' + this.rotation.toFixed(1) + 'deg)';
		}
	};

	BodyMap.prototype.autoOn = function (button) {
		var self = this;
		this.auto = true;

		if (button) {
			button.classList.add('is-active');
		}

		this.autoTimer = setInterval(function () {
			var next = self.rotation + 2;
			if (next > 45) {
				next = -45;
			}
			self.setRotation(next);
		}, 70);
	};

	BodyMap.prototype.autoOff = function () {
		this.auto = false;
		clearInterval(this.autoTimer);
		this.autoTimer = null;

		var button = one('[data-rotate-auto]', this.root);
		if (button) {
			button.classList.remove('is-active');
		}
	};

	BodyMap.prototype.highlight = function (key) {
		all('.bm-part', this.root).forEach(function (part) {
			part.classList.remove('is-lit');
		});

		all('[data-measure]', this.root).forEach(function (spot) {
			spot.classList.toggle('is-active', !!(key && spot.getAttribute('data-measure') === key));
		});

		if (!key) {
			return;
		}

		var part = one('.bm-part--' + partFor(key), this.root);
		if (part) {
			part.classList.add('is-lit');
		}
	};

	BodyMap.prototype.sync = function (onlyKey) {
		var self = this;
		var inputs = all('[data-bm-input]', this.root);
		var filled = 0;

		inputs.forEach(function (input) {
			var key = input.getAttribute('data-bm-input');
			var value = input.value.trim();

			if (value) {
				filled += 1;
			}

			if (onlyKey && key !== onlyKey) {
				return;
			}

			var spot = one('[data-measure="' + key + '"]', self.root);
			var field = one('[data-bm-field="' + key + '"]', self.root);
			var part = one('.bm-part--' + partFor(key), self.root);

			if (spot) {
				spot.classList.toggle('is-filled', !!value);
				var label = one('[data-bm-value]', spot);
				if (label) {
					label.textContent = value ? (D.persianNums ? toPersianNumber(value) : value) : '—';
				}
			}

			if (field) {
				field.classList.toggle('is-filled', !!value);
			}

			if (part) {
				part.classList.toggle('is-done', !!value);
			}
		});

		var count = one('[data-bm-count]', this.root);
		var bar = one('[data-bm-progress]', this.root);

		if (count) {
			count.textContent = D.persianNums ? toPersianNumber(filled) : String(filled);
		}

		if (bar) {
			bar.style.width = (inputs.length ? (filled / inputs.length) * 100 : 0).toFixed(1) + '%';
		}
	};

	function toPersianNumber(value) {
		return String(value).replace(/[0-9]/g, function (d) {
			return '۰۱۲۳۴۵۶۷۸۹'[d];
		});
	}

	/* ------------------------------------------------------------------
	 * Uploads
	 * ------------------------------------------------------------------ */
	function initUploads() {
		all('[data-upload]').forEach(function (wrap) {
			if (wrap.getAttribute('data-ready') === '1') {
				return;
			}
			wrap.setAttribute('data-ready', '1');

			var input = one('input[type="file"]', wrap);
			var list = one('[data-upload-list]', wrap);
			var drop = one('.devco-upload__drop', wrap);
			if (!input || !list) {
				return;
			}

			var uploaded = [];

			function addItem(file, data) {
				var item = document.createElement('div');
				item.className = 'devco-upload__item';

				if (data && data.thumb) {
					item.innerHTML = '<img src="' + data.thumb + '" alt="" /><button type="button" aria-label="حذف">×</button>';
				} else {
					item.innerHTML = '<span>' + (file ? file.name.slice(-12) : '') + '</span><button type="button" aria-label="حذف">×</button>';
				}

				item.setAttribute('data-id', data && data.id ? data.id : '');

				one('button', item).addEventListener('click', function () {
					uploaded = uploaded.filter(function (entry) {
						return entry !== item.getAttribute('data-id');
					});
					item.remove();
				});

				list.appendChild(item);
			}

			function upload(file) {
				var maxMb = parseInt(input.getAttribute('data-max'), 10) || 8;

				if (file.size > maxMb * 1024 * 1024) {
					toast('حجم فایل بیشتر از ' + maxMb + ' مگابایت است.', 'error');
					return;
				}

				var placeholder = document.createElement('div');
				placeholder.className = 'devco-upload__item is-loading';
				placeholder.innerHTML = '<span class="devco-spinner"></span>';
				list.appendChild(placeholder);

				window.Devco.post('devco_tailoring_upload', { file: file }).then(function (res) {
					placeholder.remove();

					if (res && res.success && res.data) {
						uploaded.push(res.data.id);
						addItem(file, res.data);
					} else {
						toast(res && res.data && res.data.message ? res.data.message : i18n.error, 'error');
					}
				}).catch(function () {
					placeholder.remove();
					toast(i18n.error, 'error');
				});
			}

			input.addEventListener('change', function () {
				Array.prototype.slice.call(input.files || []).forEach(upload);
				input.value = '';
			});

			if (drop) {
				['dragenter', 'dragover'].forEach(function (type) {
					drop.addEventListener(type, function (event) {
						event.preventDefault();
						drop.classList.add('is-drag');
					});
				});

				['dragleave', 'drop'].forEach(function (type) {
					drop.addEventListener(type, function (event) {
						event.preventDefault();
						drop.classList.remove('is-drag');
					});
				});

				drop.addEventListener('drop', function (event) {
					Array.prototype.slice.call(event.dataTransfer.files || []).forEach(upload);
				});
			}

			wrap.devcoUploaded = function () {
				return uploaded.slice();
			};
		});
	}

	/* ------------------------------------------------------------------
	 * Multi-step form
	 * ------------------------------------------------------------------ */
	function TailoringForm(root) {
		this.root = root;
		this.form = one('form', root);
		this.step = 1;
		this.total = all('[data-step-panel]', root).length || 1;
		this.init();
	}

	TailoringForm.prototype.init = function () {
		var self = this;

		all('[data-step-next]', this.root).forEach(function (btn) {
			btn.addEventListener('click', function () {
				var next = parseInt(btn.getAttribute('data-step-next'), 10) || self.step + 1;
				if (self.validate(self.step)) {
					self.go(next);
				}
			});
		});

		all('[data-step-prev]', this.root).forEach(function (btn) {
			btn.addEventListener('click', function () {
				var prev = parseInt(btn.getAttribute('data-step-prev'), 10) || self.step - 1;
				self.go(prev);
			});
		});

		// Click on the progress chips.
		all('[data-steps] [data-step]', this.root).forEach(function (chip) {
			chip.addEventListener('click', function () {
				var target = parseInt(chip.getAttribute('data-step'), 10);
				if (target && target < self.step) {
					self.go(target);
				}
			});
		});

		if (this.form) {
			this.form.addEventListener('submit', function (event) {
				event.preventDefault();
				self.submit();
			});

			// Live summary.
			this.form.addEventListener('input', function () {
				self.summary();
			});
			this.form.addEventListener('change', function () {
				self.summary();
			});
		}

		this.go(1);
		this.summary();
	};

	TailoringForm.prototype.go = function (step) {
		this.step = Math.max(1, Math.min(this.total, step));

		all('[data-step-panel]', this.root).forEach(function (panel) {
			var match = parseInt(panel.getAttribute('data-step-panel'), 10) === this.step;
			panel.hidden = !match;
		}, this);

		all('[data-steps] [data-step]', this.root).forEach(function (chip) {
			var index = parseInt(chip.getAttribute('data-step'), 10);
			chip.classList.toggle('is-active', index === this.step);
			chip.classList.toggle('is-done', index < this.step);
		}, this);

		var top = this.root.getBoundingClientRect().top + window.pageYOffset - 90;
		if (this.step > 1) {
			window.scrollTo({ top: top, behavior: 'smooth' });
		}

		if (this.step === this.total) {
			this.summary();
		}
	};

	TailoringForm.prototype.validate = function (step) {
		var panel = one('[data-step-panel="' + step + '"]', this.root);
		if (!panel) {
			return true;
		}

		var ok = true;

		all('input[required], select[required], textarea[required]', panel).forEach(function (input) {
			var field = input.closest('.devco-tform__field');
			var invalid = false;

			if (input.type === 'checkbox') {
				invalid = !input.checked;
			} else if (!input.value.trim()) {
				invalid = true;
			}

			if (invalid) {
				ok = false;
				if (field) {
					field.classList.add('is-invalid');
				}
				if (!panel._firstInvalid) {
					panel._firstInvalid = input;
				}
			} else if (field) {
				field.classList.remove('is-invalid');
			}
		});

		if (!ok) {
			toast('فیلدهای ضروری را تکمیل کنید.', 'error');
			if (panel._firstInvalid) {
				panel._firstInvalid.focus({ preventScroll: true });
				panel._firstInvalid = null;
			}
		}

		return ok;
	};

	TailoringForm.prototype.values = function () {
		var data = { fields: {}, measurements: {} };

		if (!this.form) {
			return data;
		}

		all('[data-bm-input]', this.form).forEach(function (input) {
			var value = input.value.trim();
			if (value) {
				data.measurements[input.getAttribute('data-bm-input')] = value;
			}
		});

		all('.devco-tform__field', this.form).forEach(function (field) {
			var control = field.querySelector('input, select, textarea');
			if (!control || control.type === 'file' || !control.name) {
				return;
			}

			var name = control.name.replace(/\[\]$/, '');
			if (name.indexOf('measure_') === 0) {
				return;
			}

			if (control.type === 'checkbox') {
				if (control.checked) {
					data.fields[name] = control.value || '1';
				}
				return;
			}

			if (control.type === 'radio') {
				if (control.checked) {
					data.fields[name] = control.value;
				}
				return;
			}

			data.fields[name] = control.value;
		});

		return data;
	};

	TailoringForm.prototype.summary = function () {
		var box = one('[data-form-summary]', this.root);
		if (!box || !this.form) {
			return;
		}

		var data = this.values();
		var rows = [];

		Object.keys(data.fields).forEach(function (key) {
			if (key === 'terms' || key.indexOf('guest_') === 0) {
				return;
			}

			var control = one('[name="' + key + '"]', this.form) || one('[name="' + key + '[]"]', this.form);
			var label = control && control.closest('.devco-tform__field') ? one('.devco-tform__label strong', control.closest('.devco-tform__field')) : null;

			rows.push('<dt>' + (label ? label.textContent : key) + '</dt><dd>' + String(data.fields[key]) + '</dd>');
		});

		var measureCount = Object.keys(data.measurements).length;
		if (measureCount) {
			rows.push('<dt>اندازه‌های ثبت‌شده</dt><dd>' + (D.persianNums ? toPersianNumber(measureCount) : measureCount) + ' مورد</dd>');
		}

		var uploads = 0;
		all('[data-upload]', this.form).forEach(function (wrap) {
			if (typeof wrap.devcoUploaded === 'function') {
				uploads += wrap.devcoUploaded().length;
			}
		});

		if (uploads) {
			rows.push('<dt>فایل‌های طرح</dt><dd>' + (D.persianNums ? toPersianNumber(uploads) : uploads) + ' فایل</dd>');
		}

		box.innerHTML = rows.length ? '<dl>' + rows.join('') + '</dl>' : '<p class="devco-hint">پس از تکمیل مراحل، خلاصه سفارش اینجا نمایش داده می‌شود.</p>';
	};

	TailoringForm.prototype.submit = function () {
		var self = this;
		var message = one('[data-tform-message]', this.root);
		var button = one('button[type="submit"]', this.form);

		for (var step = 1; step <= this.total; step++) {
			if (!this.validate(step)) {
				this.go(step);
				return;
			}
		}

		var data = this.values();
		var uploads = [];

		all('[data-upload]', this.form).forEach(function (wrap) {
			if (typeof wrap.devcoUploaded === 'function') {
				uploads = uploads.concat(wrap.devcoUploaded());
			}
		});

		var payload = {
			form_id: this.root.getAttribute('data-form-id') || 0,
			fields: data.fields,
			measurements: data.measurements,
			uploads: uploads,
			notes: (data.fields.notes || '')
		};

		var guestName = one('[name="guest_name"]', this.form);
		var guestMobile = one('[name="guest_mobile"]', this.form);
		if (guestName && guestName.value) {
			payload.guest_name = guestName.value;
		}
		if (guestMobile && guestMobile.value) {
			payload.guest_mobile = guestMobile.value;
		}

		if (button) {
			button.classList.add('is-loading');
			button.disabled = true;
			var spinner = one('.devco-spinner', button);
			if (spinner) {
				spinner.hidden = false;
			}
		}

		window.Devco.post('devco_tailoring_submit', payload).then(function (res) {
			if (button) {
				button.classList.remove('is-loading');
				button.disabled = false;
				var spinner2 = one('.devco-spinner', button);
				if (spinner2) {
					spinner2.hidden = true;
				}
			}

			if (res && res.success && res.data) {
				if (message) {
					message.textContent = res.data.message || 'درخواست ثبت شد.';
					message.className = 'devco-tform__message is-success';
				}

				self.root.innerHTML = '<div class="devco-entry">' +
					'<h3>درخواست شما ثبت شد</h3>' +
					'<p>' + (res.data.message || '') + '</p>' +
					'<a class="devco-btn devco-btn--primary" href="' + (res.data.redirect || D.dashboard) + '">مشاهده در داشبورد</a>' +
					'</div>';

				toast('درخواست دوخت ثبت شد.');
			} else if (message) {
				message.textContent = res && res.data && res.data.message ? res.data.message : i18n.error;
				message.className = 'devco-tform__message is-error';
			}
		}).catch(function () {
			if (button) {
				button.classList.remove('is-loading');
				button.disabled = false;
			}
			if (message) {
				message.textContent = i18n.error;
				message.className = 'devco-tform__message is-error';
			}
		});
	};

	/* ------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */
	function init() {
		if (!window.Devco) {
			return;
		}

		all('[data-bodymap]').forEach(function (root) {
			if (root.getAttribute('data-ready') === '1') {
				return;
			}
			root.setAttribute('data-ready', '1');
			new BodyMap(root);
		});

		initUploads();

		all('[data-tailoring-form]').forEach(function (root) {
			if (root.getAttribute('data-ready') === '1') {
				return;
			}
			root.setAttribute('data-ready', '1');
			new TailoringForm(root);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	document.addEventListener('devco:refresh', init);
})();

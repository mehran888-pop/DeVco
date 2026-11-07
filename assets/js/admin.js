/**
 * DeVco — wp-admin: settings page helpers, web-app (products / pricing / SMS),
 * and the drag & drop tailoring form builder.
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
			var value = data[key];
			if (Array.isArray(value) || (value && typeof value === 'object')) {
				body.append(key, JSON.stringify(value));
			} else if (value !== undefined && value !== null) {
				body.append(key, value);
			}
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

	function message(box, text, ok) {
		if (!box) {
			return;
		}
		box.textContent = text || '';
		box.className = (box.className || '').replace(/\bis-(error|success)\b/g, '').trim();
		if (text) {
			box.className += ok ? ' is-success' : ' is-error';
		}
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

	function fieldValues(form) {
		var out = {};

		all('input[name], select[name], textarea[name]', form).forEach(function (control) {
			var name = control.name.replace(/\[\]$/, '');

			if (control.type === 'checkbox') {
				if (!control.checked) {
					if (name === 'categories') {
						out.categories = out.categories || [];
					}
					return;
				}
				if (control.name.indexOf('[]') > -1) {
					out[name] = out[name] || [];
					out[name].push(control.value);
					return;
				}
				out[name] = control.value || '1';
				return;
			}

			if (control.type === 'file') {
				return;
			}

			out[name] = control.value;
		});

		return out;
	}

	/* ------------------------------------------------------------------
	 * Media picker (WordPress media frame)
	 * ------------------------------------------------------------------ */
	function initMedia() {
		all('[data-media]').forEach(function (wrap) {
			var button = one('.js-devco-media', wrap);
			var input = one('input[type="hidden"]', wrap);
			var preview = one('.devco-app__media-preview', wrap) || one('.devco-media-preview', wrap);

			if (!button || !input || !window.wp || !window.wp.media) {
				return;
			}

			button.addEventListener('click', function (event) {
				event.preventDefault();

				var frame = window.wp.media({
					title: 'انتخاب تصویر',
					button: { text: 'استفاده از این تصویر' },
					multiple: false,
					library: { type: 'image' }
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					input.value = attachment.id;

					if (preview) {
						preview.innerHTML = '<img src="' + (attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" alt="" />';
					}
				});

				frame.open();
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Product wizard (web-app)
	 * ------------------------------------------------------------------ */
	function initProductForm() {
		all('[data-product-form]').forEach(function (form) {
			var total = all('[data-wpanel]', form).length || 1;
			var step = 1;

			function go(next) {
				step = Math.max(1, Math.min(total, next));

				all('[data-wpanel]', form).forEach(function (panel) {
					panel.hidden = parseInt(panel.getAttribute('data-wpanel'), 10) !== step;
				});

				all('[data-wstep]', form).forEach(function (chip) {
					var index = parseInt(chip.getAttribute('data-wstep'), 10);
					chip.classList.toggle('is-active', index === step);
					chip.classList.toggle('is-done', index < step);
				});

				if (step === total) {
					summary();
				}
			}

			function summary() {
				var box = one('[data-wsummary]', form);
				if (!box) {
					return;
				}

				var values = fieldValues(form);
				var rows = [
					['نام', values.name || '—'],
					['قیمت', values.regular_price || '—'],
					['موجودی', values.stock || '—'],
					['وضعیت', values.status === 'draft' ? 'پیش‌نویس' : 'منتشر شده']
				];

				box.innerHTML = rows.map(function (row) {
					return '<div class="devco-wizard-summary__row"><span>' + row[0] + '</span><b>' + String(row[1]) + '</b></div>';
				}).join('');
			}

			all('[data-wnext]', form).forEach(function (button) {
				button.addEventListener('click', function () {
					go(parseInt(button.getAttribute('data-wnext'), 10) || step + 1);
				});
			});

			all('[data-wprev]', form).forEach(function (button) {
				button.addEventListener('click', function () {
					go(parseInt(button.getAttribute('data-wprev'), 10) || step - 1);
				});
			});

			all('[data-wstep]', form).forEach(function (chip) {
				chip.addEventListener('click', function () {
					var index = parseInt(chip.getAttribute('data-wstep'), 10);
					if (index && index < step) {
						go(index);
					}
				});
			});

			form.addEventListener('submit', function (event) {
				event.preventDefault();

				var box = one('[data-product-message]', form);
				var submit = one('button[type="submit"]', form);
				var values = fieldValues(form);
				var id = parseInt(form.getAttribute('data-id'), 10) || 0;

				if (!values.name) {
					message(box, 'نام محصول را وارد کنید.', false);
					go(1);
					return;
				}

				busy(submit, true);
				message(box, '', false);

				post('devco_app_product_save', { id: id, fields: values }).then(function (res) {
					busy(submit, false);

					if (res && res.success && res.data) {
						message(box, res.data.message || 'محصول ذخیره شد.', true);

						if (res.data.id && res.data.edit_url) {
							setTimeout(function () {
								window.location.href = res.data.edit_url;
							}, 900);
						} else if (!id && res.data.id) {
							form.setAttribute('data-id', res.data.id);
						}
					} else {
						message(box, res && res.data && res.data.message ? res.data.message : 'ذخیره نشد.', false);
					}
				}).catch(function () {
					busy(submit, false);
					message(box, 'خطای شبکه.', false);
				});
			});

			go(1);
		});
	}

	/* ------------------------------------------------------------------
	 * Pricing + status forms
	 * ------------------------------------------------------------------ */
	function initPricing() {
		all('[data-price-form]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();

				var box = one('[data-price-message]', form);
				var submit = one('button[type="submit"]', form);
				var values = fieldValues(form);
				var requestId = parseInt(form.getAttribute('data-request'), 10) || 0;

				if (!values.amount || parseFloat(values.amount) <= 0) {
					message(box, 'مبلغ را وارد کنید.', false);
					return;
				}

				busy(submit, true);

				post('devco_tailoring_price', {
					request_id: requestId,
					title: values.title || '',
					amount: values.amount,
					note: values.note || '',
					status: values.status || 'priced'
				}).then(function (res) {
					busy(submit, false);

					if (res && res.success && res.data) {
						message(box, res.data.message || 'فاکتور صادر شد.', true);
						setTimeout(function () {
							window.location.reload();
						}, 1200);
					} else {
						message(box, res && res.data && res.data.message ? res.data.message : 'خطا در صدور فاکتور.', false);
					}
				}).catch(function () {
					busy(submit, false);
					message(box, 'خطای شبکه.', false);
				});
			});
		});

		all('[data-status-form]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();

				var box = one('[data-status-message]', form);
				var submit = one('button[type="submit"]', form);
				var values = fieldValues(form);

				busy(submit, true);

				post('devco_tailoring_status', {
					request_id: parseInt(form.getAttribute('data-request'), 10) || 0,
					status: values.status || '',
					note: values.note || ''
				}).then(function (res) {
					busy(submit, false);

					if (res && res.success) {
						message(box, (res.data && res.data.message) || 'وضعیت به‌روزرسانی شد.', true);
						setTimeout(function () {
							window.location.reload();
						}, 1000);
					} else {
						message(box, res && res.data && res.data.message ? res.data.message : 'خطا.', false);
					}
				}).catch(function () {
					busy(submit, false);
					message(box, 'خطای شبکه.', false);
				});
			});
		});
	}

	/* ------------------------------------------------------------------
	 * SMS resend (log table)
	 * ------------------------------------------------------------------ */
	function initSms() {
		all('[data-sms-resend]').forEach(function (button) {
			button.addEventListener('click', function () {
				var mobile = button.getAttribute('data-mobile') || '';
				var text = button.getAttribute('data-message') || '';

				if (!mobile || !text) {
					return;
				}

				busy(button, true);

				post('devco_app_sms_resend', { mobile: mobile, message: text }).then(function (res) {
					busy(button, false);
					button.classList.add(res && res.success ? 'devco-btn--primary' : 'devco-btn--ghost');
				});
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Settings repeaters (clothes / actions rows)
	 * ------------------------------------------------------------------ */
	function initRepeaters() {
		all('[data-repeater]').forEach(function (repeater) {
			var list = one('[data-repeater-list]', repeater) || repeater;
			var template = one('template', repeater);
			var addBtn = one('[data-repeater-add]', repeater);
			if (!template || !addBtn) {
				return;
			}

			addBtn.addEventListener('click', function (event) {
				event.preventDefault();
				var html = template.innerHTML.replace(/__INDEX__/g, String(Date.now()));
				var holder = document.createElement('div');
				holder.innerHTML = html.trim();
				var row = holder.firstElementChild;
				if (row) {
					list.appendChild(row);
				}
			});

			repeater.addEventListener('click', function (event) {
				var remove = event.target.closest('[data-repeater-remove]');
				if (!remove) {
					return;
				}
				event.preventDefault();
				var row = remove.closest('[data-repeater-row]');
				if (row) {
					row.remove();
				}
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Form builder
	 * ------------------------------------------------------------------ */
	var FIELD_DEFAULTS = {
		text: { label: 'متن', placeholder: '', width: 'half' },
		textarea: { label: 'توضیحات', placeholder: '', width: 'full' },
		number: { label: 'عدد', placeholder: '0', width: 'half' },
		select: { label: 'انتخاب', options: 'گزینه ۱,گزینه ۲', width: 'half' },
		radio: { label: 'انتخاب کنید', options: 'گزینه ۱,گزینه ۲', width: 'full' },
		checkbox: { label: 'تایید می‌کنم', width: 'half' },
		date: { label: 'تاریخ', width: 'half' },
		color: { label: 'رنگ', width: 'half' },
		upload: { label: 'آپلود طرح', width: 'full' },
		bodymap: { label: 'اندازه‌گیری روی مانکن', width: 'full' },
		heading: { label: 'عنوان بخش', width: 'full' }
	};

	function Builder(root) {
		this.root = root;
		this.canvas = one('[data-builder-canvas]', root);
		this.panel = one('[data-builder-settings]', root);
		this.fields = [];
		this.selected = null;
		this.init();
	}

	Builder.prototype.init = function () {
		var self = this;

		try {
			this.fields = JSON.parse(this.root.getAttribute('data-fields') || '[]');
		} catch (e) {
			this.fields = [];
		}

		if (!Array.isArray(this.fields)) {
			this.fields = [];
		}

		this.render();

		// Palette drag.
		all('.devco-builder__tool', this.root).forEach(function (tool) {
			tool.addEventListener('dragstart', function (event) {
				event.dataTransfer.setData('text/plain', 'new:' + tool.getAttribute('data-field-type'));
				event.dataTransfer.effectAllowed = 'copy';
			});

			tool.addEventListener('dragend', function () {
				self.canvas.classList.remove('is-drag');
			});
		});

		if (this.canvas) {
			['dragenter', 'dragover'].forEach(function (type) {
				self.canvas.addEventListener(type, function (event) {
					event.preventDefault();
					self.canvas.classList.add('is-drag');
				});
			});

			this.canvas.addEventListener('dragleave', function () {
				self.canvas.classList.remove('is-drag');
			});

			this.canvas.addEventListener('drop', function (event) {
				event.preventDefault();
				self.canvas.classList.remove('is-drag');

				var payload = event.dataTransfer.getData('text/plain') || '';

				if (payload.indexOf('new:') === 0) {
					self.add(payload.slice(4));
				} else if (payload.indexOf('move:') === 0) {
					self.move(parseInt(payload.slice(5), 10));
				}
			});

			this.canvas.addEventListener('click', function (event) {
				var item = event.target.closest('[data-index]');

				if (event.target.closest('[data-remove]')) {
					var index = parseInt(item.getAttribute('data-index'), 10);
					self.fields.splice(index, 1);
					self.selected = null;
					self.render();
					return;
				}

				if (item) {
					self.select(parseInt(item.getAttribute('data-index'), 10));
				}
			});
		}

		// Save.
		var save = one('[data-form-save]', root.parentNode) || one('[data-form-save]', root);
		if (save) {
			save.addEventListener('click', function () {
				var box = one('[data-builder-message]', root.parentNode);
				busy(save, true);

				post('devco_app_forms_save', {
					form_id: parseInt(save.getAttribute('data-id'), 10) || 0,
					title: one('#devco-form-title') ? one('#devco-form-title').value : '',
					fields: self.fields
				}).then(function (res) {
					busy(save, false);
					if (res && res.success) {
						message(box, (res.data && res.data.message) || 'فرم ذخیره شد.', true);
						if (res.data && res.data.form_id) {
							save.setAttribute('data-id', res.data.form_id);
						}
					} else {
						message(box, res && res.data && res.data.message ? res.data.message : 'ذخیره نشد.', false);
					}
				}).catch(function () {
					busy(save, false);
					message(box, 'خطای شبکه.', false);
				});
			});
		}

		// New form.
		var fresh = one('[data-form-new]', root.parentNode) || one('[data-form-new]', root);
		if (fresh) {
			fresh.addEventListener('click', function () {
				var name = window.prompt('نام فرم جدید:');

				if (!name) {
					return;
				}

				post('devco_app_forms_save', { form_id: 0, title: name, fields: self.fields }).then(function (res) {
					if (res && res.success && res.data && res.data.form_id) {
						window.location.href = window.location.pathname + '?page=devco-forms&form=' + res.data.form_id;
					}
				});
			});
		}

		// Form switcher.
		var select = one('#devco-form-select');
		if (select) {
			select.addEventListener('change', function () {
				if (select.value) {
					window.location.href = select.value;
				}
			});
		}

		// Preview.
		var previewBtn = one('[data-builder-preview]', root.parentNode) || one('[data-builder-preview]', root);
		if (previewBtn) {
			previewBtn.addEventListener('click', function () {
				self.preview();
			});
		}

		this.preview();
	};

	Builder.prototype.add = function (type) {
		var defaults = FIELD_DEFAULTS[type] || { label: 'فیلد' };

		this.fields.push({
			id: 'f_' + Math.random().toString(36).slice(2, 7),
			type: type,
			label: defaults.label || 'فیلد',
			required: type === 'heading' ? 0 : 1,
			placeholder: defaults.placeholder || '',
			hint: '',
			options: defaults.options || '',
			width: defaults.width || 'full'
		});

		this.selected = this.fields.length - 1;
		this.render();
		this.preview();
	};

	Builder.prototype.move = function (from) {
		if (this.selected === null || from === this.selected) {
			return;
		}

		var item = this.fields.splice(from, 1)[0];
		this.fields.splice(this.selected, 0, item);
		this.selected = this.fields.indexOf(item);
		this.render();
		this.preview();
	};

	Builder.prototype.select = function (index) {
		this.selected = index;
		this.render();
	};

	Builder.prototype.render = function () {
		var self = this;

		if (this.canvas) {
			var placeholder = one('.devco-builder__placeholder', this.canvas);

			if (!this.fields.length) {
				this.canvas.innerHTML = '<p class="devco-builder__placeholder">فیلدها را اینجا رها کنید…</p>';
			} else {
				this.canvas.innerHTML = this.fields.map(function (field, index) {
					var controls = '';

					if (field.type === 'heading') {
						controls = '<strong>' + escapeHtml(field.label) + '</strong>';
					} else if (field.type === 'textarea') {
						controls = '<textarea rows="2" disabled placeholder="' + escapeAttr(field.placeholder) + '"></textarea>';
					} else if (field.type === 'select' || field.type === 'radio') {
						controls = '<select disabled><option>' + escapeHtml((field.options || '').split(',')[0] || 'گزینه') + '</option></select>';
					} else if (field.type === 'checkbox') {
						controls = '<label class="devco-check"><input type="checkbox" disabled /> ' + escapeHtml(field.label) + '</label>';
					} else if (field.type === 'color') {
						controls = '<input type="color" disabled />';
					} else if (field.type === 'upload') {
						controls = '<div class="devco-upload__drop"><span>آپلود فایل</span></div>';
					} else if (field.type === 'bodymap') {
						controls = '<div class="devco-upload__drop"><span>مانکن سه‌بعدی اندازه‌گیری</span></div>';
					} else if (field.type === 'date') {
						controls = '<input type="date" disabled />';
					} else {
						controls = '<input type="' + (field.type === 'number' ? 'number' : 'text') + '" disabled placeholder="' + escapeAttr(field.placeholder) + '" />';
					}

					return '<div class="devco-field devco-field--' + escapeAttr(field.width || 'full') + (index === self.selected ? ' is-selected' : '') + '" data-index="' + index + '" draggable="true">' +
						'<div class="devco-builder__item-head">' +
						'<span>' + escapeHtml(field.label) + (field.required ? ' <em>*</em>' : '') + '</span>' +
						'<button type="button" data-remove aria-label="حذف">×</button>' +
						'</div>' +
						controls +
						'</div>';
				}).join('');
			}

			all('[data-index]', this.canvas).forEach(function (item) {
				item.addEventListener('dragstart', function (event) {
					event.dataTransfer.setData('text/plain', 'move:' + item.getAttribute('data-index'));
					event.dataTransfer.effectAllowed = 'move';
					item.classList.add('is-dragging');
				});

				item.addEventListener('dragend', function () {
					item.classList.remove('is-dragging');
				});
			});
		}

		this.renderSettings();
	};

	Builder.prototype.renderSettings = function () {
		if (!this.panel) {
			return;
		}

		if (this.selected === null || !this.fields[this.selected]) {
			this.panel.innerHTML = '<h2>تنظیمات فیلد</h2><p class="devco-builder__settings-empty">یک فیلد را انتخاب کنید.</p>';
			return;
		}

		var self = this;
		var field = this.fields[this.selected];

		this.panel.innerHTML = '<h2>تنظیمات فیلد</h2>' +
			'<label class="devco-field"><span>برچسب</span><input type="text" data-prop="label" value="' + escapeAttr(field.label) + '" /></label>' +
			'<label class="devco-field"><span>راهنما / placeholder</span><input type="text" data-prop="placeholder" value="' + escapeAttr(field.placeholder || '') + '" /></label>' +
			'<label class="devco-field"><span>توضیح کوچک</span><input type="text" data-prop="hint" value="' + escapeAttr(field.hint || '') + '" /></label>' +
			(field.type === 'select' || field.type === 'radio'
				? '<label class="devco-field"><span>گزینه‌ها (با ویرگول)</span><input type="text" data-prop="options" value="' + escapeAttr(field.options || '') + '" /></label>'
				: '') +
			'<label class="devco-field"><span>عرض</span><select data-prop="width">' +
			'<option value="full"' + (field.width === 'full' ? ' selected' : '') + '>تمام عرض</option>' +
			'<option value="half"' + (field.width === 'half' ? ' selected' : '') + '>نصف</option>' +
			'</select></label>' +
			'<label class="devco-field devco-field--inline"><input type="checkbox" data-prop="required"' + (field.required ? ' checked' : '') + ' /><span>اجباری</span></label>';

		all('[data-prop]', this.panel).forEach(function (control) {
			control.addEventListener('input', function () {
				var prop = control.getAttribute('data-prop');
				var value = control.type === 'checkbox' ? (control.checked ? 1 : 0) : control.value;

				self.fields[self.selected][prop] = value;
				self.render();
				self.preview();
			});
		});
	};

	Builder.prototype.preview = function () {
		var frame = one('[data-builder-preview-frame]');
		if (!frame) {
			return;
		}

		var html = '<div class="devco-tform" style="direction:rtl;font-family:Vazirmatn,Tahoma,sans-serif;padding:14px">' +
			'<div class="devco-tform__grid">' +
			this.fields.map(function (field) {
				if (field.type === 'heading') {
					return '<div class="devco-tform__field devco-tform__field--full"><strong>' + escapeHtml(field.label) + '</strong></div>';
				}

				var control = '';

				if (field.type === 'textarea') {
					control = '<textarea rows="2"></textarea>';
				} else if (field.type === 'select') {
					control = '<select><option>' + escapeHtml((field.options || '').split(',')[0] || '') + '</option></select>';
				} else if (field.type === 'radio' || field.type === 'checkbox') {
					control = (field.options || field.label).split(',').map(function (option) {
						return '<label style="display:inline-flex;gap:5px;margin-left:10px"><input type="' + field.type + '" /> ' + escapeHtml(option) + '</label>';
					}).join('');
				} else if (field.type === 'upload') {
					control = '<div style="border:2px dashed #e5e2dc;border-radius:10px;padding:14px;text-align:center;color:#6b7280">آپلود فایل</div>';
				} else if (field.type === 'bodymap') {
					control = '<div style="border:2px dashed #e5e2dc;border-radius:10px;padding:14px;text-align:center;color:#6b7280">مانکن سه‌بعدی</div>';
				} else if (field.type === 'color') {
					control = '<input type="color" />';
				} else {
					control = '<input type="' + (field.type === 'number' ? 'number' : field.type === 'date' ? 'date' : 'text') + '" placeholder="' + escapeAttr(field.placeholder || '') + '" />';
				}

				return '<div class="devco-tform__field devco-tform__field--' + (field.width || 'full') + '">' +
					'<label class="devco-tform__label">' + escapeHtml(field.label) + (field.required ? ' <span class="devco-req">*</span>' : '') + '</label>' +
					control +
					'</div>';
			}).join('') +
			'</div></div>';

		if (frame.tagName === 'IFRAME') {
			var doc = frame.contentDocument || frame.contentWindow.document;
			doc.open();
			doc.write('<!doctype html><html dir="rtl"><head><meta charset="utf-8"></head><body style="margin:0;background:#fff">' + html + '</body></html>');
			doc.close();
		} else {
			frame.innerHTML = html;
		}
	};

	function escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function escapeAttr(value) {
		return escapeHtml(value).replace(/'/g, '&#39;');
	}

	/* ------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */
	function boot() {
		initMedia();
		initProductForm();
		initPricing();
		initSms();
		initRepeaters();

		all('[data-builder]').forEach(function (root) {
			new Builder(root);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

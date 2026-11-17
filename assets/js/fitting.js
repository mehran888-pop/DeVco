/**
 * DeVco — virtual fitting room ("اتاق پرو").
 *
 * Everything (photo, webcam stream, composition) happens in the browser;
 * no image ever leaves the customer's device.
 *
 * Notes on the interaction model:
 * - Drag/pinch uses Pointer Events, so mouse, touch and pen all work.
 * - The webcam feed is shown mirrored (like a selfie camera) and the
 *   snapshot is mirrored the same way for WYSIWYG output.
 * - The chosen garment and its transform state are persisted in
 *   localStorage so the customer doesn't lose their try-on on refresh.
 */
(function () {
	'use strict';

	var D = window.DeVcoData || {};
	var i18n = D.i18n || {};

	var SCALE_MIN = 0.4;
	var SCALE_MAX = 2.2;
	var ROTATE_MAX = 45;
	var MAX_UPLOAD_BYTES = 10 * 1024 * 1024; // 10MB

	function all(sel, scope) {
		return Array.prototype.slice.call((scope || document).querySelectorAll(sel));
	}

	function one(sel, scope) {
		return (scope || document).querySelector(sel);
	}

	function clamp(value, min, max) {
		return Math.min(max, Math.max(min, value));
	}

	function isInlineRoot(root) {
		return root.classList.contains('devco-fitting--inline');
	}

	function Fitting(root) {
		this.root = root;
		this.stage = one('[data-fit-stage]', root);
		this.video = one('[data-fit-video]', root);
		this.photo = one('[data-fit-photo]', root);
		this.base = one('[data-fit-base]', root);
		this.cloth = one('[data-fit-cloth]', root);
		this.clothImg = one('[data-fit-cloth-img]', root);
		this.empty = one('[data-fit-empty]', root);
		this.upload = one('[data-fit-upload]', root);
		this.guides = one('[data-fit-guides]', root);
		this.stream = null;

		// The garment product explicitly bound to this room (e.g. product page).
		this.defaultProductId = parseInt(root.getAttribute('data-product-id'), 10) || 0;
		this.productId = 0;
		this.currentImage = '';

		this.configuredOpacity = clamp(parseFloat(root.getAttribute('data-opacity')) || 0.88, 0.2, 1);
		this.state = { x: 0, y: 0, scale: 1, rotate: 0, opacity: this.configuredOpacity };

		// Active pointers, used for drag (1) and pinch-to-zoom (2).
		this.pointers = new Map();
		this.dragStart = null;
		this.pinchStart = null;
		this.lastFocused = null;
		this.saveTimer = null;
		this.storageKey = 'devco_fitting_' + (root.id || 'default');

		this.init();
		this.restore();
	}

	Fitting.prototype.init = function () {
		var self = this;

		// Close buttons.
		all('[data-fit-close]', this.root).forEach(function (btn) {
			btn.addEventListener('click', function () {
				self.close();
			});
		});

		// Photo upload.
		if (this.upload) {
			this.upload.addEventListener('change', function () {
				var file = self.upload.files && self.upload.files[0];
				if (file) {
					self.usePhoto(file);
				}
				// Allow picking the same file twice in a row.
				self.upload.value = '';
			});
		}

		// Webcam toggle.
		var webcamBtn = one('[data-fit-webcam]', this.root);
		if (webcamBtn) {
			webcamBtn.addEventListener('click', function () {
				if (self.stream) {
					self.stopCamera();
					webcamBtn.classList.remove('is-active');
				} else {
					self.startCamera(webcamBtn);
				}
			});
		}

		// Reset.
		var resetBtn = one('[data-fit-reset]', this.root);
		if (resetBtn) {
			resetBtn.addEventListener('click', function () {
				self.reset();
			});
		}

		// Snapshot.
		var snapBtn = one('[data-fit-snapshot]', this.root);
		if (snapBtn) {
			snapBtn.addEventListener('click', function () {
				self.snapshot();
			});
		}

		// Garment selection.
		all('[data-fit-item]', this.root).forEach(function (item) {
			item.addEventListener('click', function () {
				self.selectCloth(item);
			});
		});

		// Range inputs.
		all('[data-fit-range]', this.root).forEach(function (range) {
			range.addEventListener('input', function () {
				var key = range.getAttribute('data-fit-range');
				var value = parseFloat(range.value);
				if (Number.isNaN(value)) {
					return;
				}
				if (key === 'opacity') {
					self.state.opacity = clamp(value / 100, 0.2, 1);
				} else if (key === 'scale') {
					self.state.scale = clamp(value / 100, SCALE_MIN, SCALE_MAX);
				} else if (key === 'rotate') {
					self.state.rotate = clamp(value, -ROTATE_MAX, ROTATE_MAX);
				}
				self.applyTransform();
			});
			range.addEventListener('change', function () {
				self.save();
			});
		});

		// Add to cart.
		var addBtn = one('[data-fit-add]', this.root);
		if (addBtn) {
			addBtn.addEventListener('click', function () {
				self.addToCart(addBtn);
			});
		}

		if (this.stage) {
			// Pointer-based drag & pinch.
			this.stage.addEventListener('pointerdown', function (event) {
				self.onPointerDown(event);
			});
			window.addEventListener('pointermove', function (event) {
				self.onPointerMove(event);
			}, { passive: false });
			window.addEventListener('pointerup', function (event) {
				self.onPointerUp(event);
			});
			window.addEventListener('pointercancel', function (event) {
				self.onPointerUp(event);
			});

			// Wheel zoom (kept for desktop mice).
			this.stage.addEventListener('wheel', function (event) {
				if (!self.cloth || self.cloth.hidden) {
					return;
				}
				event.preventDefault();
				self.state.scale = clamp(self.state.scale + (event.deltaY < 0 ? 0.05 : -0.05), SCALE_MIN, SCALE_MAX);
				self.applyTransform();
				self.syncControls();
				self.saveSoon();
			}, { passive: false });
		}

		// Keyboard: Escape closes, Tab is trapped while the panel is open.
		this.root.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				self.close();
				return;
			}
			if (event.key === 'Tab') {
				self.trapTab(event);
			}
		});
	};

	/* ------------------------------------------------------------------
	 * Pointer gestures (drag + pinch-to-zoom)
	 * ------------------------------------------------------------------ */

	Fitting.prototype.clothVisible = function () {
		return this.cloth && !this.cloth.hidden;
	};

	Fitting.prototype.onPointerDown = function (event) {
		if (!this.clothVisible()) {
			return;
		}

		// Only primary button / touch contact.
		if (event.pointerType === 'mouse' && event.button !== 0) {
			return;
		}

		this.pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

		if (this.pointers.size === 1) {
			this.dragStart = {
				startX: event.clientX,
				startY: event.clientY,
				originX: this.state.x,
				originY: this.state.y,
			};
			this.cloth.classList.add('is-dragging');
			this.showGuides(true);
		} else if (this.pointers.size === 2) {
			var points = Array.from(this.pointers.values());
			this.pinchStart = {
				distance: Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y) || 1,
				center: { x: (points[0].x + points[1].x) / 2, y: (points[0].y + points[1].y) / 2 },
				scale: this.state.scale,
				x: this.state.x,
				y: this.state.y,
			};
			this.dragStart = null;
		}

		// Keep the page from selecting text / scrolling mid-gesture.
		if (event.cancelable) {
			event.preventDefault();
		}
	};

	Fitting.prototype.onPointerMove = function (event) {
		if (!this.pointers.has(event.pointerId)) {
			return;
		}

		this.pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

		if (this.pointers.size === 1 && this.dragStart) {
			this.state.x = this.dragStart.originX + (event.clientX - this.dragStart.startX);
			this.state.y = this.dragStart.originY + (event.clientY - this.dragStart.startY);
			this.applyTransform();
			if (event.cancelable) {
				event.preventDefault();
			}
			return;
		}

		if (this.pointers.size === 2 && this.pinchStart) {
			var points = Array.from(this.pointers.values());
			var distance = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y) || 1;
			var center = { x: (points[0].x + points[1].x) / 2, y: (points[0].y + points[1].y) / 2 };

			this.state.scale = clamp(this.pinchStart.scale * (distance / this.pinchStart.distance), SCALE_MIN, SCALE_MAX);
			this.state.x = this.pinchStart.x + (center.x - this.pinchStart.center.x);
			this.state.y = this.pinchStart.y + (center.y - this.pinchStart.center.y);
			this.applyTransform();
			this.syncControls();
			if (event.cancelable) {
				event.preventDefault();
			}
		}
	};

	Fitting.prototype.onPointerUp = function (event) {
		if (!this.pointers.has(event.pointerId)) {
			return;
		}

		this.pointers.delete(event.pointerId);

		if (this.pointers.size === 1) {
			// Transition from pinch back to a single-finger drag without a jump.
			var remaining = Array.from(this.pointers.values())[0];
			this.dragStart = {
				startX: remaining.x,
				startY: remaining.y,
				originX: this.state.x,
				originY: this.state.y,
			};
			this.pinchStart = null;
		} else if (this.pointers.size === 0) {
			this.dragStart = null;
			this.pinchStart = null;
			if (this.cloth) {
				this.cloth.classList.remove('is-dragging');
			}
			this.showGuides(false);
			this.save();
		}
	};

	Fitting.prototype.showGuides = function (visible) {
		if (this.stage) {
			this.stage.classList.toggle('is-guiding', !!visible);
		}
	};

	/* ------------------------------------------------------------------
	 * Rendering
	 * ------------------------------------------------------------------ */

	Fitting.prototype.applyTransform = function () {
		if (!this.cloth) {
			return;
		}

		this.cloth.style.transform =
			'translate(-50%, 0) translate(' + this.state.x + 'px,' + this.state.y + 'px) ' +
			'scale(' + this.state.scale + ') rotate(' + this.state.rotate + 'deg)';
		this.cloth.style.opacity = String(this.state.opacity);
	};

	Fitting.prototype.syncControls = function () {
		var self = this;
		all('[data-fit-range]', this.root).forEach(function (range) {
			var key = range.getAttribute('data-fit-range');
			if (key === 'opacity') {
				range.value = String(Math.round(self.state.opacity * 100));
			} else if (key === 'scale') {
				range.value = String(Math.round(self.state.scale * 100));
			} else if (key === 'rotate') {
				range.value = String(Math.round(self.state.rotate));
			}
		});
	};

	/* ------------------------------------------------------------------
	 * Persistence
	 * ------------------------------------------------------------------ */

	Fitting.prototype.saveSoon = function () {
		var self = this;
		window.clearTimeout(this.saveTimer);
		this.saveTimer = window.setTimeout(function () {
			self.save();
		}, 400);
	};

	Fitting.prototype.save = function () {
		if (!this.currentImage) {
			return;
		}
		try {
			window.localStorage.setItem(this.storageKey, JSON.stringify({
				image: this.currentImage,
				product: this.productId || 0,
				x: Math.round(this.state.x),
				y: Math.round(this.state.y),
				scale: this.state.scale,
				rotate: this.state.rotate,
				opacity: this.state.opacity
			}));
		} catch (e) {
			/* Storage full or unavailable — the try-on still works, just not persisted. */
		}
	};

	Fitting.prototype.clearSaved = function () {
		try {
			window.localStorage.removeItem(this.storageKey);
		} catch (e) {
			/* ignore */
		}
	};

	Fitting.prototype.restore = function () {
		var saved = null;
		try {
			saved = JSON.parse(window.localStorage.getItem(this.storageKey) || 'null');
		} catch (e) {
			saved = null;
		}

		if (!saved || !saved.image) {
			return;
		}

		var thumb = all('[data-fit-item]', this.root).filter(function (el) {
			return el.getAttribute('data-image') === saved.image;
		})[0];

		if (!thumb) {
			return;
		}

		this.selectCloth(thumb, { silent: true });

		if (typeof saved.x === 'number') { this.state.x = saved.x; }
		if (typeof saved.y === 'number') { this.state.y = saved.y; }
		if (typeof saved.scale === 'number') { this.state.scale = clamp(saved.scale, SCALE_MIN, SCALE_MAX); }
		if (typeof saved.rotate === 'number') { this.state.rotate = clamp(saved.rotate, -ROTATE_MAX, ROTATE_MAX); }
		if (typeof saved.opacity === 'number') { this.state.opacity = clamp(saved.opacity, 0.2, 1); }

		this.applyTransform();
		this.syncControls();
	};

	/* ------------------------------------------------------------------
	 * Photo / webcam / reset
	 * ------------------------------------------------------------------ */

	Fitting.prototype.usePhoto = function (file) {
		if (!this.photo || !window.FileReader) {
			return;
		}

		if (file.type && file.type.indexOf('image/') !== 0) {
			window.Devco.toast('لطفاً یک فایل تصویری انتخاب کنید.', 'error');
			return;
		}

		if (file.size && file.size > MAX_UPLOAD_BYTES) {
			window.Devco.toast('حداکثر حجم تصویر ۱۰ مگابایت است.', 'error');
			return;
		}

		var self = this;
		var reader = new FileReader();

		reader.onload = function (event) {
			self.stopCamera();
			self.stage.classList.remove('is-mirrored');
			self.photo.src = event.target.result;
			self.photo.hidden = false;
			if (self.base && self.base.tagName === 'IMG') {
				self.base.style.display = 'none';
			}
			self.hideEmpty();
		};
		reader.onerror = function () {
			window.Devco.toast('خواندن تصویر ممکن نشد.', 'error');
		};

		reader.readAsDataURL(file);
	};

	Fitting.prototype.startCamera = function (button) {
		var self = this;

		if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
			window.Devco.toast(i18n.cameraOff || 'دسترسی به دوربین امکان‌پذیر نیست.', 'error');
			return;
		}

		navigator.mediaDevices
			.getUserMedia({ video: { facingMode: 'user', width: { ideal: 720 } }, audio: false })
			.then(function (stream) {
				self.stream = stream;
				self.video.srcObject = stream;
				self.video.hidden = false;
				self.video.play();
				if (self.photo) {
					self.photo.hidden = true;
				}
				if (self.base && self.base.tagName === 'IMG') {
					self.base.style.display = 'none';
				}
				if (self.stage) {
					self.stage.classList.add('is-mirrored'); // selfie-style preview
				}
				self.hideEmpty();
				if (button) {
					button.classList.add('is-active');
				}
			})
			.catch(function () {
				window.Devco.toast(i18n.cameraOff || 'دسترسی به دوربین امکان‌پذیر نیست.', 'error');
			});
	};

	Fitting.prototype.stopCamera = function () {
		if (this.stream) {
			this.stream.getTracks().forEach(function (track) {
				track.stop();
			});
			this.stream = null;
		}

		if (this.stage) {
			this.stage.classList.remove('is-mirrored');
		}

		if (this.video) {
			this.video.srcObject = null;
			this.video.hidden = true;
		}

		all('[data-fit-webcam]', this.root).forEach(function (btn) {
			btn.classList.remove('is-active');
		});
	};

	Fitting.prototype.hideEmpty = function () {
		if (this.empty) {
			this.empty.hidden = true;
		}
	};

	Fitting.prototype.reset = function () {
		this.stopCamera();
		this.state = { x: 0, y: 0, scale: 1, rotate: 0, opacity: this.configuredOpacity };
		this.applyTransform();
		this.syncControls();
		this.clearSaved();

		if (this.photo) {
			this.photo.hidden = true;
			this.photo.removeAttribute('src');
		}

		if (this.base && this.base.tagName === 'IMG') {
			this.base.style.display = '';
		}

		if (this.empty && !this.currentImage) {
			this.empty.hidden = false;
		}
	};

	/* ------------------------------------------------------------------
	 * Garment selection
	 * ------------------------------------------------------------------ */

	Fitting.prototype.loadCloth = function (image, title, product) {
		var self = this;

		if (!this.clothImg) {
			return;
		}

		this.currentImage = image;
		this.productId = parseInt(product, 10) || 0;
		this.state.x = 0;
		this.state.y = 0;

		this.cloth.classList.add('is-loading');

		// Swap handlers before src so cached images still fire correctly.
		this.clothImg.onload = function () {
			self.cloth.classList.remove('is-loading');
			self.applyTransform();
			self.syncControls();
			self.save();
		};
		this.clothImg.onerror = function () {
			self.cloth.classList.remove('is-loading');
			window.Devco.toast('بارگذاری تصویر لباس ممکن نشد.', 'error');
			if (!self.clothImg.getAttribute('src')) {
				self.cloth.hidden = true;
				self.currentImage = '';
				self.productId = 0;
			}
		};

		this.clothImg.src = image;
		this.cloth.hidden = false;

		var label = one('[data-fit-selected-title]', this.root);
		if (label) {
			label.textContent = title || 'لباس انتخاب‌شده';
		}

		this.applyTransform();
		this.hideEmpty();
	};

	Fitting.prototype.selectCloth = function (item, opts) {
		opts = opts || {};

		var image = item.getAttribute('data-image');
		if (!image) {
			return;
		}

		this.loadCloth(image, item.getAttribute('data-title') || '', item.getAttribute('data-product') || '0');

		var self = this;
		all('[data-fit-item]', this.root).forEach(function (el) {
			el.classList.toggle('is-active', el === item);
		});

		if (!opts.silent) {
			this.saveSoon();
		}
	};

	/* ------------------------------------------------------------------
	 * Snapshot
	 * ------------------------------------------------------------------ */

	Fitting.prototype.snapshot = function () {
		if (!this.stage) {
			return;
		}

		var stageW = this.stage.clientWidth;
		var stageH = this.stage.clientHeight;
		var SCALE = 2; // render at 2x for a crisper download
		var canvas = document.createElement('canvas');
		canvas.width = stageW * SCALE;
		canvas.height = stageH * SCALE;

		var ctx = canvas.getContext('2d');
		ctx.fillStyle = '#101319';
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		ctx.scale(SCALE, SCALE);

		function drawFit(source, mode, mirror) {
			var sw = source.videoWidth || source.naturalWidth || source.width;
			var sh = source.videoHeight || source.naturalHeight || source.height;
			if (!sw || !sh) {
				return;
			}
			var ratio = mode === 'cover' ? Math.max(stageW / sw, stageH / sh) : Math.min(stageW / sw, stageH / sh);
			var dw = sw * ratio;
			var dh = sh * ratio;
			var dx = (stageW - dw) / 2;
			var dy = (stageH - dh) / 2;

			if (mirror) {
				ctx.save();
				ctx.translate(stageW / 2, 0);
				ctx.scale(-1, 1);
				ctx.translate(-stageW / 2, 0);
			}
			ctx.drawImage(source, dx, dy, dw, dh);
			if (mirror) {
				ctx.restore();
			}
		}

		var mirrored = this.stage.classList.contains('is-mirrored');

		if (this.stream && this.video && this.video.readyState >= 2) {
			drawFit(this.video, 'cover', mirrored);
		} else if (this.photo && !this.photo.hidden && this.photo.naturalWidth) {
			drawFit(this.photo, 'contain', false);
		} else if (this.base && this.base.tagName === 'IMG' && this.base.style.display !== 'none' && this.base.naturalWidth) {
			drawFit(this.base, 'contain', false);
		}

		if (this.clothVisible() && this.clothImg && this.clothImg.naturalWidth) {
			// offsetWidth/Height give the *unscaled* layout box; the transform
			// (translate/scale/rotate) is rebuilt here around the same anchor.
			var layoutW = this.cloth.offsetWidth;
			var layoutH = this.cloth.offsetHeight;
			var anchorTop = parseFloat(window.getComputedStyle(this.cloth).top);
			if (Number.isNaN(anchorTop)) {
				anchorTop = stageH * 0.24;
			}
			var s = this.state.scale;
			var cx = stageW / 2 + this.state.x;
			var cy = anchorTop + layoutH / 2 + this.state.y;

			ctx.save();
			ctx.globalAlpha = this.state.opacity;
			ctx.translate(cx, cy);
			ctx.rotate((this.state.rotate * Math.PI) / 180);
			ctx.drawImage(this.clothImg, (-layoutW * s) / 2, (-layoutH * s) / 2, layoutW * s, layoutH * s);
			ctx.restore();
		}

		try {
			var link = document.createElement('a');
			link.href = canvas.toDataURL('image/png');
			link.download = 'devco-fitting.png';
			link.click();
			window.Devco.toast('تصویر ذخیره شد');
		} catch (e) {
			window.Devco.toast('ذخیره تصویر ممکن نشد.', 'error');
		}
	};

	/* ------------------------------------------------------------------
	 * Add to cart
	 * ------------------------------------------------------------------ */

	Fitting.prototype.addToCart = function (button) {
		var self = this;
		var productId = this.productId || this.defaultProductId;

		if (!productId) {
			window.Devco.toast(this.currentImage ? 'برای این لباس محصولی متصل نشده است.' : 'ابتدا یک لباس انتخاب کنید.', 'error');
			return;
		}

		button.classList.add('is-loading');
		button.setAttribute('disabled', 'disabled');

		window.Devco.post('devco_buy_now', { product_id: productId, quantity: 1, skip_cart: 0, ajax_add: 1 }).then(function (res) {
			button.classList.remove('is-loading');
			button.removeAttribute('disabled');

			if (res && res.success) {
				button.classList.add('is-success');
				window.setTimeout(function () {
					button.classList.remove('is-success');
				}, 1800);

				if (typeof window.devcoRefreshCart === 'function') {
					window.devcoRefreshCart();
				}

				window.Devco.toast(i18n.added || 'به سبد خرید اضافه شد');
			} else {
				window.Devco.toast(res && res.data && res.data.message ? res.data.message : (i18n.error || 'خطایی رخ داد'), 'error');
			}
		}).catch(function () {
			button.classList.remove('is-loading');
			button.removeAttribute('disabled');
			window.Devco.toast(i18n.error || 'خطایی رخ داد', 'error');
		});
	};

	/* ------------------------------------------------------------------
	 * Open / close / focus management
	 * ------------------------------------------------------------------ */

	Fitting.prototype.focusable = function () {
		return all('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])', this.root)
			.filter(function (el) {
				return !el.disabled && el.offsetParent !== null;
			});
	};

	Fitting.prototype.trapTab = function (event) {
		if (isInlineRoot(this.root) || !this.root.classList.contains('is-open')) {
			return;
		}

		var focusable = this.focusable();
		if (!focusable.length) {
			return;
		}

		var first = focusable[0];
		var last = focusable[focusable.length - 1];

		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	};

	Fitting.prototype.open = function () {
		if (isInlineRoot(this.root)) {
			return; // Embedded variant is always visible; nothing to toggle.
		}

		this.lastFocused = document.activeElement;
		this.root.classList.add('is-open');
		this.root.setAttribute('aria-hidden', 'false');

		if (typeof window.devcoOpenOverlay === 'function') {
			window.devcoOpenOverlay(this.root);
		} else {
			document.body.classList.add('devco-locked');
		}

		var firstItem = one('[data-fit-item]', this.root);
		if (firstItem && !one('[data-fit-item].is-active', this.root)) {
			firstItem.click();
		} else {
			this.applyTransform();
		}

		var closeBtn = one('.devco-fitting__close', this.root);
		if (closeBtn) {
			window.setTimeout(function () {
				closeBtn.focus();
			}, 120);
		}
	};

	Fitting.prototype.close = function () {
		if (isInlineRoot(this.root)) {
			return;
		}

		this.root.classList.remove('is-open');
		this.root.setAttribute('aria-hidden', 'true');
		this.stopCamera();
		this.pointers.clear();
		this.dragStart = null;
		this.pinchStart = null;
		this.showGuides(false);

		if (typeof window.devcoCloseOverlay === 'function') {
			window.devcoCloseOverlay(this.root);
		} else {
			document.body.classList.remove('devco-locked');
		}

		if (this.lastFocused && typeof this.lastFocused.focus === 'function') {
			this.lastFocused.focus();
		}
		this.lastFocused = null;
	};

	/* ------------------------------------------------------------------
	 * Boot — idempotent so `devco:refresh` never double-binds
	 * ------------------------------------------------------------------ */

	var instances = [];

	function findInstance(root) {
		for (var i = 0; i < instances.length; i++) {
			if (instances[i].root === root) {
				return instances[i];
			}
		}
		return null;
	}

	function init() {
		all('[data-fitting]').forEach(function (root) {
			if (root.getAttribute('data-fit-bound') === '1') {
				return;
			}
			root.setAttribute('data-fit-bound', '1');
			instances.push(new Fitting(root));
		});

		all('.js-devco-fitting-open, [data-fitting-trigger]').forEach(function (trigger) {
			if (trigger.getAttribute('data-fit-trigger-bound') === '1') {
				return;
			}
			trigger.setAttribute('data-fit-trigger-bound', '1');

			trigger.addEventListener('click', function (event) {
				event.preventDefault();

				var targetId = trigger.getAttribute('data-fit-target');
				var root = targetId ? one('[data-fitting]#' + (window.CSS && CSS.escape ? CSS.escape(targetId) : targetId)) : all('[data-fitting]')[0];

				if (!root) {
					return;
				}

				var instance = findInstance(root);

				if (!instance) {
					root.setAttribute('data-fit-bound', '1');
					instance = new Fitting(root);
					instances.push(instance);
				}

				// A trigger may carry its own product context (product pages).
				var product = parseInt(trigger.getAttribute('data-product-id'), 10) || 0;
				var thumb = trigger.getAttribute('data-fit-image');
				if (product) {
					instance.defaultProductId = product;
					if (!instance.currentImage && thumb) {
						instance.loadCloth(thumb, trigger.getAttribute('data-fit-title') || '', product);
					} else {
						instance.productId = instance.productId || product;
					}
				}

				instance.open();
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	document.addEventListener('devco:refresh', init);
})();

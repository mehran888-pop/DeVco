/**
 * DeVco — virtual fitting room ("اتاق پرو").
 *
 * Everything (photo, webcam stream, composition) happens in the browser;
 * no image ever leaves the customer's device.
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
		this.stream = null;
		this.productId = 0;
		this.state = { x: 0, y: 0, scale: 1, rotate: 0, opacity: parseFloat(root.getAttribute('data-opacity')) || 0.88 };
		this.init();
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
				if (!file) {
					return;
				}
				self.usePhoto(file);
			});
		}

		// Webcam.
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

		// Ranges.
		all('[data-fit-range]', this.root).forEach(function (range) {
			range.addEventListener('input', function () {
				var key = range.getAttribute('data-fit-range');
				var value = parseFloat(range.value);
				if (key === 'opacity') {
					self.state.opacity = Math.max(0.2, value / 100);
				} else if (key === 'scale') {
					self.state.scale = Math.max(0.5, value / 100);
				} else if (key === 'rotate') {
					self.state.rotate = value;
				}
				self.applyTransform();
			});
		});

		// Add to cart.
		var addBtn = one('[data-fit-add]', this.root);
		if (addBtn) {
			addBtn.addEventListener('click', function () {
				self.addToCart(addBtn);
			});
		}

		// Drag & scale gestures on the stage.
		if (this.stage) {
			this.bindDrag();
			this.stage.addEventListener('wheel', function (event) {
				if (!self.cloth || self.cloth.hidden) {
					return;
				}
				event.preventDefault();
				self.state.scale = Math.min(2.2, Math.max(0.4, self.state.scale + (event.deltaY < 0 ? 0.05 : -0.05)));
				self.applyTransform();
			}, { passive: false });
		}

		// Keyboard remove.
		this.root.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				self.close();
			}
		});
	};

	Fitting.prototype.bindDrag = function () {
		var self = this;
		var dragging = false;
		var startX = 0;
		var startY = 0;
		var originX = 0;
		var originY = 0;

		function point(event) {
			return event.touches && event.touches[0] ? event.touches[0] : event;
		}

		function start(event) {
			if (!self.cloth || self.cloth.hidden) {
				return;
			}
			dragging = true;
			var p = point(event);
			startX = p.clientX;
			startY = p.clientY;
			originX = self.state.x;
			originY = self.state.y;
			self.cloth.classList.add('is-dragging');
		}

		function move(event) {
			if (!dragging) {
				return;
			}
			var p = point(event);
			self.state.x = originX + (p.clientX - startX);
			self.state.y = originY + (p.clientY - startY);
			self.applyTransform();
			event.preventDefault();
		}

		function end() {
			dragging = false;
			if (self.cloth) {
				self.cloth.classList.remove('is-dragging');
			}
		}

		this.stage.addEventListener('mousedown', start);
		window.addEventListener('mousemove', move);
		window.addEventListener('mouseup', end);

		this.stage.addEventListener('touchstart', start, { passive: true });
		this.stage.addEventListener('touchmove', move, { passive: false });
		this.stage.addEventListener('touchend', end);
	};

	Fitting.prototype.applyTransform = function () {
		if (!this.cloth) {
			return;
		}

		this.cloth.style.transform = 'translate(-50%, 0) translate(' + this.state.x + 'px,' + this.state.y + 'px) scale(' + this.state.scale + ') rotate(' + this.state.rotate + 'deg)';
		this.cloth.style.opacity = String(this.state.opacity);
	};

	Fitting.prototype.usePhoto = function (file) {
		if (!this.photo || !window.FileReader) {
			return;
		}

		var self = this;
		var reader = new FileReader();

		reader.onload = function (event) {
			self.stopCamera();
			self.photo.src = event.target.result;
			self.photo.hidden = false;
			if (self.base && self.base.tagName === 'IMG') {
				self.base.style.display = 'none';
			}
			if (self.empty) {
				self.empty.hidden = true;
			}
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
				if (self.empty) {
					self.empty.hidden = true;
				}
				if (button) {
					button.classList.add('is-active');
				}
			})
			.catch(function () {
				window.Devco.toast(i18n.cameraOff || 'دسترسی به دوربین امکان‌پذیر نیست.', 'error');
			});
	};

	Fitting.prototype.stopCamera = function () {
		if (!this.stream) {
			return;
		}

		this.stream.getTracks().forEach(function (track) {
			track.stop();
		});

		this.stream = null;

		if (this.video) {
			this.video.srcObject = null;
			this.video.hidden = true;
		}
	};

	Fitting.prototype.reset = function () {
		this.stopCamera();
		this.state = { x: 0, y: 0, scale: 1, rotate: 0, opacity: parseFloat(this.root.getAttribute('data-opacity')) || 0.88 };
		this.applyTransform();

		if (this.photo) {
			this.photo.hidden = true;
			this.photo.removeAttribute('src');
		}

		if (this.base && this.base.tagName === 'IMG') {
			this.base.style.display = '';
		}

		if (this.empty) {
			this.empty.hidden = false;
		}

		all('[data-fit-range]', this.root).forEach(function (range) {
			if (range.getAttribute('data-fit-range') === 'opacity') {
				range.value = String(Math.round((parseFloat(range.getAttribute('data-opacity')) || 0.88) * 100));
			} else {
				range.value = range.getAttribute('data-fit-range') === 'scale' ? '100' : '0';
			}
		});

		all('[data-fit-webcam]', this.root).forEach(function (btn) {
			btn.classList.remove('is-active');
		});
	};

	Fitting.prototype.selectCloth = function (item) {
		var image = item.getAttribute('data-image');
		if (!image || !this.clothImg) {
			return;
		}

		this.clothImg.src = image;
		this.cloth.hidden = false;
		this.productId = parseInt(item.getAttribute('data-product'), 10) || 0;
		this.state.x = 0;
		this.state.y = 0;
		this.applyTransform();

		all('[data-fit-item]', this.root).forEach(function (el) {
			el.classList.toggle('is-active', el === item);
		});

		var label = one('[data-fit-selected-title]', this.root);
		if (label) {
			label.textContent = item.getAttribute('data-title') || 'لباس انتخاب‌شده';
		}

		if (this.empty) {
			this.empty.hidden = true;
		}
	};

	Fitting.prototype.snapshot = function () {
		if (!this.stage) {
			return;
		}

		var width = this.stage.clientWidth;
		var height = this.stage.clientHeight;
		var canvas = document.createElement('canvas');
		canvas.width = width * 2;
		canvas.height = height * 2;

		var ctx = canvas.getContext('2d');
		ctx.fillStyle = '#101319';
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		ctx.scale(2, 2);

		function drawCover(source) {
			var sw = source.videoWidth || source.naturalWidth || source.width;
			var sh = source.videoHeight || source.naturalHeight || source.height;
			if (!sw || !sh) {
				return null;
			}

			var ratio = Math.max(width / sw, height / sh);
			var dw = sw * ratio;
			var dh = sh * ratio;
			return { x: (width - dw) / 2, y: (height - dh) / 2, w: dw, h: dh };
		}

		if (this.stream && this.video && this.video.readyState >= 2) {
			var videoBox = drawCover(this.video);
			if (videoBox) {
				ctx.drawImage(this.video, videoBox.x, videoBox.y, videoBox.w, videoBox.h);
			}
		} else if (this.photo && !this.photo.hidden && this.photo.naturalWidth) {
			var photoBox = drawCover(this.photo);
			if (photoBox) {
				ctx.drawImage(this.photo, photoBox.x, photoBox.y, photoBox.w, photoBox.h);
			}
		}

		if (this.cloth && !this.cloth.hidden && this.clothImg && this.clothImg.naturalWidth) {
			var rect = this.cloth.getBoundingClientRect();
			var stageRect = this.stage.getBoundingClientRect();
			var cw = rect.width * this.state.scale;
			var ch = rect.height * this.state.scale;
			var cx = rect.left - stageRect.left + (rect.width - cw) / 2;
			var cy = rect.top - stageRect.top;

			ctx.save();
			ctx.globalAlpha = this.state.opacity;
			ctx.translate(cx + cw / 2, cy + ch / 2);
			ctx.rotate((this.state.rotate * Math.PI) / 180);
			ctx.drawImage(this.clothImg, -cw / 2, -ch / 2, cw, ch);
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

	Fitting.prototype.addToCart = function (button) {
		var self = this;

		if (!this.productId) {
			window.Devco.toast('برای این لباس محصولی متصل نشده است.', 'error');
			return;
		}

		button.classList.add('is-loading');

		window.Devco.post('devco_buy_now', { product_id: this.productId, quantity: 1, skip_cart: 0, ajax_add: 1 }).then(function (res) {
			button.classList.remove('is-loading');

			if (res && res.success) {
				button.classList.add('is-success');
				setTimeout(function () {
					button.classList.remove('is-success');
				}, 1800);

				if (typeof window.devcoRefreshCart === 'function') {
					window.devcoRefreshCart();
				}

				window.Devco.toast(i18n.added || 'به سبد خرید اضافه شد');
			} else {
				window.Devco.toast(res && res.data && res.data.message ? res.data.message : i18n.error, 'error');
			}
		}).catch(function () {
			button.classList.remove('is-loading');
			window.Devco.toast(i18n.error, 'error');
		});
	};

	Fitting.prototype.open = function () {
		this.root.classList.add('is-open');
		this.root.setAttribute('aria-hidden', 'false');

		if (typeof window.devcoOpenOverlay === 'function') {
			window.devcoOpenOverlay(this.root);
		} else {
			document.body.classList.add('devco-locked');
		}

		var first = one('[data-fit-item]', this.root);
		if (first && !one('[data-fit-item].is-active', this.root)) {
			first.click();
		} else {
			this.applyTransform();
		}
	};

	Fitting.prototype.close = function () {
		this.root.classList.remove('is-open');
		this.root.setAttribute('aria-hidden', 'true');
		this.stopCamera();

		if (typeof window.devcoCloseOverlay === 'function') {
			window.devcoCloseOverlay(this.root);
		} else {
			document.body.classList.remove('devco-locked');
		}
	};

	/* ------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */
	function init() {
		var rooms = [];

		all('[data-fitting]').forEach(function (root) {
			if (root.classList.contains('devco-fitting--inline')) {
				rooms.push(new Fitting(root));
				return;
			}
			rooms.push(new Fitting(root));
		});

		var byId = {};
		all('[data-fitting]').forEach(function (root) {
			byId[root.id] = root;
		});

		all('.js-devco-fitting-open, [data-fitting-trigger]').forEach(function (trigger) {
			trigger.addEventListener('click', function (event) {
				event.preventDefault();

				var targetId = trigger.getAttribute('data-fit-target');
				var root = targetId && byId[targetId] ? byId[targetId] : all('[data-fitting]')[0];

				if (!root) {
					return;
				}

				var instance = null;
				rooms.forEach(function (room) {
					if (room.root === root) {
						instance = room;
					}
				});

				if (!instance) {
					instance = new Fitting(root);
					rooms.push(instance);
				}

				var product = trigger.getAttribute('data-product-id');
				if (product) {
					instance.productId = parseInt(product, 10) || instance.productId;
					var thumb = trigger.getAttribute('data-fit-image');
					if (thumb && instance.clothImg) {
						instance.clothImg.src = thumb;
						instance.cloth.hidden = false;
					}
				}

				instance.open();
			});
		});

		// Inline auto-init for Elementor previews.
		all('.devco-fitting--inline [data-fit-item]').forEach(function (item) {
			if (!one('[data-fit-item].is-active', item.parentNode) && item === item.parentNode.firstElementChild) {
				item.click();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	document.addEventListener('devco:refresh', init);
})();

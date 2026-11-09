/**
 * Safak Hospitals Manager - Frontend Script
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {

		/* 1. SMOOTH SCROLLING FOR ANCHORS */
		document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
			anchor.addEventListener('click', function(e) {
				var targetId = this.getAttribute('href');
				if (!targetId || targetId === '#' || targetId.length < 2) return;
				var targetEl = document.querySelector(targetId);
				if (targetEl) {
					e.preventDefault();
					targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
					if (history.pushState) {
						history.pushState(null, null, targetId);
					}
				}
			});
		});

		/* 2. STATS COUNTER ANIMATION */
		var statNumbers = document.querySelectorAll('.shm-stat-number[data-target]');
		if (statNumbers.length && 'IntersectionObserver' in window) {
			var observer = new IntersectionObserver(function(entries, obs) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						var el = entry.target;
						var target = parseInt(el.getAttribute('data-target'), 10);
						if (!isNaN(target) && target > 0) {
							animateNumber(el, target);
						}
						obs.unobserve(el);
					}
				});
			}, { threshold: 0.2 });

			statNumbers.forEach(function(el) {
				observer.observe(el);
			});
		}

		function animateNumber(element, target) {
			var duration = 1200;
			var start = 0;
			var startTime = null;

			function step(timestamp) {
				if (!startTime) startTime = timestamp;
				var progress = Math.min((timestamp - startTime) / duration, 1);
				var ease = 1 - (1 - progress) * (1 - progress);
				var current = Math.floor(ease * (target - start) + start);
				element.textContent = current.toLocaleString();
				if (progress < 1) {
					window.requestAnimationFrame(step);
				} else {
					element.textContent = target.toLocaleString();
				}
			}
			window.requestAnimationFrame(step);
		}

		/* 3. VIDEO TOUR FACADE CLICK-TO-LOAD */
		var videoFacades = document.querySelectorAll('[data-shm-video-facade]');
		if (videoFacades.length) {
			videoFacades.forEach(function(facade) {
				function loadVideo(e) {
					if (e) e.preventDefault();
					var embedUrl = facade.getAttribute('data-embed');
					if (!embedUrl) return;

					var iframe = document.createElement('iframe');
					iframe.setAttribute('src', embedUrl);
					iframe.setAttribute('frameborder', '0');
					iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
					iframe.setAttribute('allowfullscreen', 'true');
					iframe.style.position = 'absolute';
					iframe.style.top = '0';
					iframe.style.left = '0';
					iframe.style.width = '100%';
					iframe.style.height = '100%';
					iframe.style.border = 'none';

					var wrap = document.createElement('div');
					wrap.className = 'shm-video-responsive';
					wrap.appendChild(iframe);

					facade.parentNode.replaceChild(wrap, facade);
				}

				facade.addEventListener('click', loadVideo);
				facade.addEventListener('keydown', function(e) {
					if (e.key === 'Enter' || e.key === ' ') {
						loadVideo(e);
					}
				});
			});
		}

		/* 4. PHOTO GALLERY SHOWCASE & BLURRED LIGHTBOX */
		var galleryContainer = document.querySelector('[data-shm-gallery-lightbox]');
		if (galleryContainer) {
			var links = Array.prototype.slice.call(galleryContainer.querySelectorAll('[data-shm-gallery-item]'));
			var mainStage = galleryContainer.querySelector('[data-shm-showcase-main]');
			var mainImg = galleryContainer.querySelector('[data-shm-showcase-img]');
			var captionEl = galleryContainer.querySelector('[data-shm-showcase-caption]');

			if (links.length) {
				var thumbsHtml = '';
				links.forEach(function(item, i) {
					var thumbSrc = item.getAttribute('data-thumb') || item.getAttribute('data-full');
					var cap = item.getAttribute('data-caption') || '';
					thumbsHtml += '<div role="button" tabindex="0" class="shm-lightbox-thumb" data-thumb-idx="' + i + '" aria-label="Photo ' + (i + 1) + '">' +
						'<img src="' + thumbSrc + '" alt="' + cap.replace(/"/g, '&quot;') + '" loading="lazy">' +
						'</div>';
				});

				var modal = document.createElement('div');
				modal.className = 'shm-lightbox-modal';
				modal.setAttribute('role', 'dialog');
				modal.setAttribute('aria-modal', 'true');
				modal.innerHTML = '' +
					'<div class="shm-lightbox-counter">1 / ' + links.length + '</div>' +
					'<div role="button" tabindex="0" class="shm-lightbox-close" aria-label="Close">' +
						'<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
					'</div>' +
					'<div role="button" tabindex="0" class="shm-lightbox-nav shm-lightbox-nav--prev" aria-label="Previous">' +
						'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 18-6-6 6-6"/></svg>' +
					'</div>' +
					'<div role="button" tabindex="0" class="shm-lightbox-nav shm-lightbox-nav--next" aria-label="Next">' +
						'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m9 18 6-6-6-6"/></svg>' +
					'</div>' +
					'<div class="shm-lightbox-body">' +
						'<div class="shm-lightbox-content">' +
							'<img src="" alt="" class="shm-lightbox-img">' +
							'<div class="shm-lightbox-caption"></div>' +
						'</div>' +
						'<div class="shm-lightbox-thumbs">' + thumbsHtml + '</div>' +
					'</div>';
				document.body.appendChild(modal);

				var currentIndex = 0;
				var modalImg = modal.querySelector('.shm-lightbox-img');
				var modalCaption = modal.querySelector('.shm-lightbox-caption');
				var modalCounter = modal.querySelector('.shm-lightbox-counter');
				var thumbButtons = Array.prototype.slice.call(modal.querySelectorAll('.shm-lightbox-thumb'));
				var btnClose = modal.querySelector('.shm-lightbox-close');
				var btnPrev = modal.querySelector('.shm-lightbox-nav--prev');
				var btnNext = modal.querySelector('.shm-lightbox-nav--next');

				function setShowcaseIndex(idx) {
					if (idx < 0) idx = links.length - 1;
					if (idx >= links.length) idx = 0;
					currentIndex = idx;
					var item = links[currentIndex];
					var largeSrc = item.getAttribute('data-large') || item.getAttribute('data-full');
					var fullSrc = item.getAttribute('data-full');
					var caption = item.getAttribute('data-caption') || '';

					if (mainImg) {
						mainImg.style.opacity = '0.4';
						mainImg.setAttribute('src', largeSrc);
						mainImg.setAttribute('alt', caption);
						setTimeout(function() {
							mainImg.style.opacity = '1';
						}, 120);
					}
					if (captionEl) {
						captionEl.textContent = caption;
					}
					if (mainStage) {
						mainStage.setAttribute('href', fullSrc);
						mainStage.setAttribute('data-current-index', String(currentIndex));
					}
					links.forEach(function(l, lIdx) {
						if (lIdx === currentIndex) {
							l.classList.add('is-active');
						} else {
							l.classList.remove('is-active');
						}
					});
				}

				function showImage(idx) {
					setShowcaseIndex(idx);
					var item = links[currentIndex];
					var fullSrc = item.getAttribute('data-full');
					var caption = item.getAttribute('data-caption') || '';
					modalImg.setAttribute('src', fullSrc);
					modalCaption.textContent = caption;
					if (modalCounter) {
						modalCounter.textContent = (currentIndex + 1) + ' / ' + links.length;
					}
					thumbButtons.forEach(function(btn, tIdx) {
						if (tIdx === currentIndex) {
							btn.classList.add('is-active');
							if (btn.scrollIntoView) {
								btn.scrollIntoView({ block: 'nearest', inline: 'nearest' });
							}
						} else {
							btn.classList.remove('is-active');
						}
					});
				}

				function openModal(idx) {
					showImage(idx);
					modal.classList.add('is-open');
					document.body.style.overflow = 'hidden';
				}

				function closeModal() {
					modal.classList.remove('is-open');
					document.body.style.overflow = '';
					modalImg.setAttribute('src', '');
				}

				// Clicking a bottom thumbnail strictly switches the main showcase photo (NO expanding/lightbox)
				links.forEach(function(link, i) {
					link.addEventListener('click', function(e) {
						e.preventDefault();
						e.stopPropagation();
						setShowcaseIndex(i);
					});
				});

				// Clicking the main top stage image opens the blurred lightbox with side thumbnails
				if (mainStage) {
					mainStage.addEventListener('click', function(e) {
						e.preventDefault();
						var idx = parseInt(mainStage.getAttribute('data-current-index'), 10) || 0;
						openModal(idx);
					});
				}
						openModal(idx);
					});
				}

				thumbButtons.forEach(function(btn) {
					btn.addEventListener('click', function() {
						var idx = parseInt(this.getAttribute('data-thumb-idx'), 10);
						if (!isNaN(idx)) {
							showImage(idx);
						}
					});
				});

				btnClose.addEventListener('click', closeModal);
				btnPrev.addEventListener('click', function() { showImage(currentIndex - 1); });
				btnNext.addEventListener('click', function() { showImage(currentIndex + 1); });

				modal.addEventListener('click', function(e) {
					if (e.target === modal || e.target.classList.contains('shm-lightbox-body')) {
						closeModal();
					}
				});

				document.addEventListener('keydown', function(e) {
					if (!modal.classList.contains('is-open')) return;
					if (e.key === 'Escape') closeModal();
					if (e.key === 'ArrowLeft') {
						if (window.shmFrontend && shmFrontend.isRtl) {
							showImage(currentIndex + 1);
						} else {
							showImage(currentIndex - 1);
						}
					}
					if (e.key === 'ArrowRight') {
						if (window.shmFrontend && shmFrontend.isRtl) {
							showImage(currentIndex - 1);
						} else {
							showImage(currentIndex + 1);
						}
					}
				});
			}
		}

	});
})();

/**
 * Safak Hospitals Manager - Admin Scripts
 */
(function($) {
	'use strict';

	$(document).ready(function() {

		/* 1. TAB NAVIGATION */
		$('.shm-admin-tab').on('click', function(e) {
			e.preventDefault();
			var tabId = $(this).data('tab');

			$('.shm-admin-tab').removeClass('is-active').attr('aria-selected', 'false');
			$('.shm-admin-panel').removeClass('is-active');

			$(this).addClass('is-active').attr('aria-selected', 'true');
			$('.shm-admin-panel[data-panel="' + tabId + '"]').addClass('is-active');
		});

		/* 2. PHOTO GALLERY UPLOADER */
		var galleryFrame;
		var $gallery = $('[data-shm-gallery]');
		var $galleryInput = $gallery.find('[data-shm-gallery-input]');
		var $galleryList = $gallery.find('[data-shm-gallery-list]');

		function updateGalleryInput() {
			var ids = [];
			$galleryList.find('.shm-gallery-item').each(function() {
				ids.push($(this).data('id'));
			});
			$galleryInput.val(ids.join(','));
		}

		if ($galleryList.length && $.fn.sortable) {
			$galleryList.sortable({
				items: '.shm-gallery-item',
				cursor: 'grab',
				update: updateGalleryInput
			});
		}

		$('[data-shm-gallery-add]').on('click', function(e) {
			e.preventDefault();

			if (galleryFrame) {
				galleryFrame.open();
				return;
			}

			galleryFrame = wp.media({
				title: (window.shmAdmin && shmAdmin.i18n.galleryTitle) || 'Select Gallery Photos',
				button: {
					text: (window.shmAdmin && shmAdmin.i18n.galleryButton) || 'Add to gallery'
				},
				multiple: true,
				library: { type: 'image' }
			});

			galleryFrame.on('select', function() {
				var selection = galleryFrame.state().get('selection');
				selection.each(function(attachment) {
					var att = attachment.toJSON();
					if ($galleryList.find('[data-id="' + att.id + '"]').length === 0) {
						var thumbUrl = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
						var itemHtml = '<li class="shm-gallery-item" data-id="' + att.id + '">' +
							'<img src="' + thumbUrl + '" alt="" width="96" height="96">' +
							'<button type="button" class="shm-gallery-remove" aria-label="Remove">&times;</button>' +
							'</li>';
						$galleryList.append(itemHtml);
					}
				});
				updateGalleryInput();
			});

			galleryFrame.open();
		});

		$gallery.on('click', '.shm-gallery-remove', function(e) {
			e.preventDefault();
			$(this).closest('.shm-gallery-item').remove();
			updateGalleryInput();
		});

		$('[data-shm-gallery-clear]').on('click', function(e) {
			e.preventDefault();
			if (confirm('Are you sure you want to clear the entire gallery?')) {
				$galleryList.empty();
				updateGalleryInput();
			}
		});

		/* 3. DOCTORS RELATIONSHIP SEARCH & SORT */
		var $doctorsWrap = $('[data-shm-doctors]');
		var $doctorsSearch = $('[data-shm-doctors-search]');
		var $doctorsResults = $('[data-shm-doctors-results]');
		var $doctorsSelected = $('[data-shm-doctors-selected]');
		var fieldName = $doctorsWrap.data('name') || 'shm_meta[_hospital_doctors][]';
		if (fieldName && fieldName.slice(-2) !== '[]') {
			fieldName += '[]';
		}
		var searchTimeout = null;

		function addDoctorChip(docId, docTitle, docThumb) {
			if (!docId) return;
			if ($doctorsSelected.find('[data-id="' + docId + '"]').length > 0) {
				return;
			}

			var chipHtml = '<li class="shm-doctor-chip" data-id="' + docId + '">' +
				'<input type="hidden" name="' + fieldName + '" value="' + docId + '">' +
				'<span class="shm-doctor-chip-handle dashicons dashicons-menu"></span>';
			if (docThumb) {
				chipHtml += '<img src="' + docThumb + '" alt="" width="36" height="36">';
			} else {
				chipHtml += '<span class="shm-doctor-chip-ph dashicons dashicons-admin-users"></span>';
			}
			chipHtml += '<span class="shm-doctor-chip-name">' + $('<div>').text(docTitle).html() + '</span>' +
				'<button type="button" class="shm-doctor-chip-remove" aria-label="Remove">&times;</button>' +
				'</li>';

			$doctorsSelected.append(chipHtml);
			$('.shm-quick-add-doctor[data-id="' + docId + '"]').css('opacity', '0.55');
		}

		if ($doctorsSelected.length && $.fn.sortable) {
			$doctorsSelected.sortable({
				items: '.shm-doctor-chip',
				handle: '.shm-doctor-chip-handle',
				cursor: 'grab'
			});
		}

		$doctorsSearch.on('input', function() {
			var query = $(this).val().trim();
			clearTimeout(searchTimeout);

			if (query.length < 2) {
				$doctorsResults.empty().prop('hidden', true);
				return;
			}

			searchTimeout = setTimeout(function() {
				$doctorsResults.html('<li class="shm-doctors-result-item" style="color:#64748b;">' + (shmAdmin.i18n.searching || 'Searching...') + '</li>').prop('hidden', false);

				$.ajax({
					url: shmAdmin.ajaxUrl,
					type: 'GET',
					dataType: 'json',
					data: {
						action: 'shm_search_doctors',
						nonce: shmAdmin.nonce,
						q: query,
						post_id: shmAdmin.postId
					},
					success: function(res) {
						if (!res.success || !res.data || res.data.length === 0) {
							$doctorsResults.html('<li class="shm-doctors-result-item" style="color:#64748b;">' + (shmAdmin.i18n.noResults || 'No doctors found.') + '</li>');
							return;
						}

						var html = '';
						$.each(res.data, function(i, doc) {
							var isSelected = $doctorsSelected.find('[data-id="' + doc.id + '"]').length > 0;
							html += '<li class="shm-doctors-result-item" data-id="' + doc.id + '" data-title="' + $('<div>').text(doc.title).html() + '" data-thumb="' + (doc.thumb || '') + '">';
							if (doc.thumb) {
								html += '<img src="' + doc.thumb + '" alt="">';
							} else {
								html += '<span class="shm-item-ph dashicons dashicons-admin-users"></span>';
							}
							html += '<div class="shm-doctors-result-info">';
							html += '<div class="shm-doctors-result-title">' + $('<div>').text(doc.title).html() + '</div>';
							if (doc.specialty || doc.lang) {
								html += '<div class="shm-doctors-result-meta">' + (doc.specialty ? $('<div>').text(doc.specialty).html() : '') + (doc.lang ? ' [' + doc.lang + ']' : '') + '</div>';
							}
							html += '</div>';
							if (isSelected) {
								html += '<span class="shm-col-badge">' + (shmAdmin.i18n.added || 'Added') + '</span>';
							}
							html += '</li>';
						});

						$doctorsResults.html(html);
					},
					error: function() {
						$doctorsResults.html('<li class="shm-doctors-result-item" style="color:#b32d2e;">Search error.</li>');
					}
				});
			}, 250);
		});

		$doctorsResults.on('click', '.shm-doctors-result-item', function() {
			var docId = $(this).data('id');
			var docTitle = $(this).data('title');
			var docThumb = $(this).data('thumb');

			addDoctorChip(docId, docTitle, docThumb);
			$doctorsResults.empty().prop('hidden', true);
			$doctorsSearch.val('');
		});

		$(document).on('click', '.shm-quick-add-doctor', function(e) {
			e.preventDefault();
			var docId = $(this).data('id');
			var docTitle = $(this).data('title');
			var docThumb = $(this).data('thumb');
			addDoctorChip(docId, docTitle, docThumb);
		});

		$(document).on('click', function(e) {
			if (!$(e.target).closest('.shm-doctors-search').length) {
				$doctorsResults.empty().prop('hidden', true);
			}
		});

		$doctorsSelected.on('click', '.shm-doctor-chip-remove', function(e) {
			e.preventDefault();
			var $chip = $(this).closest('.shm-doctor-chip');
			var docId = $chip.data('id');
			$chip.remove();
			$('.shm-quick-add-doctor[data-id="' + docId + '"]').css('opacity', '1');
		});

		/* 4. SETTINGS SINGLE IMAGE UPLOADER */
		$('[data-shm-single-image]').each(function() {
			var $wrap = $(this);
			var $input = $wrap.find('[data-image-id]');
			var $preview = $wrap.find('[data-image-preview]');
			var $removeBtn = $wrap.find('[data-image-remove]');
			var frame;

			$wrap.find('[data-image-select]').on('click', function(e) {
				e.preventDefault();
				if (frame) {
					frame.open();
					return;
				}
				frame = wp.media({
					title: 'Select Image',
					button: { text: 'Use this image' },
					multiple: false
				});
				frame.on('select', function() {
					var att = frame.state().get('selection').first().toJSON();
					$input.val(att.id);
					var url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
					$preview.html('<img src="' + url + '" style="max-width:240px;height:auto;display:block;margin-bottom:8px;border-radius:8px;">');
					$removeBtn.show();
				});
				frame.open();
			});

			$removeBtn.on('click', function(e) {
				e.preventDefault();
				$input.val('');
				$preview.empty();
				$removeBtn.hide();
			});
		});

	});
})(jQuery);


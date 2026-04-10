(function ($) {
	'use strict';

	function setPreview($card, imageUrl) {
		const $preview = $card.find('.recrm-seo-manager-photo-preview');

		if (imageUrl) {
			$preview.html('<img src="' + imageUrl + '" alt="">');
		} else {
			$preview.html('<div class="recrm-seo-manager-photo-empty">Фото не вибране</div>');
		}
	}


	function normalizeKeywords(value) {
		return String(value || '')
			.split(',')
			.map(function (item) { return item.trim(); })
			.filter(function (item, index, arr) {
				return item && arr.indexOf(item) === index;
			})
			.join(', ');
	}

	$(document).on('click', '.recrm-seo-media-select', function (event) {
		event.preventDefault();

		const $button = $(this);
		const $card = $button.closest('.recrm-seo-manager-card');
		const $input = $card.find('.recrm-seo-manager-image-id');

		const frame = wp.media({
			title: 'Обрати фото',
			button: { text: 'Використати фото' },
			multiple: false
		});

		frame.on('select', function () {
			const attachment = frame.state().get('selection').first().toJSON();
			$input.val(attachment.id || '');
			setPreview($card, attachment.url || '');
		});

		frame.open();
	});



	$(document).on('click', '.recrm-seo-add-keyword', function (event) {
		event.preventDefault();

		const keyword = String($(this).data('keyword') || '').trim();
		const $card = $(this).closest('.recrm-seo-manager-card');
		const $input = $card.find('.recrm-seo-keywords-input');

		if (!keyword || !$input.length) {
			return;
		}

		const current = normalizeKeywords($input.val());
		const combined = normalizeKeywords(current ? current + ', ' + keyword : keyword);
		$input.val(combined).trigger('change').trigger('input').focus();
	});

	$(document).on('click', '.recrm-seo-media-remove', function (event) {
		event.preventDefault();

		const $card = $(this).closest('.recrm-seo-manager-card');
		$card.find('.recrm-seo-manager-image-id').val('');
		setPreview($card, '');
	});
})(jQuery);

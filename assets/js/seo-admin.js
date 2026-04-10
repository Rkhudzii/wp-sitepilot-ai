(function () {
	'use strict';

	const settings = window.recrmSeoData || {
		brand: '',
		city: 'Тернопіль',
		titleTemplate: '{title} — {brand}',
		descriptionTemplate: '{title}. Актуальні пропозиції нерухомості в {city}. Консультація, підбір та супровід від {brand}.',
		autoKeyword: true,
		autoSlug: true,
		analysisEnabled: true,
		siteUrl: '/'
	};

	function normalizeWhitespace(value) {
		return String(value || '').replace(/\s+/g, ' ').trim();
	}

	function normalizeSeoCompare(value) {
		return normalizeWhitespace(value)
			.toLowerCase()
			.replace(/тернополя|тернополі|тернополем/g, 'тернопіль')
			.replace(/квартири|квартиру|квартир/g, 'квартира')
			.replace(/новобудов/g, 'новобудови');
	}

	function includesKeyword(text, keyword) {
		const normalizedText = normalizeSeoCompare(text);
		const normalizedKeyword = normalizeSeoCompare(keyword);

		if (!normalizedKeyword) {
			return false;
		}

		const words = normalizedKeyword
			.split(/\s+/)
			.filter(Boolean)
			.filter(word => word.length > 2);

		if (!words.length) {
			return normalizedText.includes(normalizedKeyword);
		}

		let matches = 0;

		words.forEach(word => {
			if (normalizedText.includes(word)) {
				matches++;
			}
		});

		return matches >= Math.ceil(words.length * 0.7);
	}

	function keywordMatchesTitle(title, keyword) {
		const t = normalizeWhitespace(title).toLowerCase();
		const k = normalizeWhitespace(keyword).toLowerCase();

		if (t.includes('новобуд') && !k.includes('новобуд')) return false;
		if (t.includes('забудов') && !k.includes('забудов') && !k.includes('новобуд')) return false;
		if (t.includes('будин') && !k.includes('будин')) return false;
		if (t.includes('земл') && !k.includes('земл')) return false;
		if (t.includes('комерц') && !k.includes('комерц')) return false;

		return true;
	}

	function transliterate(value) {
		const map = {
			'а': 'a', 'б': 'b', 'в': 'v', 'г': 'h', 'ґ': 'g', 'д': 'd', 'е': 'e', 'є': 'ye', 'ж': 'zh',
			'з': 'z', 'и': 'y', 'і': 'i', 'ї': 'yi', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n',
			'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'kh', 'ц': 'ts',
			'ч': 'ch', 'ш': 'sh', 'щ': 'shch', 'ю': 'yu', 'я': 'ya', 'ь': '', '’': '', "'": '', 'ъ': '',
			'ы': 'y', 'э': 'e'
		};

		let output = normalizeWhitespace(value).toLowerCase();
		output = output.split('').map((char) => map[char] !== undefined ? map[char] : char).join('');
		output = output.replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '-').replace(/^-+|-+$/g, '');
		return output;
	}

	function applyTemplate(template, data) {
		return String(template || '')
			.replaceAll('{title}', data.title || '')
			.replaceAll('{brand}', data.brand || '')
			.replaceAll('{city}', data.city || '')
			.replaceAll('{keyword}', data.keyword || '')
			.trim();
	}

	function wordCount(value) {
		const normalized = normalizeWhitespace(value);
		return normalized ? normalized.split(/\s+/).length : 0;
	}

	function generateKeyword(title) {
		const city = settings.city || 'Тернопіль';
		const normalized = normalizeWhitespace(title).toLowerCase();

		if (normalized.includes('новобуд')) {
			return ('новобудови ' + city).trim();
		}

		if (normalized.includes('забудов')) {
			return ('квартира від забудовника ' + city).trim();
		}

		if (
			normalized.includes('ринок') ||
			normalized.includes('гід') ||
			normalized.includes('огляд')
		) {
			return ('нерухомість ' + city).trim();
		}

		let type = 'нерухомість';
		if (/квартир/.test(normalized)) {
			type = 'квартиру';
		} else if (/будин|котедж/.test(normalized)) {
			type = 'будинок';
		} else if (/земл|ділян/.test(normalized)) {
			type = 'земельну ділянку';
		} else if (/комерц/.test(normalized)) {
			type = 'комерційну нерухомість';
		}

		let deal = 'купити';
		if (/оренд/.test(normalized)) {
			deal = 'оренда';
		} else if (/продаж/.test(normalized)) {
			deal = 'купити';
		}

		let rooms = '';
		const roomMatch = normalized.match(/\b([1-4])[\s-]*кімнат/);
		if (roomMatch) {
			rooms = roomMatch[1] + '-кімнатну ';
		}

		const districts = {
			'дружба': 'Дружба',
			'східний': 'Східний',
			'новий світ': 'Новий світ',
			'центр': 'Центр',
			'аляска': 'Аляска',
			'бам': 'БАМ'
		};

		let district = '';
		for (const key in districts) {
			if (normalized.includes(key)) {
				district = ' ' + districts[key];
				break;
			}
		}

		if (deal === 'оренда') {
			return ('оренда ' + rooms + type + ' ' + city + district).trim();
		}

		return ('купити ' + rooms + type + ' ' + city + district).trim();
	}

	function initDemoBlock() {
		const title = document.getElementById('recrm-demo-title');
		if (!title) {
			return;
		}

		const els = {
			title: title,
			keyword: document.getElementById('recrm-demo-keyword'),
			slug: document.getElementById('recrm-demo-slug'),
			metaTitle: document.getElementById('recrm-demo-meta-title'),
			metaDescription: document.getElementById('recrm-demo-meta-description'),
			previewUrl: document.getElementById('recrm-preview-url'),
			previewTitle: document.getElementById('recrm-preview-title'),
			previewDesc: document.getElementById('recrm-preview-desc'),
			titleLength: document.getElementById('recrm-title-length'),
			descLength: document.getElementById('recrm-desc-length'),
			keywordWords: document.getElementById('recrm-keyword-words'),
			checks: document.getElementById('recrm-seo-checks'),
			btnKeyword: document.getElementById('recrm-generate-keyword'),
			btnSlug: document.getElementById('recrm-generate-slug'),
			btnMeta: document.getElementById('recrm-generate-meta')
		};

		function updatePreview() {
			if (els.previewUrl) {
				els.previewUrl.textContent = 'example.com/' + normalizeWhitespace(els.slug.value || '') + '/';
			}
			if (els.previewTitle) {
				els.previewTitle.textContent = els.metaTitle.value || '';
			}
			if (els.previewDesc) {
				els.previewDesc.textContent = els.metaDescription.value || '';
			}
			if (els.titleLength) {
				els.titleLength.textContent = (els.metaTitle.value || '').length;
			}
			if (els.descLength) {
				els.descLength.textContent = (els.metaDescription.value || '').length;
			}
			if (els.keywordWords) {
				els.keywordWords.textContent = wordCount(els.keyword.value || '');
			}
		}

		function getChecks() {
			const titleValue = els.title.value || '';
			const keyword = els.keyword.value || '';
			const slug = els.slug.value || '';
			const metaTitle = els.metaTitle.value || '';
			const metaDescription = els.metaDescription.value || '';

			return [
				{ label: 'Ключ присутній у title', ok: includesKeyword(metaTitle, keyword), warn: false },
				{ label: 'Ключ присутній у description', ok: includesKeyword(metaDescription, keyword), warn: false },
				{ label: 'Ключ присутній у slug', ok: transliterate(keyword) && slug.includes(transliterate(keyword).split('-')[0] || ''), warn: false },
				{ label: 'Ключ відповідає темі заголовка', ok: keywordMatchesTitle(titleValue, keyword), warn: false },
                { 
                label: 'Довжина title 40–80 символів', 
                ok: metaTitle.length >= 40 && metaTitle.length <= 80, 
                warn: metaTitle.length >= 81 && metaTitle.length <= 95 
                },
                { label: 'Довжина description 120–160 символів', ok: metaDescription.length >= 120 && metaDescription.length <= 160, warn: metaDescription.length >= 100 && metaDescription.length <= 180 },
				{ label: 'Ключ містить 2–5 слів', ok: wordCount(keyword) >= 2 && wordCount(keyword) <= 5, warn: wordCount(keyword) >= 1 && wordCount(keyword) <= 6 },
				{ label: 'Заголовок не порожній', ok: normalizeWhitespace(titleValue).length > 0, warn: false }
			];
		}

		function renderChecks() {
			if (!els.checks) {
				return;
			}

			if (!settings.analysisEnabled) {
				els.checks.innerHTML = '<div class="recrm-seo-check"><span>SEO аналізатор вимкнений у налаштуваннях.</span><span class="recrm-seo-status warn">УВАГА</span></div>';
				return;
			}

			const checks = getChecks();
			els.checks.innerHTML = checks.map((item) => {
				let statusClass = 'bad';
				let statusText = 'НІ';

				if (item.ok) {
					statusClass = 'ok';
					statusText = 'OK';
				} else if (item.warn) {
					statusClass = 'warn';
					statusText = 'УВАГА';
				}

				return '<div class="recrm-seo-check"><span>' + item.label + '</span><span class="recrm-seo-status ' + statusClass + '">' + statusText + '</span></div>';
			}).join('');
		}

		function regenerateKeyword(force) {
			if (!settings.autoKeyword && !force) return;
			els.keyword.value = generateKeyword(els.title.value || '');
		}

		function regenerateSlug(force) {
			if (!settings.autoSlug && !force) return;
			els.slug.value = transliterate(els.keyword.value || els.title.value || '');
		}

		function regenerateMeta() {
			const data = {
				title: els.title.value || '',
				brand: settings.brand || '',
				city: settings.city || '',
				keyword: els.keyword.value || ''
			};
			els.metaTitle.value = applyTemplate(settings.titleTemplate, data);
			els.metaDescription.value = applyTemplate(settings.descriptionTemplate, data);
		}

		function fullRefresh() {
			updatePreview();
			renderChecks();
		}

		els.title.addEventListener('input', function () {
			regenerateKeyword(false);
			regenerateSlug(false);
			regenerateMeta();
			fullRefresh();
		});

		els.keyword.addEventListener('input', function () {
			regenerateSlug(false);
			regenerateMeta();
			fullRefresh();
		});

		els.slug.addEventListener('input', fullRefresh);
		els.metaTitle.addEventListener('input', fullRefresh);
		els.metaDescription.addEventListener('input', fullRefresh);

		if (els.btnKeyword) {
			els.btnKeyword.addEventListener('click', function () {
				regenerateKeyword(true);
				fullRefresh();
			});
		}

		if (els.btnSlug) {
			els.btnSlug.addEventListener('click', function () {
				regenerateSlug(true);
				fullRefresh();
			});
		}

		if (els.btnMeta) {
			els.btnMeta.addEventListener('click', function () {
				regenerateMeta();
				fullRefresh();
			});
		}

		regenerateMeta();
		fullRefresh();
	}

	function initPostBoxes() {
		const boxes = document.querySelectorAll('.recrm-seo-post-box[data-recrm-seo-post-box="1"]');
		if (!boxes.length) {
			return;
		}

		boxes.forEach((box) => {
			if (box.dataset.recrmSeoReady === '1') {
				return;
			}
			box.dataset.recrmSeoReady = '1';

			const els = {
				sourceTitle: box.querySelector('.recrm-seo-source-title'),
				keyword: box.querySelector('.recrm-seo-keyword'),
				slug: box.querySelector('.recrm-seo-slug'),
				metaTitle: box.querySelector('.recrm-seo-meta-title'),
				metaDescription: box.querySelector('.recrm-seo-meta-description'),
				previewUrl: box.querySelector('.recrm-seo-post-preview-url'),
				previewTitle: box.querySelector('.recrm-seo-post-preview-title'),
				previewDesc: box.querySelector('.recrm-seo-post-preview-desc'),
				titleLength: box.querySelector('.recrm-seo-title-length'),
				descLength: box.querySelector('.recrm-seo-desc-length'),
				keywordWords: box.querySelector('.recrm-seo-keyword-words'),
				checks: box.querySelector('.recrm-seo-post-checks'),
				btnKeyword: box.querySelector('.recrm-seo-btn-keyword'),
				btnSlug: box.querySelector('.recrm-seo-btn-slug'),
				btnMeta: box.querySelector('.recrm-seo-btn-meta'),
				btnAll: box.querySelector('.recrm-seo-btn-all')
			};

			function getLiveTitle() {
				const editorTitle = document.querySelector('#title');
				if (editorTitle && editorTitle.value) {
					return editorTitle.value;
				}

				const blockTitle = document.querySelector('.editor-post-title__input');
				if (blockTitle && blockTitle.textContent.trim()) {
					return blockTitle.textContent.trim();
				}

				return els.sourceTitle ? els.sourceTitle.value || '' : '';
			}

			function refreshSourceTitle() {
				if (els.sourceTitle) {
					els.sourceTitle.value = getLiveTitle();
				}
			}

			function updatePreview() {
				if (els.previewUrl) {
					els.previewUrl.textContent = String(settings.siteUrl || '/').replace(/\/$/, '') + '/' + normalizeWhitespace(els.slug.value || '') + '/';
				}
				if (els.previewTitle) {
					els.previewTitle.textContent = els.metaTitle.value || '';
				}
				if (els.previewDesc) {
					els.previewDesc.textContent = els.metaDescription.value || '';
				}
				if (els.titleLength) {
					els.titleLength.textContent = (els.metaTitle.value || '').length;
				}
				if (els.descLength) {
					els.descLength.textContent = (els.metaDescription.value || '').length;
				}
				if (els.keywordWords) {
					els.keywordWords.textContent = wordCount(els.keyword.value || '');
				}
			}

            function renderChecks() {
                return;
            }

			function regenerateKeyword(force) {
				refreshSourceTitle();
				if (!settings.autoKeyword && !force) return;
				els.keyword.value = generateKeyword(els.sourceTitle ? els.sourceTitle.value || '' : '');
			}

			function regenerateSlug(force) {
				if (!settings.autoSlug && !force) return;
				els.slug.value = transliterate(els.keyword.value || (els.sourceTitle ? els.sourceTitle.value || '' : ''));
			}

			function regenerateMeta() {
				refreshSourceTitle();
				const data = {
					title: els.sourceTitle ? els.sourceTitle.value || '' : '',
					brand: settings.brand || '',
					city: settings.city || '',
					keyword: els.keyword.value || ''
				};
				els.metaTitle.value = applyTemplate(settings.titleTemplate, data);
				els.metaDescription.value = applyTemplate(settings.descriptionTemplate, data);
			}

			function fullRefresh() {
				refreshSourceTitle();
				updatePreview();
				renderChecks();
			}

			const classicTitle = document.querySelector('#title');
			if (classicTitle) {
				classicTitle.addEventListener('input', function () {
					regenerateKeyword(false);
					regenerateSlug(false);
					regenerateMeta();
					fullRefresh();
				});
			}

			document.addEventListener('input', function (e) {
				if (e.target && e.target.classList && e.target.classList.contains('editor-post-title__input')) {
					regenerateKeyword(false);
					regenerateSlug(false);
					regenerateMeta();
					fullRefresh();
				}
			});

			els.keyword.addEventListener('input', function () {
				regenerateSlug(false);
				regenerateMeta();
				fullRefresh();
			});

			els.slug.addEventListener('input', fullRefresh);
			els.metaTitle.addEventListener('input', fullRefresh);
			els.metaDescription.addEventListener('input', fullRefresh);

			if (els.btnKeyword) {
				els.btnKeyword.addEventListener('click', function () {
					regenerateKeyword(true);
					fullRefresh();
				});
			}

			if (els.btnSlug) {
				els.btnSlug.addEventListener('click', function () {
					regenerateSlug(true);
					fullRefresh();
				});
			}

			if (els.btnMeta) {
				els.btnMeta.addEventListener('click', function () {
					regenerateMeta();
					fullRefresh();
				});
			}

			if (els.btnAll) {
				els.btnAll.addEventListener('click', function () {
					regenerateKeyword(true);
					regenerateSlug(true);
					regenerateMeta();
					fullRefresh();
				});
			}

			fullRefresh();
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initDemoBlock();
		initPostBoxes();
	});
})();
/**
 * Navitto フロントエンド JavaScript
 *
 * テーマのヘッダー構造に挿入する方式
 *
 * @package Navitto
 * @since   1.2.0
 */

(function($) {
	'use strict';

	var Navitto = {

		headings: [],
		$nav: null,
		$headerParent: null,  // ナビの挿入先ヘッダー要素
		$nextH2AfterLast: null,  // 指定した最後のh2の「次の」h2（この位置に達したらナビを隠す）
		$lastTrackedH2: null,    // 監視対象の最後のh2（次h2がない場合のフォールバック）
		insertMode: 'body',   // 'inside' | 'after' | 'body'
		isScrolling: false,
		lastActiveIndex: -1,  // 前回のアクティブインデックス
		_clickedIndex: -1,    // クリックで指定されたインデックス
		_clickedTime: 0,      // クリック時刻
		settings: {
			scrollOffset: 80,
			animDuration: 500,
			showAfterScroll: 0,
			preset: 'simple',
			position: 'top',
			displayMode: 'show_all',
			selectedH2: [],
			customTexts: {},
			h2Icons: {},
			trigger: { type: 'immediate' },
			detection: null,
			fixedHeader: null
		},

		/**
		 * 初期化
		 */
		init: function() {
			var self = this;

			if (typeof navittoData !== 'undefined') {
				$.extend(this.settings, navittoData);
			}

			var bootstrap = function() {
				self.detectHeadings();
				self.filterSelectedH2();

				var minHeadings = (self.settings.displayMode === 'select') ? 1 : 2;
				if (self.headings.length >= minHeadings) {
					if (!self.$nav || !self.$nav.length) {
						self.assignIds();
						self.findHeaderParent();
						self.createNav();
						self.bindEvents();
					}
					return true;
				}
				return false;
			};

			$(document).ready(function() {
				bootstrap();
			});

			$(window).on('load.navitto', function() {
				setTimeout(function() {
					if (!self.$nav || !self.$nav.length) {
						bootstrap();
					}
				}, 150);
			});
		},

		/* ==================================================================
		   見出し収集
		   ================================================================== */

		detectHeadings: function() {
			var minHeadings = (this.settings.displayMode === 'select') ? 1 : 2;
			var detection = this.settings.detection;
			if (!detection || !detection.detectionOrder) {
				this.headings = [];
				this.collectH2Headings();
				return;
			}
			var order = detection.detectionOrder;
			for (var i = 0; i < order.length; i++) {
				var entry = order[i];
				this.headings = [];
				if (entry.source === 'h2') {
					this.collectH2Headings();
				} else {
					this.collectFromToc(entry);
				}
				if (this.headings.length >= minHeadings) {
					return;
				}
			}
		},

		collectFromToc: function(entry) {
			var self = this;
			var $container = $(entry.container);
			if ($container.length === 0) return false;
			var $items = $(entry.items);
			if ($items.length === 0) return false;

			$items.each(function() {
				var $a = $(this);
				var href = $a.attr('href') || '';
				var text = $a.text().trim();
				if (!text) return;
				var id = '';
				if (href.indexOf('#') !== -1) {
					id = href.split('#')[1] || '';
				}
				var element = null;
				if (id) {
					var $target = $('#' + id);
					if ($target.length > 0 && $target.is('h2')) {
						element = $target[0];
					}
				}
				if (!element) return;
				self.headings.push({ element: element, text: text, id: id });
			});
			return this.headings.length > 0;
		},

		collectH2Headings: function() {
			var self = this;
			var containerSelectors = [
				'.post_content',          // SWELL
				'.wp-block-post-content', // Block themes
				'.entry-content',
				'.post-content',
				'article .content',
				'#the_content'
			];

			var bestContainer = null;
			var bestCount = 0;
			var seen = [];

			for (var i = 0; i < containerSelectors.length; i++) {
				$(containerSelectors[i]).each(function() {
					if (seen.indexOf(this) !== -1) return;
					seen.push(this);

					var count = $(this).find('h2').length;
					if (count > bestCount) {
						bestCount = count;
						bestContainer = this;
					}
				});
			}

			// フォールバック（本文コンテナが見つからない場合）
			if (!bestContainer) {
				var $article = $('article').first();
				if ($article.length > 0) {
					bestContainer = $article[0];
				} else {
					var $main = $('main').first();
					if ($main.length > 0) {
						bestContainer = $main[0];
					}
				}
			}

			if (!bestContainer) return;

			$(bestContainer).find('h2').each(function() {
				var $h2 = $(this);
				var text = $h2.text().trim();
				if (text) {
					self.headings.push({ element: this, text: text, id: $h2.attr('id') || '' });
				}
			});
		},

		filterSelectedH2: function() {
			var s = this.settings;
			if (s.displayMode !== 'select' || !s.selectedH2 || s.selectedH2.length === 0) return;

			var selected = s.selectedH2;
			var texts = s.customTexts || {};
			var icons = s.h2Icons || {};
			var filtered = [];
			for (var i = 0; i < this.headings.length; i++) {
				var inSelected = false;
				for (var j = 0; j < selected.length; j++) {
					if (parseInt(selected[j], 10) === i) { inSelected = true; break; }
				}
				if (inSelected) {
					var heading = this.headings[i];
					var customText = texts[String(i)];
					if (customText && customText.length > 0) heading.text = customText;
					var iconVal = icons[String(i)];
					if (iconVal && iconVal !== 'none') heading.icon = iconVal;
					filtered.push(heading);
				}
			}
			this.headings = filtered;
		},

		assignIds: function() {
			this.headings.forEach(function(heading, index) {
				if (!heading.id) {
					heading.id = 'navitto-h2-' + index;
					heading.element.setAttribute('id', heading.id);
				}
			});
		},

		/* ==================================================================
		   ヘッダー検出 & ナビ挿入
		   ================================================================== */

		/**
		 * テーマの固定ヘッダーを探して挿入先を決定
		 *
		 * SWELL: #header 内に追加（p-spHeadMenuと同じレベル）
		 * Cocoon: ヘッダー直後に追加
		 * その他: bodyに追加（従来方式）
		 */
		findHeaderParent: function() {
			// 下部固定の場合は常に body に追加（ヘッダー内だと上部に表示されるため）
			if (this.settings.position === 'bottom') {
				this.insertMode = 'body';
				return;
			}

			// SWELL: #header が固定ヘッダーの親コンテナ
			var $swellHeader = $('#header');
			if ($swellHeader.length > 0) {
				var pos = $swellHeader.css('position');
				if (pos === 'fixed' || pos === 'sticky') {
					this.$headerParent = $swellHeader;
					this.insertMode = 'inside';
					return;
				}
			}

			// SWELL: #fix_header（PCスクロール時の固定ヘッダー）
			var $fixHeader = $('#fix_header');
			if ($fixHeader.length > 0 && $fixHeader.is(':visible')) {
				var pos2 = $fixHeader.css('position');
				if (pos2 === 'fixed' || pos2 === 'sticky') {
					this.$headerParent = $fixHeader;
					this.insertMode = 'inside';
					return;
				}
			}

			// Cocoon: .sticky-header
			var $cocoon = $('.sticky-header');
			if ($cocoon.length > 0 && $cocoon.is(':visible')) {
				this.$headerParent = $cocoon;
				this.insertMode = 'inside';
				return;
			}

			// 汎用: 画面上部の固定要素を探す
			var genericSelectors = [
				'.l-header', '#masthead', '.site-header', '#site-header'
			];
			for (var i = 0; i < genericSelectors.length; i++) {
				var $el = $(genericSelectors[i]);
				if ($el.length > 0 && $el.is(':visible')) {
					var gPos = $el.css('position');
					if (gPos === 'fixed' || gPos === 'sticky') {
						this.$headerParent = $el;
						this.insertMode = 'inside';
						return;
					}
				}
			}

			// カスタムセレクタ
			var headerData = this.settings.fixedHeader;
			if (headerData && headerData.customSelector) {
				var $custom = $(headerData.customSelector);
				if ($custom.length > 0 && $custom.is(':visible')) {
					this.$headerParent = $custom;
					this.insertMode = 'after';
					return;
				}
			}

			// 見つからない場合はbodyに追加（従来方式）
			this.insertMode = 'body';
		},

		/**
		 * ナビゲーションを生成して挿入
		 */
		createNav: function() {
			var self = this;
			var s = this.settings;
			var presetClass = s.preset ? ' cp-preset-' + s.preset : '';
			var posClass = s.position === 'bottom' ? ' cp-pos-bottom' : '';
			var navWidth = (typeof navittoData !== 'undefined' && navittoData.navWidth) || 'scroll';
			var widthClass = ' nav-' + navWidth;

			var html = '<nav class="navitto-nav' + presetClass + posClass + widthClass + '" role="navigation">';
			html += '<div class="navitto-nav__inner">';
			html += '<ul class="navitto-nav__list">';

			var iconRegistry = window.__NAVITTO_ICONS__;
			this.headings.forEach(function(heading, index) {
				var activeClass = index === 0 ? ' navitto-nav__item--active' : '';
				html += '<li class="navitto-nav__item' + activeClass + '">';
				html += '<a href="#' + self.escapeAttr(heading.id) + '" class="navitto-nav__link">';
				if (heading.icon && iconRegistry) {
					var svgHtml = iconRegistry.getSvg(heading.icon);
					if (svgHtml) {
						html += '<span class="navitto-nav__icon" aria-hidden="true">' + svgHtml + '</span>';
					}
				}
				html += '<span class="navitto-nav__text">' + self.escapeHtml(heading.text) + '</span>';
				html += '</a></li>';
			});

			html += '</ul></div></nav>';
			this.$nav = $(html);

			// 挿入モードに応じてDOMに配置
			if (this.insertMode === 'inside' && this.$headerParent) {
				// ヘッダー内に追加 → position: fixedは不要（親が固定されている）
				this.$headerParent.append(this.$nav);
				this.$nav.addClass('cp-inside-header');
			} else if (this.insertMode === 'after' && this.$headerParent) {
				// ヘッダーの直後に追加
				this.$headerParent.after(this.$nav);
				$('body').addClass('navitto-active');
				this.updateBodyPaddingClass();
			} else {
				// bodyに追加（従来方式）
				$('body').append(this.$nav);
				$('body').addClass('navitto-active');
				this.updateBodyPaddingClass();
			}

			if (s.position === 'bottom') {
				$('body').addClass('navitto-bottom');
			}

			this.detectContentWidth();
			this.updateScrollHint();
			this.checkOverflow();
			this.cacheNextH2AfterLast();

			// ナビ作成直後にスクロールオフセット・テーマ連携を設定（DOMレンダリング後）
			var self = this;
			requestAnimationFrame(function() {
				self.updateScrollMargin();
				self.syncNavTransition();
				if (s.preset === 'theme') {
					self.applyThemeColor();
				}
			});
		},

		/**
		 * テーマの固定ヘッダーの出現アニメーションに合わせてナビの表示トランジションを設定
		 * テーマにアニメーションがあればその長さに合わせ、なければプラグイン側のみトランジションを付与。
		 * テーマヘッダーが position:sticky の場合はアニメーションなしで最初から表示する。
		 */
		syncNavTransition: function() {
			if (!this.$nav || !this.$nav.length) return;

			var duration = 0.3;  // デフォルト（秒）
			var themeHeaderSticky = false;

			// 1. findHeaderParent で見つけた親ヘッダーが sticky かどうか直接チェック
			if (this.$headerParent && this.$headerParent.length) {
				var parentPos = getComputedStyle(this.$headerParent[0]).position || '';
				if (parentPos === 'sticky') {
					themeHeaderSticky = true;
					duration = 0;
				}
			}

			// 2. sticky でない場合は Detector のセレクタでトランジションを取得
			if (!themeHeaderSticky) {
				var headerData = this.settings.fixedHeader;
				if (headerData) {
					var isSp = window.innerWidth < 768;
					var sel = isSp
						? (headerData.customSelectorSp || (headerData.selectors && headerData.selectors.sp))
						: (headerData.customSelectorPc || (headerData.selectors && headerData.selectors.pc));
					if (sel) {
						var headerEl = document.querySelector(sel);
						if (headerEl) {
							// Detector 側のセレクタも sticky かチェック
							var detPos = getComputedStyle(headerEl).position || '';
							if (detPos === 'sticky') {
								themeHeaderSticky = true;
								duration = 0;
							} else {
								var cs = getComputedStyle(headerEl);
								var t = (cs.transitionDuration || '').trim();
								if (t) {
									var first = t.split(',')[0].trim();
									var match = first.match(/^([\d.]+)(s|ms)$/);
									if (match) {
										var val = parseFloat(match[1], 10);
										duration = match[2] === 'ms' ? val / 1000 : val;
									}
								}
								if (duration <= 0) {
									duration = 0.3;
								}
							}
						}
					}
				}
			}

			this.$nav[0].style.setProperty('--navitto-nav-transition-duration', duration + 's');
			if (themeHeaderSticky) {
				this.$nav.addClass('cp-theme-header-sticky');
				// 「選択した最初の見出しを通過後」のときは初期表示で開かない（一瞬開いて閉じるチラつき防止）
				var trigger = this.settings.trigger || { type: 'immediate' };
				var triggerType = trigger.type || 'immediate';
				if (triggerType !== 'first_selected') {
					this.$nav.addClass('is-visible');
					this.updateScrollMargin();
				}
			} else {
				this.$nav.removeClass('cp-theme-header-sticky');
			}
		},

		/**
		 * body に padding-top/bottom を付与すべきかどうか。
		 * ヘッダーが固定されていないテーマでは余白になるため、その場合は false。
		 */
		needsBodyPadding: function() {
			if (this.insertMode === 'after' && this.$headerParent && this.$headerParent.length) {
				var pos = getComputedStyle(this.$headerParent[0]).position || '';
				return pos === 'fixed' || pos === 'sticky';
			}
			if (this.insertMode === 'body') {
				// body 直下に固定でないヘッダーがあれば padding を付けない
				var headerSelectors = ['header', '[role="banner"]', '.site-header', '#masthead', '.l-header', '#header'];
				for (var i = 0; i < headerSelectors.length; i++) {
					var el = document.querySelector(headerSelectors[i]);
					if (el && el.offsetParent !== null) {
						var pos = getComputedStyle(el).position || '';
						if (pos !== 'fixed' && pos !== 'sticky') {
							return false;
						}
					}
				}
				return true;
			}
			return false;
		},

		/**
		 * body.navitto-active 時、padding を付与するかは needsBodyPadding に従いクラスを切り替える
		 */
		updateBodyPaddingClass: function() {
			var $body = $('body');
			if (!$body.hasClass('navitto-active')) return;
			if (this.needsBodyPadding()) {
				$body.addClass('navitto-body-padding');
			} else {
				$body.removeClass('navitto-body-padding');
			}
		},

		/**
		 * テーマ準拠時: テーマのメインカラー（アクセント）と文字色を取得してナビに反映
		 */
		applyThemeColor: function() {
			if (!this.$nav || !this.$nav.length) return;

			var nav = this.$nav[0];
			var root = document.documentElement;
			var cs = getComputedStyle(root);

			/* ----- メインカラー（アクセント色 → border-bottom / active 用）----- */
			var mainColor = null;
			var mainVars = [
				'--ark-color--main',               // Arkhe
				'--ark-color--link',               // Arkhe（リンク色）
				'--color_main',                    // SWELL
				'--jin-color-primary',             // JIN
				'--main-color',                    // SANGO
				'--cocoon-main-color',             // Cocoon
				'--primary-color',                 // 汎用
				'--color-primary',                 // 汎用
				'--accent-color',                  // 汎用
				'--wp--preset--color--primary',    // WordPress ブロックテーマ
				'--wp--custom--color--primary',    // WordPress カスタムプリセット
				'--link-color',                    // 汎用
				'--e-global-color-primary'         // Elementor
			];
			for (var i = 0; i < mainVars.length; i++) {
				var val = cs.getPropertyValue(mainVars[i]).trim();
				if (val) { mainColor = val; break; }
			}
			if (!mainColor) {
				var linkSel = ['.entry-content a', '.post-content a', 'main a', '#content a', 'article a', 'a'];
				for (var j = 0; j < linkSel.length; j++) {
					var link = document.querySelector(linkSel[j]);
					if (link) {
						var c = getComputedStyle(link).color;
						if (c && c !== 'rgba(0, 0, 0, 0)') { mainColor = c; break; }
					}
				}
			}

			/* ----- テキストカラー（テーマの文字色 → 通常のナビ文字色用）----- */
			var textColor = null;
			var textVars = [
				'--ark-color--text',               // Arkhe
				'--color_text',                    // SWELL
				'--jin-color-text',                // JIN
				'--text-color',                    // SANGO / 汎用
				'--cocoon-text-color',             // Cocoon
				'--wp--preset--color--contrast',   // WordPress ブロックテーマ
				'--body-color',                    // 汎用
				'--e-global-color-text'            // Elementor
			];
			for (var k = 0; k < textVars.length; k++) {
				var tv = cs.getPropertyValue(textVars[k]).trim();
				if (tv) { textColor = tv; break; }
			}
			if (!textColor) {
				textColor = getComputedStyle(document.body).color;
			}

			/* ----- ナビ背景色（テーマのヘッダー色に合わせる）----- */
			var headerEl = null;
			if (this.$headerParent && this.$headerParent[0]) {
				headerEl = this.$headerParent[0];
			} else {
				var headerData = this.settings.fixedHeader;
				if (headerData) {
					var isSp = window.innerWidth < 768;
					var sel = isSp
						? (headerData.customSelectorSp || (headerData.selectors && headerData.selectors.sp))
						: (headerData.customSelectorPc || (headerData.selectors && headerData.selectors.pc));
					if (sel) {
						headerEl = document.querySelector(sel);
					}
				}
			}
			// 汎用セレクタでヘッダーを探す（$headerParent が未設定のとき）
			if (!headerEl) {
				var generic = ['#header', '#fix_header', '.l-fixHeader', '.sticky-header', '.l-header', '#masthead', '.site-header', '#site-header'];
				for (var g = 0; g < generic.length; g++) {
					var el = document.querySelector(generic[g]);
					if (el && el.offsetParent !== null) {
						var pos = getComputedStyle(el).position;
						if (pos === 'fixed' || pos === 'sticky') {
							headerEl = el;
							break;
						}
					}
				}
			}
			var headerBg = null;
			if (headerEl) {
				headerBg = this.getElOrPseudoBackground(headerEl);
			}
			/* ----- 「背景を透明にする」がオンのときは透明を優先 ----- */
			var s = this.settings;
			var bgColor = null;
			if (s.themeBgTransparent) {
				bgColor = 'transparent';
			}
			if (!bgColor) {
				bgColor = headerBg;
			}
			/* ----- ヘッダーが取れない場合はテーマの背景色をフォールバック ----- */
			if (!bgColor) {
				var bgVars = [
					'--ark-color--bg',              // Arkhe
					'--color_bg', '--jin-color-bg', '--bg-color', '--cocoon-bg-color',
					'--wp--preset--color--base', '--body-bg', '--e-global-color-bg'
				];
				for (var l = 0; l < bgVars.length; l++) {
					var bv = cs.getPropertyValue(bgVars[l]).trim();
					if (bv) { bgColor = bv; break; }
				}
				if (!bgColor) {
					bgColor = getComputedStyle(document.body).backgroundColor;
					if (bgColor === 'rgba(0, 0, 0, 0)' || bgColor === 'transparent') {
						bgColor = null;
					}
				}
			}

			/* ----- CSS変数にセット（アクティブ項目の背景は指定なし＝透明）----- */
			if (mainColor) {
				nav.style.setProperty('--navitto-theme-color', mainColor);
			}
			if (textColor) {
				nav.style.setProperty('--navitto-theme-text', textColor);
			}
			if (bgColor) {
				nav.style.setProperty('--navitto-theme-bg', bgColor);
			}
			nav.style.setProperty('--navitto-theme-active-bg', 'transparent');
		},

		/**
		 * 要素本体、::before、::after の順で背景色を取得（SWELL 等でヘッダー背景が疑似要素指定の場合に対応）
		 * @param {Element} el - 対象要素
		 * @returns {string|null} 取得した背景色、すべて透明なら null
		 */
		getElOrPseudoBackground: function(el) {
			var transparent = ['rgba(0, 0, 0, 0)', 'transparent'];
			var tryBg = function(bg) {
				if (!bg || transparent.indexOf(bg) !== -1) return null;
				return bg;
			};
			var bg = getComputedStyle(el).backgroundColor;
			var out = tryBg(bg);
			if (out) return out;
			try {
				var before = getComputedStyle(el, '::before');
				if (before) {
					out = tryBg(before.backgroundColor);
					if (out) return out;
				}
			} catch (e) { /* 未対応ブラウザ */ }
			try {
				var after = getComputedStyle(el, '::after');
				if (after) {
					out = tryBg(after.backgroundColor);
					if (out) return out;
				}
			} catch (e) { /* 未対応ブラウザ */ }
			return null;
		},

		/**
		 * 色文字列を少し濃くする（係数 0～1、小さいほど暗い）
		 * @param {string} color - rgb(r,g,b) / rgba(r,g,b,a) / #hex
		 * @returns {string} rgba(...)
		 */
		darkenColor: function(color) {
			var factor = 0.92;
			if (!color || color === 'transparent') return 'rgba(0,0,0,0.12)';
			var rgba = color.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)/);
			if (rgba) {
				var r = Math.max(0, Math.floor(parseInt(rgba[1], 10) * factor));
				var g = Math.max(0, Math.floor(parseInt(rgba[2], 10) * factor));
				var b = Math.max(0, Math.floor(parseInt(rgba[3], 10) * factor));
				var a = rgba[4] != null ? parseFloat(rgba[4], 10) : 1;
				return 'rgba(' + r + ',' + g + ',' + b + ',' + a + ')';
			}
			var hex = color.match(/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/);
			if (hex) {
				var h = hex[1];
				if (h.length === 3) {
					h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
				}
				var r = Math.max(0, Math.floor(parseInt(h.substr(0, 2), 16) * factor));
				var g = Math.max(0, Math.floor(parseInt(h.substr(2, 2), 16) * factor));
				var b = Math.max(0, Math.floor(parseInt(h.substr(4, 2), 16) * factor));
				return 'rgb(' + r + ',' + g + ',' + b + ')';
			}
			return 'rgba(0,0,0,0.12)';
		},

		/**
		 * テーマのコンテンツ幅を検出してCSS変数に設定
		 */
		detectContentWidth: function() {
			// テーマの主要コンテンツコンテナから幅を取得
			var selectors = [
				'.l-content',         // SWELL
				'.l-mainContent',     // SWELL
				'#content-in',        // Cocoon
				'.wrap',              // Cocoon
				'.l-main',            // JIN
				'#main',              // 汎用
				'.main-content',      // 汎用
				'main',              // 汎用
				'.site-content'       // 汎用
			];

			for (var i = 0; i < selectors.length; i++) {
				var $el = $(selectors[i]);
				if ($el.length > 0 && $el.is(':visible')) {
					var width = $el.outerWidth();
					if (width > 0) {
						this.$nav[0].style.setProperty('--navitto-content-width', width + 'px');
						this.$nav[0].style.setProperty('--content-width', width + 'px');
						return;
					}
				}
			}
			// 見つからない場合はデフォルト（100%）のまま
		},

		/**
		 * 均等割モード: テキストがはみ出しているアイテムを検出
		 */
		checkOverflow: function() {
			var navWidth = (typeof navittoData !== 'undefined' && navittoData.navWidth) || 'scroll';
			if (navWidth !== 'equal') return;

			// レイアウト確定後に実行
			var self = this;
			requestAnimationFrame(function() {
				self.$nav.find('.navitto-nav__link').each(function() {
					var el = this;
					// scrollWidth > clientWidth ではみ出し判定
					if (el.scrollWidth > el.clientWidth) {
						$(el).addClass('is-overflow');
					} else {
						$(el).removeClass('is-overflow');
					}
				});
			});
		},

		/* ==================================================================
		   スクロールオフセット
		   ================================================================== */

		/**
		 * スクロールオフセットを取得
		 * ヘッダー内に挿入されている場合、親ヘッダーの全体高さを使う
		 */
		getScrollOffset: function() {
			// プラグインナビの実際の高さを取得
			var navH = 0;
			if (this.$nav) {
				navH = this.$nav[0].offsetHeight;
			}
			if (!navH) {
				navH = window.innerWidth <= 768 ? 48 : 56;
			}

			if (this.insertMode === 'inside' && this.$headerParent) {
				// ヘッダー内挿入: ヘッダー全体の高さ（テーマヘッダー + プラグインナビ）
				return this.$headerParent[0].offsetHeight + 40;
			}

			// body追加: プラグインナビの高さのみ
			return navH + 40;
		},

		/* ==================================================================
		   横スクロールヒント
		   ================================================================== */

		updateScrollHint: function() {
			if (!this.$nav) return;
			var $inner = this.$nav.find('.navitto-nav__inner');
			if ($inner.length === 0) return;

			var el = $inner[0];
			var scrollLeft = el.scrollLeft;
			var maxScroll = el.scrollWidth - el.clientWidth;

			$inner.toggleClass('has-scroll-left', scrollLeft > 5);
			$inner.toggleClass('has-scroll-right', maxScroll > 5 && scrollLeft < maxScroll - 5);
		},

		/* ==================================================================
		   イベント
		   ================================================================== */

		/**
		 * リサイズ時にナビの挿入先を再検出・移動
		 * SWELLなどPC/SPでヘッダー要素が変わるテーマに対応
		 */
		relocateNav: function() {
			var oldParent = this.$headerParent;
			var oldMode = this.insertMode;

			// 再検出
			this.findHeaderParent();

			// 挿入先が変わった場合のみ移動
			var parentChanged = (this.insertMode !== oldMode) ||
				(this.$headerParent && oldParent && this.$headerParent[0] !== oldParent[0]) ||
				(this.$headerParent && !oldParent) ||
				(!this.$headerParent && oldParent);

			if (!parentChanged) return;

			// ナビをDOMから一旦外す
			this.$nav.detach();

			var $body = $('body');
			if (this.insertMode === 'inside' && this.$headerParent) {
				this.$headerParent.append(this.$nav);
				this.$nav.addClass('cp-inside-header');
				$body.removeClass('navitto-active navitto-body-padding');
			} else if (this.insertMode === 'after' && this.$headerParent) {
				this.$headerParent.after(this.$nav);
				this.$nav.removeClass('cp-inside-header');
				$body.addClass('navitto-active');
				this.updateBodyPaddingClass();
			} else {
				$('body').append(this.$nav);
				this.$nav.removeClass('cp-inside-header');
				$body.addClass('navitto-active');
				this.updateBodyPaddingClass();
			}
		},

		bindEvents: function() {
			var self = this;
			var resizeTimer = null;

			$(window).on('scroll.navitto', function() {
				self.onScroll();
			});

			$(window).on('resize.navitto', function() {
				// デバウンス: リサイズ完了後に再配置
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(function() {
					self.relocateNav();
					self.syncNavTransition();
					self.detectContentWidth();
					self.updateScrollHint();
					self.checkOverflow();
					self.updateScrollMargin();
				}, 150);
			});

			this.$nav.find('.navitto-nav__inner').on('scroll', function() {
				self.updateScrollHint();
			});

			this.$nav.on('click', '.navitto-nav__link', function(e) {
				e.preventDefault();
				var $link = $(this);
				var $item = $link.parent();
				var id = $link.attr('href').slice(1);
				var idx = $item.index();

				self.$nav.find('.navitto-nav__item').removeClass('navitto-nav__item--active');
				$item.addClass('navitto-nav__item--active');
				self.lastActiveIndex = idx;
				self._clickedIndex = idx;
				self._clickedTime = Date.now();

				// クリックしたアイテムを中央に配置
				self.centerActiveItem($item);
				self.scrollTo(id);
			});

			this.onScroll();
		},

		/**
		 * 指定した最後のh2の「次の」h2をキャッシュ（その位置に達したらナビを隠す）
		 */
		cacheNextH2AfterLast: function() {
			this.$nextH2AfterLast = null;
			this.$lastTrackedH2 = null;
			if (this.headings.length === 0) return;

			var lastEl = this.headings[this.headings.length - 1].element;
			if (!lastEl) return;

			// 見出し検出で採用した実要素をそのまま保持（コンテナ差異の影響を受けない）
			this.$lastTrackedH2 = $(lastEl);

			// 文書全体のh2順で「次のh2」を取得
			var $allH2 = $('h2');
			var lastIndex = $allH2.index(lastEl);
			if (lastIndex >= 0 && lastIndex < $allH2.length - 1) {
				this.$nextH2AfterLast = $allH2.eq(lastIndex + 1);
			}
		},

		/** 境界付近で二重動作しないようヒステリシス（px） */
		_nextH2Hysteresis: 80,
		_scrollRafId: null,

		/**
		 * 指定した最後のh2を過ぎて次のh2に達したかどうか（ヒステリシス：隠すのは閾値より下にスクロールしたとき）
		 */
		isPastLastH2: function(scrollTop) {
			var offset = this.getScrollOffset();
			// 通常: 最後に採用したh2の「次のh2」に達したら非表示
			if (this.$nextH2AfterLast && this.$nextH2AfterLast.length) {
				var threshold = this.$nextH2AfterLast.offset().top - offset;
				return scrollTop >= threshold + this._nextH2Hysteresis;
			}
			// フォールバック: 次h2がない場合は、最後のh2自体を十分通過したら非表示
			if (this.$lastTrackedH2 && this.$lastTrackedH2.length) {
				var rect = this.$lastTrackedH2[0].getBoundingClientRect();
				var docTop = scrollTop + rect.top;
				var thresholdLast = docTop + this.$lastTrackedH2.outerHeight();
				return scrollTop >= thresholdLast - offset + this._nextH2Hysteresis;
			}
			return false;
		},

		/**
		 * 次のh2より十分上に戻ったか（ヒステリシス：再表示は閾値より上にスクロールしたとき）
		 */
		isBackAboveNextH2: function(scrollTop) {
			var offset = this.getScrollOffset();
			if (this.$nextH2AfterLast && this.$nextH2AfterLast.length) {
				var threshold = this.$nextH2AfterLast.offset().top - offset;
				return scrollTop < threshold - this._nextH2Hysteresis;
			}
			if (this.$lastTrackedH2 && this.$lastTrackedH2.length) {
				var rect = this.$lastTrackedH2[0].getBoundingClientRect();
				var docTop = scrollTop + rect.top;
				var thresholdLast = docTop + this.$lastTrackedH2.outerHeight();
				return scrollTop < thresholdLast - offset - this._nextH2Hysteresis;
			}
			return true;
		},

		/**
		 * 表示開始条件を満たしているか判定
		 */
		shouldShow: function(scrollTop) {
			// 指定した最後のh2の「次の」h2に達したらナビを隠す（ヒステリシス込み）
			if (this.isPastLastH2(scrollTop)) {
				return false;
			}
			// 境界のデッドゾーン内では現在の表示状態を維持（二重動作防止）
			if (!this.isBackAboveNextH2(scrollTop) && this.$nav && this.$nav.hasClass('navitto-nav--past-last-h2')) {
				return false;
			}
			var trigger = this.settings.trigger || { type: 'immediate' };
			var type = trigger.type || 'immediate';

			// テーマヘッダーが sticky かつ immediate のときだけ先頭から表示（一瞬閉じるチラつき防止）
			// first_selected のときは「最初の見出しを通過後」なので先頭から表示しない
			if (this.$nav && this.$nav.hasClass('cp-theme-header-sticky') && type !== 'first_selected') {
				return true;
			}

			// immediate: showAfterScroll ベース（デフォルト動作）
			if (type === 'immediate') {
				return scrollTop > this.settings.showAfterScroll;
			}

			// first_selected: 選択した最初の見出しを通過したら
			if (type === 'first_selected') {
				if (this.headings.length === 0) return false;
				var $first = $(this.headings[0].element);
				if ($first.length === 0) return false;
				return scrollTop >= $first.offset().top - this.getScrollOffset();
			}

			// フォールバック
			return scrollTop > this.settings.showAfterScroll;
		},

		/**
		 * scroll-padding-top のCSS変数を更新
		 * html に scroll-padding-top を設定することで
		 * scrollIntoView / アンカーリンク時に自動でオフセットされる
		 */
		updateScrollMargin: function() {
			var navBottom = this.getNavBottom();
			// ナビが非表示(0)の場合は offsetHeight ベースのフォールバック
			if (navBottom <= 0) {
				navBottom = this.getScrollOffset() - 20; // getScrollOffset は +40 しているので -20 で +20 相当
			}
			var offset = navBottom + 20;
			document.documentElement.style.setProperty('--navitto-scroll-offset', offset + 'px');
		},

		onScroll: function() {
			var self = this;

			// アクティブ更新・表示/非表示を rAF で1フレームに1回にまとめる（カクツキ軽減）
			if (!this.isScrolling && this.$nav && this.$nav.length && this._scrollRafId == null) {
				this._scrollRafId = requestAnimationFrame(function() {
					self._scrollRafId = null;
					var top = $(window).scrollTop();
					if (!self.isScrolling) {
						self.updateActive(top);
					}
					if (self.shouldShow(top)) {
						if (!self.$nav.hasClass('is-visible')) {
							self.$nav.addClass('is-visible');
							self.$nav.removeClass('navitto-nav--past-last-h2');
							$('body').removeClass('navitto-past-last-h2');
							if (self.$headerParent && self.$headerParent.length) {
								self.$headerParent.removeClass('navitto-header-hidden');
							}
							self.updateScrollMargin();
						}
					} else {
						if (self.$nav.hasClass('is-visible')) {
							self.$nav.removeClass('is-visible');
						}
						if (self.isPastLastH2(top)) {
							self.$nav.removeClass('is-visible');
							self.$nav.addClass('navitto-nav--past-last-h2');
							$('body').addClass('navitto-past-last-h2');
							if (!self.$nav.hasClass('cp-inside-header') && self.$headerParent && self.$headerParent.length) {
								self.$headerParent.addClass('navitto-header-hidden');
							}
						} else if (self.isBackAboveNextH2(top)) {
							self.$nav.removeClass('navitto-nav--past-last-h2');
							$('body').removeClass('navitto-past-last-h2');
							if (self.$headerParent && self.$headerParent.length) {
								self.$headerParent.removeClass('navitto-header-hidden');
							}
						}
					}
				});
			}
		},

		updateActive: function(scrollTop) {
			// クリック＋補正完了後2秒間は指定したインデックスを維持（補正完了時に _clickedTime を再設定している）
			if (this._clickedIndex >= 0 && (Date.now() - this._clickedTime) < 2000) {
				return;
			}
			this._clickedIndex = -1;

			var activeIndex = 0;
			var offset = this.getScrollOffset();

			this.headings.forEach(function(heading, index) {
				var $el = $(heading.element);
				if ($el.length === 0) return;
				if (scrollTop >= $el.offset().top - offset - 10) {
					activeIndex = index;
				}
			});

			var $items = this.$nav.find('.navitto-nav__item');
			$items.removeClass('navitto-nav__item--active');
			var $active = $items.eq(activeIndex).addClass('navitto-nav__item--active');

			// カレントが変わったらセンタリングとインジケーター更新
			if (activeIndex !== this.lastActiveIndex) {
				this.lastActiveIndex = activeIndex;
				this.centerActiveItem($active);
			}
		},

		/**
		 * アクティブなナビアイテムを横スクロールで中央に配置
		 */
		centerActiveItem: function($activeItem) {
			if (!$activeItem || $activeItem.length === 0) return;

			var $inner = this.$nav.find('.navitto-nav__inner');
			if ($inner.length === 0) return;

			var innerEl = $inner[0];
			var innerRect = innerEl.getBoundingClientRect();
			var itemRect = $activeItem[0].getBoundingClientRect();

			// アイテムの中央とコンテナの中央の差分をスクロール量に加算
			var itemCenter = itemRect.left + itemRect.width / 2;
			var innerCenter = innerRect.left + innerRect.width / 2;
			var scrollTarget = innerEl.scrollLeft + (itemCenter - innerCenter);

			$inner.stop().animate({ scrollLeft: scrollTarget }, 300);
		},

		/**
		 * ナビの下端のビューポート位置を取得
		 */
		getNavBottom: function() {
			if (this.insertMode === 'inside' && this.$headerParent) {
				return this.$headerParent[0].getBoundingClientRect().bottom;
			} else if (this.$nav && this.$nav[0]) {
				return this.$nav[0].getBoundingClientRect().bottom;
			}
			return 0;
		},

		scrollTo: function(id) {
			var self = this;
			var $target = $('#' + id);
			if ($target.length === 0) return;

			this.isScrolling = true;

			// scroll-padding-top を最新の値に更新
			this.updateScrollMargin();

			// Step 1: scrollIntoView でざっくり移動
			$target[0].scrollIntoView({ behavior: 'smooth', block: 'start' });

			// Step 2: スクロール停止を検知 → ナビの高さ分フワッと補正（停止検知は時間制限なし。タイムアウト時も補正は実行する）
			var lastScroll = $(window).scrollTop();
			var stableCount = 0;
			var checkCount = 0;
			var corrected = false;

			function runCorrection() {
				if (corrected) return;
				corrected = true;
				clearInterval(checkInterval);
				clearTimeout(safetyTimeout);
				// 補正後にナビのカレントが「1つ前」に戻らないよう、対象見出しのインデックスを特定して強制反映する
				var targetIndex = -1;
				for (var i = 0; i < self.headings.length; i++) {
					if (self.headings[i].element === $target[0]) {
						targetIndex = i;
						break;
					}
				}
				function finishScroll() {
					self.isScrolling = false;
					if (targetIndex >= 0) {
						self.lastActiveIndex = targetIndex;
						self._clickedIndex = targetIndex;
						self._clickedTime = Date.now();
						self.$nav.find('.navitto-nav__item').removeClass('navitto-nav__item--active');
						self.$nav.find('.navitto-nav__item').eq(targetIndex).addClass('navitto-nav__item--active');
						self.centerActiveItem(self.$nav.find('.navitto-nav__item').eq(targetIndex));
					}
				}
				requestAnimationFrame(function() {
					var headingRect = $target[0].getBoundingClientRect();
					var navBottom = self.getNavBottom();
					var desiredTop = navBottom + 20;
					if (headingRect.top < desiredTop) {
						var diff = desiredTop - headingRect.top;
						$('html, body').stop().animate({
							scrollTop: $(window).scrollTop() - diff
						}, 100, 'swing', finishScroll);
					} else {
						finishScroll();
					}
				});
			}

			var checkInterval = setInterval(function() {
				var currentScroll = $(window).scrollTop();
				if (Math.abs(currentScroll - lastScroll) < 1) {
					stableCount++;
				} else {
					stableCount = 0;
				}
				lastScroll = currentScroll;
				checkCount++;
				// 2回連続で安定 or 120回チェック(6秒)で完了とみなす
				if (stableCount >= 2 || checkCount >= 120) {
					runCorrection();
				}
			}, 50);

			// 長距離スクロールでも補正を必ず1回実行する（安定検知が遅れてもタイムアウト時に補正して isScrolling 解除）
			var safetyTimeout = setTimeout(function() {
				if (!corrected) {
					runCorrection();
				}
			}, 5000);
		},

		escapeHtml: function(str) {
			var div = document.createElement('div');
			div.textContent = str;
			return div.innerHTML;
		},

		escapeAttr: function(str) {
			return String(str)
				.replace(/&/g, '&amp;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#39;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;');
		}
	};

	Navitto.init();

})(jQuery);

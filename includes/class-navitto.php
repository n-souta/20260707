<?php
/**
 * Navitto メインクラス
 *
 * フロントエンドの表示を管理するシングルトンクラス
 *
 * @package Navitto
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Navitto_Main {

	private static $instance = null;
	private $version;

	private function __construct() {
		$this->version = NAVITTO_VERSION;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * body タグにクラスを追加（配置位置の判定用）
	 */
	public function add_body_class( $classes ) {
		if ( ! is_singular( array( 'post', 'page' ) ) ) {
			return $classes;
		}

		$post_id = get_the_ID();
		if ( ! $this->should_display( $post_id ) ) {
			return $classes;
		}

		$position = get_theme_mod( 'navitto_position', 'top' );
		if ( ! in_array( $position, array( 'top', 'bottom' ), true ) ) {
			$position = 'top';
		}
		$classes[] = 'navitto-pos-' . $position;

		return $classes;
	}

	/**
	 * スクリプトとスタイルを読み込む
	 */
	public function enqueue_scripts() {
		if ( ! is_singular( array( 'post', 'page' ) ) ) {
			return;
		}

		$post_id = get_the_ID();

		if ( ! $this->should_display( $post_id ) ) {
			return;
		}

		// CSS
		wp_enqueue_style(
			'navitto-frontend',
			NAVITTO_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			$this->version
		);

		// JavaScript
		wp_enqueue_script(
			'navitto-frontend',
			NAVITTO_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// 検出データ
		$detector       = Navitto_Detector::get_instance();
		$detection_data = $detector->get_detection_data();
		$header_data    = $detector->get_fixed_header_data();

		// 表示モード・H2選択データ
		$display_mode = get_post_meta( $post_id, '_navitto_display_mode', true );
		if ( '' === $display_mode ) {
			$display_mode = 'show_all';
		}
		// 後方互換: auto → show_all
		if ( 'auto' === $display_mode ) {
			$display_mode = 'show_all';
		}

		$selected_h2  = array();
		$custom_texts  = array();
		$h2_icons      = array();
		if ( 'select' === $display_mode ) {
			$raw = get_post_meta( $post_id, '_navitto_selected_h2', true );
			$selected_h2 = is_array( $raw ) ? $raw : array();
			$raw_texts = get_post_meta( $post_id, '_navitto_h2_custom_texts', true );
			$custom_texts = is_array( $raw_texts ) ? $raw_texts : array();
		}

		// 表示開始位置の設定
		$trigger_type = get_post_meta( $post_id, '_navitto_trigger_type', true );
		$trigger_data = array(
			'type' => $trigger_type ? $trigger_type : 'immediate',
		);
		// プリセット
		$preset = get_theme_mod( 'navitto_preset', 'simple' );
		if ( ! in_array( $preset, array( 'simple', 'theme' ), true ) ) {
			$preset = 'simple';
		}

		// 固定ナビの表示方法（投稿編集画面でのみ設定）
		$nav_width = get_post_meta( $post_id, '_navitto_nav_width', true );
		if ( ! in_array( $nav_width, array( 'scroll', 'equal' ), true ) ) {
			$nav_width = 'scroll';
		}

		// カスタマイザーの設定
		$position = get_theme_mod( 'navitto_position', 'top' );

		// JS用テキストデータ（intキーをstringに変換）
		$js_custom_texts = array();
		foreach ( $custom_texts as $k => $v ) {
			$js_custom_texts[ strval( $k ) ] = $v;
		}
		$js_h2_icons = array();

		$theme_bg_transparent = (bool) get_theme_mod( 'navitto_theme_bg_transparent', false );

		wp_localize_script(
			'navitto-frontend',
			'navittoData',
			array(
				'scrollOffset'        => 80,
				'animDuration'        => 500,
				'showAfterScroll'     => 0,
				'preset'              => $preset,
				'themeBgTransparent'   => $theme_bg_transparent,
				'position'            => $position,
				'displayMode'         => $display_mode,
				'selectedH2'          => $selected_h2,
				'customTexts'         => ! empty( $js_custom_texts ) ? $js_custom_texts : new stdClass(),
				'h2Icons'             => ! empty( $js_h2_icons ) ? $js_h2_icons : new stdClass(),
				'trigger'             => $trigger_data,
				'navWidth'            => $nav_width,
				'detection'           => $detection_data,
				'fixedHeader'         => $header_data,
			)
		);

		// インラインCSS
		$this->generate_inline_css();
	}

	/**
	 * カスタマイザー設定に対応するインライン CSS（プラグイン内の固定断片のみ）
	 *
	 * レビュー推奨どおり、サニタイズ済みの allowlist 値の組み合わせをキーに、
	 * 事前定義した CSS 文字列だけを返す（変数連結で CSS を組み立てない）。
	 *
	 * @param string $nav_height  'small'|'medium'|'large'
	 * @param string $font_weight 'default'|'bold'
	 * @return string 空文字はデフォルトテーマ変数のみ（インライン不要）
	 */
	private function get_inline_css_for_theme_mods( $nav_height, $font_weight ) {
		$map = array(
			'small|default'  => ':root{--navitto-height:44px;--navitto-height-mobile:38px;--navitto-font-size:12px;}',
			'small|bold'     => ':root{--navitto-height:44px;--navitto-height-mobile:38px;--navitto-font-size:12px;--navitto-font-weight:700;}',
			'medium|default' => '',
			'medium|bold'    => ':root{--navitto-font-weight:700;}',
			'large|default'  => ':root{--navitto-height:68px;--navitto-height-mobile:56px;--navitto-font-size:16px;}',
			'large|bold'     => ':root{--navitto-height:68px;--navitto-height-mobile:56px;--navitto-font-size:16px;--navitto-font-weight:700;}',
		);

		$key = $nav_height . '|' . $font_weight;

		return isset( $map[ $key ] ) ? $map[ $key ] : '';
	}

	/**
	 * カスタマイザー設定からインライン CSS を出力
	 */
	private function generate_inline_css() {
		$nav_height  = get_theme_mod( 'navitto_nav_height', 'medium' );
		$font_weight = get_theme_mod( 'navitto_font_weight', 'default' );

		$allowed_nav_heights = array( 'small', 'medium', 'large' );
		if ( ! in_array( $nav_height, $allowed_nav_heights, true ) ) {
			$nav_height = 'medium';
		}

		$allowed_font_weights = array( 'default', 'bold' );
		if ( ! in_array( $font_weight, $allowed_font_weights, true ) ) {
			$font_weight = 'default';
		}

		$css = $this->get_inline_css_for_theme_mods( $nav_height, $font_weight );

		if ( '' !== $css ) {
			wp_add_inline_style( 'navitto-frontend', $css );
		}
	}

	/**
	 * 表示条件をチェック
	 */
	public function should_display( $post_id ) {
		// 一括で無効にされている場合は表示しない（display_mode や選択見出しは保持したまま）
		if ( '1' === get_post_meta( $post_id, '_navitto_bulk_disabled', true ) ) {
			return false;
		}

		// 新しい表示モードを確認
		$display_mode = get_post_meta( $post_id, '_navitto_display_mode', true );

		if ( 'hide' === $display_mode ) {
			return false;
		}

		// メタ未設定: 有効化前からあった既存記事は表示しない（一括適用または個別に「表示」を選んで保存した投稿のみ表示）
		if ( '' === $display_mode || 'auto' === $display_mode ) {
			$enabled = get_post_meta( $post_id, '_navitto_enabled', true );
			if ( '0' === $enabled ) {
				return false;
			}
			if ( '' === $display_mode && '' === $enabled ) {
				return false;
			}
		}

		$post    = get_post( $post_id );
		$content = $post ? $post->post_content : '';

		// selectモードではH2数チェックをスキップ
		if ( 'select' === $display_mode ) {
			return true;
		}

		// H2見出しが2個以上
		$h2_count = $this->count_h2_headings( $content );
		if ( $h2_count < 2 ) {
			return false;
		}

		return true;
	}

	private function count_h2_headings( $content ) {
		if ( '' === $content ) {
			return 0;
		}

		$count = 0;

		preg_match_all( '/<h2[^>]*>/i', $content, $tag_matches );
		$count += count( $tag_matches[0] );

		// ブロックエディター: level 2 の見出しブロック（SWELL 等）
		preg_match_all( '/<!--\s+wp:heading\s+\{[^}]*"level"\s*:\s*2[^}]*\}/i', $content, $block_matches );
		$count += count( $block_matches[0] );

		return $count;
	}

	public function get_version() {
		return $this->version;
	}
}

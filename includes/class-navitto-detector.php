<?php
/**
 * Navitto 目次検出クラス
 *
 * テーマ内蔵目次・目次プラグインを検出し、
 * 検出優先順位に基づいてフロントエンドに情報を渡す
 *
 * @package Navitto
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Navitto_Detector
 *
 * 目次ソースの検出と優先順位管理
 *
 * @since 1.1.0
 */
class Navitto_Detector {

	/**
	 * シングルトンインスタンス
	 *
	 * @var Navitto_Detector|null
	 */
	private static $instance = null;

	/**
	 * テーマ内蔵目次のセレクタ定義（検出優先順位1）
	 *
	 * @var array
	 */
	private $theme_toc_selectors = array(
		'swell'    => array(
			'name'      => 'SWELL',
			'container' => '.wp-block-swl-toc',
			'items'     => '.wp-block-swl-toc a',
		),
		'jin'      => array(
			'name'      => 'JIN / JIN:R',
			'container' => '.jin-toc',
			'items'     => '.jin-toc a',
		),
		'sango'    => array(
			'name'      => 'SANGO',
			'container' => '.sng-toc',
			'items'     => '.sng-toc a',
		),
		'affinger' => array(
			'name'      => 'AFFINGER',
			'container' => '.st-toc',
			'items'     => '.st-toc a',
		),
		'cocoon'   => array(
			'name'      => 'Cocoon',
			'container' => '.toc',
			'items'     => '.toc a',
		),
		'the_thor' => array(
			'name'      => 'THE THOR',
			'container' => '.ep-toc',
			'items'     => '.ep-toc a',
		),
	);

	/**
	 * 目次プラグインのセレクタ定義（検出優先順位2）
	 *
	 * @var array
	 */
	private $plugin_toc_selectors = array(
		'toc_plus' => array(
			'name'      => 'Table of Contents Plus',
			'container' => '#toc_container',
			'items'     => '#toc_container a',
		),
		'easy_toc' => array(
			'name'      => 'Easy Table of Contents',
			'container' => '.ez-toc-container',
			'items'     => '.ez-toc-container a',
		),
		'rich_toc' => array(
			'name'      => 'Rich Table of Contents',
			'container' => '.rtoc-mokuji',
			'items'     => '.rtoc-mokuji a',
		),
		'lwptoc'   => array(
			'name'      => 'LuckyWP Table of Contents',
			'container' => '.lwptoc',
			'items'     => '.lwptoc a',
		),
	);

	/**
	 * テーマ別の固定ヘッダーセレクタ
	 *
	 * @var array
	 */
	private $fixed_header_selectors = array(
		'swell'    => array( 'pc' => '#fix_header',  'sp' => '#header' ),
		'jin'      => array( 'pc' => '#header',      'sp' => '#header' ),
		'sango'    => array( 'pc' => '.header',      'sp' => '.header' ),
		'affinger' => array( 'pc' => '#st-header',   'sp' => '#st-header' ),
		'cocoon'   => array( 'pc' => '.sticky-header','sp' => '.sticky-header' ),
		'the_thor' => array( 'pc' => '#header',      'sp' => '#header' ),
	);

	/**
	 * コンストラクタ
	 */
	private function __construct() {}

	/**
	 * シングルトンインスタンスを取得
	 *
	 * @return Navitto_Detector
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 現在のテーマスラッグを取得（親テーマ・子テーマ考慮）
	 *
	 * @return string テーマスラッグ（小文字）
	 */
	public function get_current_theme_slug() {
		$theme  = wp_get_theme();
		$parent = $theme->parent();
		$slug   = $parent ? $parent->get_stylesheet() : $theme->get_stylesheet();
		return strtolower( $slug );
	}

	/**
	 * テーマが目次機能を内蔵しているか判定
	 *
	 * @return string|false 対応するテーマキー、または false
	 */
	public function detect_theme_toc() {
		$slug = $this->get_current_theme_slug();

		$theme_map = array(
			'swell'           => 'swell',
			'jin'             => 'jin',
			'jin-r'           => 'jin',
			'jinr'            => 'jin',
			'sango'           => 'sango',
			'affinger'        => 'affinger',
			'affinger5'       => 'affinger',
			'affinger6'       => 'affinger',
			'wing-affinger5'  => 'affinger',
			'cocoon'          => 'cocoon',
			'cocoon-child'    => 'cocoon',
			'the-thor'        => 'the_thor',
			'the_thor'        => 'the_thor',
		);

		foreach ( $theme_map as $s => $key ) {
			if ( strpos( $slug, $s ) !== false ) {
				return $key;
			}
		}

		return false;
	}

	/**
	 * 有効な目次プラグインを検出
	 *
	 * @return array 有効な目次プラグインのキー配列
	 */
	public function detect_active_toc_plugins() {
		$active = array();

		$plugin_checks = array(
			'toc_plus' => array( 'table-of-contents-plus/toc.php' ),
			'easy_toc' => array( 'easy-table-of-contents/easy-table-of-contents.php' ),
			'rich_toc' => array( 'rich-table-of-contents/richtext-table-of-content.php' ),
			'lwptoc'   => array( 'flavor/flavor.php' ),
		);

		foreach ( $plugin_checks as $key => $files ) {
			foreach ( $files as $file ) {
				if ( is_plugin_active( $file ) ) {
					$active[] = $key;
					break;
				}
			}
		}

		return $active;
	}

	/**
	 * テーマの固定ヘッダーセレクタを取得
	 *
	 * @return array|false セレクタ配列 { pc, sp }、または false
	 */
	public function get_fixed_header_selector() {
		$theme_key = $this->detect_theme_toc();
		if ( $theme_key && isset( $this->fixed_header_selectors[ $theme_key ] ) ) {
			return $this->fixed_header_selectors[ $theme_key ];
		}
		return false;
	}

	/**
	 * 検出データをJS用に生成
	 *
	 * 優先順位:
	 * 1. テーマ内蔵目次
	 * 2. 目次プラグイン
	 * 3. H2タグから自動生成（JSのフォールバック）
	 *
	 * @return array JS用の検出データ
	 */
	public function get_detection_data() {
		$data = array(
			'detectionOrder' => array(),
			'detectedTheme'  => '',
			'detectedPlugin' => '',
			'source'         => 'h2',
		);

		// 1. テーマ内蔵目次の検出
		$theme_key = $this->detect_theme_toc();

		// SWELL: 固定ナビは本文 H2 を優先（内蔵目次だけだとリンク数不足で表示されないことがある）
		if ( 'swell' === $theme_key ) {
			$data['detectedTheme'] = $this->theme_toc_selectors['swell']['name'];
		}

		if ( $theme_key && isset( $this->theme_toc_selectors[ $theme_key ] ) ) {
			$t = $this->theme_toc_selectors[ $theme_key ];
			if ( 'swell' !== $theme_key ) {
				$data['detectedTheme'] = $t['name'];
			}
			$data['detectionOrder'][] = array(
				'source'    => 'theme',
				'name'      => $t['name'],
				'container' => $t['container'],
				'items'     => $t['items'],
			);
		}

		// 2. 目次プラグインの検出
		foreach ( $this->detect_active_toc_plugins() as $pk ) {
			if ( isset( $this->plugin_toc_selectors[ $pk ] ) ) {
				$p = $this->plugin_toc_selectors[ $pk ];
				if ( empty( $data['detectedPlugin'] ) ) {
					$data['detectedPlugin'] = $p['name'];
				}
				$data['detectionOrder'][] = array(
					'source'    => 'plugin',
					'name'      => $p['name'],
					'container' => $p['container'],
					'items'     => $p['items'],
				);
			}
		}

		// 3. 汎用セレクタ（未追加分を追加）
		$added = array();
		foreach ( $data['detectionOrder'] as $entry ) {
			$added[] = $entry['container'];
		}
		$all = array_merge( $this->theme_toc_selectors, $this->plugin_toc_selectors );
		foreach ( $all as $sel ) {
			if ( ! in_array( $sel['container'], $added, true ) ) {
				$data['detectionOrder'][] = array(
					'source'    => 'generic',
					'name'      => $sel['name'],
					'container' => $sel['container'],
					'items'     => $sel['items'],
				);
			}
		}

		// 4. H2フォールバック（SWELL は先頭、それ以外は末尾）
		$h2_entry = array(
			'source'    => 'h2',
			'name'      => 'H2 Auto Detect',
			'container' => '',
			'items'     => '',
		);
		if ( 'swell' === $theme_key ) {
			array_unshift( $data['detectionOrder'], $h2_entry );
		} else {
			$data['detectionOrder'][] = $h2_entry;
		}

		return $data;
	}

	/**
	 * 固定ヘッダー検出データをJS用に生成
	 *
	 * @return array 固定ヘッダー検出データ
	 */
	public function get_fixed_header_data() {
		return array(
			'selectors'        => $this->get_fixed_header_selector() ?: array(),
			'customSelectorPc' => get_theme_mod( 'navitto_fixed_header_selector_pc', '' ),
			'customSelectorSp' => get_theme_mod( 'navitto_fixed_header_selector_sp', '' ),
		);
	}

	/**
	 * テーマ目次セレクタを外部から追加
	 *
	 * @param string $key     セレクタキー
	 * @param array  $selector セレクタ情報
	 */
	public function add_theme_toc_selector( $key, $selector ) {
		$this->theme_toc_selectors[ $key ] = $selector;
	}

	/**
	 * プラグイン目次セレクタを外部から追加
	 *
	 * @param string $key     セレクタキー
	 * @param array  $selector セレクタ情報
	 */
	public function add_plugin_toc_selector( $key, $selector ) {
		$this->plugin_toc_selectors[ $key ] = $selector;
	}
}

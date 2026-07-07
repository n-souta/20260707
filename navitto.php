<?php
/**
 * Plugin Name:       Navitto
 * Plugin URI:        https://wordpress.org/plugins/navitto/
 * Description:       Fixed navigation bar that follows H2 headings and shows a simple, sticky table of contents.
 * Version:           1.0.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            nsouta
 * Author URI:        https://profiles.wordpress.org/nsouta/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       navitto
 * Domain Path:       /languages
 *
 * @package Navitto
 */

/**
 * Navitto is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Navitto is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Navitto. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

// 直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * プラグインバージョン
 */
define( 'NAVITTO_VERSION', '1.0.5' );

/**
 * プラグインディレクトリパス
 */
define( 'NAVITTO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * プラグインURL
 */
define( 'NAVITTO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * プラグインベースネーム
 */
define( 'NAVITTO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * GlotPress の言語パックおよび languages/ 内の翻訳を読み込む
 *
 * @since 1.0.2
 * @return void
 */
function navitto_load_textdomain() {
	load_plugin_textdomain(
		'navitto',
		false,
		dirname( NAVITTO_PLUGIN_BASENAME ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'navitto_load_textdomain', 5 );

/**
 * クラスファイルの読み込み
 * 管理画面専用クラスは is_admin() 内で遅延読込（#7 Structure）
 */
require_once NAVITTO_PLUGIN_DIR . 'includes/class-navitto.php';
require_once NAVITTO_PLUGIN_DIR . 'includes/class-navitto-admin.php';
require_once NAVITTO_PLUGIN_DIR . 'includes/class-navitto-detector.php';

/**
 * プラグインを初期化する
 *
 * @since 1.0.0
 * @return void
 */
function navitto_init() {
	// フロントエンド初期化
	$plugin = Navitto_Main::get_instance();
	$plugin->init();

	// カスタマイザー登録（管理画面・プレビュー両方で必要）
	$admin = Navitto_Admin::get_instance();
	$admin->init_customizer();

	// 管理画面初期化
	if ( is_admin() ) {
		$admin->init();

		// 設定ページ初期化（管理画面でのみ読込 — #7 Structure）
		require_once NAVITTO_PLUGIN_DIR . 'includes/class-navitto-settings.php';
		$settings = Navitto_Settings::get_instance();
		$settings->init();
	}

	// バージョンベースのアップグレードチェック（#9 Data）
	navitto_maybe_upgrade();
}
add_action( 'plugins_loaded', 'navitto_init' );

/**
 * バージョンベースのアップグレード処理
 *
 * DBバージョンと現在バージョンを比較し、必要に応じてマイグレーションを実行
 *
 * @since 1.0.1
 * @return void
 */
function navitto_maybe_upgrade() {
	$db_version = get_option( 'navitto_db_version', '0' );

	if ( version_compare( $db_version, NAVITTO_VERSION, '>=' ) ) {
		return;
	}

	// 1.0.0 → 1.0.1: 旧オプション（activation で作成された孤立データ）を削除
	if ( version_compare( $db_version, '1.0.1', '<' ) ) {
		delete_option( 'navitto_default_preset' );
		delete_option( 'navitto_position' );
	}

	// 現在のバージョンを保存
	update_option( 'navitto_db_version', NAVITTO_VERSION );
}

/**
 * プラグインアクティベーション時の処理
 *
 * @since 1.0.0
 * @return void
 */
function navitto_activate() {
	// DBバージョンを記録（#9 Data — アップグレード機構）
	add_option( 'navitto_db_version', NAVITTO_VERSION );

	// デフォルト有効化設定
	add_option( 'navitto_default_enabled', true );
}
register_activation_hook( __FILE__, 'navitto_activate' );

/**
 * プラグインディアクティベーション時の処理
 *
 * @since 1.0.0
 * @return void
 */
function navitto_deactivate() {
	// カスタム投稿タイプ/リライトルール未使用のため flush_rewrite_rules() は不要（#2 Lifecycle）
}
register_deactivation_hook( __FILE__, 'navitto_deactivate' );

<?php
/**
 * Navitto 管理画面クラス
 *
 * 投稿編集画面のメタボックスとカスタマイザーを管理
 *
 * @package Navitto
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Navitto_Admin {

	private static $instance = null;

	private function __construct() {}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 管理画面の初期化（メタボックス・保存・アセット）
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * メタボックス用のCSS/JSを投稿編集画面のみでエンキュー（#8 Structure）
	 *
	 * @param string $hook_suffix 現在の管理画面フック名
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'navitto-admin-metabox',
			NAVITTO_PLUGIN_URL . 'assets/css/admin-metabox.css',
			array(),
			NAVITTO_VERSION
		);

		wp_enqueue_script(
			'navitto-admin-metabox',
			NAVITTO_PLUGIN_URL . 'assets/js/admin-metabox.js',
			array(),
			NAVITTO_VERSION,
			true
		);
	}

	/**
	 * カスタマイザーの初期化
	 */
	public function init_customizer() {
		add_action( 'customize_register', array( $this, 'register_customizer' ) );
	}

	/* =========================================================================
	   メタボックス
	   ========================================================================= */

	public function add_meta_boxes() {
		add_meta_box(
			'navitto_settings',
			__( 'Navitto', 'navitto' ),
			array( $this, 'render_meta_box' ),
			array( 'post', 'page' ),
			'side',
			'default'
		);
	}

	/**
	 * メタボックスの内容を出力
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'navitto_save_meta', 'navitto_meta_nonce' );

		// 現在の値を取得
		$display_mode = get_post_meta( $post->ID, '_navitto_display_mode', true );
		if ( '' === $display_mode ) {
			$old_enabled = get_post_meta( $post->ID, '_navitto_enabled', true );
			if ( '0' === $old_enabled ) {
				$display_mode = 'hide';
			} else {
				// メタ未設定時は「新規投稿でデフォルトで有効にする」に従う
				$display_mode = get_option( 'navitto_default_enabled', true ) ? 'show_all' : 'hide';
			}
		}
		// 後方互換: auto → show_all
		if ( 'auto' === $display_mode ) {
			$display_mode = 'show_all';
		}

		$selected_h2   = array();
		$custom_texts  = array();
		if ( 'select' === $display_mode ) {
			$selected_h2 = get_post_meta( $post->ID, '_navitto_selected_h2', true );
			$selected_h2 = is_array( $selected_h2 ) ? $selected_h2 : array();
			$custom_texts = get_post_meta( $post->ID, '_navitto_h2_custom_texts', true );
			$custom_texts = is_array( $custom_texts ) ? $custom_texts : array();
		}

		// トリガー設定
		$trigger_type = get_post_meta( $post->ID, '_navitto_trigger_type', true );
		$trigger_type = $trigger_type ? $trigger_type : 'immediate';

		// 投稿内容からH2を取得（ブロックエディタ・クラシック両対応・最新のDB本文を使用）
		$content = $post->post_content;
		if ( $post->ID > 0 ) {
			$latest = get_post( $post->ID );
			if ( $latest && $latest->post_content !== '' ) {
				$content = $latest->post_content;
			}
		}
		$h2_list = $this->extract_h2_list_from_content( $content );

		$is_select   = ( 'select' === $display_mode );
		?>
		<div class="navitto-meta-box">
			<!-- 表示モード -->
			<div class="cp-radio-group">
				<label>
					<input type="radio" name="navitto_display_mode" value="show_all"
						<?php checked( $display_mode, 'show_all' ); ?> />
					<?php esc_html_e( 'Show fixed nav (use H2 headings as-is)', 'navitto' ); ?>
				</label>
				<label>
					<input type="radio" name="navitto_display_mode" value="select"
						<?php checked( $display_mode, 'select' ); ?> />
					<?php esc_html_e( 'Choose headings to display', 'navitto' ); ?>
				</label>
				<label>
					<input type="radio" name="navitto_display_mode" value="hide"
						<?php checked( $display_mode, 'hide' ); ?> />
					<?php esc_html_e( 'Hide fixed nav', 'navitto' ); ?>
				</label>
			</div>

			<!-- 見出し選択（select時のみ表示） -->
			<div id="cp-h2-select-area" style="<?php echo esc_attr( $is_select ? '' : 'display:none;' ); ?>" data-navitto-empty="<?php echo esc_attr( empty( $h2_list ) ? '1' : '0' ); ?>">
				<?php if ( empty( $h2_list ) ) : ?>
					<p class="description" id="navitto-h2-empty-msg"><?php esc_html_e( 'No H2 headings found.', 'navitto' ); ?></p>
				<?php else : ?>
				<?php foreach ( $h2_list as $index => $h2_text ) :
					$is_checked  = in_array( $index, $selected_h2, false );
					$custom_text = isset( $custom_texts[ $index ] ) ? $custom_texts[ $index ] : '';
				?>
					<div class="cp-h2-item">
						<label>
							<input type="checkbox"
								name="navitto_selected_h2[]"
								value="<?php echo esc_attr( $index ); ?>"
								class="cp-h2-checkbox"
								data-index="<?php echo esc_attr( $index ); ?>"
								<?php checked( $is_checked ); ?> />
							<?php echo esc_html( $h2_text ); ?>
						</label>
						<div class="cp-h2-item-row">
							<input type="text"
								name="navitto_h2_text_<?php echo esc_attr( $index ); ?>"
								class="cp-h2-text-input"
								data-index="<?php echo esc_attr( $index ); ?>"
								value="<?php echo esc_attr( $custom_text ); ?>"
								placeholder="<?php echo esc_attr( $h2_text ); ?>"
								<?php echo esc_attr( $is_checked ? '' : 'disabled' ); ?> />
						</div>
					</div>
				<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<!-- 表示開始位置（select時のみ表示） -->
			<div class="cp-trigger-settings navitto-trigger-settings" style="<?php echo esc_attr( $is_select ? '' : 'display:none;' ); ?>">
				<h4><?php esc_html_e( 'Display start position', 'navitto' ); ?></h4>

				<label>
					<input type="radio" name="_navitto_trigger_type" value="immediate"
						<?php checked( $trigger_type, 'immediate' ); ?> />
					<?php esc_html_e( 'From top of page', 'navitto' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Show fixed nav when the selected heading reaches the top of the page', 'navitto' ); ?></p>

				<label>
					<input type="radio" name="_navitto_trigger_type" value="first_selected"
						<?php checked( $trigger_type, 'first_selected' ); ?> />
					<?php esc_html_e( 'After passing the first selected heading', 'navitto' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Show after passing the first checked heading', 'navitto' ); ?></p>
			</div>

			<!-- 固定ナビの表示方法 -->
			<div class="cp-nav-width-setting" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #ddd;">
				<label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 6px;">
					<?php esc_html_e( 'Fixed nav display mode', 'navitto' ); ?>
				</label>
				<?php
				$nav_width = get_post_meta( $post->ID, '_navitto_nav_width', true );
				if ( ! in_array( $nav_width, array( 'scroll', 'equal' ), true ) ) {
					$nav_width = 'scroll';
				}
				$nw_options = array(
					'scroll' => __( 'Horizontal scroll (show all)', 'navitto' ),
					'equal'  => __( 'Equal width (hide overflow)', 'navitto' ),
				);
				foreach ( $nw_options as $val => $label ) : ?>
					<label style="display: block; margin-bottom: 4px; font-size: 13px;">
						<input type="radio" name="_navitto_nav_width"
							value="<?php echo esc_attr( $val ); ?>"
							<?php checked( $nav_width, $val ); ?> />
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * 投稿本文からH2見出しのテキスト一覧を取得（ブロック・クラシック両対応）
	 *
	 * @param string $content 投稿本文（post_content）
	 * @return array H2のテキスト配列（0始まりのインデックス）
	 */
	private function extract_h2_list_from_content( $content ) {
		$h2_list = array();

		// 1) do_blocks でレンダーしたHTMLから <h2>...</h2> を抽出
		$rendered = $content;
		if ( function_exists( 'do_blocks' ) ) {
			$rendered = do_blocks( $content );
		}
		if ( preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $rendered, $m ) && ! empty( $m[1] ) ) {
			foreach ( $m[1] as $inner ) {
				$h2_list[] = wp_strip_all_tags( $inner );
			}
			return $h2_list;
		}

		// 2) 生の本文からも <h2>...</h2> を試す（do_blocks 前のHTML）
		if ( preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $content, $m ) && ! empty( $m[1] ) ) {
			foreach ( $m[1] as $inner ) {
				$h2_list[] = wp_strip_all_tags( $inner );
			}
			return $h2_list;
		}

		// 3) ブロック形式のみ（level:2）の見出しを抽出
		if ( preg_match_all( '/<!--\s*wp:heading[^>]*"level"\s*:\s*2[^>]*-->\s*<h2[^>]*>(.*?)<\/h2>/is', $content, $m ) && ! empty( $m[1] ) ) {
			foreach ( $m[1] as $inner ) {
				$h2_list[] = wp_strip_all_tags( $inner );
			}
		}

		return $h2_list;
	}

	/**
	 * メタデータを保存
	 */
	public function save_meta( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['navitto_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['navitto_meta_nonce'] ) ), 'navitto_save_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 表示モード
		$mode = isset( $_POST['navitto_display_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['navitto_display_mode'] ) ) : 'show_all';
		if ( ! in_array( $mode, array( 'show_all', 'select', 'hide' ), true ) ) {
			$mode = 'show_all';
		}
		update_post_meta( $post_id, '_navitto_display_mode', $mode );

		// 後方互換: _navitto_enabled も更新
		update_post_meta( $post_id, '_navitto_enabled', 'hide' === $mode ? '0' : '1' );

		// 投稿で「表示」を選んだ場合は一括無効フラグを外す（個別の選択を優先）
		if ( 'show_all' === $mode || 'select' === $mode ) {
			delete_post_meta( $post_id, '_navitto_bulk_disabled' );
		}

		// H2選択データ
		if ( 'select' === $mode ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- array_map('intval') sanitizes
			$selected = isset( $_POST['navitto_selected_h2'] ) ? array_map( 'intval', wp_unslash( $_POST['navitto_selected_h2'] ) ) : array();
			update_post_meta( $post_id, '_navitto_selected_h2', $selected );

			$texts = array();
			foreach ( $selected as $idx ) {
				$key = 'navitto_h2_text_' . $idx;
				if ( isset( $_POST[ $key ] ) ) {
					$texts[ $idx ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				}
			}
			update_post_meta( $post_id, '_navitto_h2_custom_texts', $texts );

			// 表示開始位置
			$trigger_type = isset( $_POST['_navitto_trigger_type'] )
				? sanitize_text_field( wp_unslash( $_POST['_navitto_trigger_type'] ) )
				: 'immediate';
			if ( ! in_array( $trigger_type, array( 'immediate', 'first_selected' ), true ) ) {
				$trigger_type = 'immediate';
			}
			update_post_meta( $post_id, '_navitto_trigger_type', $trigger_type );
		} else {
			delete_post_meta( $post_id, '_navitto_selected_h2' );
			delete_post_meta( $post_id, '_navitto_h2_custom_texts' );
			delete_post_meta( $post_id, '_navitto_trigger_type' );
		}

		// 固定ナビの表示方法
		$nav_width = isset( $_POST['_navitto_nav_width'] ) ? sanitize_text_field( wp_unslash( $_POST['_navitto_nav_width'] ) ) : 'scroll';
		if ( ! in_array( $nav_width, array( 'scroll', 'equal' ), true ) ) {
			$nav_width = 'scroll';
		}
		update_post_meta( $post_id, '_navitto_nav_width', $nav_width );

	}

	/* =========================================================================
	   カスタマイザー
	   ========================================================================= */

	/**
	 * カスタマイザー設定を登録
	 */
	public function register_customizer( $wp_customize ) {

		// --- セクション: デザイン ---
		$wp_customize->add_section( 'navitto_design', array(
			'title'    => __( 'Navitto - Design', 'navitto' ),
			'priority' => 200,
		) );

		$preset_choices = array(
			'simple' => __( 'Simple', 'navitto' ),
			'theme'  => __( 'Match theme', 'navitto' ),
		);
		$wp_customize->add_setting( 'navitto_preset', array(
			'default'           => 'simple',
			'sanitize_callback' => array( $this, 'sanitize_preset' ),
		) );
		$wp_customize->add_control( 'navitto_preset', array(
			'label'   => __( 'Design preset', 'navitto' ),
			'section' => 'navitto_design',
			'type'    => 'select',
			'choices' => $preset_choices,
		) );

		// 配置位置
		$wp_customize->add_setting( 'navitto_position', array(
			'default'           => 'top',
			'sanitize_callback' => array( $this, 'sanitize_position' ),
		) );
		$wp_customize->add_control( 'navitto_position', array(
			'label'   => __( 'Position', 'navitto' ),
			'section' => 'navitto_design',
			'type'    => 'radio',
			'choices' => array(
				'top'    => __( 'Fixed at top', 'navitto' ),
				'bottom' => __( 'Fixed at bottom', 'navitto' ),
			),
		) );

		// ナビの高さ
		$wp_customize->add_setting( 'navitto_nav_height', array(
			'default'           => 'medium',
			'sanitize_callback' => array( $this, 'sanitize_nav_height' ),
		) );
		$wp_customize->add_control( 'navitto_nav_height', array(
			'label'   => __( 'Nav height', 'navitto' ),
			'section' => 'navitto_design',
			'type'    => 'radio',
			'choices' => array(
				'small'  => __( 'Small', 'navitto' ),
				'medium' => __( 'Medium (default)', 'navitto' ),
				'large'  => __( 'Large', 'navitto' ),
			),
		) );

		// 文字の太さ
		$wp_customize->add_setting( 'navitto_font_weight', array(
			'default'           => 'default',
			'sanitize_callback' => array( $this, 'sanitize_font_weight' ),
		) );
		$wp_customize->add_control( 'navitto_font_weight', array(
			'label'   => __( 'Font weight', 'navitto' ),
			'section' => 'navitto_design',
			'type'    => 'radio',
			'choices' => array(
				'default' => __( 'Default', 'navitto' ),
				'bold'    => __( 'Bold', 'navitto' ),
			),
		) );

		// 背景を透明にする（テーマ準拠時）
		$wp_customize->add_setting( 'navitto_theme_bg_transparent', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
		) );
		$wp_customize->add_control( 'navitto_theme_bg_transparent', array(
			'label'       => __( 'Transparent background', 'navitto' ),
			'description' => __( 'When using the theme preset, make the nav background transparent.', 'navitto' ),
			'section'     => 'navitto_design',
			'type'        => 'checkbox',
		) );

	}

	/* =========================================================================
	   サニタイズ関数
	   ========================================================================= */

	public function sanitize_preset( $value ) {
		$valid = array( 'simple', 'theme' );
		if ( ! in_array( $value, $valid, true ) ) {
			return 'simple';
		}
		return $value;
	}

	public function sanitize_position( $value ) {
		return in_array( $value, array( 'top', 'bottom' ), true ) ? $value : 'top';
	}

	public function sanitize_checkbox( $value ) {
		return (bool) $value;
	}

	public function sanitize_nav_width( $value ) {
		return in_array( $value, array( 'scroll', 'equal' ), true ) ? $value : 'scroll';
	}

	public function sanitize_nav_height( $value ) {
		return in_array( $value, array( 'small', 'medium', 'large' ), true ) ? $value : 'medium';
	}

	public function sanitize_font_weight( $value ) {
		return in_array( $value, array( 'default', 'bold' ), true ) ? $value : 'default';
	}
}

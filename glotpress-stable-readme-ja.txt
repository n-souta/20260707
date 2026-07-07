Navitto Stable Readme — GlotPress 日本語訳マッピング (EN -> JA)
================================================================
用途: https://translate.wordpress.org/projects/wp-plugins/navitto/ (Japanese / readme / Stable)
      に入力・承認するための文案。readme.txt の英語正本（SXO 改訂版）に対応。

入力のコツ:
- GlotPress では HTML タグ（<strong> 等）を原文どおり維持する
- メニュー表記は WP 日本語ロケールに合わせる（設定、外観、プラグイン 等）
- 疑問符・感嘆符は半角とし、直前に半角スペース1つ（例: 〜ますか ?）— 翻訳スタイルガイド 1-2
- 原文の "..." が日本語引用の場合は「」に置き換える — 翻訳スタイルガイド 2-3
- メタボックス等の UI ラベル参照は実際の訳語を「」で囲み、プロジェクト内で統一 — 2-2, 3-7
- 「全て」→「すべて」、「下さい」→「ください」— 翻訳スタイルガイド 3-6
- 入力後、PTE として Approve。反映まで数時間かかることがある

================================================================
Short Description（一行説明）
================================================================

EN: Improve mobile reading flow with a sticky H2 navigation bar that reduces drop-off in long WordPress posts.
JA: 長文記事のスマホ導線を改善し、離脱を減らす H2 追従ナビゲーションプラグインです。

================================================================
Description
================================================================

EN: Navitto adds a fixed navigation bar that follows H2 headings in posts and pages.
JA: Navitto は、投稿や固定ページの H2 見出しに追従する固定ナビゲーションを追加します。

EN: It is built for sites that struggle with mobile readability, scroll fatigue, and drop-off on long articles.
JA: スマホで「記事が読みにくい」「途中で離脱されやすい」と感じるサイト向けに設計しています。

EN: Readers can quickly jump to sections, understand where they are, and continue reading with less friction.
JA: 読者はセクションへ素早く移動でき、現在地を把握しやすく、ストレスなく読み進められます。

EN: If your users say things like:
JA: 次のような悩みがあるサイトに向いています。

EN: * "Long articles are hard to scan on mobile."
JA: * 「スマホで長文記事が読みにくい」

EN: * "People leave before reaching key sections."
JA: * 「重要なセクションまで読まれず離脱される」

EN: * "Theme TOC behavior is inconsistent."
JA: * 「テーマ内蔵の目次表示が安定しない」

EN: Navitto gives you a lightweight, sticky in-page navigation designed for practical content flow improvements.
JA: 軽量で実用的な固定ナビにより、記事内導線の改善を無理なく始められます。

EN: <strong>Features</strong>
JA: <strong>主な機能</strong>

EN: <strong>Fixed navigation bar</strong> - Stays at the top or bottom while scrolling and lists H2 headings.
JA: <strong>固定ナビゲーション</strong> - スクロール中も上部または下部に表示され、H2 見出しの一覧を見ながら移動できます。

EN: <strong>Display modes</strong> - Show all headings, choose specific H2 headings, or hide the nav for each post/page.
JA: <strong>表示モード切り替え</strong> - すべての見出しを表示、任意の見出しだけ表示、投稿ごとに非表示を選択できます。

EN: <strong>Customizer</strong> - Control preset (simple/theme), position, height, and font weight.
JA: <strong>カスタマイザー対応</strong> - プリセット、表示位置、高さ、文字の太さを調整できます。

EN: <strong>Bulk apply</strong> - Enable or disable Navitto on all existing posts from <strong>Settings &gt; Navitto</strong>.
JA: <strong>一括適用</strong> - <strong>設定 &gt; Navitto</strong> から既存投稿への有効化・無効化をまとめて行えます。

EN: <strong>Theme-aware offset</strong> - Attempts to avoid headings being hidden under fixed headers.
JA: <strong>テーマに配慮したオフセット調整</strong> - 固定ヘッダーがあるテーマでも、見出しが隠れにくいよう補正します。

EN: <strong>Posts and pages</strong> - Works on both content types.
JA: <strong>投稿・固定ページ対応</strong> - 一般的なコンテンツ記事や固定ページで利用できます。

EN: <strong>Source code and support</strong>
JA: <strong>ソースコードとサポート</strong>

EN: * Repository: https://github.com/n-souta/navitto
JA: * リポジトリ: https://github.com/n-souta/navitto

EN: * Issues: https://github.com/n-souta/navitto/issues
JA: * 課題・質問: https://github.com/n-souta/navitto/issues

================================================================
Installation
================================================================

EN: Upload the plugin ZIP or search for <strong>Navitto</strong> in <strong>Plugins &gt; Add New</strong>.
JA: プラグイン ZIP をアップロードするか、<strong>プラグイン &gt; 新規追加</strong> で <strong>Navitto</strong> を検索します。

EN: 2. Activate the plugin.
JA: 2. プラグインを有効化します。

EN: Edit a post or page and use the <strong>Navitto</strong> meta box in the sidebar.
JA: 投稿または固定ページの編集画面を開き、サイドバーの <strong>Navitto</strong> メタボックスを設定します。

EN: (Optional) Open <strong>Appearance &gt; Customize &gt; Navitto</strong> for global design settings.
JA: （任意）<strong>外観 &gt; カスタマイズ &gt; Navitto</strong> で全体デザインを調整します。

EN: (Optional) Open <strong>Settings &gt; Navitto</strong> for default behavior and bulk apply.
JA: （任意）<strong>設定 &gt; Navitto</strong> でデフォルト動作や一括適用を設定します。

================================================================
FAQ
================================================================

EN: = The fixed nav does not appear =
JA: = 固定ナビが表示されません =

EN: * Ensure the post/page is not set to hide the nav in the Navitto meta box.
JA: * Navitto メタボックスで、投稿/固定ページの表示モードが「固定ナビを非表示」になっていないか確認してください。

EN: * Navitto appears only when the content has at least two H2 headings.
JA: * Navitto は本文に H2 見出しが 2 つ以上ある場合にのみ表示されます。

EN: * On themes with custom TOC behavior, Navitto can fall back to direct H2 detection.
JA: * テーマ内蔵の目次挙動が不安定な場合、H2 の直接検出にフォールバックできます。

EN: = Is Navitto useful for mobile UX and reducing article drop-off? =
JA: = 長文記事やスマホ向けの導線改善に向いていますか ? =

EN: Yes. Navitto keeps section navigation visible while users scroll, so long-form posts are easier to scan on smartphones.
JA: はい。スクロール中もセクション移動をしやすくすることで、スマホでの読みにくさを減らします。

EN: This helps readers reach deeper sections instead of abandoning the page early.
JA: 記事の深い位置まで読まれやすくなり、途中離脱の抑制に役立ちます。

EN: = How do I choose which headings are shown? =
JA: = 表示する見出しを選ぶには ? =

EN: Select <strong>Choose headings to display</strong> in the Navitto meta box, then check the H2 headings you want.
JA: Navitto メタボックスで <strong>表示する見出しを選択</strong> を選び、表示したい H2 見出しにチェックを入れてください。

EN: You can also override each heading label and set when the nav starts appearing.
JA: 見出しラベルの上書きや、ナビの表示開始位置も投稿ごとに調整できます。

EN: = Does Navitto work when my theme already has a table of contents? =
JA: = テーマ内蔵の目次がある場合でも使えますか ? =

EN: Yes. When Navitto can read the theme TOC structure, it uses that source first.
JA: はい。テーマの目次構造を参照できる場合はそれを利用します。

EN: If the theme TOC has too few links, Navitto falls back to direct H2 detection.
JA: リンク数が不足する場合などは H2 の直接検出にフォールバックします。

EN: It is designed with themes such as SWELL in mind for heading detection and placement.
JA: 特に SWELL のようなテーマでも、見出し検出と表示位置を考慮して動作します。

EN: = Can I insert the nav inside the theme header? =
JA: = テーマのヘッダー内にナビを挿入できますか ? =

EN: If your theme supports the <code>navitto_fixed_nav_inside_header</code> filter and outputs Navitto inside the header area, it can render there.
JA: テーマが <code>navitto_fixed_nav_inside_header</code> フィルターをサポートし、ヘッダー領域内で Navitto を出力している場合は、そこに表示できます。

EN: Please refer to your theme's documentation.
JA: 詳細はご利用のテーマのドキュメントを参照してください。

EN: = Which content types does Navitto support? =
JA: = どのコンテンツタイプに対応していますか ? =

EN: Navitto works on both posts and pages.
JA: 投稿・固定ページの両方に対応しています。

EN: You can apply defaults globally and override behavior per post/page from the Navitto meta box.
JA: デフォルト設定を全体に適用しつつ、各投稿・各固定ページで個別に上書きできます。

EN: = How can I contribute Japanese translations for WordPress.org? =
JA: = WordPress.org 向けの日本語翻訳に協力するには ? =

EN: Navitto uses the WordPress.org translation platform.
JA: Navitto は WordPress.org の翻訳プラットフォームを利用しています。

EN: You can submit Japanese translations at:
JA: 日本語翻訳の提案は次のページから行えます。

EN: https://translate.wordpress.org/projects/wp-plugins/navitto/
JA: https://translate.wordpress.org/projects/wp-plugins/navitto/

================================================================
Screenshots
================================================================

EN: 1. Navitto meta box in the editor sidebar (display mode, H2 selection, and trigger settings).
JA: 1. 投稿編集画面の Navitto メタボックス（表示モード、H2 選択、表示開始設定）

EN: 2. Front-end fixed navigation example while scrolling a post.
JA: 2. フロント側の固定ナビ表示例（長文記事スクロール時）

EN: 3. Appearance &gt; Customize &gt; Navitto settings (preset, position, height, and typography).
JA: 外観 &gt; カスタマイズ &gt; Navitto でプリセット、配置位置、高さ、文字スタイルを設定する画面です。

EN: 4. Settings &gt; Navitto screen for default behavior and bulk apply.
JA: 設定 &gt; Navitto で既定の動作と一括適用を設定する画面です。

================================================================
Changelog / Upgrade Notice（必要に応じて）
================================================================

EN: Fixes SWELL header placement and improves H2 heading detection.
JA: SWELL でのヘッダー内配置を修正し、H2 見出し検出を改善しました。

EN: Bundled ja translation files for Settings &gt; Navitto when the site language is Japanese.
JA: サイト言語が日本語の場合、設定 &gt; Navitto 向けの日本語翻訳ファイルを同梱しました。

EN: Readme-only update. No code changes.
JA: readme のみの更新です。コード変更はありません。

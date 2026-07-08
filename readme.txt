=== Navitto ===

Contributors: nsouta
Tags: table of contents, navigation, sticky, toc, scroll
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Improve mobile reading flow with a sticky H2 navigation bar that reduces drop-off in long WordPress posts.

== Description ==

Navitto adds a fixed navigation bar that follows H2 headings in posts and pages.
It is built for sites that struggle with mobile readability, scroll fatigue, and drop-off on long articles.
Readers can quickly jump to sections, understand where they are, and continue reading with less friction.

If your users say things like:

* "Long articles are hard to scan on mobile."
* "People leave before reaching key sections."
* "Theme TOC behavior is inconsistent."

Navitto gives you a lightweight, sticky in-page navigation designed for practical content flow improvements.

**Features**

* **Fixed navigation bar** - Stays at the top or bottom while scrolling and lists H2 headings.
* **Display modes** - Show all headings, choose specific H2 headings, or hide the nav for each post/page.
* **Customizer** - Control preset (simple/theme), position, height, and font weight.
* **Bulk apply** - Enable or disable Navitto on all existing posts from **Settings > Navitto**.
* **Theme-aware offset** - Attempts to avoid headings being hidden under fixed headers.
* **Posts and pages** - Works on both content types.

**Source code and support**

* Repository: https://github.com/n-souta/navitto
* Issues: https://github.com/n-souta/navitto/issues

== Installation ==

1. Upload the plugin ZIP or search for **Navitto** in **Plugins > Add New**.
2. Activate the plugin.
3. Edit a post or page and use the **Navitto** meta box in the sidebar.
4. (Optional) Open **Appearance > Customize > Navitto** for global design settings.
5. (Optional) Open **Settings > Navitto** for default behavior and bulk apply.

== Frequently Asked Questions ==

= The fixed nav does not appear =

* Ensure the post/page is not set to hide the nav in the Navitto meta box.
* Navitto appears only when the content has at least two H2 headings.
* On themes with custom TOC behavior, Navitto can fall back to direct H2 detection.

= Is Navitto useful for mobile UX and reducing article drop-off? =

Yes. Navitto keeps section navigation visible while users scroll, so long-form posts are easier to scan on smartphones.
This helps readers reach deeper sections instead of abandoning the page early.

= How do I choose which headings are shown? =

Select **Choose headings to display** in the Navitto meta box, then check the H2 headings you want.
You can also override each heading label and set when the nav starts appearing.

= Does Navitto work when my theme already has a table of contents? =

Yes. When Navitto can read the theme TOC structure, it uses that source first.
If the theme TOC has too few links, Navitto falls back to direct H2 detection.
It is designed with themes such as SWELL in mind for heading detection and placement.

= Can I insert the nav inside the theme header? =

If your theme supports the `navitto_fixed_nav_inside_header` filter and outputs Navitto inside the header area, it can render there.
Please refer to your theme's documentation.

= Which content types does Navitto support? =

Navitto works on both posts and pages.
You can apply defaults globally and override behavior per post/page from the Navitto meta box.

= How can I contribute Japanese translations for WordPress.org? =

Navitto uses the WordPress.org translation platform.
You can submit Japanese translations at:
https://translate.wordpress.org/projects/wp-plugins/navitto/

== Screenshots ==

1. Navitto meta box in the editor sidebar (display mode, H2 selection, and trigger settings).
2. Front-end fixed navigation example while scrolling a post.
3. Appearance > Customize > Navitto settings (preset, position, height, and typography).
4. Settings > Navitto screen for default behavior and bulk apply.

== Changelog ==

= 1.0.5 = (2026-07-07)
* Standardize translatable strings to English msgids (admin metabox and customizer).
* Add Domain Path header for WordPress.org language pack compatibility.
* Update bundled Japanese translation files (languages/navitto-ja).

= 1.0.4 = (2026-05-20)
* Fix fixed nav placement on SWELL (restore header inside insertion; fixes nav not appearing correctly).
* Improve heading detection when theme TOC has too few links (fallback to H2).
* SWELL: prefer H2 detection order; support block headings with level 2.

= 1.0.3 = (2026-05-10)
* Bundled Japanese translations for the settings screen (languages/navitto-ja).

= 1.0.2 = (2026-05-01)
* Improve H2 detection when multiple content containers exist (e.g. SWELL).
* Hide the fixed nav after passing the last tracked heading when there is no following H2.

= 1.0.1 = (2026-03-29)
* Updated readme: trimmed description and simplified changelog for the plugin directory page.

= 1.0.0 = (2026-03-29)
* Initial release on the WordPress.org Plugin Directory.

== Upgrade Notice ==

= 1.0.5 =
Admin and customizer strings now use English msgids for GlotPress. Bundled ja translations cover the full admin UI.

= 1.0.4 =
Fixes SWELL header placement and improves H2 heading detection.

= 1.0.3 =
Bundled ja translation files for Settings > Navitto when the site language is Japanese.

= 1.0.2 =
Frontend fixes for SWELL heading detection and hiding the nav after the last section.

= 1.0.1 =
Readme-only update. No code changes.

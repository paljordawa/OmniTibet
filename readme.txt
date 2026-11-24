=== TibetPress ===
Tags: tibetan, typography, multilingual, language switcher, fonts
Requires at least: 6.0
Requires PHP: 8.0
Tested up to: 7.1
Stable tag: 1.2.10
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

TibetPress provides Tibetan typography and language tools for multilingual WordPress sites.

== Description ==

TibetPress helps you publish Tibetan content, manage language versions of posts and pages, and customize typography across your site.

The plugin provides language-management tools; it does not automatically translate content. You write or edit each language version and link it to the corresponding post, page, category, or tag.

= Features =

* **Tibetan typography:** Choose from 36 Tibetan font options, including Monlam Bodtig, Qomolangma, Myilam, and web font families.
* **Element-specific styling:** Configure font families and line heights for page elements such as body text, headings, navigation, quotes, and the site title.
* **Post and page translations:** Assign languages, connect existing translations, create blank translation drafts, or duplicate content as a starting point.
* **Dashboard language filtering:** Post and page lists default to the configured base language; use the language filter to view another language or all languages.
* **Admin toolbar language switcher:** Switch language from the dashboard or site-view toolbar using language flags. In the dashboard, existing translations open in the editor; missing translations lead to that language’s filtered list.
* **Translation overview:** See published, unpublished, and missing post/page translations from the settings screen.
* **Translation queue and health checks:** Filter missing, draft, unpublished, and outdated translations; detect unlinked translations, duplicate languages, and groups without a base-language post.
* **Category and tag translations:** Assign term languages, mark terms as shared, link equivalent terms, and manage translations from per-language list columns.
* **Language-specific menus:** Assign a separate WordPress menu to each theme location and language, with a base-menu fallback.
* **Theme/plugin strings:** Register stable string IDs and manage their language values in **Settings → TibetPress Settings → Multilingual Tools**.
* **Multilingual SEO and RTL:** Output `hreflang` alternates for published translations, support translated SEO titles/descriptions, and apply language-specific text direction and locale formatting.
* **Language-aware content:** Show posts and taxonomy terms for the active language. Content without a language assignment is treated as the configured base language.
* **Language switcher:** Choose dropdown, toggle, radio, list, or inline layouts, with flag and text display options.
* **Language controls:** English and Tibetan are built in. Enable optional languages, including Chinese, in settings, and hide selected languages from the frontend switcher without deleting translation data.
* **Language URL formats:** Choose language directories (such as `/bo/`), a `?lang=bo` query parameter, or language-code subdomains. The base language stays on the main domain.
* **Extended Wylie input:** Convert Wylie keystrokes to Tibetan Unicode in the post editor. Press **Alt+W** to toggle the input engine.
* **Syllable wrapping:** Optionally add zero-width break opportunities after Tibetan tsheg and shad characters.

== Installation ==

1. Upload the `tibetpress` folder to `/wp-content/plugins/`, or install the plugin ZIP through the WordPress Plugins screen.
2. Activate the plugin from **Plugins**.
3. Open **Settings → TibetPress Settings** to choose the base language, enable optional languages, and configure typography and language-switcher options.
4. Choose a language URL format under **Settings → TibetPress Settings → Multilingual & Routing**.
5. Assign languages and connect translations from the post, page, category, and tag editing screens.

Language-subdomain URLs require DNS and hosting configured to direct each language-code subdomain to this WordPress site, plus HTTPS certificates that cover those subdomains.

== Usage ==

Add the language switcher to a page or post with:

`[tibetan_lang_switcher]`

The shortcode accepts `format`, `display`, and `flag_size` options. For example:

`[tibetan_lang_switcher format="inline" display="both" flag_size="medium"]`

Available formats are `dropdown`, `toggle`, `radio`, `list`, and `inline`. Display modes are `both`, `icon_only`, and `text_only`. Flag sizes are `small`, `medium`, and `large`.

Theme developers can also call `tibetan_language_switcher()` from a theme template.

= Translation tools =

Open **Settings → TibetPress Settings → Multilingual Tools** to review the translation queue, inspect translation-group health, assign language-specific menus to theme locations, and edit registered string translations. The Multilingual Tools section is part of the TibetPress Settings page. Published translations are marked outdated when their source title, content, excerpt, or modified time has changed since the translation was last saved. Existing translations without a review snapshot are flagged for review the first time.

Theme and plugin developers can register UI strings by stable ID and retrieve the active language value:

`tmt_register_string( 'theme_hero_title', 'Welcome', 'my-theme' );`

`echo esc_html( tmt_translate_string( 'theme_hero_title', 'Welcome' ) );`

Registration is explicit; the plugin does not automatically scan rendered pages. Unassigned language menus fall back to the base-language menu.

= Language URL formats =

Choose **Language directories**, **Language query parameter**, or **Language subdomains** in the Multilingual & Routing settings. Directory URLs include a language code in the path (for example, `/bo/about/`); parameter URLs add `?lang=bo`; subdomains use the language code as a subdomain (for example, `bo.example.com`). The configured base language uses the main domain without a language prefix.

Subdomain URLs require matching DNS records, web-server routing to the WordPress installation, and HTTPS coverage for each language subdomain. With plain permalinks, directory mode uses the query-parameter format because WordPress does not have path-based permalinks enabled.

== Frequently Asked Questions ==

= Does the plugin translate my content automatically? =

No. It helps you create and link language versions. You provide the translated text.

= How do I add another language? =

Go to **Settings → TibetPress Settings → Multilingual & Routing** and enable the language. English and Tibetan are built in; other available languages are optional.

= How do I translate a category or tag? =

Open **Posts → Categories** or **Posts → Tags**. Use the per-language columns to start a translated term, or edit a term to assign its language and link it to an existing equivalent. Terms marked as shared are reused in every language.

= What language is used for posts without a language assignment? =

They are treated as the configured base language. The base language can be selected in **Settings → TibetPress Settings → Multilingual & Routing**.

= Do disabled optional languages delete existing translations? =

No. Disabling an optional language removes it from active language tools and switchers, but existing post and term translation metadata is preserved.

= What should I do if a Tibetan URL returns a 404? =

Confirm that the translated post, page, or term is published and assigned to Tibetan. If using language subdomains, check that DNS, hosting, and HTTPS are configured for that subdomain. If a directory URL still returns a 404, visit **Settings → Permalinks** and save the settings to refresh rewrite rules.

== Changelog ==

= 1.2.10 =
* Added a translation icon beside TibetPress Settings in the WordPress dashboard Settings menu.

= 1.2.9 =
* Renamed the plugin to TibetPress in WordPress and user-facing settings.

= 1.2.8 =
* Combined Tibetan Settings and Multilingual Tools under one Settings menu entry.
* Preserved old Multilingual Tools links by redirecting them to the new section.

= 1.2.7 =
* Added a flag-based language switcher to the front-end WordPress toolbar for logged-in editors.
* Front-end toolbar language choices open the translated post when available, or that language’s home page.

= 1.2.6 =
* Fixed a missing current-language record that caused warnings in the dropdown switcher.

= 1.2.5 =
* Added translation status tracking, an editorial queue, and translation-group health checks.
* Added language-specific menu assignments, a registered-string translation API, SEO metadata, `hreflang`, and RTL direction handling.

= 1.2.4 =
* Added a dashboard toolbar language switcher that opens linked translations or the selected language’s filtered list.

= 1.2.3 =
* Post and page lists in the dashboard now default to the configured base language, with options to view another language or all languages.

= 1.2.2 =
* Added configurable language directory, query-parameter, and language-subdomain URL formats.
* Refreshes language rewrite rules after settings changes and plugin updates.

= 1.2.1 =
* Documented the current typography, multilingual content, taxonomy, Wylie input, and Tibetan syllable-wrapping features.

== Upgrade Notice ==

= 1.2.10 =
Adds a translation icon beside TibetPress Settings in the dashboard menu.

= 1.2.9 =
The plugin is now named TibetPress. Existing settings and plugin identifiers are unchanged.

= 1.2.8 =
Tibetan Settings and Multilingual Tools now appear under one Settings menu entry.

= 1.2.7 =
Adds a flag-based language switcher to the front-end toolbar for logged-in editors.

= 1.2.6 =
Fixes the undefined current-language warning in the dropdown language switcher.

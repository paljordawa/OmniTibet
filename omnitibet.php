<?php
/**
 * Plugin Name:       OmniTibet
 * Plugin URI:        https://github.com/paljordawa/OmniTibet.git
 * Description:       A complete, production-ready WordPress multilingual and typography customizer for the Tibetan language (Bhod-yig). Resolves stacked character line clipping, mobile syllable wrapping via zero-width spaces, multilingual translation mapping with WPML-style duplicate options, 36 Tibetan font choices across Monlam Bodtig, Qomolangma, and Myilam categories, granular HTML element typography, customizable language switchers (dropdown, toggle, radio, list, inline with icon/text modes), tabbed admin settings, and real-time backend Extended Wylie transliteration input.
 * Version:           1.2.12
 * Author:            Dawa Paljor
 * Author URI:        https://github.com/paljordawa
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       omnitibet
 * Requires at least: 6.0
 * Requires PHP:      8.0
 *
 * @package           OmniTibet
 */

defined( 'ABSPATH' ) || exit;

// Define Plugin Constants
define( 'OMNITI_VERSION', '1.2.12' );
define( 'OMNITI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OMNITI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OMNITI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'OMNITI_FILE', __FILE__ );
define( 'OMNITI_REWRITE_VERSION', 2 );

require_once OMNITI_PLUGIN_DIR . 'includes/traits/trait-omniti-language-routing.php';
require_once OMNITI_PLUGIN_DIR . 'includes/traits/trait-omniti-post-and-term-translations.php';
require_once OMNITI_PLUGIN_DIR . 'includes/traits/trait-omniti-language-switcher.php';
require_once OMNITI_PLUGIN_DIR . 'includes/traits/trait-omniti-typography.php';
require_once OMNITI_PLUGIN_DIR . 'includes/traits/trait-omniti-admin-settings.php';
require_once OMNITI_PLUGIN_DIR . 'includes/traits/trait-omniti-syllable-wrap.php';
require_once OMNITI_PLUGIN_DIR . 'includes/traits/trait-omniti-translation-tools.php';

/**
 * Main Orchestrator Class
 */
final class OmniTibet {
	use OMNITI_Language_Routing_Trait;
	use OMNITI_Translation_Admin_Trait;
	use OMNITI_Language_Switcher_Trait;
	use OMNITI_Typography_Trait;
	use OMNITI_Admin_Settings_Trait;
	use OMNITI_Syllable_Wrap_Trait;
	use OMNITI_Translation_Tools_Trait;


	/**
	 * Singleton Instance
	 *
	 * @var OmniTibet|null
	 */
	private static ?OmniTibet $instance = null;

	/**
	 * Cached Plugin Settings
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $settings = null;

	/**
	 * Active Detected Language
	 *
	 * @var string|null
	 */
	private ?string $current_language = null;

	/**
	 * Re-entrancy guard for locale filters
	 *
	 * @var bool
	 */
	private bool $is_filtering_locale = false;

	/**
	 * Re-entrancy guard for language resolution
	 *
	 * @var bool
	 */
	private bool $is_resolving_lang = false;

	/**
	 * Supported Languages Definition
	 *
	 * @var array<string, array<string, string>>
	 */
	private array $supported_languages = [
		'en' => [
			'code'   => 'en',
			'name'   => 'English',
			'native' => 'English',
			'locale' => 'en_US',
			'dir'    => 'ltr',
		],
		'bo' => [
			'code'   => 'bo',
			'name'   => 'Tibetan',
			'native' => 'བོད་སྐད',
			'locale' => 'bo',
			'dir'    => 'ltr',
		],
	];

	/**
	 * Cached enabled language definitions.
	 *
	 * @var array<string, array<string, string>>|null
	 */
	private ?array $enabled_languages = null;

	/**
	 * Registered translatable strings discovered during this request.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private ?array $registered_translation_strings = null;

	private bool $registered_translation_strings_dirty = false;

	/**
	 * Rewrite patterns registered by this plugin during the current request.
	 *
	 * @var array<int, string>
	 */
	private array $language_rewrite_patterns = [];

	/**
	 * Predefined optional languages that can be enabled in settings.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_available_languages(): array {
		return [
			'zh' => [ 'code' => 'zh', 'name' => 'Chinese (Simplified)', 'native' => '中文', 'locale' => 'zh_CN', 'dir' => 'ltr' ],
			'es' => [ 'code' => 'es', 'name' => 'Spanish', 'native' => 'Español', 'locale' => 'es_ES', 'dir' => 'ltr' ],
			'fr' => [ 'code' => 'fr', 'name' => 'French', 'native' => 'Français', 'locale' => 'fr_FR', 'dir' => 'ltr' ],
			'de' => [ 'code' => 'de', 'name' => 'German', 'native' => 'Deutsch', 'locale' => 'de_DE', 'dir' => 'ltr' ],
			'it' => [ 'code' => 'it', 'name' => 'Italian', 'native' => 'Italiano', 'locale' => 'it_IT', 'dir' => 'ltr' ],
			'pt' => [ 'code' => 'pt', 'name' => 'Portuguese', 'native' => 'Português', 'locale' => 'pt_PT', 'dir' => 'ltr' ],
			'ru' => [ 'code' => 'ru', 'name' => 'Russian', 'native' => 'Русский', 'locale' => 'ru_RU', 'dir' => 'ltr' ],
			'ja' => [ 'code' => 'ja', 'name' => 'Japanese', 'native' => '日本語', 'locale' => 'ja', 'dir' => 'ltr' ],
			'ko' => [ 'code' => 'ko', 'name' => 'Korean', 'native' => '한국어', 'locale' => 'ko_KR', 'dir' => 'ltr' ],
			'hi' => [ 'code' => 'hi', 'name' => 'Hindi', 'native' => 'हिन्दी', 'locale' => 'hi_IN', 'dir' => 'ltr' ],
			'ar' => [ 'code' => 'ar', 'name' => 'Arabic', 'native' => 'العربية', 'locale' => 'ar', 'dir' => 'rtl' ],
			'ne' => [ 'code' => 'ne', 'name' => 'Nepali', 'native' => 'नेपाली', 'locale' => 'ne_NP', 'dir' => 'ltr' ],
		];
	}

	/**
	 * Get core languages plus the optional languages enabled in settings.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_enabled_languages(): array {
		if ( null !== $this->enabled_languages ) {
			return $this->enabled_languages;
		}

		$languages = $this->supported_languages;
		$catalog   = $this->get_available_languages();
		$settings  = get_option( 'omniti_settings', [] );
		$enabled   = is_array( $settings ) && is_array( $settings['additional_languages'] ?? null )
			? $settings['additional_languages']
			: [];

		foreach ( $enabled as $code ) {
			if ( ! is_string( $code ) ) {
				continue;
			}
			if ( isset( $catalog[ $code ] ) ) {
				$languages[ $code ] = $catalog[ $code ];
			}
		}

		$this->enabled_languages = $languages;
		return $languages;
	}

	/**
	 * Get the configured base language, falling back to English if invalid.
	 */
	private function get_base_language(): string {
		$settings = get_option( 'omniti_settings', [] );
		$base     = is_array( $settings ) && is_string( $settings['base_language'] ?? null )
			? $settings['base_language']
			: 'en';

		return isset( $this->get_enabled_languages()[ $base ] ) ? $base : 'en';
	}

	/**
	 * Get Singleton Instance
	 *
	 * @return OmniTibet
	 */
	public static function get_instance(): OmniTibet {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress Action & Filter Hooks
	 */
	private function init_hooks(): void {
		// Module A: Routing & Multilingual
		add_action( 'init', [ $this, 'register_rewrite_rules' ] );
		add_action( 'init', [ $this, 'maybe_refresh_language_rewrite_rules' ], 100 );
		add_action( 'init', [ $this, 'register_taxonomy_translation_hooks' ], 99 );
		add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
		add_filter( 'request', [ $this, 'resolve_tibetan_content_path' ] );
		add_filter( 'determine_locale', [ $this, 'filter_determine_locale' ], 1 );
		add_filter( 'locale', [ $this, 'filter_locale' ], 1 );
		add_filter( 'body_class', [ $this, 'add_body_classes' ] );
		add_filter( 'post_link', [ $this, 'filter_post_link' ], 10, 2 );
		add_filter( 'page_link', [ $this, 'filter_page_link' ], 10, 2 );
		add_filter( 'post_type_link', [ $this, 'filter_post_link' ], 10, 2 );
		add_filter( 'term_link', [ $this, 'filter_tibetan_term_link' ], 10, 3 );
		add_filter( 'get_terms_args', [ $this, 'filter_terms_by_language' ], 10, 2 );
		add_filter( 'redirect_canonical', [ $this, 'preserve_language_subdomain_redirect' ], 10, 2 );
		add_action( 'update_option_omniti_settings', [ $this, 'refresh_language_rewrite_rules' ], 10, 3 );

		// Translation Meta Box (Edit Screen - Image 2)
		add_action( 'add_meta_boxes', [ $this, 'register_translation_metabox' ] );
		add_action( 'save_post', [ $this, 'save_translation_metadata' ], 10, 2 );

		// Admin List Columns (Posts & Pages Tables - Image 1)
		add_action( 'admin_init', [ $this, 'register_admin_list_columns' ] );
		add_action( 'restrict_manage_posts', [ $this, 'render_language_filter_dropdown' ] );
		add_action( 'pre_get_posts', [ $this, 'filter_posts_by_language' ] );
		add_action( 'admin_bar_menu', [ $this, 'add_admin_language_switcher' ], 100 );
		add_filter( 'post_row_actions', [ $this, 'add_post_row_actions' ], 10, 2 );
		add_filter( 'page_row_actions', [ $this, 'add_post_row_actions' ], 10, 2 );

		// Admin Action Handlers for Translation & Duplication
		add_action( 'admin_action_omniti_action', [ $this, 'handle_admin_actions' ] );
		add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );

		// Shortcodes & Language Switcher
		add_shortcode( 'omniti_lang_switcher', [ $this, 'render_language_switcher_shortcode' ] );

		// Module B: Dynamic Typography Engine
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
		add_action( 'wp_head', [ $this, 'inject_dynamic_typography_css' ], 99 );

		// Module C: Admin Dashboard & Settings
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'admin_head', [ $this, 'inject_admin_preview_font_faces' ] );

		// Module D: Syllable Wrap Engine (zero-width spaces)
		add_filter( 'the_content', [ $this, 'filter_tibetan_syllable_wrap' ], 20 );
		add_filter( 'the_excerpt', [ $this, 'filter_tibetan_syllable_wrap' ], 20 );

		$this->register_translation_tool_hooks();
	}

	/* ==========================================================================
	   FLAG ICONS & VISUAL ASSETS
	   ========================================================================== */

	/**
	 * Retrieve Flag Image HTML for Language
	 *
	 * Uses the user-provided Tibetan flag image for 'bo'.
	 *
	 * @param string $lang_code ISO code ('bo', 'zh', 'en').
	 * @param int    $width     Flag width in pixels.
	 * @param int    $height    Flag height in pixels.
	 * @return string HTML img string.
	 */
	public function get_flag_html( string $lang_code, int $width = 20, int $height = 14 ): string {
		$plugin_url = OMNITI_PLUGIN_URL;
		return match ( $lang_code ) {
			'bo' => sprintf(
				'<img src="%s" alt="%s" width="%d" height="%d" class="omniti-flag omniti-flag-bo" style="vertical-align:middle;border-radius:2px;box-shadow:0 0 1px rgba(0,0,0,0.4);object-fit:cover;display:inline-block;" />',
				esc_url( $plugin_url . 'assets/images/tibetan-flag.svg' ),
				esc_attr__( 'Tibetan (བོད་སྐད)', 'omnitibet' ),
				$width,
				$height
			),
			'zh' => sprintf(
				'<img src="%s" alt="%s" width="%d" height="%d" class="omniti-flag omniti-flag-zh" style="vertical-align:middle;border-radius:2px;box-shadow:0 0 1px rgba(0,0,0,0.4);display:inline-block;" />',
				esc_url( $plugin_url . 'assets/images/chinese-flag.svg' ),
				esc_attr__( 'Chinese (Simplified)', 'omnitibet' ),
				$width,
				$height
			),
			'en' => sprintf(
				'<img src="%s" alt="%s" width="%d" height="%d" class="omniti-flag omniti-flag-en" style="vertical-align:middle;border-radius:2px;box-shadow:0 0 1px rgba(0,0,0,0.4);display:inline-block;" />',
				esc_url( $plugin_url . 'assets/images/english-flag.svg' ),
				esc_attr__( 'English', 'omnitibet' ),
				$width,
				$height
			),
			default => sprintf(
				'<span class="omniti-flag omniti-flag-code" role="img" aria-label="%s" style="display:inline-flex;align-items:center;justify-content:center;width:%dpx;height:%dpx;font-size:9px;font-weight:700;line-height:1;background:#e2e8f0;color:#334155;border-radius:2px;">%s</span>',
				esc_attr( strtoupper( $lang_code ) ),
				$width,
				$height,
				esc_html( strtoupper( $lang_code ) )
			),
		};
	}
}

/**
 * Global Template Tag for Theme Developers
 *
 * @param array<string, mixed> $args Arguments ('format' => 'dropdown'|'toggle').
 */
function omniti_language_switcher( array $args = [] ): void {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo OmniTibet::get_instance()->render_language_switcher_shortcode( $args );
}

function omniti_get_current_language(): string {
	return OmniTibet::get_instance()->get_current_language();
}

function omniti_get_translation( int $post_id, string $target_lang = 'bo' ): ?int {
	return OmniTibet::get_instance()->get_translation( $post_id, $target_lang );
}

/**
 * Move settings and metadata from the legacy prefixed names to OmniTibet names.
 */
function omniti_migrate_legacy_plugin_data(): void {
	$option_map = [
		'tmt_settings'            => 'omniti_settings',
		'tmt_rewrite_version'     => 'omniti_rewrite_version',
		'tmt_registered_strings'  => 'omniti_registered_strings',
		'tmt_language_menus'      => 'omniti_language_menus',
	];

	foreach ( $option_map as $legacy_name => $new_name ) {
		$legacy_value = get_option( $legacy_name, null );
		if ( null !== $legacy_value && false === get_option( $new_name, false ) ) {
			update_option( $new_name, $legacy_value );
		}
	}

	global $wpdb;
	$meta_map = [
		'_tb_lang_code'      => '_omniti_lang_code',
		'_tb_lang_group_id'  => '_omniti_lang_group_id',
	];

	foreach ( $meta_map as $legacy_name => $new_name ) {
		// Copy only missing values so the new names remain authoritative.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
				 SELECT legacy.post_id, %s, legacy.meta_value
				 FROM {$wpdb->postmeta} legacy
				 WHERE legacy.meta_key = %s
				   AND NOT EXISTS (
					 SELECT 1 FROM {$wpdb->postmeta} current_meta
					 WHERE current_meta.post_id = legacy.post_id
					   AND current_meta.meta_key = %s
				   )",
				$new_name,
				$legacy_name,
				$new_name
			)
		);
	}

	delete_transient( 'tmt_translation_groups' );
}
add_action( 'plugins_loaded', 'omniti_migrate_legacy_plugin_data', 1 );

function omniti_activate_plugin(): void {
	omniti_migrate_legacy_plugin_data();
	$elem_defs = OmniTibet::get_element_defaults();

	$defaults = [
		'font_family'               => 'monlam-bodtig-2',
		'line_height'               => 2.3,
		'enable_element_typography' => 1,
		'base_language'             => 'en',
		'fallback_language'         => 'en',
		'language_url_format'       => 'directory',
		'additional_languages'      => [],
		'hidden_languages'          => [],
		'wylie_enabled'             => 1,
		'syllable_wrap'             => 1,
		'switcher_format'           => 'dropdown',
		'switcher_display'          => 'both',
		'switcher_flag_size'        => 'small',
		'active_tab'                => 'tab-elements',
	];

	foreach ( $elem_defs as $elem_key => $elem_info ) {
		$defaults[ "font_{$elem_key}" ] = $elem_info['default_font'];
		$defaults[ "lh_{$elem_key}" ]   = $elem_info['default_lh'];
	}

	if ( false === get_option( 'omniti_settings' ) ) {
		add_option( 'omniti_settings', $defaults );
	}

	OmniTibet::get_instance()->register_rewrite_rules();
	flush_rewrite_rules();
	update_option( 'omniti_rewrite_version', OMNITI_REWRITE_VERSION );
}
register_activation_hook( OMNITI_FILE, 'omniti_activate_plugin' );

function omniti_deactivate_plugin(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( OMNITI_FILE, 'omniti_deactivate_plugin' );

// Bootstrap the Plugin
/**
 * Register a stable string ID for manual translation in Multilingual Tools.
 */
function omniti_register_string( string $string_id, string $source, string $domain = 'default' ): void {
	OmniTibet::get_instance()->register_translation_string( $string_id, $source, $domain );
}

/**
 * Return the registered string translated for the active language.
 */
function omniti_translate_string( string $string_id, string $fallback, ?string $language = null ): string {
	return OmniTibet::get_instance()->translate_registered_string( $string_id, $fallback, $language );
}

OmniTibet::get_instance();

<?php
/**
 * OMNITI Admin Settings Trait methods.
 *
 * @package OmniTibet
 */

defined( 'ABSPATH' ) || exit;

trait OMNITI_Admin_Settings_Trait {
	/* ==========================================================================
	   MODULE C: Backend Administration Dashboard & Customizer
	   ========================================================================== */

	/**
	 * Lightweight Element Defaults (No translation calls to prevent early locale recursion)
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_element_defaults(): array {
		return [
			'body'       => [ 'default_font' => 'monlam-bodtig-2', 'default_lh' => 2.3 ],
			'h1_h2'      => [ 'default_font' => 'qomolang-title',   'default_lh' => 2.1 ],
			'h3_h6'      => [ 'default_font' => 'monlam-bodtig-3', 'default_lh' => 2.2 ],
			'nav'        => [ 'default_font' => 'myilam-boldface', 'default_lh' => 2.0 ],
			'quotes'     => [ 'default_font' => 'qomolang-drutsa',  'default_lh' => 2.4 ],
			'site_title' => [ 'default_font' => 'myilam-pearl-necklace', 'default_lh' => 2.0 ],
		];
	}

	/**
	 * Retrieve Plugin Settings Merged with Defaults
	 *
	 * @return array<string, mixed>
	 */
	public function get_settings(): array {
		if ( null !== $this->settings ) {
			return $this->settings;
		}

		$elem_defs = self::get_element_defaults();

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

		$saved          = get_option( 'omniti_settings', [] );
		$this->settings = wp_parse_args( is_array( $saved ) ? $saved : [], $defaults );
		return $this->settings;
	}

	/**
	 * Summarize translation coverage for published base-language posts and pages.
	 *
	 * @param string $base_language Base language code.
	 * @return array<string, mixed>|WP_Error
	 */
	private function get_translation_coverage( string $base_language ): array|WP_Error {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The aggregate requires custom joins; request-local recomputation keeps the settings report current without broad post-meta cache invalidation.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT COALESCE(NULLIF(language_meta.meta_value, ''), %s) AS language_code,
						COALESCE(NULLIF(group_meta.meta_value, ''), CONCAT('post_', p.ID)) AS translation_group,
						p.post_status
				 FROM {$wpdb->posts} AS p
				 LEFT JOIN {$wpdb->postmeta} AS language_meta
					ON language_meta.post_id = p.ID AND language_meta.meta_key = '_omniti_lang_code'
				 LEFT JOIN {$wpdb->postmeta} AS group_meta
					ON group_meta.post_id = p.ID AND group_meta.meta_key = '_omniti_lang_group_id'
				 WHERE p.post_type IN ('post', 'page')
				   AND p.post_status NOT IN ('trash', 'auto-draft')",
				$base_language
			)
		);

		if ( ! is_array( $results ) ) {
			return new WP_Error(
				'omniti_coverage_query_failed',
				__( 'Translation coverage could not be loaded. Please try again later.', 'omnitibet' )
			);
		}

		$base_groups               = [];
		$unpublished_base_groups  = [];
		$translation_groups       = [];
		$supported_languages      = $this->get_enabled_languages();

		foreach ( $results as $result ) {
			$language_code = (string) $result->language_code;
			$group_id      = (string) $result->translation_group;
			$is_published  = 'publish' === $result->post_status;

			if ( $base_language === $language_code ) {
				if ( $is_published ) {
					$base_groups[ $group_id ] = true;
				} else {
					$unpublished_base_groups[ $group_id ] = true;
				}
				continue;
			}

			if ( isset( $supported_languages[ $language_code ] ) ) {
				$translation_groups[ $group_id ][ $language_code ][ $is_published ? 'published' : 'unpublished' ] = true;
			}
		}

		$coverage = [];
		$total    = count( $base_groups );
		foreach ( $supported_languages as $language_code => $language ) {
			if ( $base_language === $language_code ) {
				continue;
			}

			$published   = 0;
			$unpublished = 0;
			foreach ( $base_groups as $group_id => $_exists ) {
				$translation = $translation_groups[ $group_id ][ $language_code ] ?? [];
				if ( ! empty( $translation['published'] ) ) {
					++$published;
				} elseif ( ! empty( $translation['unpublished'] ) ) {
					++$unpublished;
				}
			}

			$coverage[ $language_code ] = [
				'name'        => $language['name'],
				'published'   => $published,
				'unpublished' => $unpublished,
				'missing'     => max( 0, $total - $published - $unpublished ),
			];
		}

		return [
			'base_published'   => $total,
			'base_unpublished' => count( array_diff_key( $unpublished_base_groups, $base_groups ) ),
			'languages'         => $coverage,
		];
	}

	/**
	 * Register Admin Options Page
	 */
	public function register_admin_menu(): void {
		add_options_page(
			__( 'OmniTibet Settings', 'omnitibet' ),
			__( 'OmniTibet Settings', 'omnitibet' ),
			'manage_options',
			'tibetan-settings',
			[ $this, 'render_admin_settings_page' ]
		);
	}

	/**
	 * Register Settings API Group
	 */
	public function register_settings(): void {
		register_setting(
			'omniti_settings_group',
			'omniti_settings',
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => [],
			]
		);
	}

	/**
	 * Sanitize Settings on Save
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		$sanitized = [];
		if ( ! is_array( $input ) ) {
			return $sanitized;
		}

		$registry        = $this->get_font_registry();
		$valid_font_keys = array_keys( $registry );
		$elem_defs       = self::get_element_defaults();

		// Global Fallback Font
		$sanitized['font_family'] = in_array( $input['font_family'] ?? '', $valid_font_keys, true )
			? $input['font_family']
			: 'monlam-bodtig-2';

		// Global Line-Height
		$lh = isset( $input['line_height'] ) ? floatval( $input['line_height'] ) : 2.3;
		$sanitized['line_height'] = round( min( max( $lh, 1.5 ), 4.0 ), 2 );

		// Enable Element-Specific Typography
		$sanitized['enable_element_typography'] = ! empty( $input['enable_element_typography'] ) ? 1 : 0;

		// Granular Element Typography Fields
		foreach ( $elem_defs as $elem_key => $elem_info ) {
			$font_field = "font_{$elem_key}";
			$sanitized[ $font_field ] = in_array( $input[ $font_field ] ?? '', $valid_font_keys, true )
				? $input[ $font_field ]
				: $elem_info['default_font'];

			$lh_field = "lh_{$elem_key}";
			$elem_lh  = isset( $input[ $lh_field ] ) ? floatval( $input[ $lh_field ] ) : $elem_info['default_lh'];
			$sanitized[ $lh_field ] = round( min( max( $elem_lh, 1.5 ), 4.0 ), 2 );
		}

		// Multilingual Fallback Language
		$catalog = $this->get_available_languages();
		$additional_languages = [];
		$requested_languages = is_array( $input['additional_languages'] ?? null ) ? $input['additional_languages'] : [];
		foreach ( $requested_languages as $code ) {
			if ( ! is_string( $code ) ) {
				continue;
			}
			$code = sanitize_key( (string) $code );
			if ( isset( $catalog[ $code ] ) ) {
				$additional_languages[] = $code;
			}
		}
		$sanitized['additional_languages'] = array_values( array_unique( $additional_languages ) );

		$active_languages = array_merge( array_keys( $this->supported_languages ), $sanitized['additional_languages'] );
		$hidden_languages = [];
		$requested_hidden = is_array( $input['hidden_languages'] ?? null ) ? $input['hidden_languages'] : [];
		foreach ( $requested_hidden as $code ) {
			if ( ! is_string( $code ) ) {
				continue;
			}
			$code = sanitize_key( (string) $code );
			if ( in_array( $code, $active_languages, true ) ) {
				$hidden_languages[] = $code;
			}
		}
		$sanitized['hidden_languages'] = array_values( array_unique( $hidden_languages ) );

		$valid_langs = $active_languages;
		$base_language = isset( $input['base_language'] ) && is_string( $input['base_language'] )
			? sanitize_key( $input['base_language'] )
			: 'en';
		$sanitized['base_language'] = in_array( $base_language, $valid_langs, true ) ? $base_language : 'en';
		$sanitized['fallback_language'] = in_array( $input['fallback_language'] ?? '', $valid_langs, true )
			? $input['fallback_language']
			: $sanitized['base_language'];
		$valid_url_formats = [ 'directory', 'parameter', 'subdomain' ];
		$sanitized['language_url_format'] = in_array( $input['language_url_format'] ?? '', $valid_url_formats, true )
			? $input['language_url_format']
			: 'directory';

		// Wylie Input & Syllable Wrap Toggles
		$sanitized['wylie_enabled'] = ! empty( $input['wylie_enabled'] ) ? 1 : 0;
		$sanitized['syllable_wrap'] = ! empty( $input['syllable_wrap'] ) ? 1 : 0;

		// Switcher Settings
		$valid_formats = [ 'dropdown', 'toggle', 'radio', 'list', 'inline' ];
		$sanitized['switcher_format'] = in_array( $input['switcher_format'] ?? '', $valid_formats, true )
			? $input['switcher_format']
			: 'dropdown';

		$valid_displays = [ 'both', 'icon_only', 'text_only' ];
		$sanitized['switcher_display'] = in_array( $input['switcher_display'] ?? '', $valid_displays, true )
			? $input['switcher_display']
			: 'both';

		$valid_sizes = [ 'small', 'medium', 'large' ];
		$sanitized['switcher_flag_size'] = in_array( $input['switcher_flag_size'] ?? '', $valid_sizes, true )
			? $input['switcher_flag_size']
			: 'small';

		// Active tab for persistence
		$valid_tabs = [ 'tab-elements', 'tab-switcher', 'tab-general', 'tab-multilingual', 'tab-wylie' ];
		$sanitized['active_tab'] = in_array( $input['active_tab'] ?? '', $valid_tabs, true )
			? $input['active_tab']
			: 'tab-elements';

		return $sanitized;
	}

	/**
	 * Enqueue Admin Assets
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		$settings = $this->get_settings();

		wp_enqueue_style( 'omniti-admin-css', OMNITI_PLUGIN_URL . 'assets/css/tibetan-admin.css', [], OMNITI_VERSION );

		if ( 'settings_page_tibetan-settings' === $hook_suffix ) {
			wp_enqueue_style( 'omniti-frontend-css', OMNITI_PLUGIN_URL . 'assets/css/tibetan-frontend.css', [], OMNITI_VERSION );
			wp_enqueue_script( 'omniti-switcher-js', OMNITI_PLUGIN_URL . 'assets/js/tibetan-switcher.js', [], OMNITI_VERSION, true );
			wp_enqueue_style(
				'omniti-admin-google-fonts',
				'https://fonts.googleapis.com/css2?family=Noto+Sans+Tibetan:wght@400;700&family=Noto+Serif+Tibetan:wght@400;700&display=swap',
				[],
				null
			);
		}

		if ( ! empty( $settings['wylie_enabled'] ) ) {
			$screen = get_current_screen();
			if ( $screen && ( 'post' === $screen->base || 'settings_page_tibetan-settings' === $hook_suffix ) ) {
				wp_enqueue_script(
					'omniti-wylie-converter',
					OMNITI_PLUGIN_URL . 'assets/js/wylie-converter.js',
					[],
					OMNITI_VERSION,
					true
				);
			}
		}
	}

	/**
	 * Render Administration Settings Page
	 */
	public function render_admin_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'omnitibet' ) );
		}

		if ( isset( $_GET['section'] ) && is_string( $_GET['section'] ) && 'multilingual-tools' === sanitize_key( wp_unslash( $_GET['section'] ) ) ) {
			$this->render_translation_tools_page();
			return;
		}

		$settings                      = $this->get_settings();
		$registry                      = $this->get_font_registry();
		$supported_languages           = $this->get_enabled_languages();
		$available_languages           = $this->get_available_languages();
		$selected_additional_languages = is_array( $settings['additional_languages'] ?? null ) ? $settings['additional_languages'] : [];
		$selected_hidden_languages     = is_array( $settings['hidden_languages'] ?? null ) ? $settings['hidden_languages'] : [];
		$base_language                 = $this->get_base_language();
		$coverage                      = $this->get_translation_coverage( $base_language );
		$base_posts_url                = add_query_arg( 'omniti_lang_filter', $base_language, admin_url( 'edit.php' ) );
		$base_pages_url                = add_query_arg(
			[
				'post_type'       => 'page',
				'omniti_lang_filter' => $base_language,
			],
			admin_url( 'edit.php' )
		);
		$preview_languages             = [];
		$preview_active_language       = ! in_array( $base_language, $selected_hidden_languages, true ) ? $base_language : '';
		foreach ( $supported_languages as $code => $language ) {
			if ( in_array( $code, $selected_hidden_languages, true ) ) {
				continue;
			}
			if ( '' === $preview_active_language ) {
				$preview_active_language = $code;
			}
			$preview_languages[] = [
				'code'   => $code,
				'native' => $language['native'],
				'active' => ( $code === $preview_active_language ),
			];
		}
		$elements           = $this->get_configurable_elements();
		$use_elem           = ! empty( $settings['enable_element_typography'] );
		$global_font        = $settings['font_family'] ?? 'monlam-bodtig-2';
		$global_lh          = $settings['line_height'] ?? 2.3;
		$fallback           = $settings['fallback_language'] ?? $base_language;
		$wylie              = ! empty( $settings['wylie_enabled'] );
		$wrap               = ! empty( $settings['syllable_wrap'] );
		$active_tab         = $settings['active_tab'] ?? 'tab-elements';
		$switcher_format    = $settings['switcher_format'] ?? 'dropdown';
		$switcher_display   = $settings['switcher_display'] ?? 'both';
		$switcher_flag_size = $settings['switcher_flag_size'] ?? 'small';
		$language_url_format = $settings['language_url_format'] ?? 'directory';
		?>
		<div class="wrap omniti-admin-wrap">
			<nav class="nav-tab-wrapper omniti-section-nav" aria-label="<?php esc_attr_e( 'Plugin settings sections', 'omnitibet' ); ?>">
				<a class="nav-tab nav-tab-active" href="<?php echo esc_url( admin_url( 'options-general.php?page=tibetan-settings' ) ); ?>">
					<?php esc_html_e( 'OmniTibet Settings', 'omnitibet' ); ?>
				</a>
				<a class="nav-tab" href="<?php echo esc_url( add_query_arg( [ 'page' => 'tibetan-settings', 'section' => 'multilingual-tools' ], admin_url( 'options-general.php' ) ) ); ?>">
					<?php esc_html_e( 'Multilingual Tools', 'omnitibet' ); ?>
				</a>
			</nav>
			<div class="omniti-admin-header">
				<div class="omniti-admin-brand">
					<div class="omniti-admin-brand-mark">
						<img
							src="<?php echo esc_url( OMNITI_PLUGIN_URL . 'assets/images/omnitibet.svg' ); ?>"
							alt="<?php esc_attr_e( 'OmniTibet logo', 'omnitibet' ); ?>"
							class="omniti-admin-brand-logo"
						/>
					</div>
					<div class="omniti-admin-brand-copy">
						<div class="omniti-admin-brand-kicker">
							<span><?php esc_html_e( 'OmniTibet', 'omnitibet' ); ?></span>
						</div>
						<h1><?php esc_html_e( 'OmniTibet Settings', 'omnitibet' ); ?></h1>
						<p>
							<?php esc_html_e( 'A Tibetan-first toolkit for beautiful typography and multilingual publishing. Configure fonts, manage translated content, and help visitors switch languages.', 'omnitibet' ); ?>
						</p>
						<ul class="omniti-admin-brand-features" aria-label="<?php esc_attr_e( 'Plugin features', 'omnitibet' ); ?>">
							<li><span class="dashicons dashicons-editor-textcolor" aria-hidden="true"></span><?php esc_html_e( 'Tibetan typography', 'omnitibet' ); ?></li>
							<li><span class="dashicons dashicons-translation" aria-hidden="true"></span><?php esc_html_e( 'Multilingual content', 'omnitibet' ); ?></li>
							<li><span class="dashicons dashicons-edit" aria-hidden="true"></span><?php esc_html_e( 'Wylie input', 'omnitibet' ); ?></li>
						</ul>
					</div>
				</div>
				<div class="omniti-admin-brand-version"><?php
					printf(
						/* translators: %s: plugin version number. */
						esc_html__( 'Version %s', 'omnitibet' ),
						esc_html( OMNITI_VERSION )
					);
					?></div>
			</div>

			<form method="post" action="options.php" id="omniti-settings-form">
				<?php settings_fields( 'omniti_settings_group' ); ?>
				<input type="hidden" name="omniti_settings[active_tab]" id="omniti_active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />

				<!-- Tab Navigation Bar -->
				<nav class="nav-tab-wrapper omniti-nav-tabs" role="tablist">
					<a href="#tab-elements" class="nav-tab <?php echo 'tab-elements' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-elements" role="tab">
						<span class="dashicons dashicons-layout"></span>
						<?php esc_html_e( 'HTML Element Typography', 'omnitibet' ); ?>
					</a>
					<a href="#tab-switcher" class="nav-tab <?php echo 'tab-switcher' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-switcher" role="tab">
						<span class="dashicons dashicons-translation"></span>
						<?php esc_html_e( 'Language Switcher', 'omnitibet' ); ?>
					</a>
					<a href="#tab-general" class="nav-tab <?php echo 'tab-general' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-general" role="tab">
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Global Fallback Typography', 'omnitibet' ); ?>
					</a>
					<a href="#tab-multilingual" class="nav-tab <?php echo 'tab-multilingual' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-multilingual" role="tab">
						<span class="dashicons dashicons-admin-site-alt3"></span>
						<?php esc_html_e( 'Multilingual & Routing', 'omnitibet' ); ?>
					</a>
					<a href="#tab-wylie" class="nav-tab <?php echo 'tab-wylie' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="tab-wylie" role="tab">
						<span class="dashicons dashicons-keyboard"></span>
						<?php esc_html_e( 'Wylie Input Engine', 'omnitibet' ); ?>
					</a>
				</nav>

				<div class="omniti-admin-container">
					<div class="omniti-admin-main">

						<!-- ==========================================
						     TAB 1: HTML Element Typography Customizer
						     ========================================== -->
						<div id="tab-elements" class="omniti-tab-pane <?php echo 'tab-elements' === $active_tab ? 'active' : ''; ?>" role="tabpanel">
							<div class="omniti-card">
								<h2 class="omniti-card-title">
									<span class="dashicons dashicons-layout"></span>
									<?php esc_html_e( 'HTML Element Typography Customizer', 'omnitibet' ); ?>
								</h2>
								<p class="description">
									<?php esc_html_e( 'Assign distinct Tibetan fonts and line-heights to individual HTML elements across your website (e.g. Calligraphic title for headings, clear Uchen for body, modern boldface for menus).', 'omnitibet' ); ?>
								</p>

								<div class="omniti-form-row" style="margin-top:15px;padding-bottom:15px;border-bottom:1px solid #f1f5f9;">
									<label class="omniti-toggle-switch">
										<input type="checkbox" name="omniti_settings[enable_element_typography]" id="omniti_enable_element_typography" value="1" <?php checked( $use_elem ); ?> />
										<span><strong><?php esc_html_e( 'Enable Element-Specific Typography (Body, Headings, Navigation, Quotes, Branding)', 'omnitibet' ); ?></strong></span>
									</label>
									<p class="description" style="margin-left:56px;">
										<?php esc_html_e( 'When active, the element-level rules below take precedence over the global fallback font.', 'omnitibet' ); ?>
									</p>
								</div>

								<!-- Configurable Elements Table -->
								<table class="omniti-elements-table <?php echo $use_elem ? '' : 'omniti-disabled'; ?>" id="omniti-elements-table">
									<thead>
										<tr>
											<th style="width:32%;"><?php esc_html_e( 'HTML Element & Role', 'omnitibet' ); ?></th>
											<th style="width:48%;"><?php esc_html_e( 'Assigned Tibetan Font Stack', 'omnitibet' ); ?></th>
											<th style="width:20%;"><?php esc_html_e( 'Line-Height', 'omnitibet' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $elements as $elem_key => $elem_info ) :
											$selected_font = $settings[ "font_{$elem_key}" ] ?? $elem_info['default_font'];
											$elem_lh       = $settings[ "lh_{$elem_key}" ] ?? $elem_info['default_lh'];
										?>
											<tr data-elem="<?php echo esc_attr( $elem_key ); ?>">
												<td>
													<div class="omniti-elem-header">
														<span class="omniti-elem-icon dashicons <?php echo esc_attr( $elem_info['icon'] ); ?>"></span>
														<div>
															<div class="omniti-elem-name"><?php echo esc_html( $elem_info['label'] ); ?></div>
															<div class="omniti-elem-desc"><?php echo esc_html( $elem_info['desc'] ); ?></div>
															<div style="margin-top:4px;">
																<?php foreach ( $elem_info['tags'] as $tag ) : ?>
																	<span class="omniti-tag-badge">&lt;<?php echo esc_html( $tag ); ?>&gt;</span>
																<?php endforeach; ?>
															</div>
														</div>
													</div>
												</td>
												<td>
													<select name="omniti_settings[font_<?php echo esc_attr( $elem_key ); ?>]" id="omniti_font_<?php echo esc_attr( $elem_key ); ?>" class="omniti-font-select" data-elem="<?php echo esc_attr( $elem_key ); ?>">
														<?php echo $this->render_font_options_html( $selected_font ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													</select>
												</td>
												<td>
													<input type="number" step="0.1" min="1.5" max="4.0" name="omniti_settings[lh_<?php echo esc_attr( $elem_key ); ?>]" id="omniti_lh_<?php echo esc_attr( $elem_key ); ?>" value="<?php echo esc_attr( (string) $elem_lh ); ?>" class="omniti-lh-input" data-elem="<?php echo esc_attr( $elem_key ); ?>" />
													<div style="font-size:11px;color:#94a3b8;margin-top:4px;">Def: <?php echo esc_html( (string) $elem_info['default_lh'] ); ?></div>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>

								<!-- Live Multi-Element Typography Browser Mockup -->
								<div class="omniti-multi-preview-card" id="omniti-multi-preview-card">
									<div class="omniti-browser-bar">
										<div class="omniti-browser-dots">
											<span class="omniti-browser-dot red"></span>
											<span class="omniti-browser-dot yellow"></span>
											<span class="omniti-browser-dot green"></span>
										</div>
										<span class="omniti-browser-url">https://your-site.com/bo/preview</span>
										<span style="font-size:11px;color:#94a3b8;margin-left:auto;font-weight:600;"><?php esc_html_e( 'LIVE MULTI-ELEMENT PREVIEW', 'omnitibet' ); ?></span>
									</div>
									<div class="omniti-preview-content">
										<!-- Navbar Mockup: Site Title & Navigation -->
										<div class="omniti-preview-navbar">
											<div class="omniti-preview-site-title" id="omniti-prev-site_title">
												<?php echo esc_html( $elements['site_title']['preview_text'] ); ?>
											</div>
											<div class="omniti-preview-nav-links" id="omniti-prev-nav">
												<?php echo esc_html( $elements['nav']['preview_text'] ); ?>
											</div>
										</div>

										<!-- Article Mockup: H1, H3, Body, Blockquote -->
										<div class="omniti-preview-article">
											<h1 class="omniti-preview-h1" id="omniti-prev-h1_h2">
												<?php echo esc_html( $elements['h1_h2']['preview_text'] ); ?>
											</h1>
											<h3 class="omniti-preview-h3" id="omniti-prev-h3_h6">
												<?php echo esc_html( $elements['h3_h6']['preview_text'] ); ?>
											</h3>
											<p class="omniti-preview-body-text" id="omniti-prev-body">
												<?php echo esc_html( $elements['body']['preview_text'] ); ?>
											</p>
											<blockquote class="omniti-preview-blockquote" id="omniti-prev-quotes">
												<?php echo esc_html( $elements['quotes']['preview_text'] ); ?>
											</blockquote>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- ==========================================
						     TAB 2: Language Switcher Customizer
						     ========================================== -->
						<div id="tab-switcher" class="omniti-tab-pane <?php echo 'tab-switcher' === $active_tab ? 'active' : ''; ?>" role="tabpanel">
							<!-- Card 1: Switcher Design & Format -->
							<div class="omniti-card">
								<h2 class="omniti-card-title">
									<span class="dashicons dashicons-art"></span>
									<?php esc_html_e( 'Language Switcher Design & Layout', 'omnitibet' ); ?>
								</h2>
								<p class="description">
									<?php esc_html_e( 'Customize how the language switcher displays on your site: choose between dropdowns, toggle buttons, radio inputs, vertical lists, or inline links, and configure display content.', 'omnitibet' ); ?>
								</p>

								<!-- Switcher Format Selection -->
								<div class="omniti-form-row" style="margin-top:20px;">
									<label class="omniti-label" style="font-size:14px;font-weight:600;margin-bottom:8px;display:block;">
										<?php esc_html_e( '1. Switcher Format / Style:', 'omnitibet' ); ?>
									</label>
									<div class="omniti-radio-cards-grid" id="omniti-format-grid">
										<?php
										$formats = [
											'dropdown' => [
												'title' => __( 'Dropdown Menu', 'omnitibet' ),
												'desc'  => __( 'Compact popover menu with current language and arrow', 'omnitibet' ),
												'icon'  => 'dashicons-arrow-down-alt2',
											],
											'toggle' => [
												'title' => __( 'Toggle Pills', 'omnitibet' ),
												'desc'  => __( 'Horizontal pill buttons with active highlighted state', 'omnitibet' ),
												'icon'  => 'dashicons-button',
											],
											'radio' => [
												'title' => __( 'Radio Buttons', 'omnitibet' ),
												'desc'  => __( 'Interactive radio selector with instant redirection', 'omnitibet' ),
												'icon'  => 'dashicons-marker',
											],
											'list' => [
												'title' => __( 'Vertical List', 'omnitibet' ),
												'desc'  => __( 'Clean vertical list item rows ideal for sidebars & footers', 'omnitibet' ),
												'icon'  => 'dashicons-editor-ul',
											],
											'inline' => [
												'title' => __( 'Inline Links', 'omnitibet' ),
												'desc'  => __( 'Minimalist text links separated by sleek vertical dividers', 'omnitibet' ),
												'icon'  => 'dashicons-ellipsis',
											],
										];
										foreach ( $formats as $f_key => $f_info ) :
											$is_sel = ( $switcher_format === $f_key );
										?>
											<label class="omniti-card-option <?php echo $is_sel ? 'selected' : ''; ?>" data-format="<?php echo esc_attr( $f_key ); ?>">
												<input type="radio" name="omniti_settings[switcher_format]" value="<?php echo esc_attr( $f_key ); ?>" <?php checked( $is_sel ); ?> />
												<span class="dashicons <?php echo esc_attr( $f_info['icon'] ); ?> omniti-card-option-icon"></span>
												<div class="omniti-card-option-title"><?php echo esc_html( $f_info['title'] ); ?></div>
												<div class="omniti-card-option-desc"><?php echo esc_html( $f_info['desc'] ); ?></div>
											</label>
										<?php endforeach; ?>
									</div>
								</div>

								<!-- Display Mode Selection -->
								<div class="omniti-form-row" style="margin-top:24px;padding-top:20px;border-top:1px solid #f1f5f9;">
									<label class="omniti-label" style="font-size:14px;font-weight:600;margin-bottom:8px;display:block;">
										<?php esc_html_e( '2. Display Content (Flag Icon vs. Native Name):', 'omnitibet' ); ?>
									</label>
									<div class="omniti-radio-cards-grid" id="omniti-display-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
										<?php
										$displays = [
											'both' => [
												'title' => __( 'Flag & Native Name (Both)', 'omnitibet' ),
												'desc'  => __( 'Shows flag graphic accompanied by native script (e.g. བོད་ཡིག)', 'omnitibet' ),
												'icon'  => 'dashicons-visibility',
											],
											'icon_only' => [
												'title' => __( 'Flag Icon Only', 'omnitibet' ),
												'desc'  => __( 'Ultra-compact display showing flag graphics without text labels', 'omnitibet' ),
												'icon'  => 'dashicons-format-image',
											],
											'text_only' => [
												'title' => __( 'Native Name Only', 'omnitibet' ),
												'desc'  => __( 'Clean typographic text display without flag graphics', 'omnitibet' ),
												'icon'  => 'dashicons-editor-textcolor',
											],
										];
										foreach ( $displays as $d_key => $d_info ) :
											$is_sel = ( $switcher_display === $d_key );
										?>
											<label class="omniti-card-option <?php echo $is_sel ? 'selected' : ''; ?>" data-display="<?php echo esc_attr( $d_key ); ?>">
												<input type="radio" name="omniti_settings[switcher_display]" value="<?php echo esc_attr( $d_key ); ?>" <?php checked( $is_sel ); ?> />
												<span class="dashicons <?php echo esc_attr( $d_info['icon'] ); ?> omniti-card-option-icon"></span>
												<div class="omniti-card-option-title"><?php echo esc_html( $d_info['title'] ); ?></div>
												<div class="omniti-card-option-desc"><?php echo esc_html( $d_info['desc'] ); ?></div>
											</label>
										<?php endforeach; ?>
									</div>
								</div>

								<!-- Flag Size Selection -->
								<div class="omniti-form-row" style="margin-top:24px;padding-top:20px;border-top:1px solid #f1f5f9;">
									<label class="omniti-label" style="font-size:14px;font-weight:600;margin-bottom:8px;display:block;">
										<?php esc_html_e( '3. Flag Icon Dimension:', 'omnitibet' ); ?>
									</label>
									<div class="omniti-flag-size-options" id="omniti-size-group">
										<label class="omniti-size-pill <?php echo 'small' === $switcher_flag_size ? 'selected' : ''; ?>" data-size="small">
											<input type="radio" name="omniti_settings[switcher_flag_size]" value="small" <?php checked( 'small' === $switcher_flag_size ); ?> />
											<span><strong><?php esc_html_e( 'Small', 'omnitibet' ); ?></strong> (16 &times; 11 px) &mdash; <?php esc_html_e( 'Ideal for navbar and tight headers', 'omnitibet' ); ?></span>
										</label>
										<label class="omniti-size-pill <?php echo 'medium' === $switcher_flag_size ? 'selected' : ''; ?>" data-size="medium">
											<input type="radio" name="omniti_settings[switcher_flag_size]" value="medium" <?php checked( 'medium' === $switcher_flag_size ); ?> />
											<span><strong><?php esc_html_e( 'Medium', 'omnitibet' ); ?></strong> (22 &times; 15 px) &mdash; <?php esc_html_e( 'Standard comfortable button size', 'omnitibet' ); ?></span>
										</label>
										<label class="omniti-size-pill <?php echo 'large' === $switcher_flag_size ? 'selected' : ''; ?>" data-size="large">
											<input type="radio" name="omniti_settings[switcher_flag_size]" value="large" <?php checked( 'large' === $switcher_flag_size ); ?> />
											<span><strong><?php esc_html_e( 'Large', 'omnitibet' ); ?></strong> (28 &times; 19 px) &mdash; <?php esc_html_e( 'Bold high-visibility display', 'omnitibet' ); ?></span>
										</label>
									</div>
								</div>
							</div>

							<!-- Card 2: Live Interactive Switcher Preview -->
							<div class="omniti-card">
								<h2 class="omniti-card-title">
									<span class="dashicons dashicons-visibility"></span>
									<?php esc_html_e( 'Live Interactive Switcher Preview', 'omnitibet' ); ?>
								</h2>
								<p class="description">
									<?php esc_html_e( 'Preview updates in real time as you select formats, display modes, or flag dimensions above.', 'omnitibet' ); ?>
								</p>

								<div class="omniti-switcher-preview-box" id="omniti-switcher-preview-container">
									<?php echo $this->get_language_switcher_html( [ 'format' => $switcher_format, 'display' => $switcher_display, 'flag_size' => $switcher_flag_size ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
							</div>

							<!-- Card 3: Shortcode & Template Tag Integration -->
							<div class="omniti-card">
								<h2 class="omniti-card-title">
									<span class="dashicons dashicons-shortcode"></span>
									<?php esc_html_e( 'Shortcode & Template Tag Integration', 'omnitibet' ); ?>
								</h2>
								<p class="description">
									<?php esc_html_e( 'Place this switcher anywhere on your website. When attributes are omitted, it automatically uses your saved settings above.', 'omnitibet' ); ?>
								</p>

								<div style="margin-top:14px;">
									<label style="font-weight:600;font-size:13px;color:#334155;"><?php esc_html_e( 'Standard Shortcode (Block Editor, Widgets, Elementor):', 'omnitibet' ); ?></label>
									<div class="omniti-code-box">
										<code id="omniti-shortcode-display">[omniti_lang_switcher]</code>
										<button type="button" class="omniti-btn-copy" id="omniti-copy-shortcode"><?php esc_html_e( 'Copy Shortcode', 'omnitibet' ); ?></button>
									</div>
								</div>

								<div style="margin-top:14px;">
									<label style="font-weight:600;font-size:13px;color:#334155;"><?php esc_html_e( 'PHP Template Tag (header.php, navigation templates):', 'omnitibet' ); ?></label>
									<div class="omniti-code-box">
										<code id="omniti-php-display">&lt;?php omniti_language_switcher(); ?&gt;</code>
										<button type="button" class="omniti-btn-copy" id="omniti-copy-php"><?php esc_html_e( 'Copy PHP Code', 'omnitibet' ); ?></button>
									</div>
								</div>
							</div>
						</div>

						<!-- ==========================================
						     TAB 3: Global Fallback Typography
						     ========================================== -->
						<div id="tab-general" class="omniti-tab-pane <?php echo 'tab-general' === $active_tab ? 'active' : ''; ?>" role="tabpanel">
							<div class="omniti-card">
								<h2 class="omniti-card-title">
									<span class="dashicons dashicons-admin-generic"></span>
									<?php esc_html_e( 'Global Fallback Typography', 'omnitibet' ); ?>
								</h2>
								<p class="description">
									<?php esc_html_e( 'Default typography applied if the element-specific customizer is toggled off, or as a base fallback across your entire theme.', 'omnitibet' ); ?>
								</p>

								<div class="omniti-form-row">
									<label for="omniti_font_family" class="omniti-label">
										<?php esc_html_e( 'Global Font Family Selection:', 'omnitibet' ); ?>
									</label>
									<select name="omniti_settings[font_family]" id="omniti_font_family" class="widefat">
										<?php echo $this->render_font_options_html( $global_font ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</select>
								</div>

								<div class="omniti-form-row">
									<label for="omniti_line_height" class="omniti-label">
										<?php esc_html_e( 'Global Line-Height Overwrite:', 'omnitibet' ); ?>
									</label>
									<input type="number" step="0.1" min="1.5" max="4.0" name="omniti_settings[line_height]" id="omniti_line_height" value="<?php echo esc_attr( (string) $global_lh ); ?>" style="width:120px;" />
									<span style="margin-left:8px;color:#64748b;"><?php esc_html_e( '(Default: 2.3 - Recommended range: 2.1 to 2.5)', 'omnitibet' ); ?></span>
								</div>

								<div class="omniti-form-row" style="margin-top:20px;">
									<label class="omniti-label" style="font-weight:600;margin-bottom:8px;display:block;">
										<?php esc_html_e( 'Global Typography Preview:', 'omnitibet' ); ?>
									</label>
									<div class="omniti-global-preview-box" id="omniti-global-preview">
										༄༅། །འཛམ་གླིང་ཞི་བདེ་དང་མཐུན་སྒྲིལ་གོང་འཕེལ་དུ་གཏོང་བར་བོད་ཀྱི་རིག་གཞུང་ནི་རྩ་ཆེའི་ནོར་བུ་ཞིག་ཡིན། འདིར་བོད་ཡིག་གི་ཡིག་གཟུགས་དང་ཚིག་གི་བར་ཐག་སོགས་བཀོད་སྒྲིག་བྱེད་ཐུབ།
									</div>
								</div>
							</div>
						</div>

						<!-- ==========================================
						     TAB 4: Multilingual Mapping & Routing
						     ========================================== -->
						<div id="tab-multilingual" class="omniti-tab-pane <?php echo 'tab-multilingual' === $active_tab ? 'active' : ''; ?>" role="tabpanel">
							<div class="omniti-card">
								<h2 class="omniti-card-title">
									<span class="dashicons dashicons-admin-site-alt3"></span>
									<?php esc_html_e( 'Multilingual Mapping & Syllable Wrap Engine', 'omnitibet' ); ?>
								</h2>

								<div class="omniti-form-row">
									<label for="omniti_base_language" class="omniti-label">
										<?php esc_html_e( 'Base Language:', 'omnitibet' ); ?>
									</label>
									<select name="omniti_settings[base_language]" id="omniti_base_language">
										<?php foreach ( $supported_languages as $code => $language ) : ?>
											<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $base_language, $code ); ?>>
												<?php echo esc_html( $language['name'] . ' (' . $language['native'] . ')' ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description">
										<?php esc_html_e( 'Posts and pages without an assigned language are treated as this language. Changing this does not rewrite language codes already saved on posts.', 'omnitibet' ); ?>
									</p>
								</div>

								<div class="omniti-form-row">
									<label for="omniti_fallback_lang" class="omniti-label">
										<?php esc_html_e( 'Fallback Language Allocation:', 'omnitibet' ); ?>
									</label>
									<select name="omniti_settings[fallback_language]" id="omniti_fallback_lang">
										<?php foreach ( $supported_languages as $code => $language ) : ?>
											<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $fallback, $code ); ?>>
												<?php echo esc_html( $language['name'] . ( $base_language === $code ? ' (Base language)' : '' ) ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description">
										<?php esc_html_e( 'Determines the content language to use when a requested translation is unavailable.', 'omnitibet' ); ?>
									</p>
								</div>

								<fieldset class="omniti-form-row">
									<legend class="omniti-label"><?php esc_html_e( 'Language URL Format', 'omnitibet' ); ?></legend>
									<label style="display:block;margin:8px 0;">
										<input type="radio" name="omniti_settings[language_url_format]" value="directory" <?php checked( $language_url_format, 'directory' ); ?> />
										<strong><?php esc_html_e( 'Language directories', 'omnitibet' ); ?></strong>
										<span class="description"><?php esc_html_e( 'Place the language code in the URL path, for example /bo/.', 'omnitibet' ); ?></span>
									</label>
									<label style="display:block;margin:8px 0;">
										<input type="radio" name="omniti_settings[language_url_format]" value="parameter" <?php checked( $language_url_format, 'parameter' ); ?> />
										<strong><?php esc_html_e( 'Language query parameter', 'omnitibet' ); ?></strong>
										<span class="description"><?php esc_html_e( 'Add the language code as a URL parameter, for example ?lang=bo.', 'omnitibet' ); ?></span>
									</label>
									<label style="display:block;margin:8px 0;">
										<input type="radio" name="omniti_settings[language_url_format]" value="subdomain" <?php checked( $language_url_format, 'subdomain' ); ?> />
										<strong><?php esc_html_e( 'Language subdomains', 'omnitibet' ); ?></strong>
										<span class="description"><?php esc_html_e( 'Use the language code as a subdomain. The base language stays on the main domain.', 'omnitibet' ); ?></span>
									</label>
									<p class="description">
										<?php esc_html_e( 'Subdomain mode requires DNS, hosting, and HTTPS to be configured for each enabled language subdomain and routed to this WordPress site.', 'omnitibet' ); ?>
									</p>
								</fieldset>

								<div class="omniti-form-row" style="margin-top:20px;padding-top:20px;border-top:1px solid #f1f5f9;">
									<strong class="omniti-label"><?php esc_html_e( 'Available Languages', 'omnitibet' ); ?></strong>
									<p class="description"><?php esc_html_e( 'English and Tibetan are built in. Chinese and other optional languages are unchecked by default; check one to add it to translation tools and the language switcher. Uncheck it to remove it; existing translated posts and metadata are preserved.', 'omnitibet' ); ?></p>
									<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;margin-top:10px;">
										<?php foreach ( $available_languages as $code => $language ) : ?>
											<label style="display:flex;align-items:center;gap:7px;">
												<input type="checkbox" name="omniti_settings[additional_languages][]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $selected_additional_languages, true ) ); ?> />
												<span><?php echo esc_html( $language['name'] . ' (' . $language['native'] . ')' ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</div>

								<div class="omniti-form-row" style="margin-top:20px;padding-top:20px;border-top:1px solid #f1f5f9;">
									<strong class="omniti-label"><?php esc_html_e( 'Hide Languages from Frontend Switchers', 'omnitibet' ); ?></strong>
									<p class="description"><?php esc_html_e( 'Hidden languages remain enabled for routing and translation management; they are only omitted from frontend language switchers.', 'omnitibet' ); ?></p>
									<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;margin-top:10px;">
										<?php foreach ( $supported_languages as $code => $language ) : ?>
											<label style="display:flex;align-items:center;gap:7px;">
												<input type="checkbox" name="omniti_settings[hidden_languages][]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $selected_hidden_languages, true ) ); ?> />
												<span><?php echo esc_html( $language['name'] . ' (' . $language['native'] . ')' ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</div>

								<div class="omniti-form-row" style="margin-top:20px;padding-top:20px;border-top:1px solid #f1f5f9;">
									<label class="omniti-toggle-switch">
										<input type="checkbox" name="omniti_settings[syllable_wrap]" value="1" <?php checked( $wrap ); ?> />
										<span><strong><?php esc_html_e( 'Enable Syllable Wrap Engine (Zero-Width Space &#8203; after Tsheg ་ and Shad །)', 'omnitibet' ); ?></strong></span>
									</label>
									<p class="description" style="margin-left:56px;">
										<?php esc_html_e( 'Prevents mobile layout breaks and glyph clipping by automatically injecting zero-width spaces after each Tibetan tsheg dot delimiter and shad stroke.', 'omnitibet' ); ?>
									</p>
								</div>
							</div>

							<div class="omniti-card">
								<h2 class="omniti-card-title">
									<span class="dashicons dashicons-chart-bar"></span>
									<?php esc_html_e( 'Translation Coverage', 'omnitibet' ); ?>
								</h2>
								<?php if ( is_wp_error( $coverage ) ) : ?>
									<div class="notice notice-error inline">
										<p><?php echo esc_html( $coverage->get_error_message() ); ?></p>
									</div>
								<?php else : ?>
									<p class="description">
										<?php
										printf(
											/* translators: 1: base language, 2: published post/page groups, 3: unpublished post/page groups. */
											esc_html__( 'Base language: %1$s. Published source groups: %2$s; unpublished source groups: %3$s.', 'omnitibet' ),
											esc_html( $supported_languages[ $base_language ]['name'] ),
											esc_html( number_format_i18n( $coverage['base_published'] ) ),
											esc_html( number_format_i18n( $coverage['base_unpublished'] ) )
										);
										?>
									</p>
									<?php if ( ! empty( $coverage['languages'] ) ) : ?>
										<table class="widefat striped" style="margin-top:12px;">
											<thead>
												<tr>
													<th><?php esc_html_e( 'Language', 'omnitibet' ); ?></th>
													<th><?php esc_html_e( 'Published', 'omnitibet' ); ?></th>
													<th><?php esc_html_e( 'Unpublished', 'omnitibet' ); ?></th>
													<th><?php esc_html_e( 'Missing', 'omnitibet' ); ?></th>
													<th><?php esc_html_e( 'Review Base Content', 'omnitibet' ); ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $coverage['languages'] as $language_code => $language_coverage ) : ?>
													<tr>
														<td><?php echo esc_html( $language_coverage['name'] ); ?></td>
														<td><?php echo esc_html( number_format_i18n( $language_coverage['published'] ) ); ?></td>
														<td><?php echo esc_html( number_format_i18n( $language_coverage['unpublished'] ) ); ?></td>
														<td><?php echo esc_html( number_format_i18n( $language_coverage['missing'] ) ); ?></td>
														<td>
															<a href="<?php echo esc_url( $base_posts_url ); ?>"><?php esc_html_e( 'Posts', 'omnitibet' ); ?></a>
															&middot;
															<a href="<?php echo esc_url( $base_pages_url ); ?>"><?php esc_html_e( 'Pages', 'omnitibet' ); ?></a>
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
										<p class="description">
											<?php esc_html_e( 'Open the base-language list and use the target language column to create or edit translations.', 'omnitibet' ); ?>
										</p>
									<?php else : ?>
										<p class="description"><?php esc_html_e( 'Enable another language to see its translation coverage here.', 'omnitibet' ); ?></p>
									<?php endif; ?>
								<?php endif; ?>
							</div>

							<div class="omniti-card">
								<h2 class="omniti-card-title">
									<span class="dashicons dashicons-category"></span>
									<?php esc_html_e( 'Category & Tag Translations', 'omnitibet' ); ?>
								</h2>
								<p class="description">
									<?php esc_html_e( 'Assign a language to categories and tags, mark language-neutral terms as shared, or link equivalent terms across languages. Duplicated posts use a linked target-language term when available and keep the original term when no translation is linked.', 'omnitibet' ); ?>
								</p>
								<div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
									<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=category&post_type=post' ) ); ?>">
										<?php esc_html_e( 'Manage Categories', 'omnitibet' ); ?>
									</a>
									<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=post_tag&post_type=post' ) ); ?>">
										<?php esc_html_e( 'Manage Tags', 'omnitibet' ); ?>
									</a>
								</div>
							</div>

							<!-- Card: Post & Page Translation Management -->
							<div class="omniti-card">
								<h2 class="omniti-card-title">
									<span class="dashicons dashicons-admin-post"></span>
									<?php esc_html_e( 'Post & Page Translation Management', 'omnitibet' ); ?>
								</h2>
								<p class="description">
									<?php esc_html_e( 'Easily translate or duplicate your posts and pages into any enabled language from your WordPress post tables.', 'omnitibet' ); ?>
								</p>
								<div style="margin-top:15px;display:flex;gap:12px;flex-wrap:wrap;">
									<a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" class="button button-secondary">
										<span class="dashicons dashicons-admin-post" style="vertical-align:middle;margin-right:4px;"></span>
										<?php esc_html_e( 'Go to All Posts Table', 'omnitibet' ); ?>
									</a>
									<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>" class="button button-secondary">
										<span class="dashicons dashicons-admin-page" style="vertical-align:middle;margin-right:4px;"></span>
										<?php esc_html_e( 'Go to All Pages Table', 'omnitibet' ); ?>
									</a>
								</div>
							</div>
						</div>

						<!-- ==========================================
						     TAB 5: Wylie Input Engine
						     ========================================== -->
						<div id="tab-wylie" class="omniti-tab-pane <?php echo 'tab-wylie' === $active_tab ? 'active' : ''; ?>" role="tabpanel">
							<div class="omniti-card">
								<h2 class="omniti-card-title">
									<span class="dashicons dashicons-keyboard"></span>
									<?php esc_html_e( 'Extended Wylie Transliteration Input Engine', 'omnitibet' ); ?>
								</h2>
								<p class="description">
									<?php esc_html_e( 'Convert standard Extended Wylie Latin keystrokes into native Tibetan Unicode characters in real time as you type.', 'omnitibet' ); ?>
								</p>

								<div class="omniti-form-row" style="margin-top:15px;">
									<label class="omniti-toggle-switch">
										<input type="checkbox" name="omniti_settings[wylie_enabled]" id="omniti_wylie_enabled" value="1" <?php checked( $wylie ); ?> />
										<span><strong><?php esc_html_e( 'Enable Real-Time Wylie Input in Post Editor', 'omnitibet' ); ?></strong></span>
									</label>
									<p class="description" style="margin-left:56px;">
										<?php esc_html_e( 'When active, typing in Gutenberg or Classic Editor automatically converts Wylie phonetics to Tibetan. Press Alt+W to toggle conversion on/off while typing.', 'omnitibet' ); ?>
									</p>
								</div>
							</div>

							<!-- Card: Interactive Wylie Live Sandbox -->
							<div class="omniti-card">
								<h2 class="omniti-card-title">
									<span class="dashicons dashicons-edit"></span>
									<?php esc_html_e( 'Interactive Wylie Live Typing Sandbox', 'omnitibet' ); ?>
								</h2>
								<p class="description">
									<?php esc_html_e( 'Test the real-time transliteration engine directly in this box! Make sure Wylie input is enabled above, then type below (e.g. type "bkra shis bde legs" or "sangs rgyas"):', 'omnitibet' ); ?>
								</p>

								<div class="omniti-wylie-sandbox-wrap">
									<textarea id="omniti-wylie-sandbox" class="omniti-wylie-sandbox-textarea" placeholder="<?php esc_attr_e( 'Type Wylie here (e.g. bkra shis bde legs / bod yig / sangs rgyas)...', 'omnitibet' ); ?>" rows="4"></textarea>
									<div style="margin-top:8px;font-size:12px;color:#64748b;display:flex;justify-content:space-between;align-items:center;">
										<span><?php esc_html_e( 'Shortcut:', 'omnitibet' ); ?> <kbd style="background:#e2e8f0;padding:2px 6px;border-radius:4px;font-weight:bold;">Alt</kbd> + <kbd style="background:#e2e8f0;padding:2px 6px;border-radius:4px;font-weight:bold;">W</kbd> <?php esc_html_e( 'to toggle Wylie engine', 'omnitibet' ); ?></span>
										<button type="button" class="button button-small" onclick="document.getElementById('omniti-wylie-sandbox').value='';document.getElementById('omniti-wylie-sandbox').focus();"><?php esc_html_e( 'Clear Sandbox', 'omnitibet' ); ?></button>
									</div>
								</div>

								<!-- Wylie Cheat Table -->
								<h3 style="margin-top:24px;font-size:14px;color:#1e293b;"><?php esc_html_e( 'Quick Extended Wylie Cheat Sheet:', 'omnitibet' ); ?></h3>
								<table class="omniti-wylie-cheat-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Wylie Input', 'omnitibet' ); ?></th>
											<th><?php esc_html_e( 'Tibetan Output', 'omnitibet' ); ?></th>
											<th><?php esc_html_e( 'Description / Category', 'omnitibet' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td><code>ka, kha, ga, nga</code></td>
											<td style="font-size:16px;">ཀ་ ཁ་ ག་ ང༌</td>
											<td><?php esc_html_e( 'First Consonant Row (Guttrals)', 'omnitibet' ); ?></td>
										</tr>
										<tr>
											<td><code>bkra shis bde legs</code></td>
											<td style="font-size:16px;">བཀྲ་ཤིས་བདེ་ལེགས</td>
											<td><?php esc_html_e( 'Tashi Delek (Greeting with stacked subjoined ra "kra")', 'omnitibet' ); ?></td>
										</tr>
										<tr>
											<td><code>sangs rgyas</code></td>
											<td style="font-size:16px;">སངས་རྒྱས</td>
											<td><?php esc_html_e( 'Sangye (Buddha with superscribed and subjoined ya "rgya")', 'omnitibet' ); ?></td>
										</tr>
										<tr>
											<td><code>spyan ras gzigs</code></td>
											<td style="font-size:16px;">སྤྱན་རས་གཟིགས</td>
											<td><?php esc_html_e( 'Chenrezig (Avalokiteshvara with complex stacking)', 'omnitibet' ); ?></td>
										</tr>
										<tr>
											<td><code>space</code> / <code>/</code> / <code>//</code></td>
											<td style="font-size:16px;">་ / ། / ༎</td>
											<td><?php esc_html_e( 'Tsheg (syllable dot), Shad (single verse line), Nyis Shad (double line)', 'omnitibet' ); ?></td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>

						<div style="margin-top:24px;">
							<?php submit_button( __( 'Save OmniTibet Settings', 'omnitibet' ), 'primary large' ); ?>
						</div>

					</div>

					<!-- Sidebar Info Column -->
					<div class="omniti-admin-sidebar">
						<div class="omniti-card">
							<h3 class="omniti-card-title">
								<span class="dashicons dashicons-category"></span>
								<?php esc_html_e( 'Font Design Categories', 'omnitibet' ); ?>
							</h3>
							<p style="margin:0 0 10px 0;font-size:13px;color:#475569;">
								<?php esc_html_e( '36 professional Tibetan font designs bundled inside the plugin:', 'omnitibet' ); ?>
							</p>
							<div style="margin-bottom:8px;">
								<span class="omniti-cat-pill omniti-cat-monlam">Monlam Bodtig (4)</span>
								<span class="omniti-cat-pill omniti-cat-qomolang">Qomolangma (17)</span>
								<span class="omniti-cat-pill omniti-cat-myilam">Myilam (11)</span>
								<span class="omniti-cat-pill omniti-cat-web">Google & Web (4)</span>
							</div>
							<p style="font-size:12px;color:#64748b;margin:0;">
								<?php esc_html_e( 'Self-hosted woff2 files ensure instant rendering on all phones, tablets, and desktop computers without user installs.', 'omnitibet' ); ?>
							</p>
						</div>

						<div class="omniti-card">
							<h3 class="omniti-card-title">
								<span class="dashicons dashicons-translation"></span>
								<?php esc_html_e( 'Customizable Switcher', 'omnitibet' ); ?>
							</h3>
							<p style="font-size:13px;color:#475569;margin-bottom:8px;">
								<?php esc_html_e( 'Choose from 5 styles (Dropdown, Toggle, Radio, List, Inline) and customize display content (Flag & Text, Flag Only, Text Only).', 'omnitibet' ); ?>
							</p>
							<p style="font-size:12px;color:#64748b;margin:0;">
								<?php esc_html_e( 'All styles adapt cleanly to light and dark theme backgrounds.', 'omnitibet' ); ?>
							</p>
						</div>

						<div class="omniti-card">
							<h3 class="omniti-card-title">
								<span class="dashicons dashicons-keyboard"></span>
								<?php esc_html_e( 'Keyboard Shortcut', 'omnitibet' ); ?>
							</h3>
							<p><?php esc_html_e( 'In Gutenberg or Classic editor, press:', 'omnitibet' ); ?></p>
							<p><kbd style="background:#e2e8f0;padding:3px 8px;border-radius:4px;font-weight:bold;">Alt</kbd> + <kbd style="background:#e2e8f0;padding:3px 8px;border-radius:4px;font-weight:bold;">W</kbd></p>
							<p style="font-size:12px;color:#64748b;">
								<?php esc_html_e( 'Instantly toggles Wylie phonetic typing mode without touching the mouse.', 'omnitibet' ); ?>
							</p>
						</div>
					</div>
				</div>
			</form>
		</div>

		<?php ob_start(); ?>
		function omnitiInitSettings() {
			var fontRegistry     = <?php echo wp_json_encode( $registry ); ?>;
			var pluginUrl        = <?php echo wp_json_encode( OMNITI_PLUGIN_URL ); ?>;
			var previewLanguages = <?php echo wp_json_encode( $preview_languages ); ?>;
			var elemToggle       = document.getElementById('omniti_enable_element_typography');
			var elemTable        = document.getElementById('omniti-elements-table');
			var globalFontSelect = document.getElementById('omniti_font_family');
			var globalLhInput    = document.getElementById('omniti_line_height');
			var globalPreview    = document.getElementById('omniti-global-preview');
			var activeTabInput   = document.getElementById('omniti_active_tab');

			/* --- Tab Switching Logic --- */
			var navTabs = document.querySelectorAll('.omniti-nav-tabs .nav-tab');
			var tabPanes = document.querySelectorAll('.omniti-tab-pane');

			function omnitiActivateTab(tabId) {
				if (!tabId) return;
				var cleanId = tabId.replace(/^#/, '');
				var targetPane = document.getElementById(cleanId);
				if (!targetPane) return;

				navTabs.forEach(function(tab) {
					var tId = tab.getAttribute('data-tab');
					if (tId === cleanId) {
						tab.classList.add('nav-tab-active');
						tab.setAttribute('aria-selected', 'true');
					} else {
						tab.classList.remove('nav-tab-active');
						tab.setAttribute('aria-selected', 'false');
					}
				});

				tabPanes.forEach(function(pane) {
					if (pane.id === cleanId) {
						pane.classList.add('active');
					} else {
						pane.classList.remove('active');
					}
				});

				if (activeTabInput) {
					activeTabInput.value = cleanId;
				}

				if (window.history && window.history.replaceState) {
					window.history.replaceState(null, '', '#' + cleanId);
				}
			}

			navTabs.forEach(function(tab) {
				tab.addEventListener('click', function(e) {
					e.preventDefault();
					var tabId = this.getAttribute('data-tab');
					omnitiActivateTab(tabId);
				});
			});

			// If URL hash or saved tab is present, activate that tab
			var initialTab = '';
			if (window.location.hash) {
				var hashId = window.location.hash.substring(1);
				if (document.getElementById(hashId)) {
					initialTab = hashId;
				}
			}
			if (!initialTab && activeTabInput && activeTabInput.value) {
				if (document.getElementById(activeTabInput.value)) {
					initialTab = activeTabInput.value;
				}
			}
			if (initialTab) {
				omnitiActivateTab(initialTab);
			}

			/* --- Multi-Element Typography Preview --- */
			function omnitiUpdateSingleElementPreview(elemKey) {
				var fontSelect = document.getElementById('omniti_font_' + elemKey);
				var lhInput    = document.getElementById('omniti_lh_' + elemKey);
				var prevElem   = document.getElementById('omniti-prev-' + elemKey);

				if (!prevElem) return;

				var fontKey = 'monlam-bodtig-2';
				var lhVal   = '2.3';

				if (elemToggle && elemToggle.checked && fontSelect) {
					fontKey = fontSelect.value;
					lhVal   = lhInput ? lhInput.value : '2.3';
				} else if (globalFontSelect) {
					fontKey = globalFontSelect.value;
					lhVal   = globalLhInput ? globalLhInput.value : '2.3';
				}

				if (fontRegistry[fontKey]) {
					prevElem.style.fontFamily = fontRegistry[fontKey].stack;
				}
				prevElem.style.lineHeight = lhVal;
			}

			function omnitiUpdateAllPreviews() {
				var elements = ['body', 'h1_h2', 'h3_h6', 'nav', 'quotes', 'site_title'];
				elements.forEach(omnitiUpdateSingleElementPreview);
			}

			function omnitiUpdateGlobalPreview() {
				if (!globalPreview || !globalFontSelect) return;
				var fKey = globalFontSelect.value;
				var lhVal = globalLhInput ? globalLhInput.value : '2.3';

				if (fontRegistry[fKey]) {
					globalPreview.style.fontFamily = fontRegistry[fKey].stack;
				}
				globalPreview.style.lineHeight = lhVal;
			}

			// Element table active/disabled toggle
			if (elemToggle && elemTable) {
				elemToggle.addEventListener('change', function() {
					if (elemToggle.checked) {
						elemTable.classList.remove('omniti-disabled');
					} else {
						elemTable.classList.add('omniti-disabled');
					}
					omnitiUpdateAllPreviews();
				});
			}

			document.querySelectorAll('.omniti-font-select, .omniti-lh-input').forEach(function(input) {
				var elemKey = input.getAttribute('data-elem');
				input.addEventListener('change', function() {
					omnitiUpdateSingleElementPreview(elemKey);
				});
				input.addEventListener('input', function() {
					omnitiUpdateSingleElementPreview(elemKey);
				});
			});

			if (globalFontSelect) {
				globalFontSelect.addEventListener('change', function() {
					if (elemToggle && !elemToggle.checked) {
						omnitiUpdateAllPreviews();
					}
					omnitiUpdateGlobalPreview();
				});
			}

			if (globalLhInput) {
				globalLhInput.addEventListener('input', function() {
					if (elemToggle && !elemToggle.checked) {
						omnitiUpdateAllPreviews();
					}
					omnitiUpdateGlobalPreview();
				});
			}

			/* --- Switcher Live Preview & Shortcode Generator --- */
			function omnitiGetFlagImg(code, size) {
				var w = 16, h = 11;
				if (size === 'medium') { w = 22; h = 15; }
				else if (size === 'large') { w = 28; h = 19; }

				var src = code === 'bo' ? pluginUrl + 'assets/images/tibetan-flag.svg'
				        : ( code === 'zh' ? pluginUrl + 'assets/images/chinese-flag.svg' : ( code === 'en' ? pluginUrl + 'assets/images/english-flag.svg' : '' ) );
				if (!src) {
					return '<span class="omniti-flag omniti-flag-code" aria-label="' + code.toUpperCase() + '" style="display:inline-flex;align-items:center;justify-content:center;width:' + w + 'px;height:' + h + 'px;font-size:9px;font-weight:700;line-height:1;background:#e2e8f0;color:#334155;border-radius:2px;">' + code.toUpperCase() + '</span>';
				}

				return '<img src="' + src + '" alt="' + code + '" width="' + w + '" height="' + h + '" class="omniti-flag omniti-flag-' + code + '" style="vertical-align:middle;border-radius:2px;box-shadow:0 0 1px rgba(0,0,0,0.4);object-fit:cover;display:inline-block;" />';
			}

			function omnitiUpdateSwitcherLivePreview() {
				var formatInput  = document.querySelector('input[name="omniti_settings[switcher_format]"]:checked');
				var displayInput = document.querySelector('input[name="omniti_settings[switcher_display]"]:checked');
				var sizeInput    = document.querySelector('input[name="omniti_settings[switcher_flag_size]"]:checked');

				var format  = formatInput ? formatInput.value : 'dropdown';
				var display = displayInput ? displayInput.value : 'both';
				var size    = sizeInput ? sizeInput.value : 'small';

				var container = document.getElementById('omniti-switcher-preview-container');
				if (!container) return;

				var langs = previewLanguages;
				var noPreviewLanguages = !langs.length;

				var wrapperClasses = 'omniti-language-switcher omniti-' + format + ' omniti-disp-' + display + ' omniti-size-' + size;
				var html = '';

				if (format === 'toggle') {
					html = '<div class="' + wrapperClasses + '" role="navigation">';
					langs.forEach(function(item) {
						html += '<a href="javascript:void(0)" class="omniti-toggle-btn ' + (item.active ? 'active' : '') + '">';
						if (display !== 'text_only') html += '<span class="omniti-flag-wrap">' + omnitiGetFlagImg(item.code, size) + '</span>';
						if (display !== 'icon_only') html += '<span class="omniti-toggle-label">' + item.native + '</span>';
						html += '</a>';
					});
					html += '</div>';
				} else if (format === 'radio') {
					html = '<div class="' + wrapperClasses + '" role="radiogroup">';
					langs.forEach(function(item) {
						html += '<label class="omniti-radio-label ' + (item.active ? 'active' : '') + '">';
						html += '<input type="radio" name="omniti_preview_radio" ' + (item.active ? 'checked' : '') + ' />';
						if (display !== 'text_only') html += '<span class="omniti-flag-wrap">' + omnitiGetFlagImg(item.code, size) + '</span>';
						if (display !== 'icon_only') html += '<span class="omniti-radio-text">' + item.native + '</span>';
						html += '</label>';
					});
					html += '</div>';
				} else if (format === 'list') {
					html = '<ul class="' + wrapperClasses + '" role="navigation">';
					langs.forEach(function(item) {
						html += '<li class="omniti-list-item ' + (item.active ? 'active' : '') + '">';
						html += '<a href="javascript:void(0)" class="omniti-list-link">';
						html += '<span class="omniti-item-inner">';
						if (display !== 'text_only') html += '<span class="omniti-flag-wrap">' + omnitiGetFlagImg(item.code, size) + '</span>';
						if (display !== 'icon_only') html += '<span class="omniti-list-text">' + item.native + '</span>';
						html += '</span>';
						if (item.active) html += '<span class="omniti-list-badge">&#10003;</span>';
						html += '</a></li>';
					});
					html += '</ul>';
				} else if (format === 'inline') {
					html = '<div class="' + wrapperClasses + '" role="navigation">';
					langs.forEach(function(item, idx) {
						html += '<a href="javascript:void(0)" class="omniti-inline-link ' + (item.active ? 'active' : '') + '">';
						if (display !== 'text_only') html += '<span class="omniti-flag-wrap">' + omnitiGetFlagImg(item.code, size) + '</span>';
						if (display !== 'icon_only') html += '<span class="omniti-inline-text">' + item.native + '</span>';
						html += '</a>';
						if (idx < langs.length - 1) html += '<span class="omniti-inline-divider">|</span>';
					});
					html += '</div>';
				} else {
					// Dropdown Menu
					html = '<div class="' + wrapperClasses + '" data-omniti-switcher role="navigation">';
					html += '<button class="omniti-switcher-current" type="button" aria-haspopup="true" aria-expanded="false">';
					if (display !== 'text_only') html += '<span class="omniti-flag-wrap">' + omnitiGetFlagImg('bo', size) + '</span>';
					if (display !== 'icon_only') html += '<span class="omniti-lang-label" style="margin-left:5px;">བོད་ཡིག</span>';
					html += '<span class="omniti-arrow" aria-hidden="true">&#9662;</span>';
					html += '</button>';
					html += '<ul class="omniti-switcher-menu" role="menu">';
					langs.forEach(function(item) {
						html += '<li role="none"><a role="menuitem" href="javascript:void(0)" class="omniti-item ' + (item.active ? 'active' : '') + '">';
						html += '<span class="omniti-item-inner">';
						if (display !== 'text_only') html += '<span class="omniti-flag-wrap">' + omnitiGetFlagImg(item.code, size) + '</span>';
						if (display !== 'icon_only') html += '<span class="omniti-item-name">' + item.native + '</span>';
						html += '</span>';
						if (item.active) html += '<span class="omniti-check">&#10003;</span>';
						html += '</a></li>';
					});
					html += '</ul></div>';
				}

				container.innerHTML = noPreviewLanguages ? '' : html;

				// Attach dropdown toggle event if dropdown
				if (format === 'dropdown') {
					var btn = container.querySelector('.omniti-switcher-current');
					var sw  = container.querySelector('.omniti-language-switcher');
					if (btn && sw) {
						btn.addEventListener('click', function(e) {
							e.preventDefault();
							e.stopPropagation();
							sw.classList.toggle('omniti-open');
							btn.setAttribute('aria-expanded', sw.classList.contains('omniti-open') ? 'true' : 'false');
						});
					}
				}

				// Update Code Snippets
				var shortcodeElem = document.getElementById('omniti-shortcode-display');
				var phpElem       = document.getElementById('omniti-php-display');

				if (shortcodeElem) {
					var sc = '[omniti_lang_switcher';
					if (format !== 'dropdown') sc += ' format="' + format + '"';
					if (display !== 'both') sc += ' display="' + display + '"';
					if (size !== 'small') sc += ' flag_size="' + size + '"';
					sc += ']';
					shortcodeElem.textContent = sc;
				}

				if (phpElem) {
					var phpPrefix = String.fromCharCode(60) + '?php omniti_language_switcher';
					var phpArgs = [];
					if (format !== 'dropdown') phpArgs.push("'format' => '" + format + "'");
					if (display !== 'both') phpArgs.push("'display' => '" + display + "'");
					if (size !== 'small') phpArgs.push("'flag_size' => '" + size + "'");

					if (phpArgs.length > 0) {
						phpElem.textContent = phpPrefix + '( [ ' + phpArgs.join(', ') + ' ] ); ?>';
					} else {
						phpElem.textContent = phpPrefix + '(); ?>';
					}
				}
			}

			// Format option cards click & sync
			document.querySelectorAll('#omniti-format-grid .omniti-card-option').forEach(function(card) {
				card.addEventListener('click', function() {
					document.querySelectorAll('#omniti-format-grid .omniti-card-option').forEach(function(c) {
						c.classList.remove('selected');
					});
					this.classList.add('selected');
					var radio = this.querySelector('input[type="radio"]');
					if (radio) {
						radio.checked = true;
						omnitiUpdateSwitcherLivePreview();
					}
				});
			});

			// Display option cards click & sync
			document.querySelectorAll('#omniti-display-grid .omniti-card-option').forEach(function(card) {
				card.addEventListener('click', function() {
					document.querySelectorAll('#omniti-display-grid .omniti-card-option').forEach(function(c) {
						c.classList.remove('selected');
					});
					this.classList.add('selected');
					var radio = this.querySelector('input[type="radio"]');
					if (radio) {
						radio.checked = true;
						omnitiUpdateSwitcherLivePreview();
					}
				});
			});

			// Size option pills click & sync
			document.querySelectorAll('#omniti-size-group .omniti-size-pill').forEach(function(pill) {
				pill.addEventListener('click', function() {
					document.querySelectorAll('#omniti-size-group .omniti-size-pill').forEach(function(p) {
						p.classList.remove('selected');
					});
					this.classList.add('selected');
					var radio = this.querySelector('input[type="radio"]');
					if (radio) {
						radio.checked = true;
						omnitiUpdateSwitcherLivePreview();
					}
				});
			});

			// Copy to Clipboard buttons
			function omnitiSetupCopyBtn(btnId, targetElemId) {
				var btn = document.getElementById(btnId);
				var target = document.getElementById(targetElemId);
				if (!btn || !target) return;

				btn.addEventListener('click', function() {
					var text = target.textContent;
					if (navigator.clipboard && navigator.clipboard.writeText) {
						navigator.clipboard.writeText(text).then(function() {
							omnitiShowCopied(btn);
						});
					} else {
						var ta = document.createElement('textarea');
						ta.value = text;
						document.body.appendChild(ta);
						ta.select();
						document.execCommand('copy');
						document.body.removeChild(ta);
						omnitiShowCopied(btn);
					}
				});
			}

			function omnitiShowCopied(btn) {
				var origText = btn.textContent;
				btn.textContent = '<?php esc_attr_e( 'Copied!', 'omnitibet' ); ?>';
				btn.classList.add('copied');
				setTimeout(function() {
					btn.textContent = origText;
					btn.classList.remove('copied');
				}, 2000);
			}

			omnitiSetupCopyBtn('omniti-copy-shortcode', 'omniti-shortcode-display');
			omnitiSetupCopyBtn('omniti-copy-php', 'omniti-php-display');

			// Close dropdown in preview when clicking outside
			document.addEventListener('click', function(e) {
				var sw = document.querySelector('#omniti-switcher-preview-container .omniti-language-switcher.omniti-open');
				if (sw && !e.target.closest('#omniti-switcher-preview-container')) {
					sw.classList.remove('omniti-open');
					var btn = sw.querySelector('.omniti-switcher-current');
					if (btn) btn.setAttribute('aria-expanded', 'false');
				}
			});

			// Initial preview render
			omnitiUpdateAllPreviews();
			omnitiUpdateGlobalPreview();
			omnitiUpdateSwitcherLivePreview();
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', omnitiInitSettings);
		} else {
			omnitiInitSettings();
		}
		<?php
		$inline_js = ob_get_clean();
		wp_add_inline_script( 'omniti-switcher-js', (string) $inline_js, 'after' );
		?>
		<?php
	}
}

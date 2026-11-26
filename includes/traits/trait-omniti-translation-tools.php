<?php
/**
 * OMNITI Translation Tools trait.
 *
 * @package OmniTibet
 */

defined( 'ABSPATH' ) || exit;

trait OMNITI_Translation_Tools_Trait {
	public function register_translation_tool_hooks(): void {
		add_action( 'admin_init', [ $this, 'redirect_legacy_translation_tools_page' ] );
		add_action( 'admin_init', [ $this, 'save_translation_tool_options' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_translation_seo_metabox' ] );
		add_action( 'save_post', [ $this, 'save_translation_seo_metadata' ], 20, 2 );
		add_action( 'save_post', [ $this, 'record_translation_review_snapshot' ], 30, 2 );
		add_filter( 'wp_nav_menu_args', [ $this, 'filter_language_nav_menu' ] );
		add_filter( 'language_attributes', [ $this, 'filter_language_direction' ] );
		add_filter( 'body_class', [ $this, 'add_language_direction_class' ] );
		add_filter( 'pre_get_document_title', [ $this, 'filter_translation_seo_title' ] );
		add_filter( 'wpseo_title', [ $this, 'filter_translation_seo_title' ] );
		add_filter( 'rank_math/frontend/title', [ $this, 'filter_translation_seo_title' ] );
		add_filter( 'wpseo_metadesc', [ $this, 'filter_translation_seo_description' ] );
		add_filter( 'rank_math/frontend/description', [ $this, 'filter_translation_seo_description' ] );
		add_filter( 'wpseo_canonical', [ $this, 'filter_translation_canonical' ] );
		add_filter( 'rank_math/frontend/canonical', [ $this, 'filter_translation_canonical' ] );
		add_filter( 'get_canonical_url', [ $this, 'filter_translation_core_canonical' ], 10, 2 );
		add_action( 'wp_head', [ $this, 'output_translation_seo_description' ], 2 );
		add_action( 'wp_head', [ $this, 'output_language_alternates' ], 3 );
	}

	public function redirect_legacy_translation_tools_page(): void {
		if ( ! isset( $_GET['page'] ) || ! is_string( $_GET['page'] ) || 'omniti-translation-tools' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'tibetan-settings',
					'section' => 'multilingual-tools',
				],
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	public function save_translation_tool_options(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) || empty( $_POST['omniti_tools_action'] ) ) {
			return;
		}

		$action = is_string( $_POST['omniti_tools_action'] )
			? sanitize_key( wp_unslash( $_POST['omniti_tools_action'] ) )
			: '';
		if ( ! in_array( $action, [ 'menus', 'strings' ], true ) ) {
			return;
		}

		check_admin_referer( 'omniti_save_' . $action, 'omniti_tools_nonce' );
		$this->persist_registered_translation_strings();
		if ( 'menus' === $action ) {
			$this->save_language_menu_assignments();
		} else {
			$this->save_registered_string_translations();
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'tibetan-settings',
					'section' => 'multilingual-tools',
					'tab'     => $action,
					'updated' => '1',
				],
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	public function render_translation_tools_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'omnitibet' ) );
		}

		$tab = isset( $_GET['tab'] ) && is_string( $_GET['tab'] )
			? sanitize_key( wp_unslash( $_GET['tab'] ) )
			: 'queue';
		$tabs = [
			'queue'   => __( 'Translation Queue', 'omnitibet' ),
			'menus'   => __( 'Language Menus', 'omnitibet' ),
			'strings' => __( 'String Translations', 'omnitibet' ),
			'health'  => __( 'Translation Health', 'omnitibet' ),
		];
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'queue';
		}
		?>
		<div class="wrap">
			<nav class="nav-tab-wrapper omniti-section-nav" aria-label="<?php esc_attr_e( 'Plugin settings sections', 'omnitibet' ); ?>">
				<a class="nav-tab" href="<?php echo esc_url( admin_url( 'options-general.php?page=tibetan-settings' ) ); ?>">
					<?php esc_html_e( 'OmniTibet Settings', 'omnitibet' ); ?>
				</a>
				<a class="nav-tab nav-tab-active" href="<?php echo esc_url( add_query_arg( [ 'page' => 'tibetan-settings', 'section' => 'multilingual-tools' ], admin_url( 'options-general.php' ) ) ); ?>">
					<?php esc_html_e( 'Multilingual Tools', 'omnitibet' ); ?>
				</a>
			</nav>
			<h1><?php esc_html_e( 'Multilingual Tools', 'omnitibet' ); ?></h1>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'omnitibet' ); ?></p></div>
			<?php endif; ?>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_id => $label ) : ?>
					<a class="nav-tab <?php echo $tab === $tab_id ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 'page' => 'tibetan-settings', 'section' => 'multilingual-tools', 'tab' => $tab_id ], admin_url( 'options-general.php' ) ) ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
			<?php
			if ( 'menus' === $tab ) {
				$this->render_language_menu_settings();
			} elseif ( 'strings' === $tab ) {
				$this->render_string_translation_settings();
			} elseif ( 'health' === $tab ) {
				$this->render_translation_health_report();
			} else {
				$this->render_translation_queue();
			}
			?>
		</div>
		<?php
	}

	public function register_translation_string( string $string_id, string $source, string $domain = 'default' ): void {
		$string_id = sanitize_key( $string_id );
		$source    = trim( $source );
		if ( '' === $string_id || '' === $source ) {
			return;
		}

		if ( null === $this->registered_translation_strings ) {
			$stored = get_option( 'omniti_registered_strings', [] );
			$this->registered_translation_strings = is_array( $stored ) ? $stored : [];
		}
		$strings = $this->registered_translation_strings;
		$current = $strings[ $string_id ] ?? [];
		$current = is_array( $current ) ? $current : [];
		$translations = is_array( $current['translations'] ?? null ) ? $current['translations'] : [];
		if ( ( $current['source'] ?? null ) === $source && ( $current['domain'] ?? null ) === sanitize_key( $domain ) && $translations === ( $current['translations'] ?? [] ) ) {
			return;
		}
		$current['source'] = $source;
		$current['domain'] = sanitize_key( $domain );
		$current['translations'] = $translations;
		$strings[ $string_id ] = $current;
		$this->registered_translation_strings = $strings;
		$this->registered_translation_strings_dirty = true;
		add_action( 'shutdown', [ $this, 'persist_registered_translation_strings' ], 99 );
	}

	public function persist_registered_translation_strings(): void {
		if ( ! $this->registered_translation_strings_dirty || ! is_array( $this->registered_translation_strings ) ) {
			return;
		}
		if ( ! add_option( 'omniti_registered_strings', $this->registered_translation_strings, '', 'no' ) ) {
			update_option( 'omniti_registered_strings', $this->registered_translation_strings );
		}
		$this->registered_translation_strings_dirty = false;
	}

	public function translate_registered_string( string $string_id, string $fallback, ?string $language = null ): string {
		$string_id = sanitize_key( $string_id );
		$language = $language ?? $this->get_current_language();
		if ( null === $this->registered_translation_strings ) {
			$stored = get_option( 'omniti_registered_strings', [] );
			$this->registered_translation_strings = is_array( $stored ) ? $stored : [];
		}
		$strings = $this->registered_translation_strings;
		if ( ! is_array( $strings ) || ! isset( $strings[ $string_id ] ) || ! is_array( $strings[ $string_id ] ) ) {
			return $fallback;
		}

		$entry = $strings[ $string_id ];
		$translations = is_array( $entry['translations'] ?? null ) ? $entry['translations'] : [];
		if ( isset( $translations[ $language ] ) && is_string( $translations[ $language ] ) && '' !== $translations[ $language ] ) {
			return $entry['translations'][ $language ];
		}

		return is_string( $entry['source'] ?? null ) ? $entry['source'] : $fallback;
	}

	public function record_translation_review_snapshot( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$language = get_post_meta( $post_id, '_omniti_lang_code', true ) ?: $this->get_base_language();
		if ( $language === $this->get_base_language() ) {
			return;
		}

		$source_id = $this->get_translation( $post_id, $this->get_base_language() );
		$source    = $source_id ? get_post( $source_id ) : null;
		if ( $source instanceof WP_Post ) {
			update_post_meta( $post_id, '_omniti_source_revision_gmt', $source->post_modified_gmt );
			update_post_meta( $post_id, '_omniti_source_revision_hash', $this->get_translation_source_fingerprint( $source ) );
		}
	}

	public function get_translation_status( WP_Post $source, string $language ): array {
		$translation_id = $this->get_translation( $source->ID, $language );
		if ( ! $translation_id ) {
			return [ 'status' => 'missing', 'post_id' => 0 ];
		}

		$translation = get_post( $translation_id );
		if ( ! $translation instanceof WP_Post ) {
			return [ 'status' => 'missing', 'post_id' => 0 ];
		}
		if ( 'publish' !== $translation->post_status ) {
			return [
				'status'  => 'draft' === $translation->post_status ? 'draft' : 'unpublished',
				'post_id' => $translation->ID,
			];
		}

		$snapshot = get_post_meta( $translation->ID, '_omniti_source_revision_gmt', true );
		$snapshot_hash = get_post_meta( $translation->ID, '_omniti_source_revision_hash', true );
		$source_time = strtotime( $source->post_modified_gmt . ' UTC' );
		$snapshot_time = is_string( $snapshot ) && '' !== $snapshot ? strtotime( $snapshot . ' UTC' ) : false;
		$source_hash = $this->get_translation_source_fingerprint( $source );
		if (
			false === $snapshot_time
			|| ( false !== $source_time && $source_time > $snapshot_time )
			|| ! is_string( $snapshot_hash )
			|| ! hash_equals( $source_hash, $snapshot_hash )
		) {
			return [ 'status' => 'outdated', 'post_id' => $translation->ID ];
		}

		return [ 'status' => 'published', 'post_id' => $translation->ID ];
	}

	private function get_translation_source_fingerprint( WP_Post $source ): string {
		return hash( 'sha256', $source->post_title . "\0" . $source->post_content . "\0" . $source->post_excerpt );
	}

	public function filter_language_nav_menu( array $args ): array {
		if ( is_admin() || empty( $args['theme_location'] ) || ! is_string( $args['theme_location'] ) ) {
			return $args;
		}

		$assignments = get_option( 'omniti_language_menus', [] );
		if ( ! is_array( $assignments ) ) {
			return $args;
		}
		$location = $args['theme_location'];
		$location_assignments = is_array( $assignments[ $location ] ?? null ) ? $assignments[ $location ] : [];
		$language = $this->get_current_language();
		$menu_id  = absint( $location_assignments[ $language ] ?? 0 );
		if ( ! $menu_id && $language !== $this->get_base_language() ) {
			$menu_id = absint( $location_assignments[ $this->get_base_language() ] ?? 0 );
		}
		if ( $menu_id && wp_get_nav_menu_object( $menu_id ) ) {
			$args['menu'] = $menu_id;
		}

		return $args;
	}

	public function filter_language_direction( string $attributes ): string {
		$languages = $this->get_enabled_languages();
		$direction = $languages[ $this->get_current_language() ]['dir'] ?? 'ltr';
		$direction = in_array( $direction, [ 'ltr', 'rtl' ], true ) ? $direction : 'ltr';
		$escaped   = esc_attr( $direction );

		if ( preg_match( '/\bdir=(["\']).*?\1/i', $attributes ) ) {
			return (string) preg_replace( '/\bdir=(["\']).*?\1/i', 'dir="' . $escaped . '"', $attributes, 1 );
		}

		return rtrim( $attributes ) . ' dir="' . $escaped . '"';
	}

	public function add_language_direction_class( array $classes ): array {
		$languages = $this->get_enabled_languages();
		if ( 'rtl' === ( $languages[ $this->get_current_language() ]['dir'] ?? 'ltr' ) ) {
			$classes[] = 'omniti-language-rtl';
		}
		return $classes;
	}

	public function filter_translation_seo_title( string $title ): string {
		if ( ! is_singular() ) {
			return $title;
		}
		$translated_title = get_post_meta( get_queried_object_id(), '_omniti_seo_title', true );
		return is_string( $translated_title ) && '' !== $translated_title ? $translated_title : $title;
	}

	public function filter_translation_seo_description( string $description ): string {
		if ( ! is_singular() ) {
			return $description;
		}
		$translated_description = get_post_meta( get_queried_object_id(), '_omniti_seo_description', true );
		return is_string( $translated_description ) && '' !== $translated_description ? $translated_description : $description;
	}

	public function filter_translation_canonical( $canonical ) {
		if ( is_singular() ) {
			return get_permalink( get_queried_object_id() );
		}
		if ( is_category() || is_tag() ) {
			$term_link = get_term_link( get_queried_object() );
			return is_wp_error( $term_link ) ? $canonical : $term_link;
		}
		return $canonical;
	}

	public function filter_translation_core_canonical( string $canonical_url, WP_Post $post ): string {
		return $this->filter_post_link( $canonical_url, $post );
	}

	public function output_translation_seo_description(): void {
		if ( ! is_singular() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$description = get_post_meta( get_queried_object_id(), '_omniti_seo_description', true );
		if ( is_string( $description ) && '' !== $description ) {
			printf( "<meta name=\"description\" content=\"%s\" />\n", esc_attr( $description ) );
		}
	}

	public function output_language_alternates(): void {
		if ( is_admin() || is_feed() ) {
			return;
		}

		$urls = [];
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
				return;
			}
			$current_language = get_post_meta( $post->ID, '_omniti_lang_code', true ) ?: $this->get_base_language();
			foreach ( $this->get_enabled_languages() as $code => $_language ) {
				$translation_id = $code === $current_language ? $post->ID : $this->get_translation( $post->ID, $code );
				$translation    = $translation_id ? get_post( $translation_id ) : null;
				if ( $translation instanceof WP_Post && 'publish' === $translation->post_status ) {
					$urls[ $code ] = get_permalink( $translation->ID );
				}
			}
		} elseif ( is_category() || is_tag() ) {
			$term = get_queried_object();
			$taxonomy = is_category() ? 'category' : 'post_tag';
			if ( ! $term instanceof WP_Term ) {
				return;
			}
			$term_language = get_term_meta( $term->term_id, '_omniti_term_language', true ) ?: $this->get_base_language();
			$is_shared = '1' === get_term_meta( $term->term_id, '_omniti_term_shared', true );
			foreach ( $this->get_enabled_languages() as $code => $_language ) {
				$term_id = $this->get_translated_taxonomy_term_id( $term->term_id, $taxonomy, $code );
				if ( ! is_int( $term_id ) || ( ! $is_shared && $code !== $term_language && $term_id === $term->term_id ) ) {
					continue;
				}

				remove_filter( 'term_link', [ $this, 'filter_tibetan_term_link' ], 10 );
				$link = get_term_link( $term_id, $taxonomy );
				add_filter( 'term_link', [ $this, 'filter_tibetan_term_link' ], 10, 3 );
				if ( ! is_wp_error( $link ) ) {
					$urls[ $code ] = $this->apply_language_to_url( $link, $code );
				}
			}
		} elseif ( is_front_page() || is_home() ) {
			foreach ( $this->get_enabled_languages() as $code => $_language ) {
				$urls[ $code ] = $this->get_language_home_url( $code );
			}
		}

		foreach ( $urls as $language => $url ) {
			printf( "<link rel=\"alternate\" hreflang=\"%s\" href=\"%s\" />\n", esc_attr( $language ), esc_url( $url ) );
		}
		if ( isset( $urls[ $this->get_base_language() ] ) ) {
			printf( "<link rel=\"alternate\" hreflang=\"x-default\" href=\"%s\" />\n", esc_url( $urls[ $this->get_base_language() ] ) );
		}
	}

	public function register_translation_seo_metabox(): void {
		foreach ( get_post_types( [ 'public' => true ], 'names' ) as $post_type ) {
			add_meta_box(
				'omniti_translation_seo',
				__( 'Translated SEO Metadata', 'omnitibet' ),
				[ $this, 'render_translation_seo_metabox' ],
				$post_type,
				'normal',
				'default'
			);
		}
	}

	public function render_translation_seo_metabox( WP_Post $post ): void {
		wp_nonce_field( 'omniti_save_translation_seo', 'omniti_translation_seo_nonce' );
		$title = get_post_meta( $post->ID, '_omniti_seo_title', true );
		$description = get_post_meta( $post->ID, '_omniti_seo_description', true );
		?>
		<p>
			<label for="omniti_seo_title"><strong><?php esc_html_e( 'SEO title', 'omnitibet' ); ?></strong></label>
			<input type="text" class="widefat" id="omniti_seo_title" name="omniti_seo_title" value="<?php echo esc_attr( is_string( $title ) ? $title : '' ); ?>" />
		</p>
		<p>
			<label for="omniti_seo_description"><strong><?php esc_html_e( 'SEO description', 'omnitibet' ); ?></strong></label>
			<textarea class="widefat" rows="3" id="omniti_seo_description" name="omniti_seo_description"><?php echo esc_textarea( is_string( $description ) ? $description : '' ); ?></textarea>
		</p>
		<p class="description"><?php esc_html_e( 'These values are used when no SEO plugin overrides the corresponding metadata.', 'omnitibet' ); ?></p>
		<?php
	}

	public function save_translation_seo_metadata( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['omniti_translation_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['omniti_translation_seo_nonce'] ) ), 'omniti_save_translation_seo' ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$title = isset( $_POST['omniti_seo_title'] ) && is_string( $_POST['omniti_seo_title'] )
			? sanitize_text_field( wp_unslash( $_POST['omniti_seo_title'] ) )
			: '';
		$description = isset( $_POST['omniti_seo_description'] ) && is_string( $_POST['omniti_seo_description'] )
			? sanitize_textarea_field( wp_unslash( $_POST['omniti_seo_description'] ) )
			: '';
		$this->update_post_translation_meta( $post_id, '_omniti_seo_title', $title );
		$this->update_post_translation_meta( $post_id, '_omniti_seo_description', $description );
	}

	private function update_post_translation_meta( int $post_id, string $meta_key, string $value ): void {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}
		update_post_meta( $post_id, $meta_key, $value );
	}

	private function save_language_menu_assignments(): void {
		$locations   = get_registered_nav_menus();
		$languages   = $this->get_enabled_languages();
		$requested   = isset( $_POST['omniti_language_menus'] ) && is_array( $_POST['omniti_language_menus'] )
			? wp_unslash( $_POST['omniti_language_menus'] )
			: [];
		$assignments = [];

		foreach ( $locations as $location => $_label ) {
			if ( ! isset( $requested[ $location ] ) || ! is_array( $requested[ $location ] ) ) {
				continue;
			}
			foreach ( $languages as $code => $_language ) {
				$value = $requested[ $location ][ $code ] ?? 0;
				$menu_id = is_scalar( $value ) ? absint( $value ) : 0;
				if ( $menu_id && wp_get_nav_menu_object( $menu_id ) ) {
					$assignments[ $location ][ $code ] = $menu_id;
				}
			}
		}
		update_option( 'omniti_language_menus', $assignments );
	}

	private function save_registered_string_translations(): void {
		$strings = get_option( 'omniti_registered_strings', [] );
		if ( ! is_array( $strings ) ) {
			return;
		}
		$requested = isset( $_POST['omniti_string_translations'] ) && is_array( $_POST['omniti_string_translations'] )
			? wp_unslash( $_POST['omniti_string_translations'] )
			: [];

		foreach ( $strings as $string_id => &$entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$entry['translations'] = is_array( $entry['translations'] ?? null ) ? $entry['translations'] : [];
			$requested_translations = is_array( $requested[ $string_id ] ?? null ) ? $requested[ $string_id ] : [];
			foreach ( $this->get_enabled_languages() as $code => $_language ) {
				if ( $code === $this->get_base_language() ) {
					continue;
				}
				$value = isset( $requested_translations[ $code ] ) && is_string( $requested_translations[ $code ] )
					? wp_kses_post( $requested_translations[ $code ] )
					: '';
				if ( '' === trim( $value ) ) {
					unset( $entry['translations'][ $code ] );
				} else {
					$entry['translations'][ $code ] = $value;
				}
			}
		}
		unset( $entry );
		update_option( 'omniti_registered_strings', $strings );
	}

	private function render_translation_queue(): void {
		$languages = $this->get_enabled_languages();
		$base      = $this->get_base_language();
		$language_filter = isset( $_GET['language'] ) && is_string( $_GET['language'] )
			? sanitize_key( wp_unslash( $_GET['language'] ) )
			: 'all';
		$status_filter = isset( $_GET['status'] ) && is_string( $_GET['status'] )
			? sanitize_key( wp_unslash( $_GET['status'] ) )
			: 'all';
		if ( 'all' !== $language_filter && ! isset( $languages[ $language_filter ] ) ) {
			$language_filter = 'all';
		}

		$post_types = get_post_types( [ 'public' => true ], 'names' );
		unset( $post_types['attachment'] );
		$page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] )
			? max( 1, absint( $_GET['paged'] ) )
			: 1;
		$query = new WP_Query(
			[
				'post_type'      => array_values( $post_types ),
				'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future' ],
				'posts_per_page' => 25,
				'paged'          => $page,
				'meta_query'     => [
					'relation' => 'OR',
					[
						'key'   => '_omniti_lang_code',
						'value' => $base,
					],
					[
						'key'     => '_omniti_lang_code',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'   => '_omniti_lang_code',
						'value' => '',
					],
				],
			]
		);
		$statuses = [
			'all'         => __( 'All statuses', 'omnitibet' ),
			'missing'     => __( 'Missing', 'omnitibet' ),
			'draft'       => __( 'Draft', 'omnitibet' ),
			'unpublished' => __( 'Unpublished', 'omnitibet' ),
			'published'   => __( 'Published', 'omnitibet' ),
			'outdated'    => __( 'Outdated', 'omnitibet' ),
		];
		if ( ! isset( $statuses[ $status_filter ] ) ) {
			$status_filter = 'all';
		}
		?>
		<form method="get" style="margin:16px 0;">
			<input type="hidden" name="page" value="tibetan-settings" />
			<input type="hidden" name="section" value="multilingual-tools" />
			<input type="hidden" name="tab" value="queue" />
			<select name="language">
				<option value="all"><?php esc_html_e( 'All target languages', 'omnitibet' ); ?></option>
				<?php foreach ( $languages as $code => $language ) :
					if ( $code === $base ) {
						continue;
					}
					?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $language_filter, $code ); ?>><?php echo esc_html( $language['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="status">
				<?php foreach ( $statuses as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $status_filter, $code ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Filter', 'omnitibet' ), 'secondary', '', false ); ?>
		</form>
		<table class="widefat striped">
			<thead><tr>
				<th><?php esc_html_e( 'Source content', 'omnitibet' ); ?></th>
				<th><?php esc_html_e( 'Target language', 'omnitibet' ); ?></th>
				<th><?php esc_html_e( 'Status', 'omnitibet' ); ?></th>
				<th><?php esc_html_e( 'Action', 'omnitibet' ); ?></th>
			</tr></thead>
			<tbody>
				<?php
				$row_count = 0;
				foreach ( $query->posts as $source ) :
					foreach ( $languages as $code => $language ) :
						if ( $code === $base || ( 'all' !== $language_filter && $language_filter !== $code ) ) {
							continue;
						}
						$state = $this->get_translation_status( $source, $code );
						if ( 'all' !== $status_filter && $status_filter !== $state['status'] ) {
							continue;
						}
						$row_count++;
						?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $source->ID ) ); ?>"><?php echo esc_html( get_the_title( $source ) ?: __( '(no title)', 'omnitibet' ) ); ?></a></td>
							<td><?php echo esc_html( $language['name'] . ' (' . $language['native'] . ')' ); ?></td>
							<td><?php echo esc_html( $statuses[ $state['status'] ] ?? $state['status'] ); ?></td>
							<td><?php $this->render_translation_queue_action( $source, $code, $state ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
				<?php if ( 0 === $row_count ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No translation tasks match these filters.', 'omnitibet' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
		$pagination = paginate_links(
			[
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'current'   => $page,
				'total'     => max( 1, (int) $query->max_num_pages ),
				'add_args'  => [ 'page' => 'tibetan-settings', 'section' => 'multilingual-tools', 'tab' => 'queue', 'language' => $language_filter, 'status' => $status_filter ],
			]
		);
		if ( $pagination ) {
			echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $pagination ) . '</div></div>';
		}
		wp_reset_postdata();
	}

	private function render_translation_queue_action( WP_Post $source, string $language, array $state ): void {
		if ( ! empty( $state['post_id'] ) ) {
			$edit_url = get_edit_post_link( (int) $state['post_id'] );
			if ( $edit_url ) {
				printf( '<a class="button button-small" href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit translation', 'omnitibet' ) );
				return;
			}
		}
		$url = add_query_arg(
			[
				'action'      => 'omniti_action',
				'omniti_task'    => 'translate',
				'source_id'   => $source->ID,
				'target_lang' => $language,
				'_wpnonce'    => wp_create_nonce( 'omniti_action_nonce' ),
			],
			admin_url( 'admin.php' )
		);
		printf( '<a class="button button-primary button-small" href="%s">%s</a>', esc_url( $url ), esc_html__( 'Add translation', 'omnitibet' ) );
	}

	private function render_language_menu_settings(): void {
		$locations = get_registered_nav_menus();
		$menus     = wp_get_nav_menus();
		$languages = $this->get_enabled_languages();
		$base      = $this->get_base_language();
		$assignments = get_option( 'omniti_language_menus', [] );
		$assignments = is_array( $assignments ) ? $assignments : [];
		$assigned_locations = get_nav_menu_locations();
		?>
		<p><?php esc_html_e( 'Assign a separate WordPress menu to each theme location and language. Unassigned languages fall back to the base-language menu.', 'omnitibet' ); ?></p>
		<?php if ( empty( $locations ) || empty( $menus ) ) : ?>
			<p><?php esc_html_e( 'Register a theme menu location and create menus under Appearance → Menus before assigning language menus.', 'omnitibet' ); ?></p>
			<?php return; ?>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'omniti_save_menus', 'omniti_tools_nonce' ); ?>
			<input type="hidden" name="omniti_tools_action" value="menus" />
			<table class="widefat striped" style="margin-top:16px;">
				<thead><tr>
					<th><?php esc_html_e( 'Theme location', 'omnitibet' ); ?></th>
					<?php foreach ( $languages as $language ) : ?><th><?php echo esc_html( $language['name'] ); ?></th><?php endforeach; ?>
				</tr></thead>
				<tbody>
					<?php foreach ( $locations as $location => $label ) : ?>
						<tr>
							<th><?php echo esc_html( $label ); ?></th>
							<?php foreach ( $languages as $code => $_language ) :
								$selected_menu = absint( $assignments[ $location ][ $code ] ?? ( $code === $base ? ( $assigned_locations[ $location ] ?? 0 ) : 0 ) );
								?>
								<td>
									<select name="omniti_language_menus[<?php echo esc_attr( $location ); ?>][<?php echo esc_attr( $code ); ?>]">
										<option value="0"><?php echo esc_html( $code === $base ? __( 'Use theme default', 'omnitibet' ) : __( 'Use base menu', 'omnitibet' ) ); ?></option>
										<?php foreach ( $menus as $menu ) : ?>
											<option value="<?php echo esc_attr( (string) $menu->term_id ); ?>" <?php selected( $selected_menu, (int) $menu->term_id ); ?>><?php echo esc_html( $menu->name ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( __( 'Save language menus', 'omnitibet' ) ); ?>
		</form>
		<?php
	}

	private function render_string_translation_settings(): void {
		$strings   = get_option( 'omniti_registered_strings', [] );
		$strings   = is_array( $strings ) ? $strings : [];
		$languages = $this->get_enabled_languages();
		$base      = $this->get_base_language();
		?>
		<p><?php esc_html_e( 'Register stable string IDs with omniti_register_string(), then retrieve the active language value with omniti_translate_string().', 'omnitibet' ); ?></p>
		<?php if ( empty( $strings ) ) : ?>
			<p><?php esc_html_e( 'No strings have been registered yet.', 'omnitibet' ); ?></p>
			<?php return; ?>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'omniti_save_strings', 'omniti_tools_nonce' ); ?>
			<input type="hidden" name="omniti_tools_action" value="strings" />
			<table class="widefat striped" style="margin-top:16px;">
				<thead><tr>
					<th><?php esc_html_e( 'String ID / domain', 'omnitibet' ); ?></th>
					<th><?php esc_html_e( 'Source text', 'omnitibet' ); ?></th>
					<?php foreach ( $languages as $code => $language ) :
						if ( $code !== $base ) :
							?>
							<th><?php echo esc_html( $language['name'] ); ?></th>
						<?php endif; ?>
					<?php endforeach; ?>
				</tr></thead>
				<tbody>
					<?php foreach ( $strings as $id => $entry ) :
						if ( ! is_array( $entry ) ) {
							continue;
						}
						?>
						<tr>
							<td><code><?php echo esc_html( $id ); ?></code><br /><small><?php echo esc_html( $entry['domain'] ?? 'default' ); ?></small></td>
							<td><?php echo esc_html( $entry['source'] ?? '' ); ?></td>
							<?php foreach ( $languages as $code => $_language ) :
								if ( $code === $base ) {
									continue;
								}
								$value = $entry['translations'][ $code ] ?? '';
								?>
								<td><textarea class="widefat" rows="2" name="omniti_string_translations[<?php echo esc_attr( $id ); ?>][<?php echo esc_attr( $code ); ?>]"><?php echo esc_textarea( is_string( $value ) ? $value : '' ); ?></textarea></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( __( 'Save string translations', 'omnitibet' ) ); ?>
		</form>
		<?php
	}

	private function render_translation_health_report(): void {
		global $wpdb;
		$base       = $this->get_base_language();
		$post_types = array_values( get_post_types( [ 'public' => true ], 'names' ) );
		$attachment_index = array_search( 'attachment', $post_types, true );
		if ( false !== $attachment_index ) {
			unset( $post_types[ $attachment_index ] );
		}
		$post_types = array_values( $post_types );
		if ( empty( $post_types ) ) {
			esc_html_e( 'No public content types were found.', 'omnitibet' );
			return;
		}
		$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- The health report needs a multi-table join to detect group and per-language duplicates in one pass.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT group_meta.meta_value AS group_id, content.ID, content.post_title, language_meta.meta_value AS language_code
			FROM {$wpdb->postmeta} AS group_meta
			INNER JOIN {$wpdb->posts} AS content ON content.ID = group_meta.post_id
			LEFT JOIN {$wpdb->postmeta} AS language_meta
				ON language_meta.post_id = content.ID AND language_meta.meta_key = '_omniti_lang_code'
			WHERE group_meta.meta_key = '_omniti_lang_group_id'
				AND content.post_type IN ({$placeholders})
				AND content.post_status NOT IN ('trash', 'auto-draft')
			ORDER BY group_meta.meta_value, content.ID",
				$post_types
			)
		);
		if ( null === $rows ) {
			printf(
				'<div class="notice notice-error inline"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: database error message */
						__( 'The translation health check could not run: %s', 'omnitibet' ),
						$wpdb->last_error
					)
				)
			);
			return;
		}
		$groups = [];
		foreach ( $rows as $row ) {
			$code = ! empty( $row->language_code ) ? $row->language_code : $base;
			$groups[ $row->group_id ][ $code ][] = $row;
		}
		$issues = [];
		foreach ( $groups as $group_id => $languages ) {
			if ( empty( $languages[ $base ] ) ) {
				$issues[] = [ 'type' => __( 'Translation group has no base-language post', 'omnitibet' ), 'group' => $group_id, 'items' => array_merge( ...array_values( $languages ) ) ];
			}
			foreach ( $languages as $code => $items ) {
				if ( count( $items ) > 1 ) {
					$issues[] = [
						'type'  => sprintf(
							/* translators: %s: language name. */
							__( 'Multiple posts assigned to %s in one group', 'omnitibet' ),
							$this->get_enabled_languages()[ $code ]['name'] ?? $code
						),
						'group' => $group_id,
						'items' => $items,
					];
				}
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- This on-demand health check must reflect current translation metadata; caching would require invalidation on changes across posts.
		$orphan_posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT content.ID, content.post_title, language_meta.meta_value AS language_code
			FROM {$wpdb->posts} AS content
			INNER JOIN {$wpdb->postmeta} AS language_meta
				ON language_meta.post_id = content.ID AND language_meta.meta_key = '_omniti_lang_code'
			LEFT JOIN {$wpdb->postmeta} AS group_meta
				ON group_meta.post_id = content.ID AND group_meta.meta_key = '_omniti_lang_group_id'
			WHERE content.post_type IN ({$placeholders})
				AND content.post_status NOT IN ('trash', 'auto-draft')
				AND language_meta.meta_value <> ''
				AND language_meta.meta_value <> %s
				AND (group_meta.meta_id IS NULL OR group_meta.meta_value = '')",
				array_merge( $post_types, [ $base ] )
			)
		);
		if ( null === $orphan_posts ) {
			printf(
				'<div class="notice notice-error inline"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: database error message */
						__( 'The unlinked-translation check could not run: %s', 'omnitibet' ),
						$wpdb->last_error
					)
				)
			);
			return;
		}
		foreach ( $orphan_posts as $orphan_post ) {
			$issues[] = [
				'type'  => __( 'Non-base post is not linked to a translation group', 'omnitibet' ),
				'group' => __( '(none)', 'omnitibet' ),
				'items' => [ $orphan_post ],
			];
		}

		if ( empty( $issues ) ) {
			echo '<div class="notice notice-success inline"><p>' . esc_html__( 'No duplicate-language, missing-base, or unlinked-post issues were found.', 'omnitibet' ) . '</p></div>';
			return;
		}
		?>
		<p><?php esc_html_e( 'These checks are read-only. Edit or reconnect the listed posts to repair a group.', 'omnitibet' ); ?></p>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Issue', 'omnitibet' ); ?></th><th><?php esc_html_e( 'Group', 'omnitibet' ); ?></th><th><?php esc_html_e( 'Posts', 'omnitibet' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $issues as $issue ) : ?>
					<tr>
						<td><?php echo esc_html( $issue['type'] ); ?></td>
						<td><code><?php echo esc_html( $issue['group'] ); ?></code></td>
						<td><?php foreach ( $issue['items'] as $item ) : ?>
							<a href="<?php echo esc_url( get_edit_post_link( (int) $item->ID ) ); ?>"><?php echo esc_html( $item->post_title ?: __( '(no title)', 'omnitibet' ) ); ?></a>
							(<?php echo esc_html( $item->language_code ?: $base ); ?>)<br />
						<?php endforeach; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}

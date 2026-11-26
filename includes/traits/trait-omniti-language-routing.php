<?php
/**
 * OMNITI Language Routing Trait methods.
 *
 * @package OmniTibet
 */

defined( 'ABSPATH' ) || exit;

trait OMNITI_Language_Routing_Trait {
	/* ==========================================================================
	   MODULE A: Multilingual Mapping & Routing
	   ========================================================================== */

	/**
	 * Register a language-directory route for each enabled language.
	 */
	public function register_rewrite_rules(): void {
		global $wp_rewrite;

		if ( isset( $wp_rewrite->extra_rules_top ) && is_array( $wp_rewrite->extra_rules_top ) ) {
			foreach ( $this->language_rewrite_patterns as $pattern ) {
				unset( $wp_rewrite->extra_rules_top[ $pattern ] );
			}
		}
		$this->language_rewrite_patterns = [];

		foreach ( $this->get_enabled_languages() as $code => $_language ) {
			$language_pattern = preg_quote( $code, '#' );
			$rules = [
				'^' . $language_pattern . '/?$'                         => 'index.php?omniti_lang=' . $code,
				'^' . $language_pattern . '/page/?([0-9]{1,})/?$'      => 'index.php?omniti_lang=' . $code . '&paged=$matches[1]',
				'^' . $language_pattern . '/(.+?)/?$'                  => 'index.php?omniti_lang=' . $code . '&name=$matches[1]',
			];
			foreach ( $rules as $pattern => $query ) {
				add_rewrite_rule( $pattern, $query, 'top' );
				$this->language_rewrite_patterns[] = $pattern;
			}
		}
	}

	/**
	 * Refresh directory rewrite rules when enabled languages or URL mode changes.
	 *
	 * @param mixed  $old_value Previous settings.
	 * @param mixed  $value     New settings.
	 * @param string $option    Updated option name.
	 */
	public function refresh_language_rewrite_rules( $old_value, $value, string $option ): void {
		if ( ! is_array( $old_value ) || ! is_array( $value ) ) {
			return;
		}

		$old_languages = is_array( $old_value['additional_languages'] ?? null ) ? $old_value['additional_languages'] : [];
		$new_languages = is_array( $value['additional_languages'] ?? null ) ? $value['additional_languages'] : [];
		$old_format    = $old_value['language_url_format'] ?? '';
		$new_format    = $value['language_url_format'] ?? 'directory';
		if ( $old_languages === $new_languages && $old_format === $new_format ) {
			return;
		}

		$this->enabled_languages = null;
		$this->settings          = null;
		$this->register_rewrite_rules();
		flush_rewrite_rules( false );
		update_option( 'omniti_rewrite_version', OMNITI_REWRITE_VERSION );
	}

	/**
	 * Refresh the stored rewrite rules once after a plugin update.
	 */
	public function maybe_refresh_language_rewrite_rules(): void {
		if ( (int) get_option( 'omniti_rewrite_version', 0 ) >= OMNITI_REWRITE_VERSION ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( 'omniti_rewrite_version', OMNITI_REWRITE_VERSION );
	}

	/**
	 * Register 'omniti_lang' and 'lang' Query Variables
	 *
	 * @param array<int, string> $vars Registered query variables.
	 * @return array<int, string>
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = 'omniti_lang';
		$vars[] = 'lang';
		return $vars;
	}

	/**
	 * Resolve language-prefixed paths to their translated page, post, or taxonomy term.
	 *
	 * @param array<string, mixed> $query_vars Parsed request variables.
	 * @return array<string, mixed>
	 */
	public function resolve_tibetan_content_path( array $query_vars ): array {
		$target_language = isset( $query_vars['omniti_lang'] ) && is_string( $query_vars['omniti_lang'] )
			? sanitize_key( $query_vars['omniti_lang'] )
			: '';
		if ( ! isset( $this->get_enabled_languages()[ $target_language ] ) || empty( $query_vars['name'] ) || ! is_string( $query_vars['name'] ) ) {
			return $query_vars;
		}

		$path     = trim( rawurldecode( $query_vars['name'] ), '/' );
		$segments = explode( '/', $path );
		$slug     = sanitize_title( end( $segments ) );

		if ( '' === $path || '' === $slug ) {
			return $query_vars;
		}

		$category_base = trim( (string) get_option( 'category_base' ), '/' );
		$category_base = '' !== $category_base ? $category_base : 'category';
		$tag_base      = trim( (string) get_option( 'tag_base' ), '/' );
		$tag_base      = '' !== $tag_base ? $tag_base : 'tag';

		if ( $category_base === $segments[0] ) {
			$term = get_term_by( 'slug', $slug, 'category' );
			if ( $term instanceof WP_Term && $this->is_term_available_in_language( $term, $target_language ) ) {
				unset( $query_vars['name'], $query_vars['pagename'] );
				$query_vars['cat'] = $term->term_id;
				return $query_vars;
			}
		}

		if ( $tag_base === $segments[0] ) {
			$term = get_term_by( 'slug', $slug, 'post_tag' );
			if ( $term instanceof WP_Term && $this->is_term_available_in_language( $term, $target_language ) ) {
				unset( $query_vars['name'], $query_vars['pagename'] );
				$query_vars['tag_id'] = $term->term_id;
				return $query_vars;
			}
		}

		$page = get_page_by_path( $path, OBJECT, 'page' );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status && $target_language === ( get_post_meta( $page->ID, '_omniti_lang_code', true ) ?: $this->get_base_language() ) ) {
			unset( $query_vars['name'], $query_vars['pagename'] );
			$query_vars['page_id'] = $page->ID;
			return $query_vars;
		}

		$post_types = get_post_types( [ 'public' => true ], 'names' );
		unset( $post_types['page'], $post_types['attachment'] );
		$posts = get_posts(
			[
				'post_type'      => array_values( $post_types ),
				'post_status'    => 'publish',
				'name'           => $slug,
				'numberposts'    => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => '_omniti_lang_code',
				'meta_value'     => $target_language,
			]
		);
		if ( ! empty( $posts ) ) {
			unset( $query_vars['name'], $query_vars['pagename'] );
			$query_vars['p'] = (int) $posts[0];
		}

		return $query_vars;
	}

	/**
	 * Add the Tibetan path prefix to translated taxonomy term links.
	 *
	 * @param string  $term_link Term URL.
	 * @param WP_Term $term      Term object.
	 * @param string  $taxonomy  Taxonomy name.
	 * @return string
	 */
	public function filter_tibetan_term_link( string $term_link, WP_Term $term, string $taxonomy ): string {
		if ( ! in_array( $taxonomy, [ 'category', 'post_tag' ], true ) ) {
			return $term_link;
		}

		$is_shared     = '1' === get_term_meta( $term->term_id, '_omniti_term_shared', true );
		$term_language = get_term_meta( $term->term_id, '_omniti_term_language', true ) ?: $this->get_base_language();
		$link_language = $is_shared ? $this->get_current_language() : $term_language;
		if ( $link_language === $this->get_base_language() ) {
			return $term_link;
		}

		return $this->apply_language_to_url( $term_link, $link_language );
	}

	/**
	 * Restrict front-end category and tag queries to the active language.
	 *
	 * @param array<string, mixed> $args      Term query arguments.
	 * @param array<int, string>   $taxonomies Taxonomy names.
	 * @return array<string, mixed>
	 */
	public function filter_terms_by_language( array $args, array $taxonomies ): array {
		if ( is_admin() || empty( $taxonomies ) || ! empty( array_diff( $taxonomies, [ 'category', 'post_tag' ] ) ) ) {
			return $args;
		}

		$language       = $this->get_current_language();
		$base_language  = $this->get_base_language();
		$language_query = [
			'relation' => 'OR',
			[
				'key'     => '_omniti_term_language',
				'value'   => $language,
				'compare' => '=',
			],
			[
				'key'     => '_omniti_term_shared',
				'value'   => '1',
				'compare' => '=',
			],
		];

		if ( $language === $base_language ) {
			$language_query[] = [
				'key'     => '_omniti_term_language',
				'compare' => 'NOT EXISTS',
			];
			$language_query[] = [
				'key'     => '_omniti_term_language',
				'value'   => '',
				'compare' => '=',
			];
		}

		$existing_meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : [];
		$args['meta_query']  = empty( $existing_meta_query )
			? $language_query
			: [
				'relation' => 'AND',
				$existing_meta_query,
				$language_query,
			];

		return $args;
	}

	/**
	 * Check whether a term is shared or belongs to the requested language.
	 */
	private function is_term_available_in_language( WP_Term $term, string $language ): bool {
		if ( '1' === get_term_meta( $term->term_id, '_omniti_term_shared', true ) ) {
			return true;
		}

		$term_language = get_term_meta( $term->term_id, '_omniti_term_language', true ) ?: $this->get_base_language();
		return $term_language === $language;
	}

	/**
	 * Detect Current Active Language
	 *
	 * @return string Active ISO 639-1 language code.
	 */
	public function get_current_language(): string {
		if ( null !== $this->current_language ) {
			return $this->current_language;
		}
		$supported_languages = $this->get_enabled_languages();
		$base_language       = $this->get_base_language();

		// Re-entrancy safety guard
		if ( $this->is_resolving_lang ) {
			return $base_language;
		}
		$this->is_resolving_lang = true;

		// 1. Check URL query variables for an enabled language.
		$query_lang = '';
		if ( isset( $GLOBALS['wp_query'] ) && function_exists( 'get_query_var' ) ) {
			$query_lang = get_query_var( 'omniti_lang' );
			if ( empty( $query_lang ) ) {
				$query_lang = get_query_var( 'lang' );
			}
		}
		if ( empty( $query_lang ) && isset( $_GET['lang'] ) && is_string( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_lang = sanitize_key( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( is_string( $query_lang ) && ! empty( $query_lang ) && isset( $supported_languages[ $query_lang ] ) ) {
			$this->current_language  = $query_lang;
			$this->is_resolving_lang = false;
			return $this->current_language;
		}

		// 2. Resolve a language subdomain when that URL format is active.
		$host_language = $this->get_language_from_host();
		if ( $host_language ) {
			$this->current_language  = $host_language;
			$this->is_resolving_lang = false;
			return $host_language;
		}

		// 3. Check for a language directory in the request path.
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$path = wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
			if ( is_string( $path ) ) {
				$segments = explode( '/', trim( $path, '/' ) );
				if ( ! empty( $segments[0] ) && isset( $supported_languages[ $segments[0] ] ) ) {
					$this->current_language  = $segments[0];
					$this->is_resolving_lang = false;
					return $segments[0];
				}
			}
		}

		// 4. Check Post Language if query has run and is a single post/page
		if ( isset( $GLOBALS['wp_query'] ) && function_exists( 'is_singular' ) && is_singular() ) {
			$post_id = get_the_ID();
			if ( $post_id ) {
				$post_lang = get_post_meta( $post_id, '_omniti_lang_code', true );
				if ( ! empty( $post_lang ) && isset( $supported_languages[ $post_lang ] ) ) {
					$this->current_language  = $post_lang;
					$this->is_resolving_lang = false;
					return $this->current_language;
				}
				if ( empty( $post_lang ) ) {
					$this->current_language  = $base_language;
					$this->is_resolving_lang = false;
					return $this->current_language;
				}
			}
		}

		// 5. Fallback Default: query option directly without invoking heavy translation calls
		$saved                   = get_option( 'omniti_settings', [] );
		$fallback = is_array( $saved ) && is_string( $saved['fallback_language'] ?? null )
			? $saved['fallback_language']
			: $base_language;
		$this->current_language  = isset( $supported_languages[ $fallback ] ) ? $fallback : $base_language;
		$this->is_resolving_lang = false;
		return $this->current_language;
	}

	public function filter_determine_locale( string $locale ): string {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $locale;
		}
		if ( $this->is_filtering_locale ) {
			return $locale;
		}
		$this->is_filtering_locale = true;
		$lang                      = $this->get_current_language();
		$this->is_filtering_locale = false;

		if ( 'bo' === $lang ) {
			return 'bo';
		}
		$languages = $this->get_enabled_languages();
		return isset( $languages[ $lang ]['locale'] ) && ! isset( $this->supported_languages[ $lang ] )
			? $languages[ $lang ]['locale']
			: $locale;
	}

	public function filter_locale( string $locale ): string {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $locale;
		}
		if ( $this->is_filtering_locale ) {
			return $locale;
		}
		$this->is_filtering_locale = true;
		$lang                      = $this->get_current_language();
		$this->is_filtering_locale = false;

		if ( 'bo' === $lang ) {
			return 'bo';
		}
		$languages = $this->get_enabled_languages();
		return isset( $languages[ $lang ]['locale'] ) && ! isset( $this->supported_languages[ $lang ] )
			? $languages[ $lang ]['locale']
			: $locale;
	}

	public function add_body_classes( array $classes ): array {
		$lang = $this->get_current_language();
		$classes[] = 'omniti-lang-' . sanitize_html_class( $lang );
		if ( 'bo' === $lang ) {
			$classes[] = 'omniti-tibetan-active';
		}
		return $classes;
	}

	public function filter_post_link( string $permalink, $post ): string {
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return $permalink;
		}

		$post_lang = get_post_meta( $post->ID, '_omniti_lang_code', true ) ?: $this->get_base_language();
		if ( $this->get_base_language() === $post_lang ) {
			return $permalink;
		}

		return $this->apply_language_to_url( $permalink, $post_lang );
	}

	/**
	 * Get the selected URL format from settings.
	 */
	private function get_language_url_format(): string {
		$settings = get_option( 'omniti_settings', [] );
		$format   = is_array( $settings ) && is_string( $settings['language_url_format'] ?? null )
			? $settings['language_url_format']
			: 'directory';

		return in_array( $format, [ 'directory', 'parameter', 'subdomain' ], true ) ? $format : 'directory';
	}

	/**
	 * Apply the configured language URL format to an existing URL.
	 */
	private function apply_language_to_url( string $url, string $language ): string {
		if ( $language === $this->get_base_language() ) {
			return $url;
		}

		$format = $this->get_language_url_format();
		if ( 'parameter' === $format || ( 'directory' === $format && ! get_option( 'permalink_structure' ) ) ) {
			return add_query_arg( 'lang', $language, $url );
		}

		if ( 'subdomain' === $format ) {
			$url_parts  = wp_parse_url( $url );
			$home_parts = wp_parse_url( home_url( '/' ) );
			if ( ! is_array( $url_parts ) || ! is_array( $home_parts ) || empty( $home_parts['host'] ) ) {
				return $url;
			}

			$root_host = preg_replace( '/^www\./i', '', $home_parts['host'] );
			$host      = $language . '.' . $root_host;
			$scheme    = $url_parts['scheme'] ?? ( $home_parts['scheme'] ?? 'https' );
			$port      = isset( $url_parts['port'] ) ? ':' . $url_parts['port'] : '';
			$path      = $url_parts['path'] ?? '/';
			$query     = isset( $url_parts['query'] ) ? '?' . $url_parts['query'] : '';
			$fragment  = isset( $url_parts['fragment'] ) ? '#' . $url_parts['fragment'] : '';

			return $scheme . '://' . $host . $port . $path . $query . $fragment;
		}

		$home_url = trailingslashit( home_url( '/' ) );
		if ( str_starts_with( $url, $home_url ) ) {
			$relative = substr( $url, strlen( $home_url ) );
			if ( $language === strtok( $relative, '/' ) ) {
				return $url;
			}
			return $home_url . $language . '/' . $relative;
		}

		return add_query_arg( 'lang', $language, $url );
	}

	/**
	 * Get the language home URL using the selected URL format.
	 */
	private function get_language_home_url( string $language ): string {
		return $this->apply_language_to_url( home_url( '/' ), $language );
	}

	/**
	 * Match the request host against the configured language subdomains.
	 */
	private function get_language_from_host(): ?string {
		if ( 'subdomain' !== $this->get_language_url_format() || empty( $_SERVER['HTTP_HOST'] ) || ! is_string( $_SERVER['HTTP_HOST'] ) ) {
			return null;
		}

		$host_parts = wp_parse_url( 'http://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) );
		if ( ! is_array( $host_parts ) || empty( $host_parts['host'] ) ) {
			return null;
		}
		$request_host = strtolower( preg_replace( '/^www\./i', '', $host_parts['host'] ) );

		foreach ( $this->get_enabled_languages() as $code => $_language ) {
			if ( $code !== $this->get_base_language() && $request_host === $this->get_language_subdomain_host( $code ) ) {
				return $code;
			}
		}

		return null;
	}

	/**
	 * Build a language subdomain from the WordPress home host.
	 */
	private function get_language_subdomain_host( string $language ): string {
		$home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		if ( ! is_string( $home_host ) || '' === $home_host ) {
			return '';
		}

		$root_host = preg_replace( '/^www\./i', '', strtolower( $home_host ) );
		return $language . '.' . $root_host;
	}

	/**
	 * Keep WordPress canonical redirects on the active language subdomain.
	 *
	 * @param string|false|null $redirect_url Canonical redirect URL.
	 * @param string            $requested_url Requested URL.
	 * @return string|false|null
	 */
	public function preserve_language_subdomain_redirect( $redirect_url, string $requested_url ) {
		$language = $this->get_language_from_host();
		if ( ! $language || ! is_string( $redirect_url ) ) {
			return $redirect_url;
		}

		return $this->apply_language_to_url( $redirect_url, $language );
	}

	public function filter_page_link( string $permalink, int $post_id ): string {
		$post = get_post( $post_id );
		return $post ? $this->filter_post_link( $permalink, $post ) : $permalink;
	}

	/**
	 * Retrieve Linked Translation Post ID for a Given Target Language
	 *
	 * @param int    $post_id     Original post ID.
	 * @param string $target_lang Target ISO code (e.g. 'bo', 'zh', 'en').
	 * @return int|null Post ID of translated post or null if not found.
	 */
	public function get_translation( int $post_id, string $target_lang ): ?int {
		$group_id = get_post_meta( $post_id, '_omniti_lang_group_id', true );
		if ( empty( $group_id ) ) {
			return null;
		}

		$base_language = $this->get_base_language();
		$cache_key     = 'omniti_group_' . md5( (string) $group_id . ':' . $base_language );
		$group_map = wp_cache_get( $cache_key, 'omniti_translations' );

		if ( false === $group_map ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- The join is needed to map every language in a translation group; the result is cached below.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT group_meta.post_id, COALESCE(NULLIF(language_meta.meta_value, ''), %s) AS lang_code
					 FROM {$wpdb->postmeta} AS group_meta
					 LEFT JOIN {$wpdb->postmeta} AS language_meta
					   ON language_meta.post_id = group_meta.post_id
					  AND language_meta.meta_key = '_omniti_lang_code'
					 WHERE group_meta.meta_key = '_omniti_lang_group_id'
					   AND group_meta.meta_value = %s",
					$base_language,
					$group_id
				)
			);

			$group_map = [];
			if ( ! empty( $results ) ) {
				foreach ( $results as $row ) {
					$group_map[ $row->lang_code ] = (int) $row->post_id;
				}
			}
			wp_cache_set( $cache_key, $group_map, 'omniti_translations', HOUR_IN_SECONDS );
		}

		return $group_map[ $target_lang ] ?? null;
	}
}

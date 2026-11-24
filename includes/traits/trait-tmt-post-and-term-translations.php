<?php
/**
 * TMT Translation Admin Trait methods.
 *
 * @package Tibetan_Multilingual_Typography
 */

defined( 'ABSPATH' ) || exit;

trait TMT_Translation_Admin_Trait {
	/* ==========================================================================
	   POSTS & PAGES LIST TABLE COLUMNS (Image 1)
	   ========================================================================== */

	/**
	 * Register Columns for All Public Post Types
	 */
	public function register_admin_list_columns(): void {
		$post_types = get_post_types( [ 'public' => true ], 'names' );
		foreach ( $post_types as $pt ) {
			add_filter( "manage_{$pt}_posts_columns", [ $this, 'add_language_columns' ] );
			add_action( "manage_{$pt}_posts_custom_column", [ $this, 'render_language_column' ], 10, 2 );
		}
	}

	/**
	 * Register language controls for the built-in Categories and Tags taxonomies.
	 */
	public function register_taxonomy_translation_hooks(): void {
		foreach ( [ 'category', 'post_tag' ] as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			add_action( "{$taxonomy}_add_form_fields", [ $this, 'render_taxonomy_term_add_fields' ] );
			add_action( "{$taxonomy}_edit_form_fields", [ $this, 'render_taxonomy_term_edit_fields' ], 10, 2 );
			add_action( "created_{$taxonomy}", [ $this, 'save_taxonomy_translation_fields' ], 10, 3 );
			add_action( "edited_{$taxonomy}", [ $this, 'save_taxonomy_translation_fields' ], 10, 3 );
			add_filter( "manage_edit-{$taxonomy}_columns", [ $this, 'add_taxonomy_language_column' ] );
			add_filter( "manage_{$taxonomy}_custom_column", [ $this, 'render_taxonomy_language_column' ], 10, 3 );
		}
	}

	public function render_taxonomy_term_add_fields( string $taxonomy ): void {
		$this->render_taxonomy_translation_fields( $taxonomy );
	}

	public function render_taxonomy_term_edit_fields( WP_Term $term, string $taxonomy ): void {
		$this->render_taxonomy_translation_fields( $taxonomy, $term );
	}

	/**
	 * Render language, shared-term, and term-link controls.
	 */
	private function render_taxonomy_translation_fields( string $taxonomy, ?WP_Term $term = null ): void {
		$is_edit            = null !== $term;
		$base_language      = $this->get_base_language();
		$languages          = $this->get_enabled_languages();
		$prefill_source_term = null;
		$prefill_target_lang = '';
		if ( ! $is_edit && isset( $_GET['tmt_source_term'] ) && is_string( $_GET['tmt_source_term'] ) ) {
			$source_term = get_term( absint( wp_unslash( $_GET['tmt_source_term'] ) ), $taxonomy );
			if ( $source_term instanceof WP_Term && current_user_can( 'edit_term', $source_term->term_id ) ) {
				$prefill_source_term = $source_term;
			}
		}
		if ( ! $is_edit && isset( $_GET['tmt_target_lang'] ) && is_string( $_GET['tmt_target_lang'] ) ) {
			$requested_language = sanitize_key( wp_unslash( $_GET['tmt_target_lang'] ) );
			if ( isset( $languages[ $requested_language ] ) ) {
				$prefill_target_lang = $requested_language;
			}
		}

		$current_lang  = $is_edit ? get_term_meta( $term->term_id, '_tmt_term_language', true ) : $prefill_target_lang;
		$current_lang  = ! empty( $current_lang ) && isset( $languages[ $current_lang ] ) ? $current_lang : $base_language;
		$is_shared     = $is_edit && '1' === get_term_meta( $term->term_id, '_tmt_term_shared', true );
		$current_group = $is_edit ? get_term_meta( $term->term_id, '_tmt_term_translation_group', true ) : '';
		$linked_term   = $prefill_source_term ? (int) $prefill_source_term->term_id : 0;

		wp_nonce_field( 'tmt_save_term_language', 'tmt_term_language_nonce' );

		$language_control = '<select name="tmt_term_language" id="tmt_term_language">';
		foreach ( $languages as $code => $language ) {
			$language_control .= sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $code ),
				selected( $current_lang, $code, false ),
				esc_html( $language['name'] . ' (' . $language['native'] . ')' )
			);
		}
		$language_control .= '</select>';
		$this->render_taxonomy_term_field( 'tmt_term_language', __( 'Term language', 'tibetpress' ), $language_control, $is_edit );

		$shared_control = sprintf(
			'<label><input type="checkbox" name="tmt_term_shared" value="1" %s /> %s</label>',
			checked( $is_shared, true, false ),
			esc_html__( 'Reuse this term as-is in every language', 'tibetpress' )
		);
		$this->render_taxonomy_term_field( 'tmt_term_shared', __( 'Language behavior', 'tibetpress' ), $shared_control, $is_edit );

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'exclude'    => $is_edit ? [ $term->term_id ] : [],
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $terms ) ) {
			$link_control = '<p class="description">' . esc_html( $terms->get_error_message() ) . '</p>';
		} else {
			if ( $prefill_source_term ) {
				$link_label = __( 'Link to source translation', 'tibetpress' );
			} elseif ( $is_edit && ! empty( $current_group ) ) {
				$link_label = __( 'Keep current translation group', 'tibetpress' );
			} else {
				$link_label = __( 'Start a new translation group', 'tibetpress' );
			}
			$link_control = '<select name="tmt_term_translation_id" id="tmt_term_translation_id">';
			$link_control .= '<option value="0">' . esc_html( $link_label ) . '</option>';
			if ( $is_edit && ! empty( $current_group ) ) {
				$link_control .= '<option value="-1">' . esc_html__( 'Unlink and start a new group', 'tibetpress' ) . '</option>';
			}

			foreach ( $terms as $related_term ) {
				if ( '1' === get_term_meta( $related_term->term_id, '_tmt_term_shared', true ) ) {
					continue;
				}
				$related_group = get_term_meta( $related_term->term_id, '_tmt_term_translation_group', true );
				if ( ! empty( $current_group ) && $current_group === $related_group && ! $linked_term ) {
					$linked_term = (int) $related_term->term_id;
				}
				$related_lang = get_term_meta( $related_term->term_id, '_tmt_term_language', true ) ?: $base_language;
				$lang_name    = $languages[ $related_lang ]['name'] ?? $related_lang;
				$link_control .= sprintf(
					'<option value="%d" %s>%s — %s</option>',
					(int) $related_term->term_id,
					selected( $linked_term, (int) $related_term->term_id, false ),
					esc_html( $related_term->name ),
					esc_html( $lang_name )
				);
			}
			$link_control .= '</select>';
			$link_control .= '<p class="description">' . esc_html__( 'Choose an existing equivalent term to link translations. Shared terms do not need a translation link.', 'tibetpress' ) . '</p>';
		}
		$this->render_taxonomy_term_field( 'tmt_term_translation_id', __( 'Link translated term', 'tibetpress' ), $link_control, $is_edit );
	}

	/**
	 * Render a taxonomy term field in the add or edit form layout.
	 */
	private function render_taxonomy_term_field( string $id, string $label, string $control, bool $is_edit ): void {
		if ( $is_edit ) {
			printf(
				'<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td>%3$s</td></tr>',
				esc_attr( $id ),
				esc_html( $label ),
				$control // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			return;
		}

		printf(
			'<div class="form-field"><label for="%1$s">%2$s</label>%3$s</div>',
			esc_attr( $id ),
			esc_html( $label ),
			$control // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Save language and translation group metadata for a category or tag.
	 *
	 * @param int   $term_id Term ID.
	 * @param int   $tt_id   Term taxonomy ID.
	 * @param array $args    Term insertion arguments.
	 */
	public function save_taxonomy_translation_fields( int $term_id, int $tt_id, array $args ): void {
		if (
			! isset( $_POST['tmt_term_language_nonce'] )
			|| ! is_string( $_POST['tmt_term_language_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmt_term_language_nonce'] ) ), 'tmt_save_term_language' )
		) {
			return;
		}

		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}
		if ( ! in_array( $term->taxonomy, [ 'category', 'post_tag' ], true ) || ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		$languages = $this->get_enabled_languages();
		$language  = isset( $_POST['tmt_term_language'] ) && is_string( $_POST['tmt_term_language'] )
			? sanitize_key( wp_unslash( $_POST['tmt_term_language'] ) )
			: $this->get_base_language();
		if ( ! isset( $languages[ $language ] ) ) {
			$language = $this->get_base_language();
		}

		if ( ! empty( $_POST['tmt_term_shared'] ) ) {
			delete_term_meta( $term_id, '_tmt_term_language' );
			delete_term_meta( $term_id, '_tmt_term_translation_group' );
			update_term_meta( $term_id, '_tmt_term_shared', '1' );
			return;
		}

		$selected_value = isset( $_POST['tmt_term_translation_id'] ) && is_string( $_POST['tmt_term_translation_id'] )
			? sanitize_text_field( wp_unslash( $_POST['tmt_term_translation_id'] ) )
			: '0';
		$current_group = get_term_meta( $term_id, '_tmt_term_translation_group', true );

		$selected_term_id = '-1' === $selected_value ? 0 : absint( $selected_value );
		if ( $selected_term_id ) {
			$selected_term = get_term( $selected_term_id );
			if ( ! $selected_term || is_wp_error( $selected_term ) || $selected_term->taxonomy !== $term->taxonomy ) {
				wp_die( esc_html__( 'The selected translated term is invalid for this taxonomy.', 'tibetpress' ) );
			}
			if ( '1' === get_term_meta( $selected_term_id, '_tmt_term_shared', true ) ) {
				wp_die( esc_html__( 'Shared terms cannot be linked as translated terms.', 'tibetpress' ) );
			}
		}

		delete_term_meta( $term_id, '_tmt_term_shared' );
		update_term_meta( $term_id, '_tmt_term_language', $language );
		if ( '-1' === $selected_value ) {
			update_term_meta( $term_id, '_tmt_term_translation_group', 'tmt_term_' . wp_generate_uuid4() );
			return;
		}

		$selected_group = $selected_term_id ? get_term_meta( $selected_term_id, '_tmt_term_translation_group', true ) : '';
		$group_ids      = array_values( array_unique( array_filter( [ $current_group, $selected_group ] ) ) );
		$group_id       = $current_group ?: $selected_group ?: 'tmt_term_' . wp_generate_uuid4();
		$member_ids     = [ $term_id ];

		if ( $selected_term_id ) {
			$member_ids[] = $selected_term_id;
		}
		foreach ( $group_ids as $existing_group_id ) {
			$members = get_terms(
				[
					'taxonomy'   => $term->taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
					'meta_key'   => '_tmt_term_translation_group',
					'meta_value' => $existing_group_id,
				]
			);
			if ( is_wp_error( $members ) ) {
				wp_die( esc_html__( 'Existing term translations could not be loaded.', 'tibetpress' ) );
			}
			$member_ids = array_merge( $member_ids, array_map( 'absint', $members ) );
		}

		foreach ( array_unique( $member_ids ) as $member_id ) {
			update_term_meta( $member_id, '_tmt_term_translation_group', $group_id );
		}
	}

	public function add_taxonomy_language_column( array $columns ): array {
		$columns['tmt_term_language'] = __( 'Language', 'tibetpress' );
		foreach ( $this->get_enabled_languages() as $code => $language ) {
			$columns[ 'tmt_term_lang_' . $code ] = sprintf(
				'<span title="%s">%s</span>',
				esc_attr( $language['name'] . ' (' . $language['native'] . ')' ),
				$this->get_flag_html( $code, 20, 14 )
			);
		}
		return $columns;
	}

	public function render_taxonomy_language_column( string $content, string $column_name, int $term_id ): string {
		if ( 'tmt_term_language' === $column_name ) {
			if ( '1' === get_term_meta( $term_id, '_tmt_term_shared', true ) ) {
				return esc_html__( 'Shared', 'tibetpress' );
			}

			$language_code = get_term_meta( $term_id, '_tmt_term_language', true ) ?: $this->get_base_language();
			$languages     = $this->get_enabled_languages();
			return esc_html( $languages[ $language_code ]['name'] ?? $language_code );
		}

		if ( ! str_starts_with( $column_name, 'tmt_term_lang_' ) ) {
			return $content;
		}

		$target_language = substr( $column_name, strlen( 'tmt_term_lang_' ) );
		if ( ! isset( $this->get_enabled_languages()[ $target_language ] ) ) {
			return $content;
		}

		$term = get_term( $term_id );
		if ( ! $term instanceof WP_Term ) {
			return '';
		}

		$is_shared = '1' === get_term_meta( $term_id, '_tmt_term_shared', true );
		$term_lang = get_term_meta( $term_id, '_tmt_term_language', true ) ?: $this->get_base_language();
		if ( $is_shared || $term_lang === $target_language ) {
			return '<span class="tmt-col-current" title="' . esc_attr__( 'Current or shared term language', 'tibetpress' ) . '">&#10003;</span>';
		}

		$translation_id = $this->get_translated_taxonomy_term_id( $term_id, $term->taxonomy, $target_language );
		if ( is_wp_error( $translation_id ) ) {
			return '<span class="tmt-col-error" title="' . esc_attr( $translation_id->get_error_message() ) . '">!</span>';
		}
		if ( $translation_id !== $term_id ) {
			$edit_link = get_edit_term_link( $translation_id, $term->taxonomy );
			if ( $edit_link ) {
				return sprintf(
					'<a href="%s" class="tmt-col-action tmt-col-edit" title="%s"><span class="dashicons dashicons-edit"></span></a>',
					esc_url( $edit_link ),
					esc_attr(
						sprintf(
							/* translators: %s: target language name. */
							__( 'Edit %s term translation', 'tibetpress' ),
							$this->get_enabled_languages()[ $target_language ]['name']
						)
					)
				);
			}
		}

		$add_link = add_query_arg(
			[
				'taxonomy'        => $term->taxonomy,
				'post_type'       => 'post',
				'tmt_source_term' => $term_id,
				'tmt_target_lang' => $target_language,
			],
			admin_url( 'edit-tags.php' )
		) . '#addtag';

		return sprintf(
			'<a href="%s" class="tmt-col-action tmt-col-plus" title="%s">+</a>',
			esc_url( $add_link ),
			esc_attr(
				sprintf(
					/* translators: %s: target language name. */
					__( 'Add %s term translation', 'tibetpress' ),
					$this->get_enabled_languages()[ $target_language ]['name']
				)
			)
		);
	}

	/**
	 * Insert Language Flag Columns into Post List Table
	 *
	 * Matches Image 1 layout: Flags placed next to the Title column.
	 *
	 * @param array<string, string> $columns Existing table columns.
	 * @return array<string, string> Modified columns.
	 */
	public function add_language_columns( array $columns ): array {
		$new_columns = [];
		$supported_languages = $this->get_enabled_languages();
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'title' === $key ) {
				foreach ( $supported_languages as $code => $info ) {
					$new_columns[ 'tmt_lang_' . $code ] = sprintf(
						'<span title="%s">%s</span>',
						esc_attr( $info['name'] . ' (' . $info['native'] . ')' ),
						$this->get_flag_html( $code, 20, 14 )
					);
				}
			}
		}
		return $new_columns;
	}

	/**
	 * Render Language Column Cell for Each Post Row
	 *
	 * Shows '+' (blue plus) if not translated, or pencil icon (✏️) if translated.
	 * Also provides a duplicate option.
	 *
	 * @param string $column_name Name of the column.
	 * @param int    $post_id     Current post ID.
	 */
	public function render_language_column( string $column_name, int $post_id ): void {
		if ( ! str_starts_with( $column_name, 'tmt_lang_' ) ) {
			return;
		}

		$target_lang       = substr( $column_name, strlen( 'tmt_lang_' ) );
		$supported_languages = $this->get_enabled_languages();
		if ( ! isset( $supported_languages[ $target_lang ] ) ) {
			return;
		}
		$post_lang   = get_post_meta( $post_id, '_tb_lang_code', true ) ?: $this->get_base_language();

		// If this post itself is in this target language
		if ( $post_lang === $target_lang ) {
			echo '<span class="tmt-col-current" title="' . esc_attr__( 'Current language of this post', 'tibetpress' ) . '">&#10003;</span>';
			return;
		}

		$trans_id = $this->get_translation( $post_id, $target_lang );
		$nonce    = wp_create_nonce( 'tmt_action_nonce' );

		if ( $trans_id && $trans_id !== $post_id ) {
			// Translation exists: Render pencil edit icon
			$edit_url    = get_edit_post_link( $trans_id );
			$trans_title = get_the_title( $trans_id ) ?: __( '(no title)', 'tibetpress' );
			printf(
				'<a href="%s" class="tmt-col-action tmt-col-edit" title="%s"><span class="dashicons dashicons-edit"></span></a>',
				esc_url( $edit_url ),
				esc_attr(
					sprintf(
						/* translators: 1: target language name, 2: translated post title. */
						__( 'Edit %1$s translation: "%2$s"', 'tibetpress' ),
						$supported_languages[ $target_lang ]['name'],
						$trans_title
					)
				)
			);
		} else {
			// Translation does NOT exist: Render '+' (translate) and duplicate action
			$translate_url = add_query_arg(
				[
					'action'      => 'tmt_action',
					'tmt_task'    => 'translate',
					'source_id'   => $post_id,
					'target_lang' => $target_lang,
					'_wpnonce'    => $nonce,
				],
				admin_url( 'admin.php' )
			);

			$duplicate_url = add_query_arg(
				[
					'action'      => 'tmt_action',
					'tmt_task'    => 'duplicate',
					'source_id'   => $post_id,
					'target_lang' => $target_lang,
					'_wpnonce'    => $nonce,
				],
				admin_url( 'admin.php' )
			);

			printf(
				'<div class="tmt-col-actions">' .
				'<a href="%s" class="tmt-col-action tmt-col-plus" title="%s">+</a>' .
				'<a href="%s" class="tmt-col-action tmt-col-dup" title="%s"><span class="dashicons dashicons-admin-page"></span></a>' .
				'</div>',
				esc_url( $translate_url ),
				esc_attr(
					sprintf(
						/* translators: %s: target language name. */
						__( 'Add translation in %s', 'tibetpress' ),
						$supported_languages[ $target_lang ]['name']
					)
				),
				esc_url( $duplicate_url ),
				esc_attr(
					sprintf(
						/* translators: %s: target language name. */
						__( 'Duplicate content to %s', 'tibetpress' ),
						$supported_languages[ $target_lang ]['name']
					)
				)
			);
		}
	}

	/**
	 * Add Quick Translation & Duplication Actions to Row Hover
	 *
	 * @param array<string, string> $actions Existing row action links.
	 * @param WP_Post               $post    Current post.
	 * @return array<string, string>
	 */
	public function add_post_row_actions( array $actions, WP_Post $post ): array {
		$post_lang = get_post_meta( $post->ID, '_tb_lang_code', true ) ?: $this->get_base_language();
		$nonce     = wp_create_nonce( 'tmt_action_nonce' );
		$supported_languages = $this->get_enabled_languages();

		foreach ( $supported_languages as $lang_code => $language ) {
			if ( $post_lang === $lang_code ) {
				continue;
			}
			$lang_label = $language['native'];
			$trans_id = $this->get_translation( $post->ID, $lang_code );
			if ( ! $trans_id ) {
				$dup_url = add_query_arg(
					[
						'action'      => 'tmt_action',
						'tmt_task'    => 'duplicate',
						'source_id'   => $post->ID,
						'target_lang' => $lang_code,
						'_wpnonce'    => $nonce,
					],
					admin_url( 'admin.php' )
				);
				$actions[ 'tmt_dup_' . $lang_code ] = sprintf(
					'<a href="%s" style="color:#0f766e;">%s %s</a>',
					esc_url( $dup_url ),
					esc_html__( 'Duplicate to', 'tibetpress' ),
					esc_html( $lang_label )
				);
			}
		}
		return $actions;
	}

	/**
	 * Render Language Filter Dropdown in Posts List Table
	 */
	public function render_language_filter_dropdown(): void {
		$current_screen = get_current_screen();
		if ( ! $current_screen || 'edit' !== $current_screen->base ) {
			return;
		}

		$selected = $this->get_base_language();
		if ( isset( $_GET['tmt_lang_filter'] ) && is_string( $_GET['tmt_lang_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected = sanitize_key( wp_unslash( $_GET['tmt_lang_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' !== $selected && ! isset( $this->get_enabled_languages()[ $selected ] ) ) {
				$selected = $this->get_base_language();
			}
		}
		?>
		<select name="tmt_lang_filter" id="tmt_lang_filter">
			<option value=""><?php esc_html_e( 'All languages', 'tibetpress' ); ?></option>
			<?php foreach ( $this->get_enabled_languages() as $code => $info ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $selected, $code ); ?>>
					<?php echo esc_html( $info['name'] . ' (' . $info['native'] . ')' ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Add a language selector to the WordPress toolbar on dashboard and site views.
	 */
	public function add_admin_language_switcher( WP_Admin_Bar $admin_bar ): void {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		$languages = $this->get_enabled_languages();
		if ( empty( $languages ) ) {
			return;
		}

		$is_dashboard     = is_admin();
		$current_language = $is_dashboard ? $this->get_admin_current_language() : $this->get_current_language();
		if ( 'all' !== $current_language && ! isset( $languages[ $current_language ] ) ) {
			$current_language = $this->get_base_language();
		}
		if ( 'all' !== $current_language && ! isset( $languages[ $current_language ] ) ) {
			$current_language = (string) array_key_first( $languages );
		}

		$current_label = 'all' === $current_language
			? __( 'All languages', 'tibetpress' )
			: ( $languages[ $current_language ]['native'] ?? $languages[ $current_language ]['name'] );
		$current_flag = 'all' === $current_language ? '' : $this->get_flag_html( $current_language, 16, 11 );

		$admin_bar->add_node(
			[
				'id'    => 'tmt-admin-language-switcher',
				'title' => $current_flag . '<span class="ab-label">' . sprintf(
					/* translators: %s: current language name */
					esc_html__( 'Language: %s', 'tibetpress' ),
					esc_html( $current_label )
				) . '</span>',
				'href'  => $this->get_language_toolbar_url( $current_language ),
			]
		);

		if ( $is_dashboard ) {
			$admin_bar->add_node(
				[
					'id'     => 'tmt-admin-language-all',
					'parent' => 'tmt-admin-language-switcher',
					'title'  => esc_html__( 'All languages', 'tibetpress' ),
					'href'   => $this->get_language_toolbar_url( 'all' ),
					'meta'   => [
						'class' => 'all' === $current_language ? 'tmt-current-admin-language' : '',
					],
				]
			);
		}

		foreach ( $languages as $code => $language ) {
			$admin_bar->add_node(
				[
					'id'     => 'tmt-admin-language-' . sanitize_key( $code ),
					'parent' => 'tmt-admin-language-switcher',
					'title'  => $this->get_flag_html( $code, 16, 11 ) . '<span class="ab-label">' . esc_html( $language['name'] . ' (' . $language['native'] . ')' ) . '</span>',
					'href'   => $this->get_language_toolbar_url( $code ),
					'meta'   => [
						'class' => $code === $current_language ? 'tmt-current-admin-language' : '',
					],
				]
			);
		}
	}

	/**
	 * Choose a dashboard or frontend destination for a toolbar language.
	 */
	private function get_language_toolbar_url( string $language ): string {
		if ( is_admin() ) {
			return $this->get_admin_language_switch_url( $language );
		}

		$languages = $this->get_enabled_languages();
		if ( ! isset( $languages[ $language ] ) ) {
			return $this->get_language_home_url( $this->get_base_language() );
		}

		$post_id = is_singular() ? get_queried_object_id() : 0;
		if ( ! $post_id ) {
			return $this->get_language_home_url( $language );
		}

		if ( $language === $this->get_current_language() ) {
			return (string) get_permalink( $post_id );
		}

		$translation_id = $this->get_translation( $post_id, $language );
		if ( $translation_id ) {
			return (string) get_permalink( $translation_id );
		}

		$fallback = $this->get_settings()['fallback_language'] ?? $this->get_base_language();
		if ( $language === $fallback ) {
			$post_language = get_post_meta( $post_id, '_tb_lang_code', true ) ?: $this->get_base_language();
			return $post_language === $fallback
				? (string) get_permalink( $post_id )
				: $this->get_language_home_url( $fallback );
		}

		return $this->get_language_home_url( $language );
	}

	/**
	 * Get the language represented by the current admin screen.
	 */
	private function get_admin_current_language(): string {
		$languages = $this->get_enabled_languages();
		$screen    = get_current_screen();

		if ( $screen && 'post' === $screen->base && isset( $_GET['post'] ) && is_scalar( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post = get_post( absint( wp_unslash( $_GET['post'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $post instanceof WP_Post ) {
				$post_language = get_post_meta( $post->ID, '_tb_lang_code', true ) ?: $this->get_base_language();
				return isset( $languages[ $post_language ] ) ? $post_language : $this->get_base_language();
			}
		}

		if ( $screen && 'edit' === $screen->base && isset( $_GET['tmt_lang_filter'] ) && is_string( $_GET['tmt_lang_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$requested_language = sanitize_key( wp_unslash( $_GET['tmt_lang_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' === $requested_language ) {
				return 'all';
			}
			if ( isset( $languages[ $requested_language ] ) ) {
				return $requested_language;
			}
		}

		return $this->get_base_language();
	}

	/**
	 * Build the destination URL for an admin language selection.
	 */
	private function get_admin_language_switch_url( string $language ): string {
		$languages = $this->get_enabled_languages();
		$screen    = get_current_screen();

		if ( 'all' !== $language && isset( $languages[ $language ] ) && $screen && 'post' === $screen->base && isset( $_GET['post'] ) && is_scalar( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post = get_post( absint( wp_unslash( $_GET['post'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $post instanceof WP_Post ) {
				$post_language = get_post_meta( $post->ID, '_tb_lang_code', true ) ?: $this->get_base_language();
				if ( $language === $post_language ) {
					$edit_url = get_edit_post_link( $post->ID, 'raw' );
					if ( is_string( $edit_url ) ) {
						return $edit_url;
					}
				} else {
					$translation_id = $this->get_translation( $post->ID, $language );
					if ( $translation_id ) {
						$edit_url = get_edit_post_link( $translation_id, 'raw' );
						if ( is_string( $edit_url ) ) {
							return $edit_url;
						}
					}
				}
			}
		}

		$post_type = $screen && ! empty( $screen->post_type ) && post_type_exists( $screen->post_type )
			? $screen->post_type
			: ( current_user_can( 'edit_posts' ) ? 'post' : 'page' );
		$url = admin_url( 'edit.php' );
		if ( 'post' !== $post_type ) {
			$url = add_query_arg( 'post_type', $post_type, $url );
		}

		return add_query_arg( 'tmt_lang_filter', 'all' === $language ? '' : $language, $url );
	}

	/**
	 * Filter Posts Query by Selected Language in Admin List
	 *
	 * @param WP_Query $query Main query.
	 */
	public function filter_posts_by_language( WP_Query $query ): void {
		if ( is_admin() ) {
			if ( ! $query->is_main_query() ) {
				return;
			}

			$screen = get_current_screen();
			if ( ! $screen || 'edit' !== $screen->base ) {
				return;
			}

			$filter_lang = $this->get_base_language();
			if ( isset( $_GET['tmt_lang_filter'] ) && is_string( $_GET['tmt_lang_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$requested_language = sanitize_key( wp_unslash( $_GET['tmt_lang_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( '' === $requested_language ) {
					return;
				}
				if ( isset( $this->get_enabled_languages()[ $requested_language ] ) ) {
					$filter_lang = $requested_language;
				}
			}

			if ( isset( $this->get_enabled_languages()[ $filter_lang ] ) ) {
				$meta_query = $query->get( 'meta_query' );
				$meta_query = is_array( $meta_query ) ? $meta_query : [];
				if ( $this->get_base_language() === $filter_lang ) {
					$language_clause = [
						'relation' => 'OR',
						[
							'key'     => '_tb_lang_code',
							'value'   => $filter_lang,
							'compare' => '=',
						],
						[
							'key'     => '_tb_lang_code',
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => '_tb_lang_code',
							'value'   => '',
							'compare' => '=',
						],
					];
				} else {
					$language_clause = [
						'key'     => '_tb_lang_code',
						'value'   => $filter_lang,
						'compare' => '=',
					];
				}

				$query->set(
					'meta_query',
					empty( $meta_query )
						? [ $language_clause ]
						: [
							'relation' => 'AND',
							$meta_query,
							$language_clause,
						]
				);
			}
			return;
		}

		$filter_lang = $this->get_current_language();
		if ( $this->get_base_language() === $filter_lang ) {
			$language_clause = [
				'relation' => 'OR',
				[
					'key'     => '_tb_lang_code',
					'value'   => $filter_lang,
					'compare' => '=',
				],
				[
					'key'     => '_tb_lang_code',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => '_tb_lang_code',
					'value'   => '',
					'compare' => '=',
				],
			];
		} else {
			$language_clause = [
				'key'     => '_tb_lang_code',
				'value'   => $filter_lang,
				'compare' => '=',
			];
		}

		$existing_meta_query = $query->get( 'meta_query' );
		$existing_meta_query = is_array( $existing_meta_query ) ? $existing_meta_query : [];
		$query->set(
			'meta_query',
			empty( $existing_meta_query )
				? [ $language_clause ]
				: [
					'relation' => 'AND',
					$existing_meta_query,
					$language_clause,
				]
		);
	}

	/* ==========================================================================
	   POST EDIT SCREEN METABOX & ACTIONS (Image 2)
	   ========================================================================== */

	/**
	 * Register Post/Page Translation Meta Box
	 */
	public function register_translation_metabox(): void {
		$post_types = get_post_types( [ 'public' => true ], 'names' );
		foreach ( $post_types as $pt ) {
			add_meta_box(
				'tmt_post_translation_box',
				__( 'Language & Translations', 'tibetpress' ),
				[ $this, 'render_translation_metabox' ],
				$pt,
				'side',
				'high'
			);
		}
	}

	/**
	 * Render Post Translation Meta Box HTML (Matches Image 2 UI)
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_translation_metabox( WP_Post $post ): void {
		wp_nonce_field( 'tmt_save_post_lang', 'tmt_lang_nonce' );

		$supported_languages = $this->get_enabled_languages();
		$current_post_lang  = get_post_meta( $post->ID, '_tb_lang_code', true ) ?: $this->get_base_language();
		$group_id           = get_post_meta( $post->ID, '_tb_lang_group_id', true );
		if ( empty( $group_id ) ) {
			$group_id = 'grp_' . wp_generate_uuid4();
			update_post_meta( $post->ID, '_tb_lang_group_id', $group_id );
		}

		$nonce = wp_create_nonce( 'tmt_action_nonce' );
		?>
		<div class="tmt-editor-box">
			<!-- Connect with translations link -->
			<div class="tmt-connect-header">
				<a href="#" class="tmt-connect-link" id="tmt-toggle-connect">
					<?php esc_html_e( 'Connect with translations', 'tibetpress' ); ?>
				</a>
			</div>

			<!-- Collapsible Connect Panel -->
			<div class="tmt-connect-panel" id="tmt-connect-drawer" style="display:none;">
				<div class="tmt-connect-inner">
					<label for="tmt_link_existing_post">
						<strong><?php esc_html_e( 'Link an existing post to this translation group:', 'tibetpress' ); ?></strong>
					</label>
					<input type="number" id="tmt_link_existing_post" name="tmt_link_existing_post" placeholder="<?php esc_attr_e( 'Enter Post ID to link', 'tibetpress' ); ?>" class="widefat" style="margin:5px 0 8px 0;" />
					
					<div style="display:flex;gap:6px;">
						<button type="submit" name="tmt_meta_action" value="connect_post" class="button button-secondary button-small">
							<?php esc_html_e( 'Connect Post', 'tibetpress' ); ?>
						</button>
						<?php if ( ! empty( $group_id ) ) : ?>
							<a href="<?php echo esc_url( add_query_arg( [ 'action' => 'tmt_action', 'tmt_task' => 'unlink', 'source_id' => $post->ID, '_wpnonce' => $nonce ], admin_url( 'admin.php' ) ) ); ?>" class="button button-link-delete button-small" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to unlink this post from its translations?', 'tibetpress' ) ); ?>');">
								<?php esc_html_e( 'Unlink', 'tibetpress' ); ?>
							</a>
						<?php endif; ?>
					</div>
					<input type="hidden" name="tb_lang_group_id" value="<?php echo esc_attr( $group_id ); ?>" />
				</div>
			</div>

			<!-- Post Language Selector -->
			<div class="tmt-doc-lang-row" style="margin: 10px 0 14px 0;">
				<label for="tb_lang_code" style="font-weight:600;font-size:12px;color:#475569;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'This document language:', 'tibetpress' ); ?>
				</label>
				<select name="tb_lang_code" id="tb_lang_code" class="widefat" style="font-size:13px;">
					<?php foreach ( $supported_languages as $code => $info ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current_post_lang, $code ); ?>>
							<?php echo esc_html( $info['name'] . ' (' . $info['native'] . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Translate this Document Section (Image 2) -->
			<div class="tmt-section-title">
				<strong><?php esc_html_e( 'Translate this Document', 'tibetpress' ); ?></strong>
			</div>

			<div class="tmt-wpml-card">
				<!-- Card Tabs Header -->
				<div class="tmt-card-tabs">
					<span class="tmt-tab-icon" title="<?php esc_attr_e( 'Overview', 'tibetpress' ); ?>">
						<span class="dashicons dashicons-grid-view"></span>
					</span>
					<span class="tmt-tab-icon active" title="<?php esc_attr_e( 'Translate & Duplicate', 'tibetpress' ); ?>">
						<span class="dashicons dashicons-edit"></span>
					</span>
					<span class="tmt-tab-icon" title="<?php esc_attr_e( 'WordPress Core', 'tibetpress' ); ?>">
						<span class="dashicons dashicons-wordpress"></span>
					</span>
				</div>

				<!-- Table matching Image 2 -->
				<table class="tmt-doc-table">
					<thead>
						<tr>
							<th style="text-align:left;padding-left:12px;"><?php esc_html_e( 'Language', 'tibetpress' ); ?></th>
							<th style="width:70px;text-align:center;"><?php esc_html_e( 'Translate', 'tibetpress' ); ?></th>
							<th style="width:70px;text-align:center;"><?php esc_html_e( 'Duplicate', 'tibetpress' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$secondary_langs = $supported_languages;
						unset( $secondary_langs[ $current_post_lang ] );

						foreach ( $secondary_langs as $code => $data ) :
							$trans_id = $this->get_translation( $post->ID, $code );

							$translate_url = add_query_arg(
								[
									'action'      => 'tmt_action',
									'tmt_task'    => 'translate',
									'source_id'   => $post->ID,
									'target_lang' => $code,
									'_wpnonce'    => $nonce,
								],
								admin_url( 'admin.php' )
							);

							$duplicate_url = add_query_arg(
								[
									'action'      => 'tmt_action',
									'tmt_task'    => 'duplicate',
									'source_id'   => $post->ID,
									'target_lang' => $code,
									'_wpnonce'    => $nonce,
								],
								admin_url( 'admin.php' )
							);
						?>
							<tr>
								<td class="tmt-lang-label-cell">
									<?php echo $this->get_flag_html( $code, 18, 12 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span class="tmt-lang-text"><?php echo esc_html( $data['name'] ); ?></span>
								</td>

								<!-- Translate Column -->
								<td class="tmt-center-cell">
									<?php if ( $trans_id && $trans_id !== $post->ID ) : ?>
										<a href="<?php echo esc_url( get_edit_post_link( $trans_id ) ); ?>" class="tmt-btn-edit" title="<?php esc_attr_e( 'Edit Translation', 'tibetpress' ); ?>">
											<span class="dashicons dashicons-edit"></span>
										</a>
									<?php else : ?>
										<a href="<?php echo esc_url( $translate_url ); ?>" class="tmt-table-plus" title="<?php esc_attr_e( 'Add Translation', 'tibetpress' ); ?>">+</a>
									<?php endif; ?>
								</td>

								<!-- Duplicate Column -->
								<td class="tmt-center-cell">
									<?php if ( $trans_id && $trans_id !== $post->ID ) : ?>
										<span class="tmt-check-done" title="<?php esc_attr_e( 'Already translated', 'tibetpress' ); ?>">&#10003;</span>
									<?php else : ?>
										<input type="checkbox" name="tmt_duplicate_langs[]" value="<?php echo esc_attr( $code ); ?>" class="tmt-dup-checkbox" title="<?php esc_attr_e( 'Duplicate this document', 'tibetpress' ); ?>" />
										<a href="<?php echo esc_url( $duplicate_url ); ?>" class="tmt-quick-dup-link" title="<?php esc_attr_e( 'Instant Duplicate', 'tibetpress' ); ?>">
											<span class="dashicons dashicons-admin-page"></span>
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Action button that appears when duplicate checkbox is checked -->
				<div class="tmt-dup-action-bar" id="tmt-dup-action-bar" style="display:none;padding:10px;background:#f8fafc;border-top:1px solid #e2e8f0;">
					<button type="submit" name="tmt_meta_action" value="duplicate_checked" class="button button-primary button-small" style="width:100%;font-weight:600;">
						<?php esc_html_e( 'Duplicate Selected Document', 'tibetpress' ); ?>
					</button>
				</div>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			const toggleBtn = document.getElementById('tmt-toggle-connect');
			const drawer = document.getElementById('tmt-connect-drawer');
			if (toggleBtn && drawer) {
				toggleBtn.addEventListener('click', function(e) {
					e.preventDefault();
					drawer.style.display = (drawer.style.display === 'none' || drawer.style.display === '') ? 'block' : 'none';
				});
			}

			const checkboxes = document.querySelectorAll('.tmt-dup-checkbox');
			const actionBar = document.getElementById('tmt-dup-action-bar');
			function checkDupes() {
				let anyChecked = false;
				checkboxes.forEach(function(cb) {
					if (cb.checked) anyChecked = true;
				});
				if (actionBar) {
					actionBar.style.display = anyChecked ? 'block' : 'none';
				}
			}
			checkboxes.forEach(function(cb) {
				cb.addEventListener('change', checkDupes);
			});
		});
		</script>
		<?php
	}

	/**
	 * Save Post Language & Translation Group Metadata
	 *
	 * Handles standard save, connection, and duplicate requests.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_translation_metadata( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['tmt_lang_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmt_lang_nonce'] ) ), 'tmt_save_post_lang' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$supported_languages = $this->get_enabled_languages();
		$current_post_lang = get_post_meta( $post_id, '_tb_lang_code', true ) ?: $this->get_base_language();

		// Save language code
		if ( isset( $_POST['tb_lang_code'] ) ) {
			$lang_code = sanitize_key( wp_unslash( $_POST['tb_lang_code'] ) );
			if ( isset( $supported_languages[ $lang_code ] ) ) {
				update_post_meta( $post_id, '_tb_lang_code', $lang_code );
				$current_post_lang = $lang_code;
			}
		}

		// Save translation group ID (or generate a unique one if empty)
		$group_id = ! empty( $_POST['tb_lang_group_id'] ) ? sanitize_text_field( wp_unslash( $_POST['tb_lang_group_id'] ) ) : '';
		if ( empty( $group_id ) ) {
			$group_id = 'grp_' . wp_generate_uuid4();
		}
		update_post_meta( $post_id, '_tb_lang_group_id', $group_id );

		// Connect an existing post if requested
		if ( isset( $_POST['tmt_meta_action'] ) && 'connect_post' === $_POST['tmt_meta_action'] && ! empty( $_POST['tmt_link_existing_post'] ) ) {
			$link_id = absint( $_POST['tmt_link_existing_post'] );
			if ( $link_id && current_user_can( 'edit_post', $link_id ) ) {
				update_post_meta( $link_id, '_tb_lang_group_id', $group_id );
			}
		}

		// Invalidate group cache
		$cache_key = 'tmt_group_' . md5( (string) $group_id . ':' . $this->get_base_language() );
		wp_cache_delete( $cache_key, 'tmt_translations' );

		// Handle duplicate checked languages action
		if ( isset( $_POST['tmt_meta_action'] ) && 'duplicate_checked' === $_POST['tmt_meta_action'] && ! empty( $_POST['tmt_duplicate_langs'] ) && is_array( $_POST['tmt_duplicate_langs'] ) ) {
			$dupe_ids = [];
			foreach ( $_POST['tmt_duplicate_langs'] as $target_code ) {
				$code = sanitize_key( $target_code );
				if ( isset( $supported_languages[ $code ] ) && $code !== $current_post_lang ) {
					$new_id = $this->duplicate_post_to_language( $post_id, $code );
					if ( is_wp_error( $new_id ) ) {
						wp_die( esc_html( $new_id->get_error_message() ) );
					}
					$dupe_ids[] = $new_id;
				}
			}

			if ( 1 === count( $dupe_ids ) ) {
				// Redirect directly to the single duplicated draft
				wp_safe_redirect( admin_url( 'post.php?post=' . $dupe_ids[0] . '&action=edit&tmt_msg=duplicated' ) );
				exit;
			}
		}
	}

	/**
	 * Central Admin Action Dispatcher
	 */
	public function handle_admin_actions(): void {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'tmt_action_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'tibetpress' ) );
		}

		$task        = isset( $_GET['tmt_task'] ) ? sanitize_key( wp_unslash( $_GET['tmt_task'] ) ) : '';
		$source_id   = isset( $_GET['source_id'] ) ? absint( $_GET['source_id'] ) : 0;
		$target_lang = isset( $_GET['target_lang'] ) ? sanitize_key( wp_unslash( $_GET['target_lang'] ) ) : '';

		if ( ! $source_id || ! current_user_can( 'edit_post', $source_id ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tibetpress' ) );
		}

		if ( 'duplicate' === $task ) {
			$new_id = $this->duplicate_post_to_language( $source_id, $target_lang );
			if ( is_wp_error( $new_id ) ) {
				wp_die( esc_html( $new_id->get_error_message() ) );
			}
			wp_safe_redirect( admin_url( 'post.php?post=' . $new_id . '&action=edit&tmt_msg=duplicated' ) );
			exit;
		}

		if ( 'translate' === $task ) {
			$new_id = $this->create_blank_translation( $source_id, $target_lang );
			if ( is_wp_error( $new_id ) ) {
				wp_die( esc_html( $new_id->get_error_message() ) );
			}
			wp_safe_redirect( admin_url( 'post.php?post=' . $new_id . '&action=edit&tmt_msg=translated' ) );
			exit;
		}

		if ( 'unlink' === $task ) {
			delete_post_meta( $source_id, '_tb_lang_group_id' );
			wp_safe_redirect( admin_url( 'post.php?post=' . $source_id . '&action=edit&tmt_msg=unlinked' ) );
			exit;
		}

		wp_safe_redirect( admin_url( 'post.php?post=' . $source_id . '&action=edit' ) );
		exit;
	}

	/**
	 * Duplicate a Post to a Target Language (Title, Content, Taxonomies, Meta, Thumbnail)
	 *
	 * @param int    $source_post_id Source post ID.
	 * @param string $target_lang    Enabled target language code.
	 * @return int|WP_Error New post ID.
	 */
	public function duplicate_post_to_language( int $source_post_id, string $target_lang ): int|WP_Error {
		if ( ! isset( $this->get_enabled_languages()[ $target_lang ] ) ) {
			return new WP_Error( 'invalid_language', __( 'The requested language is not enabled.', 'tibetpress' ) );
		}

		$source_post = get_post( $source_post_id );
		if ( ! $source_post ) {
			return new WP_Error( 'not_found', __( 'Source post not found.', 'tibetpress' ) );
		}

		// Ensure group ID on source post
		$group_id = get_post_meta( $source_post_id, '_tb_lang_group_id', true );
		if ( empty( $group_id ) ) {
			$group_id = 'grp_' . wp_generate_uuid4();
			update_post_meta( $source_post_id, '_tb_lang_group_id', $group_id );
		}

		// Ensure language on source post
		$source_lang = get_post_meta( $source_post_id, '_tb_lang_code', true );
		if ( empty( $source_lang ) ) {
			$source_lang = $this->get_base_language();
			update_post_meta( $source_post_id, '_tb_lang_code', $source_lang );
		}

		// Check if translation already exists
		$existing_id = $this->get_translation( $source_post_id, $target_lang );
		if ( $existing_id && $existing_id !== $source_post_id ) {
			return $existing_id;
		}

		// Insert duplicate draft
		$new_post_id = wp_insert_post(
			[
				'post_title'     => $source_post->post_title,
				'post_content'   => $source_post->post_content,
				'post_excerpt'   => $source_post->post_excerpt,
				'post_status'    => 'draft',
				'post_type'      => $source_post->post_type,
				'post_author'    => get_current_user_id(),
				'comment_status' => $source_post->comment_status,
				'ping_status'    => $source_post->ping_status,
				'post_parent'    => $source_post->post_parent,
				'menu_order'     => $source_post->menu_order,
			],
			true
		);

		if ( is_wp_error( $new_post_id ) ) {
			return $new_post_id;
		}

		// Assign language and translation group ID
		update_post_meta( $new_post_id, '_tb_lang_code', $target_lang );
		update_post_meta( $new_post_id, '_tb_lang_group_id', $group_id );

		// Clone Taxonomies (Categories, Tags)
		$taxonomies = get_object_taxonomies( $source_post->post_type );
		foreach ( $taxonomies as $tax ) {
			$terms = wp_get_object_terms( $source_post_id, $tax, [ 'fields' => 'ids' ] );
			if ( is_wp_error( $terms ) ) {
				wp_delete_post( $new_post_id, true );
				return $terms;
			}
			if ( empty( $terms ) ) {
				continue;
			}

			$target_terms = [];
			foreach ( $terms as $term_id ) {
				$term_id = absint( $term_id );
				if ( in_array( $tax, [ 'category', 'post_tag' ], true ) ) {
					$target_term_id = $this->get_translated_taxonomy_term_id( $term_id, $tax, $target_lang );
					if ( is_wp_error( $target_term_id ) ) {
						wp_delete_post( $new_post_id, true );
						return $target_term_id;
					}
					$target_terms[] = $target_term_id;
				} else {
					$target_terms[] = $term_id;
				}
			}

			$term_assignment = wp_set_object_terms( $new_post_id, $target_terms, $tax );
			if ( is_wp_error( $term_assignment ) ) {
				wp_delete_post( $new_post_id, true );
				return $term_assignment;
			}
		}

		// Invalidate cache after the duplicate and its taxonomy relationships are ready.
		$cache_key = 'tmt_group_' . md5( (string) $group_id . ':' . $this->get_base_language() );
		wp_cache_delete( $cache_key, 'tmt_translations' );

		// Clone Featured Image
		$thumb_id = get_post_thumbnail_id( $source_post_id );
		if ( $thumb_id ) {
			set_post_thumbnail( $new_post_id, $thumb_id );
		}

		// Clone Custom Fields / Post Meta (except WordPress internals and translation keys)
		$custom_keys = get_post_custom_keys( $source_post_id );
		if ( ! empty( $custom_keys ) ) {
			foreach ( $custom_keys as $k ) {
				if ( in_array( $k, [ '_tb_lang_code', '_tb_lang_group_id', '_edit_lock', '_edit_last' ], true ) ) {
					continue;
				}
				$vals = get_post_custom_values( $k, $source_post_id );
				foreach ( $vals as $v ) {
					add_post_meta( $new_post_id, $k, maybe_unserialize( $v ) );
				}
			}
		}

		return $new_post_id;
	}

	/**
	 * Map a category or tag to its translated term when one is linked.
	 *
	 * If no translation exists, keep the original term as the intentional fallback.
	 *
	 * @param int    $term_id     Source term ID.
	 * @param string $taxonomy    Taxonomy name.
	 * @param string $target_lang Target language code.
	 * @return int|WP_Error Target term ID or lookup error.
	 */
	private function get_translated_taxonomy_term_id( int $term_id, string $taxonomy, string $target_lang ): int|WP_Error {
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'tmt_term_not_found', __( 'A source taxonomy term could not be loaded.', 'tibetpress' ) );
		}

		if ( '1' === get_term_meta( $term_id, '_tmt_term_shared', true ) ) {
			return $term_id;
		}

		$source_language = get_term_meta( $term_id, '_tmt_term_language', true ) ?: $this->get_base_language();
		if ( $source_language === $target_lang ) {
			return $term_id;
		}

		$group_id = get_term_meta( $term_id, '_tmt_term_translation_group', true );
		if ( empty( $group_id ) ) {
			return $term_id;
		}

		$language_query = [
			'relation' => 'OR',
			[
				'key'     => '_tmt_term_language',
				'value'   => $target_lang,
				'compare' => '=',
			],
		];
		if ( $target_lang === $this->get_base_language() ) {
			$language_query[] = [
				'key'     => '_tmt_term_language',
				'compare' => 'NOT EXISTS',
			];
		}

		$translated_terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
				'number'     => 1,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
				'meta_query' => [
					'relation' => 'AND',
					[
						'key'     => '_tmt_term_translation_group',
						'value'   => $group_id,
						'compare' => '=',
					],
					$language_query,
				],
			]
		);
		if ( is_wp_error( $translated_terms ) ) {
			return new WP_Error( 'tmt_term_translation_lookup_failed', __( 'A translated taxonomy term could not be loaded.', 'tibetpress' ) );
		}

		return ! empty( $translated_terms ) ? absint( $translated_terms[0] ) : $term_id;
	}

	/**
	 * Create a Blank Translation Draft
	 *
	 * @param int    $source_post_id Source post ID.
	 * @param string $target_lang    Enabled target language code.
	 * @return int|WP_Error
	 */
	public function create_blank_translation( int $source_post_id, string $target_lang ): int|WP_Error {
		if ( ! isset( $this->get_enabled_languages()[ $target_lang ] ) ) {
			return new WP_Error( 'invalid_language', __( 'The requested language is not enabled.', 'tibetpress' ) );
		}

		$source_post = get_post( $source_post_id );
		if ( ! $source_post ) {
			return new WP_Error( 'not_found', __( 'Source post not found.', 'tibetpress' ) );
		}

		$group_id = get_post_meta( $source_post_id, '_tb_lang_group_id', true );
		if ( empty( $group_id ) ) {
			$group_id = 'grp_' . wp_generate_uuid4();
			update_post_meta( $source_post_id, '_tb_lang_group_id', $group_id );
		}

		$source_lang = get_post_meta( $source_post_id, '_tb_lang_code', true );
		if ( empty( $source_lang ) ) {
			$source_lang = $this->get_base_language();
			update_post_meta( $source_post_id, '_tb_lang_code', $source_lang );
		}

		$existing_id = $this->get_translation( $source_post_id, $target_lang );
		if ( $existing_id && $existing_id !== $source_post_id ) {
			return $existing_id;
		}

		$new_post_id = wp_insert_post(
			[
				'post_title'     => '',
				'post_content'   => '',
				'post_status'    => 'draft',
				'post_type'      => $source_post->post_type,
				'post_author'    => get_current_user_id(),
				'comment_status' => $source_post->comment_status,
				'ping_status'    => $source_post->ping_status,
			],
			true
		);

		if ( is_wp_error( $new_post_id ) ) {
			return $new_post_id;
		}

		update_post_meta( $new_post_id, '_tb_lang_code', $target_lang );
		update_post_meta( $new_post_id, '_tb_lang_group_id', $group_id );

		$cache_key = 'tmt_group_' . md5( (string) $group_id . ':' . $this->get_base_language() );
		wp_cache_delete( $cache_key, 'tmt_translations' );

		return $new_post_id;
	}

	/**
	 * Display Admin Notices for Translation/Duplication Events
	 */
	public function display_admin_notices(): void {
		if ( ! empty( $_GET['tmt_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$msg = sanitize_key( wp_unslash( $_GET['tmt_msg'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$text = match ( $msg ) {
				'duplicated' => __( 'Document successfully duplicated for translation. You can now edit the translation below.', 'tibetpress' ),
				'translated' => __( 'New translation draft created and linked.', 'tibetpress' ),
				'unlinked'   => __( 'Post successfully unlinked from translation group.', 'tibetpress' ),
				'connected'  => __( 'Post successfully connected to translation group.', 'tibetpress' ),
				default      => '',
			};
			if ( ! empty( $text ) ) {
				echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html( $text ) . '</strong></p></div>';
			}
		}
	}
}

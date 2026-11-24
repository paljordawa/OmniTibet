<?php
/**
 * TMT Language Switcher Trait methods.
 *
 * @package Tibetan_Multilingual_Typography
 */

defined( 'ABSPATH' ) || exit;

trait TMT_Language_Switcher_Trait {
	/**
	 * Render Frontend Language Switcher Shortcode
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function render_language_switcher_shortcode( array $atts = [] ): string {
		$settings = $this->get_settings();
		$parsed   = shortcode_atts(
			[
				'format'    => $settings['switcher_format'] ?? 'dropdown',
				'display'   => $settings['switcher_display'] ?? 'both',
				'flag_size' => $settings['switcher_flag_size'] ?? 'small',
			],
			$atts,
			'tibetan_lang_switcher'
		);

		return $this->get_language_switcher_html( $parsed );
	}

	/**
	 * Generate Language Switcher HTML
	 *
	 * Supports:
	 * - formats: 'dropdown', 'toggle', 'radio', 'list', 'inline'
	 * - displays: 'both', 'icon_only', 'text_only'
	 * - flag_size: 'small', 'medium', 'large'
	 *
	 * @param string|array<string, mixed> $args Format string or associative array of options.
	 * @return string
	 */
	public function get_language_switcher_html( string|array $args = [] ): string {
		if ( is_string( $args ) ) {
			$args = [ 'format' => $args ];
		}

		$settings = $this->get_settings();

		$format = ! empty( $args['format'] ) ? sanitize_key( $args['format'] ) : ( $settings['switcher_format'] ?? 'dropdown' );
		if ( ! in_array( $format, [ 'dropdown', 'toggle', 'radio', 'list', 'inline' ], true ) ) {
			$format = 'dropdown';
		}

		$display = ! empty( $args['display'] ) ? sanitize_key( $args['display'] ) : ( $settings['switcher_display'] ?? 'both' );
		if ( in_array( $display, [ 'icon', 'icons', 'flag', 'flags', 'icon_only', 'flag_only' ], true ) ) {
			$display = 'icon_only';
		} elseif ( in_array( $display, [ 'text', 'texts', 'name', 'names', 'text_only' ], true ) ) {
			$display = 'text_only';
		} else {
			$display = 'both';
		}

		$flag_size = ! empty( $args['flag_size'] ) ? sanitize_key( $args['flag_size'] ) : ( $settings['switcher_flag_size'] ?? 'small' );
		$dimensions = match ( $flag_size ) {
			'large'  => [ 'w' => 28, 'h' => 19 ],
			'medium' => [ 'w' => 22, 'h' => 15 ],
			default  => [ 'w' => 16, 'h' => 11 ],
		};

		$current_lang = $this->get_current_language();
		$post_id      = ( isset( $GLOBALS['wp_query'] ) && function_exists( 'is_singular' ) && is_singular() ) ? get_the_ID() : 0;
		$fallback     = $settings['fallback_language'] ?? $this->get_base_language();
		$supported_languages = $this->get_enabled_languages();
		if ( ! isset( $supported_languages[ $current_lang ] ) ) {
			$current_lang = $this->get_base_language();
		}
		if ( ! isset( $supported_languages[ $current_lang ] ) ) {
			$current_lang = (string) array_key_first( $supported_languages );
		}
		$current_info = $supported_languages[ $current_lang ] ?? null;
		if ( ! is_array( $current_info ) ) {
			return '';
		}
		$hidden_languages = is_array( $settings['hidden_languages'] ?? null ) ? $settings['hidden_languages'] : [];

		// Build target URLs
		$items = [];
		foreach ( $supported_languages as $code => $info ) {
			if ( in_array( $code, $hidden_languages, true ) ) {
				continue;
			}
			$url = '';
			if ( $post_id ) {
				if ( $code === $current_lang ) {
					$url = get_permalink( $post_id );
				} else {
					$translated_id = $this->get_translation( $post_id, $code );
					if ( $translated_id ) {
						$url = get_permalink( $translated_id );
					} elseif ( $code === $fallback ) {
						$post_language = get_post_meta( $post_id, '_tb_lang_code', true ) ?: $this->get_base_language();
						$url = $post_language === $fallback
							? get_permalink( $post_id )
							: $this->get_language_home_url( $fallback );
					} else {
						$url = $this->get_language_home_url( $code );
					}
				}
			} else {
				$url = $this->get_language_home_url( $code );
			}

			$items[ $code ] = [
				'url'    => $url,
				'name'   => $info['name'],
				'native' => $info['native'],
				'active' => ( $code === $current_lang ),
			];
		}
		if ( empty( $items ) ) {
			return '';
		}

		$wrapper_classes = [
			'tmt-language-switcher',
			'tmt-' . $format,
			'tmt-disp-' . $display,
			'tmt-size-' . $flag_size,
		];

		ob_start();

		if ( 'toggle' === $format ) : ?>
			<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" role="navigation" aria-label="<?php esc_attr_e( 'Language Selection', 'tibetpress' ); ?>">
				<?php foreach ( $items as $code => $item ) : ?>
					<a href="<?php echo esc_url( $item['url'] ); ?>" class="tmt-toggle-btn <?php echo $item['active'] ? 'active' : ''; ?>" hreflang="<?php echo esc_attr( $code ); ?>" title="<?php echo esc_attr( $item['native'] . ' (' . $item['name'] . ')' ); ?>">
						<?php if ( 'text_only' !== $display ) : ?>
							<span class="tmt-flag-wrap"><?php echo $this->get_flag_html( $code, $dimensions['w'], $dimensions['h'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
						<?php if ( 'icon_only' !== $display ) : ?>
							<span class="tmt-toggle-label"><?php echo esc_html( $item['native'] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php elseif ( 'radio' === $format ) : ?>
			<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" role="radiogroup" aria-label="<?php esc_attr_e( 'Language Selection', 'tibetpress' ); ?>">
				<?php foreach ( $items as $code => $item ) :
					$input_id = 'tmt_radio_' . esc_attr( $code ) . '_' . wp_unique_id();
				?>
					<label for="<?php echo esc_attr( $input_id ); ?>" class="tmt-radio-label <?php echo $item['active'] ? 'active' : ''; ?>" title="<?php echo esc_attr( $item['native'] . ' (' . $item['name'] . ')' ); ?>">
						<input type="radio" id="<?php echo esc_attr( $input_id ); ?>" name="tmt_radio_lang_grp" value="<?php echo esc_url( $item['url'] ); ?>" <?php checked( $item['active'] ); ?> onchange="if(this.value){window.location.href=this.value;}" />
						<?php if ( 'text_only' !== $display ) : ?>
							<span class="tmt-flag-wrap"><?php echo $this->get_flag_html( $code, $dimensions['w'], $dimensions['h'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
						<?php if ( 'icon_only' !== $display ) : ?>
							<span class="tmt-radio-text"><?php echo esc_html( $item['native'] ); ?></span>
						<?php endif; ?>
					</label>
				<?php endforeach; ?>
			</div>
		<?php elseif ( 'list' === $format ) : ?>
			<ul class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" role="navigation" aria-label="<?php esc_attr_e( 'Language Selection', 'tibetpress' ); ?>">
				<?php foreach ( $items as $code => $item ) : ?>
					<li class="tmt-list-item <?php echo $item['active'] ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( $item['url'] ); ?>" class="tmt-list-link" hreflang="<?php echo esc_attr( $code ); ?>" title="<?php echo esc_attr( $item['native'] . ' (' . $item['name'] . ')' ); ?>">
							<span class="tmt-item-inner">
								<?php if ( 'text_only' !== $display ) : ?>
									<span class="tmt-flag-wrap"><?php echo $this->get_flag_html( $code, $dimensions['w'], $dimensions['h'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<?php endif; ?>
								<?php if ( 'icon_only' !== $display ) : ?>
									<span class="tmt-list-text"><?php echo esc_html( $item['native'] ); ?></span>
								<?php endif; ?>
							</span>
							<?php if ( $item['active'] ) : ?>
								<span class="tmt-list-badge" aria-hidden="true">&#10003;</span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( 'inline' === $format ) : ?>
			<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" role="navigation" aria-label="<?php esc_attr_e( 'Language Selection', 'tibetpress' ); ?>">
				<?php
				$total_items = count( $items );
				$curr_idx    = 0;
				foreach ( $items as $code => $item ) :
					$curr_idx++;
				?>
					<a href="<?php echo esc_url( $item['url'] ); ?>" class="tmt-inline-link <?php echo $item['active'] ? 'active' : ''; ?>" hreflang="<?php echo esc_attr( $code ); ?>" title="<?php echo esc_attr( $item['native'] . ' (' . $item['name'] . ')' ); ?>">
						<?php if ( 'text_only' !== $display ) : ?>
							<span class="tmt-flag-wrap"><?php echo $this->get_flag_html( $code, $dimensions['w'], $dimensions['h'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
						<?php if ( 'icon_only' !== $display ) : ?>
							<span class="tmt-inline-text"><?php echo esc_html( $item['native'] ); ?></span>
						<?php endif; ?>
					</a>
					<?php if ( $curr_idx < $total_items ) : ?>
						<span class="tmt-inline-divider" aria-hidden="true">|</span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" data-tmt-switcher role="navigation" aria-label="<?php esc_attr_e( 'Language Selection', 'tibetpress' ); ?>">
				<button class="tmt-switcher-current" aria-haspopup="true" aria-expanded="false" type="button" title="<?php echo esc_attr( $current_info['native'] ); ?>">
					<?php if ( 'text_only' !== $display ) : ?>
						<span class="tmt-flag-wrap"><?php echo $this->get_flag_html( $current_lang, $dimensions['w'], $dimensions['h'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php endif; ?>
					<?php if ( 'icon_only' !== $display ) : ?>
						<span class="tmt-lang-label" style="margin-left:5px;"><?php echo esc_html( $current_info['native'] ); ?></span>
					<?php endif; ?>
					<span class="tmt-arrow" aria-hidden="true">&#9662;</span>
				</button>
				<ul class="tmt-switcher-menu" role="menu">
					<?php foreach ( $items as $code => $item ) : ?>
						<li role="none">
							<a role="menuitem" href="<?php echo esc_url( $item['url'] ); ?>" class="tmt-item <?php echo $item['active'] ? 'active' : ''; ?>" hreflang="<?php echo esc_attr( $code ); ?>" title="<?php echo esc_attr( $item['native'] . ' (' . $item['name'] . ')' ); ?>">
								<span class="tmt-item-inner">
									<?php if ( 'text_only' !== $display ) : ?>
										<span class="tmt-flag-wrap"><?php echo $this->get_flag_html( $code, $dimensions['w'], $dimensions['h'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<?php endif; ?>
									<?php if ( 'icon_only' !== $display ) : ?>
										<span class="tmt-item-name"><?php echo esc_html( $item['native'] ); ?></span>
									<?php endif; ?>
								</span>
								<?php if ( $item['active'] ) : ?>
									<span class="tmt-check" aria-hidden="true">&#10003;</span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif;
		return (string) ob_get_clean();
	}
}

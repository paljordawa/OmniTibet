<?php
/**
 * OMNITI Syllable Wrap Trait methods.
 *
 * @package OmniTibet
 */

defined( 'ABSPATH' ) || exit;

trait OMNITI_Syllable_Wrap_Trait {
	/* ==========================================================================
	   MODULE D: Frontend Syllable Wrap Engine (Regex Fixes)
	   ========================================================================== */

	public function filter_tibetan_syllable_wrap( string $content ): string {
		if ( 'bo' !== $this->get_current_language() ) {
			return $content;
		}

		$settings = $this->get_settings();
		if ( empty( $settings['syllable_wrap'] ) || empty( $content ) ) {
			return $content;
		}

		$parts = preg_split(
			'/(<script[\s\S]*?<\/script>|<style[\s\S]*?<\/style>|<pre[\s\S]*?<\/pre>|<code[\s\S]*?<\/code>|<[^>]+>)/iu',
			$content,
			-1,
			PREG_SPLIT_DELIM_CAPTURE
		);

		if ( ! is_array( $parts ) ) {
			return $content;
		}

		$output = '';
		foreach ( $parts as $part ) {
			if ( ! empty( $part ) && ( '<' === $part[0] ) ) {
				$output .= $part;
				continue;
			}

			// In text chunks, insert zero-width space &#8203; after tsheg (U+0F0B)
			$modified = preg_replace( '/\x{0F0B}(?!(&#8203;|\x{200B}))/u', "་&#8203;", $part );

			// Also append after Tibetan shad (U+0F0D / །)
			if ( null !== $modified ) {
				$modified = preg_replace( '/\x{0F0D}(?!(&#8203;|\x{200B}))/u', "།&#8203;", $modified );
			}

			$output .= ( null !== $modified ) ? $modified : $part;
		}

		return $output;
	}
}

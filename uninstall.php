<?php
/**
 * OmniTibet
 * Uninstallation Script
 *
 * @package OmniTibet
 * @version 1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options
delete_option( 'omniti_settings' );
delete_option( 'omniti_rewrite_version' );
delete_option( 'omniti_registered_strings' );
delete_option( 'omniti_language_menus' );
delete_option( 'tmt_settings' );
delete_option( 'tmt_rewrite_version' );
delete_option( 'tmt_registered_strings' );
delete_option( 'tmt_language_menus' );

// Delete transients
delete_transient( 'omniti_translation_groups' );
delete_transient( 'tmt_translation_groups' );

// Clean up post meta if requested or needed
// delete_post_meta_by_key( '_omniti_lang_code' );
// delete_post_meta_by_key( '_omniti_lang_group_id' );
// delete_post_meta_by_key( '_tb_lang_code' );
// delete_post_meta_by_key( '_tb_lang_group_id' );

<?php
/**
 * OmniTibet
 * Uninstallation Script
 *
 * @package Tibetan_Multilingual_Typography
 * @version 1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options
delete_option( 'tmt_settings' );

// Delete transients
delete_transient( 'tmt_translation_groups' );

// Clean up post meta if requested or needed
// delete_post_meta_by_key( '_tb_lang_code' );
// delete_post_meta_by_key( '_tb_lang_group_id' );

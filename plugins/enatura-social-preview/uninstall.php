<?php
// Settings are retained on deactivation; removed only on explicit uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
if ( is_multisite() ) {
    // Avoid unbounded network-wide database work. Remove per-site settings before deletion if needed.
    return;
}
delete_option( 'enatura_social_preview' );

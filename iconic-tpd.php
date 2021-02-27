<?php
/**
 * Plugin Name: Temporarily Disable Plugins by Iconic
 * Plugin URI: https://iconicwp.com/
 * Description: A "must-use" plugin which allows you to temporarily disable plugins via the admin bar.
 * Version: 1.0.0
 * Author: Iconic
 * Author URI: https://iconicwp.com
 * Text Domain: iconic-tdp
 */

/**
 * Deactivate plugins temporarily.
 *
 * @param $active_plugins
 * @param $option_name
 *
 * @return mixed
 */
function iconic_tpd_option_active_plugins( $active_plugins, $option_name ) {
	if ( ! iconic_tpd_is_enabled() ) {
		return $active_plugins;
	}

	static $new_active_plugins = null;

	if ( ! is_null( $new_active_plugins ) ) {
		// Return normal list of active plugins after
		// first time, to avoid conflicts.
		return $active_plugins;
	}

	$to_remove = iconic_tpd_get_disabled_plugins();

	$new_active_plugins = $active_plugins;

	foreach ( $active_plugins as $key => $value ) {
		if ( in_array( $value, $to_remove, true ) ) {
			unset( $new_active_plugins[ $key ] );
		}
	}

	return $new_active_plugins;
}

add_filter( 'option_active_plugins', 'iconic_tpd_option_active_plugins', 999, 2 );

/**
 * Add versions to admin bar.
 */
function iconic_tpd_active_plugins_menu() {
	if ( ! iconic_tpd_is_enabled() ) {
		return;
	}

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	global $wp_admin_bar, $wp_version;

	$menu_id                   = 'iconic-active-plugins';
	$plugins                   = get_plugins();
	$active_plugins            = get_option( 'active_plugins' );
	$disabled_plugins          = iconic_tpd_get_disabled_plugins();
	$unfiltered_active_plugins = iconic_tpd_get_unfiltered_active_plugins( $active_plugins );

	$wp_admin_bar->add_menu( array( 'id' => $menu_id, 'title' => __( 'Plugins', 'iconic-tdp' ), 'href' => '' ) );

	foreach ( $plugins as $path => $plugin ) {
		if ( ! in_array( $path, $unfiltered_active_plugins, true ) ) {
			continue;
		}

		$is_active = ! in_array( $path, $disabled_plugins, true );
		$title     = $is_active ? sprintf( '<span>%s</span>', $plugin['Name'] ) : sprintf( '<del>%s</del>', $plugin['Name'] );
		$href      = iconic_tpd_get_action_url( $path, $is_active ? 'disable' : 'enable' );

		$wp_admin_bar->add_menu( array( 'parent' => $menu_id, 'title' => $title, 'id' => sanitize_title( $plugin['Name'] ), 'href' => $href ) );
	}
}

add_action( 'admin_bar_menu', 'iconic_tpd_active_plugins_menu', 5000 );

/**
 * Get action URL.
 *
 * @param $plugin_path
 * @param $type
 *
 * @return string
 */
function iconic_tpd_get_action_url( $plugin_path, $type ) {
	$original_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

	$url = $original_url;
	$url = add_query_arg( 'url', $original_url, $url );
	$url = add_query_arg( 'action', 'iconic-tpd-set', $url );
	$url = add_query_arg( 'plugin_path', $plugin_path, $url );
	$url = add_query_arg( 'type', $type, $url );

	return add_query_arg( '_wpnonce', wp_create_nonce( 'iconic-tpd' ), $url );
}

/**
 * Get unfiltered_active_plugins.
 *
 * @return mixed|void
 */
function iconic_tpd_get_unfiltered_active_plugins( $active_plugins = array() ) {
	global $wpdb;

	static $active_plugins = array();

	if ( ! empty( $active_plugins ) ) {
		return $active_plugins;
	}

	$active_plugins_var = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'active_plugins'" );

	if ( empty( $active_plugins_var ) || is_wp_error( $active_plugins_var ) ) {
		return $active_plugins;
	}

	$active_plugins = maybe_unserialize( $active_plugins_var );

	return (array) $active_plugins;
}

/**
 * Add scroll to admin menu.
 */
function iconic_tpd_header_styles() {
	if ( ! iconic_tpd_is_enabled() ) {
		return;
	}

	?>
	<style type="text/css">
		.ab-sub-wrapper {
			max-height: 402px;
			overflow-y: scroll;
		}

		#wp-admin-bar-iconic-active-plugins .ab-sub-wrapper a:hover span {
			text-decoration: line-through;
		}

		#wp-admin-bar-iconic-active-plugins .ab-sub-wrapper a del {
			text-decoration: line-through;
			opacity: 0.5;
		}

		#wp-admin-bar-iconic-active-plugins .ab-sub-wrapper a:hover del {
			text-decoration: none;
			opacity: 1;
		}
	</style>
	<?php
}

add_action( 'wp_head', 'iconic_tpd_header_styles' );
add_action( 'admin_head', 'iconic_tpd_header_styles' );

/**
 * Process action URL.
 */
function iconic_tpd_process_action() {
	if ( ! iconic_tpd_is_enabled() ) {
		return;
	}

	$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

	if ( 'iconic-tpd-set' !== $action ) {
		return;
	}

	$nonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING );

	if ( ! wp_verify_nonce( $nonce, 'iconic-tpd' ) ) {
		die( __( 'Nope!', 'iconic-tdp' ) );
	}

	$url         = filter_input( INPUT_GET, 'url', FILTER_SANITIZE_STRING );
	$plugin_path = filter_input( INPUT_GET, 'plugin_path', FILTER_SANITIZE_STRING );
	$type        = filter_input( INPUT_GET, 'type', FILTER_SANITIZE_STRING );

	$temporary_deactivations = iconic_tpd_get_disabled_plugins();

	if ( 'enable' === $type && ! empty( $temporary_deactivations ) ) {
		foreach ( $temporary_deactivations as $key => $value ) {
			if ( $plugin_path !== $value ) {
				continue;
			}

			unset( $temporary_deactivations[ $key ] );
		}
	} elseif ( 'disable' === $type ) {
		$temporary_deactivations[] = $plugin_path;
	}

	update_option( 'iconic_tpd', $temporary_deactivations );

	wp_safe_redirect( $url );
	exit;
}

add_action( 'template_redirect', 'iconic_tpd_process_action' );
add_action( 'admin_init', 'iconic_tpd_process_action' );

/**
 * Get disabled_plugins.
 *
 * @return array
 */
function iconic_tpd_get_disabled_plugins() {
	return array_filter( get_option( 'iconic_tpd', array() ) );
}

/**
 * Check if TPD should be enabled.
 *
 * @return bool
 */
function iconic_tpd_is_enabled() {
	if ( ! function_exists( 'wp_get_current_user' ) ) {
		include ABSPATH . 'wp-includes/pluggable.php';
	}

	return current_user_can( 'administrator' ) && strpos( $_SERVER['REQUEST_URI'], '/wp-admin/plugins.php' ) === false;
}

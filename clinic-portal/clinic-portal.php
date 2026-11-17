<?php
/**
 * Plugin Name:       Clinic Portal
 * Description:       Medical clinic portal: physician directory, departments, appointment booking, patient registration with automatic age calculation, staff patient dashboard, FAQ, announcements, contact page and Crisp live chat.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Clinic Portal
 * License:           GPL-2.0-or-later
 * Text Domain:       clinic-portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CLINIC_PORTAL_VERSION', '1.0.0' );
define( 'CLINIC_PORTAL_FILE', __FILE__ );
define( 'CLINIC_PORTAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'CLINIC_PORTAL_URL', plugin_dir_url( __FILE__ ) );

require_once CLINIC_PORTAL_DIR . 'includes/helpers.php';
require_once CLINIC_PORTAL_DIR . 'includes/icons.php';
require_once CLINIC_PORTAL_DIR . 'includes/post-types.php';
require_once CLINIC_PORTAL_DIR . 'includes/roles.php';
require_once CLINIC_PORTAL_DIR . 'includes/settings.php';
require_once CLINIC_PORTAL_DIR . 'includes/handlers.php';
require_once CLINIC_PORTAL_DIR . 'includes/shortcodes.php';
require_once CLINIC_PORTAL_DIR . 'includes/seed.php';

/**
 * Activation: register types, roles, seed content, flush rewrites.
 */
function clinic_portal_activate() {
	clinic_register_post_types();
	clinic_add_roles();
	clinic_portal_seed( true );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'clinic_portal_activate' );

function clinic_portal_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'clinic_portal_deactivate' );

/**
 * Keep roles and capabilities in sync after plugin updates.
 */
function clinic_portal_maybe_upgrade() {
	if ( get_option( 'clinic_portal_version' ) !== CLINIC_PORTAL_VERSION ) {
		clinic_add_roles();
		update_option( 'clinic_portal_version', CLINIC_PORTAL_VERSION );
	}
}
add_action( 'init', 'clinic_portal_maybe_upgrade', 20 );

/**
 * Front-end assets.
 */
function clinic_portal_assets() {
	wp_enqueue_style( 'clinic-portal', CLINIC_PORTAL_URL . 'assets/clinic.css', array(), CLINIC_PORTAL_VERSION );
	wp_enqueue_script( 'clinic-portal', CLINIC_PORTAL_URL . 'assets/clinic.js', array(), CLINIC_PORTAL_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'clinic_portal_assets' );

/**
 * Crisp live chat widget, printed in the site footer.
 */
function clinic_portal_crisp_script() {
	$id = clinic_opt( 'crisp_id' );
	if ( ! $id || ! preg_match( '/^[0-9a-fA-F\-]{36}$/', $id ) ) {
		return;
	}
	?>
<script type="text/javascript">
window.$crisp=[];window.CRISP_WEBSITE_ID="<?php echo esc_js( $id ); ?>";
(function(){var d=document,s=d.createElement("script");s.src="https://client.crisp.chat/l.js";s.async=1;d.getElementsByTagName("head")[0].appendChild(s);})();
</script>
	<?php
}
add_action( 'wp_footer', 'clinic_portal_crisp_script', 50 );

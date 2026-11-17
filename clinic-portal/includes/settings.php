<?php
/**
 * Settings page: Settings > Clinic Portal.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function clinic_settings_menu() {
	add_options_page( 'Clinic Portal', 'Clinic Portal', 'manage_options', 'clinic-portal', 'clinic_settings_page' );
}
add_action( 'admin_menu', 'clinic_settings_menu' );

function clinic_settings_register() {
	register_setting(
		'clinic_portal',
		'clinic_portal_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'clinic_settings_sanitize',
			'default'           => clinic_defaults(),
		)
	);
}
add_action( 'admin_init', 'clinic_settings_register' );

function clinic_settings_sanitize( $in ) {
	$d   = clinic_defaults();
	$out = array();
	$in  = is_array( $in ) ? $in : array();

	foreach ( array( 'clinic_name', 'phone', 'emergency_phone', 'address' ) as $k ) {
		$out[ $k ] = isset( $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : $d[ $k ];
	}
	$out['email'] = isset( $in['email'] ) && is_email( $in['email'] ) ? sanitize_email( $in['email'] ) : $d['email'];

	$out['id_prefix'] = isset( $in['id_prefix'] ) ? strtoupper( substr( preg_replace( '/[^A-Za-z0-9]/', '', $in['id_prefix'] ), 0, 6 ) ) : $d['id_prefix'];
	if ( '' === $out['id_prefix'] ) {
		$out['id_prefix'] = $d['id_prefix'];
	}

	foreach ( array( 'open_time', 'close_time', 'sat_close' ) as $k ) {
		$out[ $k ] = ( isset( $in[ $k ] ) && preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $in[ $k ] ) ) ? $in[ $k ] : $d[ $k ];
	}
	$out['sunday_open']  = ! empty( $in['sunday_open'] ) ? '1' : '0';
	$out['slot_minutes'] = isset( $in['slot_minutes'] ) && in_array( (string) $in['slot_minutes'], array( '15', '20', '30', '45', '60' ), true ) ? (string) $in['slot_minutes'] : $d['slot_minutes'];

	$lat = isset( $in['map_lat'] ) ? (float) $in['map_lat'] : (float) $d['map_lat'];
	$lng = isset( $in['map_lng'] ) ? (float) $in['map_lng'] : (float) $d['map_lng'];
	$out['map_lat'] = ( $lat >= -90 && $lat <= 90 ) ? (string) $lat : $d['map_lat'];
	$out['map_lng'] = ( $lng >= -180 && $lng <= 180 ) ? (string) $lng : $d['map_lng'];

	$crisp = isset( $in['crisp_id'] ) ? trim( $in['crisp_id'] ) : '';
	$out['crisp_id'] = preg_match( '/^[0-9a-fA-F\-]{36}$/', $crisp ) ? $crisp : '';

	$out['video_url'] = isset( $in['video_url'] ) ? esc_url_raw( $in['video_url'] ) : '';
	$out['audio_url'] = isset( $in['audio_url'] ) ? esc_url_raw( $in['audio_url'] ) : '';

	do_action( 'clinic_portal_options_saved' );
	return $out;
}

function clinic_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o    = wp_parse_args( (array) get_option( 'clinic_portal_options', array() ), clinic_defaults() );
	$name = 'clinic_portal_options';
	$text = function ( $key, $label, $type = 'text', $help = '' ) use ( $o, $name ) {
		echo '<tr><th scope="row"><label for="cp_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="cp_' . esc_attr( $key ) . '" name="' . esc_attr( $name . '[' . $key . ']' ) . '" value="' . esc_attr( $o[ $key ] ) . '">';
		if ( $help ) {
			echo '<p class="description">' . wp_kses_post( $help ) . '</p>';
		}
		echo '</td></tr>';
	};
	?>
	<div class="wrap">
		<h1>Clinic Portal</h1>
		<?php if ( isset( $_GET['cp_seeded'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Missing pages and sample content were created. Existing content was left untouched.</p></div>
		<?php endif; ?>
		<p>The pages, physicians, departments, FAQs and announcements created on activation are <strong>sample content</strong>. Edit or replace them before you go live.</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'clinic_portal' ); ?>

			<h2>Clinic details</h2>
			<table class="form-table" role="presentation">
				<?php
				$text( 'clinic_name', 'Clinic name' );
				$text( 'phone', 'Main phone' );
				$text( 'emergency_phone', 'Emergency hotline' );
				$text( 'email', 'Email', 'email' );
				$text( 'address', 'Address' );
				$text( 'id_prefix', 'Patient ID prefix', 'text', 'Up to 6 letters or numbers. Patient IDs look like <code>' . esc_html( $o['id_prefix'] ) . '-' . esc_html( wp_date( 'Y' ) ) . '-7K2M9Q</code>.' );
				$text( 'map_lat', 'Map latitude' );
				$text( 'map_lng', 'Map longitude' );
				?>
			</table>

			<h2>Opening hours and appointment slots</h2>
			<table class="form-table" role="presentation">
				<?php
				$text( 'open_time', 'Opening time', 'time' );
				$text( 'close_time', 'Closing time, Monday to Friday', 'time' );
				$text( 'sat_close', 'Closing time, Saturday', 'time' );
				?>
				<tr><th scope="row">Sunday</th><td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[sunday_open]" value="1" <?php checked( $o['sunday_open'], '1' ); ?>> Open on Sundays (uses Saturday hours)</label></td></tr>
				<tr><th scope="row"><label for="cp_slot_minutes">Appointment length</label></th><td>
					<select id="cp_slot_minutes" name="<?php echo esc_attr( $name ); ?>[slot_minutes]">
						<?php foreach ( array( '15', '20', '30', '45', '60' ) as $m ) : ?>
							<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $o['slot_minutes'], $m ); ?>><?php echo esc_html( $m ); ?> minutes</option>
						<?php endforeach; ?>
					</select></td></tr>
			</table>

			<h2>Live chat (Crisp)</h2>
			<table class="form-table" role="presentation">
				<?php $text( 'crisp_id', 'Crisp Website ID', 'text', 'Paste the ID from Crisp (Settings, Website Settings, Setup instructions). It looks like <code>xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx</code>. Leave empty to turn the chat off.' ); ?>
			</table>
			<div class="card" style="max-width:780px">
				<h3>How to connect Crisp</h3>
				<ol>
					<li>Create a free account at <a href="https://crisp.chat" target="_blank" rel="noopener">crisp.chat</a> and add your clinic as a website.</li>
					<li>In Crisp open <em>Settings, Website Settings, Setup instructions</em> and copy the <strong>Website ID</strong>.</li>
					<li>Paste it into the field above and save. The chat bubble appears on every page, in the footer area.</li>
					<li>In Crisp, set up the <em>Chatbox</em> hours and the <em>Automated responses</em> (Chatbot scenarios, away messages, quick replies for opening hours and booking) so patients get an instant reply even when no agent is online. Install the Crisp mobile app so staff can answer from their phones.</li>
					<li>Remind staff not to ask for diagnoses or sensitive health details in chat. Direct patients to the registration form and the secure staff dashboard instead.</li>
				</ol>
				<p>The plugin prints this script in the footer once the ID is saved, so you do not need to paste any code:</p>
				<pre style="white-space:pre-wrap;background:#f6f7f7;padding:12px;border:1px solid #dcdcde">window.$crisp=[];window.CRISP_WEBSITE_ID="YOUR-WEBSITE-ID";
(function(){var d=document,s=d.createElement("script");s.src="https://client.crisp.chat/l.js";s.async=1;d.getElementsByTagName("head")[0].appendChild(s);})();</pre>
			</div>

			<h2>Media placeholders</h2>
			<table class="form-table" role="presentation">
				<?php
				$text( 'video_url', 'Welcome video URL', 'url', 'A direct link to an MP4 file in your Media Library, or a YouTube or Vimeo link. Empty shows a styled placeholder.' );
				$text( 'audio_url', 'Health tips audio URL', 'url', 'A direct link to an MP3 file in your Media Library. Empty shows a styled placeholder.' );
				?>
			</table>

			<?php submit_button(); ?>
		</form>

		<hr>
		<h2>Tools</h2>
		<p>Re-create any missing pages, menu items and sample content (nothing that exists is overwritten).</p>
		<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=clinic_seed' ), 'clinic_seed' ) ); ?>">Create missing pages and content</a></p>

		<h2>Staff accounts</h2>
		<p>Create users with the <strong>Clinic Doctor</strong> or <strong>Clinic Nurse</strong> role under Users, Add New. They sign in on the Patient Management Dashboard page and can review registered patient records. Only administrators can delete records.</p>
	</div>
	<?php
}

function clinic_seed_action() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'clinic_seed' ) ) {
		wp_die( 'Not allowed.' );
	}
	clinic_portal_seed( false );
	flush_rewrite_rules();
	wp_safe_redirect( admin_url( 'options-general.php?page=clinic-portal&cp_seeded=1' ) );
	exit;
}
add_action( 'admin_post_clinic_seed', 'clinic_seed_action' );

<?php
/**
 * Forms and staff dashboard: patient registration, booking, dashboard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Field renderer                                                      */
/* ------------------------------------------------------------------ */

function clinic_field( $a ) {
	$a = wp_parse_args(
		$a,
		array(
			'id'           => '',
			'name'         => '',
			'label'        => '',
			'type'         => 'text',
			'required'     => false,
			'placeholder'  => '',
			'options'      => array(),
			'value'        => '',
			'error'        => '',
			'help'         => '',
			'rows'         => 3,
			'autocomplete' => '',
			'readonly'     => false,
			'wide'         => false,
			'attrs'        => array(),
			'select_label' => 'Select',
		)
	);
	$id   = $a['id'];
	$hid  = $id . '-help';
	$eid  = $id . '-err';
	$desc = ( $a['help'] ? $hid . ' ' : '' ) . $eid;

	$common  = ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $a['name'] ) . '" aria-describedby="' . esc_attr( $desc ) . '"';
	$common .= $a['required'] ? ' required aria-required="true"' : '';
	$common .= $a['readonly'] ? ' readonly' : '';
	$common .= $a['error'] ? ' aria-invalid="true"' : '';
	$common .= $a['autocomplete'] ? ' autocomplete="' . esc_attr( $a['autocomplete'] ) . '"' : '';
	$common .= $a['placeholder'] ? ' placeholder="' . esc_attr( $a['placeholder'] ) . '"' : '';
	foreach ( (array) $a['attrs'] as $k => $v ) {
		$common .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}

	$classes = 'cp-field' . ( $a['wide'] ? ' cp-field--wide' : '' ) . ( $a['error'] ? ' has-error' : '' );
	ob_start();
	?>
	<div class="<?php echo esc_attr( $classes ); ?>">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $a['label'] ); ?><?php if ( $a['required'] ) : ?> <span class="cp-req" aria-hidden="true">*</span><span class="screen-reader-text"> (required)</span><?php endif; ?></label>
		<?php if ( 'textarea' === $a['type'] ) : ?>
			<textarea rows="<?php echo (int) $a['rows']; ?>"<?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_textarea( $a['value'] ); ?></textarea>
		<?php elseif ( 'select' === $a['type'] ) : ?>
			<select<?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<option value=""><?php echo esc_html( $a['select_label'] ); ?></option>
				<?php foreach ( $a['options'] as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( (string) $a['value'], (string) $val ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php else : ?>
			<input type="<?php echo esc_attr( $a['type'] ); ?>" value="<?php echo esc_attr( $a['value'] ); ?>"<?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<?php endif; ?>
		<?php if ( $a['help'] ) : ?>
			<p class="cp-help" id="<?php echo esc_attr( $hid ); ?>"><?php echo esc_html( $a['help'] ); ?></p>
		<?php endif; ?>
		<p class="cp-field__error" id="<?php echo esc_attr( $eid ); ?>" role="alert"><?php echo esc_html( $a['error'] ); ?></p>
	</div>
	<?php
	return ob_get_clean();
}

function clinic_consent_field( $id, $error, $checked = false ) {
	$text = 'I consent to ' . clinic_opt( 'clinic_name' ) . ' collecting and processing my personal and health information for care and clinic administration, in line with the Data Privacy Act of 2012 (RA 10173). I understand I may ask to see or correct my information at any time.';
	ob_start();
	?>
	<div class="cp-field cp-field--wide cp-consent<?php echo $error ? ' has-error' : ''; ?>">
		<label class="cp-check" for="<?php echo esc_attr( $id ); ?>">
			<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="consent" value="1" required aria-required="true" aria-describedby="<?php echo esc_attr( $id ); ?>-err" <?php checked( $checked ); ?>>
			<span><?php echo esc_html( $text ); ?></span>
		</label>
		<p class="cp-field__error" id="<?php echo esc_attr( $id ); ?>-err" role="alert"><?php echo esc_html( $error ); ?></p>
	</div>
	<?php
	return ob_get_clean();
}

function clinic_form_hidden( $action ) {
	$back = get_permalink();
	if ( ! $back ) {
		$back = home_url( '/' );
	}
	ob_start();
	wp_nonce_field( 'cp_' . $action, 'cp_nonce', false );
	?>
	<input type="hidden" name="cp_action" value="<?php echo esc_attr( $action ); ?>">
	<input type="hidden" name="cp_back" value="<?php echo esc_url( $back ); ?>">
	<div class="cp-hp" aria-hidden="true"><label>Leave this field empty <input type="text" name="cp_website" tabindex="-1" autocomplete="off"></label></div>
	<?php
	return ob_get_clean();
}

function clinic_error_summary( $errors, $prefix ) {
	if ( ! $errors ) {
		return '';
	}
	ob_start();
	?>
	<div class="cp-notice cp-notice--error" role="alert" tabindex="-1">
		<?php echo clinic_icon( 'alert', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div>
			<strong>Please fix the following and submit again:</strong>
			<ul>
				<?php foreach ( $errors as $key => $msg ) : ?>
					<li><?php if ( '_form' !== $key ) : ?><a href="#<?php echo esc_attr( $prefix . $key ); ?>"><?php echo esc_html( $msg ); ?></a><?php else : echo esc_html( $msg ); endif; ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------ */
/* Patient registration                                                */
/* ------------------------------------------------------------------ */

function clinic_sc_registration() {
	$flash = clinic_flash_get();
	$flash = ( $flash && isset( $flash['type'] ) && 'register' === $flash['type'] ) ? $flash : null;

	ob_start();
	echo '<section class="cp-section cp-section--soft"><div class="cp-container cp-split cp-split--form" id="cp-result">';

	if ( $flash && 'success' === $flash['status'] ) {
		if ( ! empty( $flash['honeypot'] ) ) {
			$flash['patient_id'] = '';
		}
		?>
		<div class="cp-card cp-success">
			<span class="cp-success__icon"><?php echo clinic_icon( 'check', 34 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<h2>Registration complete</h2>
			<?php if ( ! empty( $flash['patient_id'] ) ) : ?>
				<p>Thank you<?php echo $flash['name'] ? ', ' . esc_html( $flash['name'] ) : ''; ?>. Your Patient ID is:</p>
				<p class="cp-pid" data-cp-copy-source><?php echo esc_html( $flash['patient_id'] ); ?></p>
				<p><button type="button" class="cp-btn cp-btn--ghost" data-cp-copy>Copy Patient ID</button></p>
				<p class="cp-muted"><?php echo ! empty( $flash['email_sent'] ) ? 'We also emailed it to you.' : 'Please write it down or take a screenshot.'; ?> Bring a valid government ID on your first visit.</p>
			<?php endif; ?>
			<div class="cp-actions cp-actions--center">
				<a class="cp-btn cp-btn--primary" href="<?php echo esc_url( add_query_arg( 'patient_id', rawurlencode( isset( $flash['patient_id'] ) ? $flash['patient_id'] : '' ), clinic_page_url( 'book-appointment' ) ) ); ?>">Book an appointment <?php echo clinic_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<a class="cp-btn cp-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to home</a>
			</div>
		</div>
		<aside class="cp-aside"><?php echo clinic_illustration( 'registration' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></aside>
		<?php
		echo '</div></section>';
		return ob_get_clean();
	}

	$old    = $flash ? (array) $flash['old'] : array();
	$errors = $flash ? (array) $flash['errors'] : array();
	$fields = clinic_patient_fields();
	$groups = clinic_patient_groups();
	$pid    = clinic_generate_patient_id();
	?>
	<div class="cp-card cp-form-card">
		<h2 class="cp-form-title">Online patient registration</h2>
		<p class="cp-muted">Fields marked with <span class="cp-req">*</span> are required. It takes about three minutes.</p>
		<?php echo clinic_error_summary( $errors, 'cp-reg-' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<form class="cp-form" method="post" data-cp-validate>
			<?php echo clinic_form_hidden( 'register' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php foreach ( $groups as $gkey => $glabel ) : ?>
				<fieldset class="cp-fieldset">
					<legend><?php echo esc_html( $glabel ); ?></legend>
					<div class="cp-grid">
					<?php
					foreach ( $fields as $key => $f ) {
						if ( $f['group'] !== $gkey ) {
							continue;
						}
						$args = array(
							'id'           => 'cp-reg-' . $key,
							'name'         => $key,
							'label'        => $f['label'],
							'type'         => $f['type'],
							'required'     => ! empty( $f['required'] ),
							'placeholder'  => isset( $f['placeholder'] ) ? $f['placeholder'] : '',
							'help'         => isset( $f['help'] ) ? $f['help'] : '',
							'autocomplete' => isset( $f['autocomplete'] ) ? $f['autocomplete'] : '',
							'rows'         => isset( $f['rows'] ) ? $f['rows'] : 3,
							'value'        => isset( $old[ $key ] ) ? $old[ $key ] : '',
							'error'        => isset( $errors[ $key ] ) ? $errors[ $key ] : '',
							'wide'         => 'textarea' === $f['type'],
						);
						if ( 'select' === $f['type'] ) {
							$args['options']      = array_combine( $f['options'], $f['options'] );
							$args['select_label'] = isset( $f['placeholder'] ) ? $f['placeholder'] : 'Select';
						}
						if ( 'patient_id' === $f['type'] ) {
							$args['type']     = 'text';
							$args['readonly'] = true;
							$args['value']    = $pid;
							$args['attrs']    = array( 'data-cp-pid' => '1', 'class' => 'cp-readonly' );
						}
						if ( 'age' === $f['type'] ) {
							$args['type']        = 'text';
							$args['readonly']    = true;
							$args['placeholder'] = 'Select your date of birth';
							$args['value']       = ! empty( $old['dob'] ) ? clinic_calc_age( $old['dob'] ) : '';
							$args['attrs']       = array( 'data-cp-age' => '1', 'class' => 'cp-readonly', 'tabindex' => '-1' );
						}
						if ( 'date' === $f['type'] ) {
							$args['attrs'] = array(
								'data-cp-dob' => '1',
								'max'         => wp_date( 'Y-m-d' ),
								'min'         => wp_date( 'Y-m-d', strtotime( '-120 years' ) ),
							);
						}
						if ( 'tel' === $f['type'] ) {
							$args['attrs'] = array( 'inputmode' => 'tel', 'data-rule' => 'phone' );
						}
						echo clinic_field( $args ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
					</div>
				</fieldset>
			<?php endforeach; ?>
			<?php echo clinic_consent_field( 'cp-reg-consent', isset( $errors['consent'] ) ? $errors['consent'] : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="cp-actions">
				<button type="submit" class="cp-btn cp-btn--primary cp-btn--lg">Submit registration <?php echo clinic_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
		</form>
	</div>

	<aside class="cp-aside">
		<div class="cp-aside__card">
			<?php echo clinic_illustration( 'registration' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<h3>Before you start</h3>
			<ul class="cp-checklist">
				<li><?php echo clinic_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Have a valid government ID ready for your first visit.</li>
				<li><?php echo clinic_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> List allergies and current medicines as accurately as you can.</li>
				<li><?php echo clinic_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Your Patient ID is created for you, keep it for booking.</li>
			</ul>
			<p class="cp-muted cp-privacy"><?php echo clinic_icon( 'lock', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Your information is stored privately and can only be opened by authorised clinic staff.</p>
		</div>
	</aside>
	</div></section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'clinic_registration', 'clinic_sc_registration' );

/* ------------------------------------------------------------------ */
/* Appointment booking                                                 */
/* ------------------------------------------------------------------ */

function clinic_sc_booking() {
	$flash = clinic_flash_get();
	$flash = ( $flash && isset( $flash['type'] ) && 'book' === $flash['type'] ) ? $flash : null;

	ob_start();
	echo '<section class="cp-section cp-section--soft"><div class="cp-container cp-split cp-split--form" id="cp-result">';

	if ( $flash && 'success' === $flash['status'] ) {
		?>
		<div class="cp-card cp-success">
			<span class="cp-success__icon"><?php echo clinic_icon( 'calendar', 34 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<h2>Appointment requested</h2>
			<?php if ( ! empty( $flash['ref'] ) ) : ?>
				<p>Your reference number is:</p>
				<p class="cp-pid" data-cp-copy-source><?php echo esc_html( $flash['ref'] ); ?></p>
				<dl class="cp-summary">
					<div><dt>Department</dt><dd><?php echo esc_html( $flash['dept'] ); ?></dd></div>
					<div><dt>Physician</dt><dd><?php echo esc_html( $flash['phys'] ); ?></dd></div>
					<div><dt>When</dt><dd><?php echo esc_html( $flash['when'] ); ?></dd></div>
				</dl>
				<p class="cp-muted">Our staff will confirm your slot by email or phone. To change or cancel, call <a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', clinic_opt( 'phone' ) ) ); ?>"><?php echo esc_html( clinic_opt( 'phone' ) ); ?></a>.</p>
			<?php else : ?>
				<p>Thank you. We will be in touch to confirm your slot.</p>
			<?php endif; ?>
			<div class="cp-actions cp-actions--center">
				<a class="cp-btn cp-btn--primary" href="<?php echo esc_url( clinic_page_url( 'patient-registration' ) ); ?>">Register as a patient</a>
				<a class="cp-btn cp-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to home</a>
			</div>
		</div>
		<aside class="cp-aside"><?php echo clinic_illustration( 'booking' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></aside>
		<?php
		echo '</div></section>';
		return ob_get_clean();
	}

	$old    = $flash ? (array) $flash['old'] : array();
	$errors = $flash ? (array) $flash['errors'] : array();

	// Prefill from links: ?physician=ID, ?department=ID, ?patient_id=CODE.
	if ( ! $flash ) {
		// phpcs:disable WordPress.Security.NonceVerification
		$p_id = isset( $_GET['physician'] ) ? absint( $_GET['physician'] ) : 0;
		$d_id = isset( $_GET['department'] ) ? absint( $_GET['department'] ) : 0;
		if ( $p_id && 'clinic_physician' === get_post_type( $p_id ) ) {
			$old['physician_id']  = $p_id;
			$old['department_id'] = (int) get_post_meta( $p_id, '_cp_department_id', true );
		} elseif ( $d_id && 'clinic_department' === get_post_type( $d_id ) ) {
			$old['department_id'] = $d_id;
		}
		if ( isset( $_GET['patient_id'] ) ) {
			$old['patient_id'] = strtoupper( preg_replace( '/[^A-Za-z0-9\-]/', '', wp_unslash( $_GET['patient_id'] ) ) );
		}
		// phpcs:enable
	}

	$depts = array();
	foreach ( clinic_get_departments() as $d ) {
		$depts[ $d->ID ] = $d->post_title;
	}
	$phys_opts = clinic_get_physicians();
	$by_day    = clinic_slots_by_weekday();

	// Union of all slot values for the no-JavaScript fallback.
	$all_times = array();
	foreach ( $by_day as $slots ) {
		foreach ( $slots as $s ) {
			$all_times[ $s['value'] ] = $s['label'];
		}
	}
	ksort( $all_times );

	$min = wp_date( 'Y-m-d' );
	$max = wp_date( 'Y-m-d', strtotime( '+90 days' ) );
	$g   = function ( $k ) use ( $old ) {
		return isset( $old[ $k ] ) ? $old[ $k ] : '';
	};
	$e = function ( $k ) use ( $errors ) {
		return isset( $errors[ $k ] ) ? $errors[ $k ] : '';
	};
	?>
	<div class="cp-card cp-form-card">
		<h2 class="cp-form-title">Book an appointment</h2>
		<p class="cp-muted">Choose a department, a physician and a time that suits you. We confirm every request.</p>
		<?php echo clinic_error_summary( $errors, 'cp-bk-' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<form class="cp-form" method="post" data-cp-validate data-cp-booking data-slots="<?php echo esc_attr( wp_json_encode( $by_day ) ); ?>" data-selected-time="<?php echo esc_attr( $g( 'time' ) ); ?>">
			<?php echo clinic_form_hidden( 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<fieldset class="cp-fieldset">
				<legend>Visit details</legend>
				<div class="cp-grid">
					<?php
					echo clinic_field( array( 'id' => 'cp-bk-department_id', 'name' => 'department_id', 'label' => 'Department', 'type' => 'select', 'required' => true, 'options' => $depts, 'value' => $g( 'department_id' ), 'error' => $e( 'department_id' ), 'select_label' => 'Choose a department', 'attrs' => array( 'data-cp-dept' => '1' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
					<div class="cp-field<?php echo $e( 'physician_id' ) ? ' has-error' : ''; ?>">
						<label for="cp-bk-physician_id">Physician</label>
						<select id="cp-bk-physician_id" name="physician_id" data-cp-phys aria-describedby="cp-bk-physician_id-err">
							<option value="0">Any available physician</option>
							<?php foreach ( $phys_opts as $p ) : ?>
								<option value="<?php echo (int) $p->ID; ?>" data-dept="<?php echo (int) get_post_meta( $p->ID, '_cp_department_id', true ); ?>" <?php selected( (int) $g( 'physician_id' ), $p->ID ); ?>><?php echo esc_html( $p->post_title . ( get_post_meta( $p->ID, '_cp_specialty', true ) ? ' (' . get_post_meta( $p->ID, '_cp_specialty', true ) . ')' : '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="cp-field__error" id="cp-bk-physician_id-err" role="alert"><?php echo esc_html( $e( 'physician_id' ) ); ?></p>
					</div>
					<?php
					echo clinic_field( array( 'id' => 'cp-bk-date', 'name' => 'date', 'label' => 'Preferred date', 'type' => 'date', 'required' => true, 'value' => $g( 'date' ), 'error' => $e( 'date' ), 'attrs' => array( 'min' => $min, 'max' => $max, 'data-cp-date' => '1' ), 'help' => 'Up to 90 days ahead.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo clinic_field( array( 'id' => 'cp-bk-time', 'name' => 'time', 'label' => 'Preferred time', 'type' => 'select', 'required' => true, 'options' => $all_times, 'value' => $g( 'time' ), 'error' => $e( 'time' ), 'select_label' => 'Choose a date first', 'attrs' => array( 'data-cp-time' => '1' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>
				<p class="cp-slot-note" data-cp-slot-note aria-live="polite"></p>
			</fieldset>
			<fieldset class="cp-fieldset">
				<legend>Patient details</legend>
				<div class="cp-grid">
					<?php
					echo clinic_field( array( 'id' => 'cp-bk-patient_name', 'name' => 'patient_name', 'label' => 'Patient full name', 'type' => 'text', 'required' => true, 'value' => $g( 'patient_name' ), 'error' => $e( 'patient_name' ), 'autocomplete' => 'name' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo clinic_field( array( 'id' => 'cp-bk-mobile', 'name' => 'mobile', 'label' => 'Mobile number', 'type' => 'tel', 'required' => true, 'value' => $g( 'mobile' ), 'error' => $e( 'mobile' ), 'placeholder' => '09171234567', 'autocomplete' => 'tel', 'attrs' => array( 'inputmode' => 'tel', 'data-rule' => 'phone' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo clinic_field( array( 'id' => 'cp-bk-email', 'name' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true, 'value' => $g( 'email' ), 'error' => $e( 'email' ), 'autocomplete' => 'email' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo clinic_field( array( 'id' => 'cp-bk-patient_id', 'name' => 'patient_id', 'label' => 'Patient ID (optional)', 'type' => 'text', 'value' => $g( 'patient_id' ), 'error' => $e( 'patient_id' ), 'help' => 'Leave empty if you are a new patient.', 'placeholder' => clinic_opt( 'id_prefix' ) . '-' . wp_date( 'Y' ) . '-XXXXXX' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo clinic_field( array( 'id' => 'cp-bk-reason', 'name' => 'reason', 'label' => 'Reason for visit (optional)', 'type' => 'textarea', 'rows' => 3, 'wide' => true, 'value' => $g( 'reason' ), 'error' => $e( 'reason' ), 'help' => 'A short note helps us prepare. For emergencies call the hotline instead.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>
			</fieldset>
			<?php echo clinic_consent_field( 'cp-bk-consent', $e( 'consent' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="cp-actions">
				<button type="submit" class="cp-btn cp-btn--primary cp-btn--lg">Request appointment <?php echo clinic_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
		</form>
	</div>

	<aside class="cp-aside">
		<div class="cp-aside__card">
			<?php echo clinic_illustration( 'booking' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<h3>Clinic hours</h3>
			<table class="cp-hours">
				<?php foreach ( clinic_hours_rows() as $row ) : ?>
					<tr><th scope="row"><?php echo esc_html( $row[0] ); ?></th><td><?php echo esc_html( $row[1] ); ?></td></tr>
				<?php endforeach; ?>
			</table>
			<p class="cp-muted">New here? <a href="<?php echo esc_url( clinic_page_url( 'patient-registration' ) ); ?>">Register as a patient first</a> to get your Patient ID.</p>
		</div>
	</aside>
	</div></section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'clinic_booking', 'clinic_sc_booking' );

/* ------------------------------------------------------------------ */
/* Staff dashboard                                                     */
/* ------------------------------------------------------------------ */

function clinic_sc_dashboard() {
	ob_start();
	echo '<section class="cp-section cp-section--soft"><div class="cp-container cp-dashboard">';

	if ( ! is_user_logged_in() ) {
		?>
		<div class="cp-split cp-split--login">
			<div class="cp-card cp-login">
				<span class="cp-success__icon cp-success__icon--blue"><?php echo clinic_icon( 'lock', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<h2>Staff sign in</h2>
				<p class="cp-muted">This area is for clinic doctors, nurses and administrators. Patient records are confidential.</p>
				<?php
				wp_login_form(
					array(
						'redirect'       => get_permalink(),
						'label_username' => 'Username or email',
						'label_log_in'   => 'Sign in',
						'remember'       => false,
					)
				);
				?>
			</div>
			<aside class="cp-aside"><?php echo clinic_illustration( 'secure' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></aside>
		</div>
		<?php
		echo '</div></section>';
		return ob_get_clean();
	}

	if ( ! clinic_user_can_view_patients() ) {
		?>
		<div class="cp-card cp-login">
			<span class="cp-success__icon cp-success__icon--red"><?php echo clinic_icon( 'lock', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<h2>Access restricted</h2>
			<p>Your account does not have permission to view patient records. Please ask a clinic administrator to give you the Doctor or Nurse role.</p>
			<p><a class="cp-btn cp-btn--ghost" href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">Sign out</a></p>
		</div>
		<?php
		echo '</div></section>';
		return ob_get_clean();
	}

	// phpcs:disable WordPress.Security.NonceVerification
	$view_id = isset( $_GET['patient'] ) ? absint( $_GET['patient'] ) : 0;
	// phpcs:enable

	$user = wp_get_current_user();
	?>
	<div class="cp-dash-head">
		<div>
			<p class="cp-eyebrow">Staff area</p>
			<h2>Patient records</h2>
			<p class="cp-muted">Signed in as <strong><?php echo esc_html( $user->display_name ); ?></strong>. Access to each record is logged.</p>
		</div>
		<a class="cp-btn cp-btn--ghost" href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">Sign out</a>
	</div>
	<?php

	if ( $view_id ) {
		clinic_dashboard_detail( $view_id );
	} else {
		clinic_dashboard_list();
	}
	echo '</div></section>';
	return ob_get_clean();
}
add_shortcode( 'clinic_staff_dashboard', 'clinic_sc_dashboard' );

function clinic_dashboard_list() {
	// phpcs:disable WordPress.Security.NonceVerification
	$q      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$sex    = isset( $_GET['sex'] ) ? sanitize_text_field( wp_unslash( $_GET['sex'] ) ) : '';
	$blood  = isset( $_GET['blood'] ) ? sanitize_text_field( wp_unslash( $_GET['blood'] ) ) : '';
	$paged  = isset( $_GET['pg'] ) ? max( 1, absint( $_GET['pg'] ) ) : 1;
	// phpcs:enable
	$fields = clinic_patient_fields();

	$args = array(
		'post_type'      => 'clinic_patient',
		'post_status'    => 'private',
		'posts_per_page' => 15,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	$meta = array();
	if ( '' !== $q ) {
		if ( preg_match( '/^[\d\+\-\s\(\)]{4,}$/', $q ) ) {
			$meta[] = array( 'key' => '_cp_mobile', 'value' => preg_replace( '/[\s\-\(\)]/', '', $q ), 'compare' => 'LIKE' );
		} else {
			$args['s'] = $q;
		}
	}
	if ( $sex && in_array( $sex, $fields['sex']['options'], true ) ) {
		$meta[] = array( 'key' => '_cp_sex', 'value' => $sex );
	}
	if ( $blood && in_array( $blood, $fields['blood_type']['options'], true ) ) {
		$meta[] = array( 'key' => '_cp_blood_type', 'value' => $blood );
	}
	if ( $meta ) {
		$meta['relation']   = 'AND';
		$args['meta_query'] = $meta;
	}
	$page_url = get_permalink();
	$query    = new WP_Query( $args );

	$total = (int) wp_count_posts( 'clinic_patient' )->private;
	$today = new WP_Query(
		array(
			'post_type'      => 'clinic_patient',
			'post_status'    => 'private',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'date_query'     => array( array( 'after' => 'today midnight', 'inclusive' => true ) ),
		)
	);
	$week  = new WP_Query(
		array(
			'post_type'      => 'clinic_patient',
			'post_status'    => 'private',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'date_query'     => array( array( 'after' => '7 days ago', 'inclusive' => true ) ),
		)
	);
	?>
	<div class="cp-stats">
		<div class="cp-stat"><span class="cp-stat__num"><?php echo (int) $total; ?></span><span class="cp-stat__label">Total patients</span></div>
		<div class="cp-stat"><span class="cp-stat__num"><?php echo (int) $today->found_posts; ?></span><span class="cp-stat__label">Registered today</span></div>
		<div class="cp-stat"><span class="cp-stat__num"><?php echo (int) $week->found_posts; ?></span><span class="cp-stat__label">Last 7 days</span></div>
	</div>

	<form class="cp-card cp-filter" method="get" role="search" aria-label="Search patient records">
		<div class="cp-field cp-field--grow">
			<label for="cp-q">Search by name, Patient ID or mobile</label>
			<input type="search" id="cp-q" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="For example Santos, CWC-2026 or 0917">
		</div>
		<div class="cp-field">
			<label for="cp-sex">Sex</label>
			<select id="cp-sex" name="sex"><option value="">All</option>
				<?php foreach ( $fields['sex']['options'] as $o ) : ?>
					<option <?php selected( $sex, $o ); ?>><?php echo esc_html( $o ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="cp-field">
			<label for="cp-blood">Blood type</label>
			<select id="cp-blood" name="blood"><option value="">All</option>
				<?php foreach ( $fields['blood_type']['options'] as $o ) : ?>
					<option <?php selected( $blood, $o ); ?>><?php echo esc_html( $o ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="cp-filter__btns">
			<button type="submit" class="cp-btn cp-btn--primary">Search</button>
			<?php if ( $q || $sex || $blood ) : ?>
				<a class="cp-btn cp-btn--ghost" href="<?php echo esc_url( get_permalink() ); ?>">Clear</a>
			<?php endif; ?>
		</div>
	</form>

	<?php if ( ! $query->have_posts() ) : ?>
		<div class="cp-card cp-empty"><p>No patient records match your search.</p></div>
	<?php else : ?>
		<div class="cp-card cp-table-wrap">
			<table class="cp-table">
				<thead>
					<tr><th scope="col">Patient ID</th><th scope="col">Name</th><th scope="col">Age</th><th scope="col">Sex</th><th scope="col">Blood</th><th scope="col">Mobile</th><th scope="col">Registered</th><th scope="col"><span class="screen-reader-text">Action</span></th></tr>
				</thead>
				<tbody>
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					$id    = get_the_ID();
					$m     = function ( $k ) use ( $id ) {
						return get_post_meta( $id, '_cp_' . $k, true );
					};
					$link  = add_query_arg(
						array(
							'patient' => $id,
							'_cpv'    => wp_create_nonce( 'cp_view_' . $id ),
						),
						$page_url
					);
					$name  = trim( $m( 'last_name' ) . ', ' . $m( 'first_name' ) . ' ' . $m( 'suffix' ) );
					?>
					<tr>
						<td data-label="Patient ID"><code><?php echo esc_html( $m( 'patient_id' ) ); ?></code></td>
						<td data-label="Name"><strong><?php echo esc_html( $name ); ?></strong></td>
						<td data-label="Age"><?php echo esc_html( clinic_calc_age( $m( 'dob' ) ) ); ?></td>
						<td data-label="Sex"><?php echo esc_html( $m( 'sex' ) ); ?></td>
						<td data-label="Blood"><?php echo esc_html( $m( 'blood_type' ) ); ?></td>
						<td data-label="Mobile"><?php echo esc_html( $m( 'mobile' ) ); ?></td>
						<td data-label="Registered"><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></td>
						<td><a class="cp-btn cp-btn--small" href="<?php echo esc_url( $link ); ?>">Open record</a></td>
					</tr>
				<?php endwhile; ?>
				</tbody>
			</table>
		</div>
		<?php
		$links = paginate_links(
			array(
				'base'      => str_replace( 'PGNUM', '%#%', esc_url_raw( add_query_arg( 'pg', 'PGNUM' ) ) ),
				'format'    => '',
				'current'   => $paged,
				'total'     => $query->max_num_pages,
				'prev_text' => 'Previous',
				'next_text' => 'Next',
				'type'      => 'list',
			)
		);
		if ( $links ) {
			echo '<nav class="cp-pagination" aria-label="Pagination">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		wp_reset_postdata();
	endif;
}

function clinic_dashboard_detail( $id ) {
	$back = remove_query_arg( array( 'patient', '_cpv' ) );
	// phpcs:ignore WordPress.Security.NonceVerification
	$nonce = isset( $_GET['_cpv'] ) ? sanitize_text_field( wp_unslash( $_GET['_cpv'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'cp_view_' . $id ) || 'clinic_patient' !== get_post_type( $id ) ) {
		echo '<div class="cp-card cp-empty"><p>This link has expired. Please open the record again from the list.</p><p><a class="cp-btn cp-btn--ghost" href="' . esc_url( $back ) . '">Back to the list</a></p></div>';
		return;
	}
	if ( ! current_user_can( 'read_post', $id ) ) {
		echo '<div class="cp-card cp-empty"><p>You do not have permission to open this record.</p></div>';
		return;
	}

	// Access log: last 20 views.
	$user = wp_get_current_user();
	$log  = get_post_meta( $id, '_cp_access_log', true );
	$log  = is_array( $log ) ? $log : array();
	$prev = $log ? $log[0] : null;
	array_unshift( $log, array( 'user' => $user->display_name, 'time' => time() ) );
	update_post_meta( $id, '_cp_access_log', array_slice( $log, 0, 20 ) );

	$m      = function ( $k ) use ( $id ) {
		return get_post_meta( $id, '_cp_' . $k, true );
	};
	$groups = clinic_patient_groups();
	$fields = clinic_patient_fields();
	$name   = trim( $m( 'first_name' ) . ' ' . $m( 'middle_name' ) . ' ' . $m( 'last_name' ) . ' ' . $m( 'suffix' ) );
	$allergy = $m( 'allergies' );
	$none    = in_array( strtolower( trim( $allergy ) ), array( '', 'none', 'no', 'n/a', 'na', 'nka', 'nkda' ), true );
	?>
	<p class="cp-noprint"><a class="cp-link-back" href="<?php echo esc_url( $back ); ?>">Back to all records</a></p>
	<article class="cp-card cp-record">
		<header class="cp-record__head">
			<div>
				<h3><?php echo esc_html( $name ); ?></h3>
				<p><code><?php echo esc_html( $m( 'patient_id' ) ); ?></code>, <?php echo esc_html( clinic_calc_age( $m( 'dob' ) ) ); ?> years old, <?php echo esc_html( $m( 'sex' ) ); ?>, Blood type <?php echo esc_html( $m( 'blood_type' ) ); ?></p>
			</div>
			<div class="cp-record__actions cp-noprint">
				<button type="button" class="cp-btn cp-btn--ghost" data-cp-print><?php echo clinic_icon( 'print', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Print</button>
				<?php if ( current_user_can( 'edit_post', $id ) ) : ?>
					<a class="cp-btn cp-btn--primary" href="<?php echo esc_url( get_edit_post_link( $id, '' ) ); ?>">Edit in admin</a>
				<?php endif; ?>
			</div>
		</header>

		<div class="cp-allergy <?php echo $none ? 'cp-allergy--none' : 'cp-allergy--alert'; ?>" role="note">
			<?php echo clinic_icon( $none ? 'shield' : 'alert', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div><strong>Known allergies</strong><br><?php echo $allergy ? nl2br( esc_html( $allergy ) ) : 'Not recorded'; ?></div>
		</div>

		<?php foreach ( $groups as $gkey => $glabel ) : ?>
			<section class="cp-record__group">
				<h4><?php echo esc_html( $glabel ); ?></h4>
				<dl class="cp-dl">
					<?php
					foreach ( $fields as $key => $f ) {
						if ( $f['group'] !== $gkey ) {
							continue;
						}
						if ( 'age' === $key ) {
							$val = clinic_calc_age( $m( 'dob' ) );
						} else {
							$val = $m( $key );
						}
						if ( 'allergies' === $key ) {
							continue;
						}
						?>
						<div class="<?php echo 'textarea' === $f['type'] ? 'cp-dl__wide' : ''; ?>">
							<dt><?php echo esc_html( $f['label'] ); ?></dt>
							<dd><?php echo '' !== (string) $val ? nl2br( esc_html( $val ) ) : '<span class="cp-muted">Not provided</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
						</div>
					<?php } ?>
				</dl>
			</section>
		<?php endforeach; ?>

		<footer class="cp-record__foot cp-muted">
			Registered <?php echo esc_html( get_the_date( 'F j, Y g:i a', $id ) ); ?>.
			<?php if ( $prev ) : ?>
				Previously opened by <?php echo esc_html( $prev['user'] ); ?> on <?php echo esc_html( wp_date( 'M j, Y g:i a', $prev['time'] ) ); ?>.
			<?php endif; ?>
		</footer>
	</article>
	<?php
}

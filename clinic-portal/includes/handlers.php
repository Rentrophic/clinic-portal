<?php
/**
 * Front-end form handlers (registration, booking, contact).
 * Pattern: validate on POST, store the result in a short-lived transient,
 * redirect back, then the shortcode shows the result (post, redirect, get).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function clinic_handle_forms() {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['cp_action'] ) ) {
		return;
	}
	$action = sanitize_key( wp_unslash( $_POST['cp_action'] ) );
	$slugs  = array(
		'register' => 'patient-registration',
		'book'     => 'book-appointment',
		'contact'  => 'contact-us',
	);
	if ( ! isset( $slugs[ $action ] ) ) {
		return;
	}

	$fallback = clinic_page_url( $slugs[ $action ] );
	$back     = isset( $_POST['cp_back'] ) ? esc_url_raw( wp_unslash( $_POST['cp_back'] ) ) : '';
	$back     = $back ? wp_validate_redirect( $back, $fallback ) : $fallback;
	$back     = remove_query_arg( array( 'cp_r', 'physician', 'department' ), $back );
	$anchor   = '#cp-result';

	$finish = function ( $payload ) use ( $back, $action, $anchor ) {
		$payload['type'] = $action;
		$token           = clinic_flash_set( $payload );
		wp_safe_redirect( add_query_arg( 'cp_r', $token, $back ) . $anchor );
		exit;
	};

	// Honeypot: bots fill the hidden field. Pretend everything worked.
	if ( ! empty( $_POST['cp_website'] ) ) {
		$finish( array( 'status' => 'success', 'honeypot' => true ) );
	}

	// Nonce.
	$nonce = isset( $_POST['cp_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['cp_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'cp_' . $action ) ) {
		$finish( array( 'status' => 'error', 'errors' => array( '_form' => 'Your session expired. Please try again.' ), 'old' => array() ) );
	}

	// Rate limit.
	$limit = 'contact' === $action ? 5 : 10;
	if ( clinic_rate_limited( $action, $limit ) ) {
		$finish( array( 'status' => 'error', 'errors' => array( '_form' => 'Too many submissions from your connection. Please try again later or call us.' ), 'old' => array() ) );
	}

	$handler = 'clinic_process_' . $action;
	$result  = $handler( $_POST );
	if ( isset( $result['status'] ) && 'success' === $result['status'] ) {
		clinic_rate_hit( $action );
	}
	$finish( $result );
}
add_action( 'template_redirect', 'clinic_handle_forms', 5 );

/* ------------------------------------------------------------------ */
/* Patient registration                                                */
/* ------------------------------------------------------------------ */

function clinic_process_register( $in ) {
	list( $data, $errors ) = clinic_validate_patient( $in );

	if ( empty( $in['consent'] ) ) {
		$errors['consent'] = 'Please confirm your consent to continue.';
	}
	if ( $errors ) {
		return array( 'status' => 'error', 'errors' => $errors, 'old' => $data );
	}

	// Use the ID shown on the form when it is still valid and unused.
	$pid    = '';
	$shown  = isset( $in['patient_id'] ) && ! is_array( $in['patient_id'] ) ? strtoupper( sanitize_text_field( wp_unslash( $in['patient_id'] ) ) ) : '';
	$prefix = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) clinic_opt( 'id_prefix' ) ) );
	$prefix = '' === $prefix ? 'CLN' : $prefix;
	if ( preg_match( '/^[A-Z0-9]+-\d{4}-[A-Z0-9]{6,8}$/', $shown ) && 0 === strpos( $shown, $prefix . '-' . wp_date( 'Y' ) . '-' ) && ! clinic_patient_id_exists( $shown ) ) {
		$pid = $shown;
	}
	if ( '' === $pid ) {
		$pid = clinic_generate_patient_id();
	}
	$title = trim( $data['last_name'] . ', ' . $data['first_name'] ) . ' (' . $pid . ')';

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'clinic_patient',
			'post_status' => 'private',
			'post_title'  => $title,
		),
		true
	);
	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return array( 'status' => 'error', 'errors' => array( '_form' => 'We could not save your registration. Please try again or call the clinic.' ), 'old' => $data );
	}

	foreach ( $data as $key => $val ) {
		update_post_meta( $post_id, '_cp_' . $key, $val );
	}
	update_post_meta( $post_id, '_cp_patient_id', $pid );
	update_post_meta( $post_id, '_cp_age_at_registration', clinic_calc_age( $data['dob'] ) );
	update_post_meta( $post_id, '_cp_consent_at', current_time( 'mysql' ) );

	$clinic = clinic_opt( 'clinic_name' );
	$body   = "Hello " . $data['first_name'] . ",\n\nThank you for registering with " . $clinic . ".\n\nYour Patient ID: " . $pid . "\n\nPlease keep this ID and bring a valid government ID on your first visit. You can use your Patient ID when booking appointments online.\n\n" . $clinic . "\n" . clinic_opt( 'phone' );
	$sent   = wp_mail( $data['email'], 'Your Patient ID at ' . $clinic, $body );

	wp_mail(
		clinic_opt( 'email' ),
		'New patient registration: ' . $pid,
		"A new patient registered online.\n\nPatient ID: " . $pid . "\nName: " . $data['last_name'] . ', ' . $data['first_name'] . "\n\nReview the record in the staff dashboard."
	);

	return array(
		'status'     => 'success',
		'patient_id' => $pid,
		'name'       => $data['first_name'],
		'email_sent' => (bool) $sent,
	);
}

/* ------------------------------------------------------------------ */
/* Appointment booking                                                 */
/* ------------------------------------------------------------------ */

function clinic_process_book( $in ) {
	$errors = array();
	$get    = function ( $k ) use ( $in ) {
		$v = isset( $in[ $k ] ) && ! is_array( $in[ $k ] ) ? wp_unslash( $in[ $k ] ) : '';
		return $v;
	};

	$old = array(
		'department_id' => absint( $get( 'department_id' ) ),
		'physician_id'  => absint( $get( 'physician_id' ) ),
		'date'          => sanitize_text_field( $get( 'date' ) ),
		'time'          => sanitize_text_field( $get( 'time' ) ),
		'patient_name'  => sanitize_text_field( $get( 'patient_name' ) ),
		'mobile'        => sanitize_text_field( $get( 'mobile' ) ),
		'email'         => sanitize_email( $get( 'email' ) ),
		'patient_id'    => strtoupper( sanitize_text_field( $get( 'patient_id' ) ) ),
		'reason'        => sanitize_textarea_field( $get( 'reason' ) ),
	);

	// Department.
	$dept = $old['department_id'];
	if ( ! $dept || 'clinic_department' !== get_post_type( $dept ) || 'publish' !== get_post_status( $dept ) ) {
		$errors['department_id'] = 'Please choose a department.';
	}

	// Physician (optional).
	$phys = $old['physician_id'];
	if ( $phys ) {
		if ( 'clinic_physician' !== get_post_type( $phys ) || 'publish' !== get_post_status( $phys ) ) {
			$errors['physician_id'] = 'Please choose a valid physician.';
		} elseif ( $dept && (int) get_post_meta( $phys, '_cp_department_id', true ) !== $dept ) {
			$errors['physician_id'] = 'That physician does not belong to the selected department.';
		}
	}

	// Date and time.
	$tz    = wp_timezone();
	$today = new DateTime( 'today', $tz );
	$d     = DateTime::createFromFormat( 'Y-m-d', $old['date'], $tz );
	if ( ! $d || $d->format( 'Y-m-d' ) !== $old['date'] ) {
		$errors['date'] = 'Please choose a valid date.';
	} else {
		$d->setTime( 0, 0 );
		$max = clone $today;
		$max->modify( '+90 days' );
		if ( $d < $today ) {
			$errors['date'] = 'Please choose today or a future date.';
		} elseif ( $d > $max ) {
			$errors['date'] = 'Appointments can be booked up to 90 days ahead.';
		} else {
			$slots = clinic_slots_for_date( $old['date'] );
			if ( ! $slots ) {
				$errors['date'] = 'The clinic is closed on that day. Please choose another date.';
			} elseif ( ! in_array( $old['time'], $slots, true ) ) {
				$errors['time'] = 'Please choose an available time.';
			} elseif ( $old['date'] === $today->format( 'Y-m-d' ) ) {
				$now = new DateTime( 'now', $tz );
				if ( clinic_to_minutes( $old['time'] ) <= ( (int) $now->format( 'G' ) ) * 60 + (int) $now->format( 'i' ) ) {
					$errors['time'] = 'That time has already passed. Please choose a later time.';
				}
			}
		}
	}

	// Patient details.
	if ( '' === $old['patient_name'] ) {
		$errors['patient_name'] = 'Please enter the patient name.';
	}
	$mobile = preg_replace( '/[\s\-\(\)\.]/', '', $old['mobile'] );
	if ( ! preg_match( '/^\+?\d{7,15}$/', $mobile ) ) {
		$errors['mobile'] = 'Please enter a valid mobile number, digits only.';
	} else {
		$old['mobile'] = $mobile;
	}
	if ( ! is_email( $old['email'] ) ) {
		$errors['email'] = 'Please enter a valid email address.';
	}
	if ( '' !== $old['patient_id'] ) {
		if ( ! preg_match( '/^[A-Z0-9\-]{6,24}$/', $old['patient_id'] ) || ! clinic_patient_id_exists( $old['patient_id'] ) ) {
			$errors['patient_id'] = 'We could not find that Patient ID. Leave it empty if you are a new patient.';
		}
	}
	if ( mb_strlen( $old['reason'] ) > 500 ) {
		$errors['reason'] = 'Please keep the reason under 500 characters.';
	}
	if ( empty( $in['consent'] ) ) {
		$errors['consent'] = 'Please confirm your consent to continue.';
	}

	// Slot already taken for this physician?
	if ( ! $errors && $phys ) {
		$taken = new WP_Query(
			array(
				'post_type'      => 'clinic_appointment',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'AND',
					array( 'key' => '_cp_physician_id', 'value' => $phys ),
					array( 'key' => '_cp_date', 'value' => $old['date'] ),
					array( 'key' => '_cp_time', 'value' => $old['time'] ),
					array( 'key' => '_cp_status', 'value' => 'cancelled', 'compare' => '!=' ),
				),
			)
		);
		if ( $taken->have_posts() ) {
			$errors['time'] = 'That time is already booked for this physician. Please choose another time.';
		}
	}

	if ( $errors ) {
		return array( 'status' => 'error', 'errors' => $errors, 'old' => $old );
	}

	$ref   = 'APT-' . strtoupper( wp_generate_password( 6, false, false ) );
	$title = $old['patient_name'] . ' - ' . $old['date'] . ' ' . clinic_time_label( $old['time'] );
	$id    = wp_insert_post(
		array(
			'post_type'   => 'clinic_appointment',
			'post_status' => 'publish',
			'post_title'  => $title,
		),
		true
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return array( 'status' => 'error', 'errors' => array( '_form' => 'We could not save your booking. Please try again or call the clinic.' ), 'old' => $old );
	}
	foreach ( $old as $k => $v ) {
		update_post_meta( $id, '_cp_' . $k, $v );
	}
	update_post_meta( $id, '_cp_status', 'pending' );
	update_post_meta( $id, '_cp_ref', $ref );

	$dept_name = get_the_title( $dept );
	$phys_name = $phys ? get_the_title( $phys ) : 'Any available physician';
	$when      = wp_date( 'l, F j, Y', $d->getTimestamp() ) . ' at ' . clinic_time_label( $old['time'] );
	$clinic    = clinic_opt( 'clinic_name' );

	$body = "Hello " . $old['patient_name'] . ",\n\nWe received your appointment request.\n\nReference: " . $ref . "\nDepartment: " . $dept_name . "\nPhysician: " . $phys_name . "\nWhen: " . $when . "\n\nOur staff will confirm your slot shortly. To change or cancel, call " . clinic_opt( 'phone' ) . " and quote your reference.\n\n" . $clinic;
	wp_mail( $old['email'], 'Appointment request ' . $ref . ' at ' . $clinic, $body );
	wp_mail(
		clinic_opt( 'email' ),
		'New appointment request ' . $ref,
		"Reference: " . $ref . "\nPatient: " . $old['patient_name'] . "\nDepartment: " . $dept_name . "\nPhysician: " . $phys_name . "\nWhen: " . $when . "\n\nConfirm it in the admin area under Appointments."
	);

	return array(
		'status' => 'success',
		'ref'    => $ref,
		'dept'   => $dept_name,
		'phys'   => $phys_name,
		'when'   => $when,
	);
}

/* ------------------------------------------------------------------ */
/* Contact message                                                     */
/* ------------------------------------------------------------------ */

function clinic_process_contact( $in ) {
	$errors = array();
	$get    = function ( $k ) use ( $in ) {
		return isset( $in[ $k ] ) && ! is_array( $in[ $k ] ) ? wp_unslash( $in[ $k ] ) : '';
	};
	$old = array(
		'name'    => sanitize_text_field( $get( 'name' ) ),
		'email'   => sanitize_email( $get( 'email' ) ),
		'subject' => sanitize_text_field( $get( 'subject' ) ),
		'message' => sanitize_textarea_field( $get( 'message' ) ),
	);
	if ( '' === $old['name'] ) {
		$errors['name'] = 'Please enter your name.';
	}
	if ( ! is_email( $old['email'] ) ) {
		$errors['email'] = 'Please enter a valid email address.';
	}
	if ( strlen( $old['message'] ) < 10 ) {
		$errors['message'] = 'Please write a short message (at least 10 characters).';
	}
	if ( mb_strlen( $old['message'] ) > 2000 ) {
		$errors['message'] = 'Please keep your message under 2000 characters.';
	}
	if ( $errors ) {
		return array( 'status' => 'error', 'errors' => $errors, 'old' => $old );
	}
	$subject = $old['subject'] ? $old['subject'] : 'Website message';
	$headers = array( 'Reply-To: ' . $old['name'] . ' <' . $old['email'] . '>' );
	$sent    = wp_mail(
		clinic_opt( 'email' ),
		'[Website] ' . $subject,
		"From: " . $old['name'] . ' <' . $old['email'] . ">\n\n" . $old['message'] . "\n\nPlease do not reply with medical details by email.",
		$headers
	);
	return array( 'status' => 'success', 'sent' => (bool) $sent );
}

/* ------------------------------------------------------------------ */
/* Dashboard page: never cache, never index                            */
/* ------------------------------------------------------------------ */

function clinic_dashboard_headers() {
	if ( ! is_singular( 'page' ) ) {
		return;
	}
	$page = get_queried_object();
	if ( $page && has_shortcode( $page->post_content, 'clinic_staff_dashboard' ) ) {
		nocache_headers();
		add_filter(
			'wp_robots',
			function ( $robots ) {
				$robots['noindex']  = true;
				$robots['nofollow'] = true;
				return $robots;
			}
		);
	}
}
add_action( 'template_redirect', 'clinic_dashboard_headers' );

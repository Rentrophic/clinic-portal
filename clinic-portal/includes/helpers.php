<?php
/**
 * Shared helpers: options, patient schema, validation, age, time slots.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Options                                                             */
/* ------------------------------------------------------------------ */

function clinic_defaults() {
	return array(
		'clinic_name'     => 'CareWell Medical Clinic',
		'phone'           => '(02) 8123 4567',
		'emergency_phone' => '(02) 8999 0000',
		'email'           => 'hello@carewell-clinic.example',
		'address'         => '123 Health Avenue, Caloocan City, Metro Manila',
		'map_lat'         => '14.6507',
		'map_lng'         => '120.9668',
		'open_time'       => '08:00',
		'close_time'      => '17:00',
		'sat_close'       => '12:00',
		'sunday_open'     => '0',
		'slot_minutes'    => '30',
		'id_prefix'       => 'CWC',
		'crisp_id'        => '',
		'video_url'       => '',
		'audio_url'       => '',
	);
}

function clinic_opt( $key ) {
	static $opts = null;
	if ( null === $opts || did_action( 'clinic_portal_options_saved' ) ) {
		$opts = wp_parse_args( (array) get_option( 'clinic_portal_options', array() ), clinic_defaults() );
	}
	return isset( $opts[ $key ] ) ? $opts[ $key ] : '';
}

/**
 * Page slugs and titles used across the site.
 */
function clinic_pages() {
	return array(
		'home'                => 'Home',
		'physicians'          => 'Physicians',
		'departments-services' => 'Departments and Services',
		'book-appointment'    => 'Book an Appointment',
		'patient-registration' => 'Patient Registration',
		'staff-dashboard'     => 'Patient Management Dashboard',
		'announcements-faq'   => 'Announcements and FAQ',
		'contact-us'          => 'Contact Us',
	);
}

function clinic_page_url( $slug ) {
	static $cache = array();
	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}
	if ( 'home' === $slug ) {
		$cache[ $slug ] = home_url( '/' );
		return $cache[ $slug ];
	}
	$page = get_page_by_path( $slug );
	$cache[ $slug ] = $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
	return $cache[ $slug ];
}

/* ------------------------------------------------------------------ */
/* Time helpers                                                        */
/* ------------------------------------------------------------------ */

function clinic_time_label( $hhmm ) {
	$parts = explode( ':', $hhmm );
	if ( count( $parts ) < 2 ) {
		return $hhmm;
	}
	$h    = (int) $parts[0];
	$m    = (int) $parts[1];
	$ampm = $h >= 12 ? 'PM' : 'AM';
	$h12  = $h % 12;
	if ( 0 === $h12 ) {
		$h12 = 12;
	}
	return sprintf( '%d:%02d %s', $h12, $m, $ampm );
}

function clinic_to_minutes( $hhmm ) {
	$parts = explode( ':', $hhmm );
	return ( (int) $parts[0] ) * 60 + ( isset( $parts[1] ) ? (int) $parts[1] : 0 );
}

/**
 * Available appointment slots (HH:MM) for a Y-m-d date, from the clinic hours.
 */
function clinic_slots_for_date( $ymd ) {
	$d = DateTime::createFromFormat( 'Y-m-d', $ymd, wp_timezone() );
	if ( ! $d || $d->format( 'Y-m-d' ) !== $ymd ) {
		return array();
	}
	$n = (int) $d->format( 'N' );
	if ( 7 === $n && '1' !== (string) clinic_opt( 'sunday_open' ) ) {
		return array();
	}
	$open  = clinic_to_minutes( clinic_opt( 'open_time' ) );
	$close = clinic_to_minutes( 6 === $n ? clinic_opt( 'sat_close' ) : clinic_opt( 'close_time' ) );
	$step  = max( 10, (int) clinic_opt( 'slot_minutes' ) );
	$slots = array();
	for ( $m = $open; $m + $step <= $close; $m += $step ) {
		$slots[] = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
	}
	return $slots;
}

/**
 * Slots keyed by ISO weekday (1 = Monday ... 7 = Sunday), for the booking script.
 */
function clinic_slots_by_weekday() {
	$out  = array();
	$base = new DateTime( 'monday this week', wp_timezone() );
	for ( $i = 0; $i < 7; $i++ ) {
		$d = clone $base;
		$d->modify( '+' . $i . ' day' );
		$slots = clinic_slots_for_date( $d->format( 'Y-m-d' ) );
		$out[ (int) $d->format( 'N' ) ] = array_map(
			function ( $s ) {
				return array( 'value' => $s, 'label' => clinic_time_label( $s ) );
			},
			$slots
		);
	}
	return $out;
}

/**
 * Plain text list of opening hours.
 */
function clinic_hours_rows() {
	$rows   = array();
	$open   = clinic_time_label( clinic_opt( 'open_time' ) );
	$close  = clinic_time_label( clinic_opt( 'close_time' ) );
	$sat    = clinic_time_label( clinic_opt( 'sat_close' ) );
	$rows[] = array( 'Monday to Friday', $open . ' to ' . $close );
	$rows[] = array( 'Saturday', $open . ' to ' . $sat );
	$rows[] = array( 'Sunday', '1' === (string) clinic_opt( 'sunday_open' ) ? $open . ' to ' . $sat : 'Closed' );
	$rows[] = array( 'Emergency line', 'Open 24 hours' );
	return $rows;
}

/* ------------------------------------------------------------------ */
/* Age and patient ID                                                  */
/* ------------------------------------------------------------------ */

function clinic_calc_age( $dob ) {
	$d = DateTime::createFromFormat( 'Y-m-d', $dob, wp_timezone() );
	if ( ! $d ) {
		return '';
	}
	$now = new DateTime( 'now', wp_timezone() );
	if ( $d > $now ) {
		return '';
	}
	return (int) $d->diff( $now )->y;
}

function clinic_patient_id_exists( $id ) {
	global $wpdb;
	$found = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
			'_cp_patient_id',
			$id
		)
	);
	return ! empty( $found );
}

function clinic_generate_patient_id() {
	$prefix = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) clinic_opt( 'id_prefix' ) ) );
	if ( '' === $prefix ) {
		$prefix = 'CLN';
	}
	$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	$len   = strlen( $chars );
	for ( $try = 0; $try < 25; $try++ ) {
		$suffix = '';
		for ( $i = 0; $i < 6; $i++ ) {
			$suffix .= $chars[ random_int( 0, $len - 1 ) ];
		}
		$id = $prefix . '-' . wp_date( 'Y' ) . '-' . $suffix;
		if ( ! clinic_patient_id_exists( $id ) ) {
			return $id;
		}
	}
	return $prefix . '-' . wp_date( 'Y' ) . '-' . strtoupper( wp_generate_password( 8, false, false ) );
}

/* ------------------------------------------------------------------ */
/* Patient registration schema                                         */
/* ------------------------------------------------------------------ */

function clinic_patient_groups() {
	return array(
		'personal'  => 'Personal Information',
		'contact'   => 'Contact Information',
		'emergency' => 'Emergency Contact Information',
		'medical'   => 'Medical Information',
	);
}

function clinic_patient_fields() {
	return array(
		'patient_id'      => array( 'group' => 'personal', 'label' => 'Patient ID', 'type' => 'patient_id', 'help' => 'Generated automatically. Keep it for your records.' ),
		'first_name'      => array( 'group' => 'personal', 'label' => 'First Name', 'type' => 'text', 'required' => true, 'autocomplete' => 'given-name' ),
		'middle_name'     => array( 'group' => 'personal', 'label' => 'Middle Name', 'type' => 'text', 'autocomplete' => 'additional-name' ),
		'last_name'       => array( 'group' => 'personal', 'label' => 'Last Name', 'type' => 'text', 'required' => true, 'autocomplete' => 'family-name' ),
		'suffix'          => array( 'group' => 'personal', 'label' => 'Suffix', 'type' => 'select', 'options' => array( 'Jr.', 'Sr.', 'II', 'III', 'IV' ), 'placeholder' => 'None' ),
		'dob'             => array( 'group' => 'personal', 'label' => 'Date of Birth', 'type' => 'date', 'required' => true, 'autocomplete' => 'bday' ),
		'age'             => array( 'group' => 'personal', 'label' => 'Age', 'type' => 'age', 'help' => 'Calculated automatically from the date of birth.' ),
		'sex'             => array( 'group' => 'personal', 'label' => 'Sex', 'type' => 'select', 'required' => true, 'options' => array( 'Male', 'Female' ), 'autocomplete' => 'sex' ),
		'civil_status'    => array( 'group' => 'personal', 'label' => 'Civil Status', 'type' => 'select', 'required' => true, 'options' => array( 'Single', 'Married', 'Widowed', 'Separated', 'Divorced' ) ),
		'blood_type'      => array( 'group' => 'personal', 'label' => 'Blood Type', 'type' => 'select', 'required' => true, 'options' => array( 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown' ) ),

		'mobile'          => array( 'group' => 'contact', 'label' => 'Mobile Number', 'type' => 'tel', 'required' => true, 'placeholder' => '09171234567', 'autocomplete' => 'tel' ),
		'email'           => array( 'group' => 'contact', 'label' => 'Email Address', 'type' => 'email', 'required' => true, 'placeholder' => 'name@example.com', 'autocomplete' => 'email' ),
		'address'         => array( 'group' => 'contact', 'label' => 'Home Address', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'autocomplete' => 'street-address' ),

		'ec_name'         => array( 'group' => 'emergency', 'label' => 'Contact Person Full Name', 'type' => 'text', 'required' => true ),
		'ec_relationship' => array( 'group' => 'emergency', 'label' => 'Relationship to Patient', 'type' => 'text', 'required' => true, 'placeholder' => 'Spouse, parent, sibling, friend' ),
		'ec_phone'        => array( 'group' => 'emergency', 'label' => 'Emergency Contact Number', 'type' => 'tel', 'required' => true, 'placeholder' => '09171234567' ),
		'ec_address'      => array( 'group' => 'emergency', 'label' => 'Emergency Complete Address (Optional)', 'type' => 'textarea', 'rows' => 2 ),

		'allergies'       => array( 'group' => 'medical', 'label' => 'Known Allergies', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'placeholder' => 'Type None if you have no known allergies' ),
		'conditions'      => array( 'group' => 'medical', 'label' => 'Existing Medical Conditions', 'type' => 'textarea', 'rows' => 2, 'placeholder' => 'For example hypertension, asthma' ),
		'history'         => array( 'group' => 'medical', 'label' => 'Previous Medical History', 'type' => 'textarea', 'rows' => 3, 'placeholder' => 'Past surgeries, hospital stays, major illnesses' ),
		'medications'     => array( 'group' => 'medical', 'label' => 'Current Medications', 'type' => 'textarea', 'rows' => 2, 'placeholder' => 'Name and dose, one per line' ),
		'preferred_hospital' => array( 'group' => 'medical', 'label' => 'Preferred Hospital or Clinic', 'type' => 'text' ),
	);
}

/**
 * Validate and sanitize registration input. Returns array( data, errors ).
 */
function clinic_validate_patient( $input ) {
	$fields = clinic_patient_fields();
	$data   = array();
	$errors = array();

	foreach ( $fields as $key => $f ) {
		if ( in_array( $key, array( 'patient_id', 'age' ), true ) ) {
			continue;
		}
		$raw = isset( $input[ $key ] ) ? $input[ $key ] : '';
		if ( is_array( $raw ) ) {
			$raw = '';
		}
		$raw = wp_unslash( $raw );

		if ( 'textarea' === $f['type'] ) {
			$val = sanitize_textarea_field( $raw );
		} elseif ( 'email' === $f['type'] ) {
			$val = sanitize_email( $raw );
		} else {
			$val = sanitize_text_field( $raw );
		}
		$val = trim( $val );

		if ( 'email' === $f['type'] && '' === $val && '' !== trim( (string) $raw ) ) {
			$errors[ $key ] = 'Please enter a valid email address.';
			$data[ $key ]   = '';
			continue;
		}

		if ( ! empty( $f['required'] ) && '' === $val ) {
			$errors[ $key ] = $f['label'] . ' is required.';
			$data[ $key ]   = '';
			continue;
		}
		if ( '' === $val ) {
			$data[ $key ] = '';
			continue;
		}

		$max = ( 'textarea' === $f['type'] ) ? 1000 : 120;
		if ( mb_strlen( $val ) > $max ) {
			$errors[ $key ] = $f['label'] . ' is too long.';
		}

		switch ( $f['type'] ) {
			case 'select':
				if ( ! in_array( $val, $f['options'], true ) ) {
					$errors[ $key ] = 'Please choose a valid option for ' . $f['label'] . '.';
				}
				break;
			case 'email':
				if ( ! is_email( $val ) ) {
					$errors[ $key ] = 'Please enter a valid email address.';
				}
				break;
			case 'tel':
				$clean = preg_replace( '/[\s\-\(\)\.]/', '', $val );
				if ( ! preg_match( '/^\+?\d{7,15}$/', $clean ) ) {
					$errors[ $key ] = 'Please enter a valid phone number, digits only (7 to 15 digits).';
				} else {
					$val = $clean;
				}
				break;
			case 'date':
				$d = DateTime::createFromFormat( 'Y-m-d', $val, wp_timezone() );
				if ( ! $d || $d->format( 'Y-m-d' ) !== $val ) {
					$errors[ $key ] = 'Please enter a valid date of birth.';
				} else {
					$now = new DateTime( 'now', wp_timezone() );
					$old = clone $now;
					$old->modify( '-120 years' );
					if ( $d > $now ) {
						$errors[ $key ] = 'Date of birth cannot be in the future.';
					} elseif ( $d < $old ) {
						$errors[ $key ] = 'Please check the date of birth.';
					}
				}
				break;
		}
		$data[ $key ] = $val;
	}
	return array( $data, $errors );
}

/* ------------------------------------------------------------------ */
/* Misc                                                                */
/* ------------------------------------------------------------------ */

function clinic_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
}

/**
 * Simple per-IP rate limit on successful submissions, to slow down spam.
 * Failed validations do not count, so honest typos never lock anyone out.
 */
function clinic_rate_limited( $bucket, $max = 10 ) {
	$key = 'cp_rl_' . $bucket . '_' . md5( clinic_client_ip() );
	return (int) get_transient( $key ) >= $max;
}

function clinic_rate_hit( $bucket, $window = HOUR_IN_SECONDS ) {
	$key = 'cp_rl_' . $bucket . '_' . md5( clinic_client_ip() );
	set_transient( $key, (int) get_transient( $key ) + 1, $window );
}

/**
 * Flash storage used for post, redirect, get flow.
 */
function clinic_flash_set( $payload ) {
	$token = wp_generate_password( 16, false, false );
	set_transient( 'cp_flash_' . $token, $payload, 10 * MINUTE_IN_SECONDS );
	return $token;
}

function clinic_flash_get() {
	if ( empty( $_GET['cp_r'] ) ) {
		return null;
	}
	$token = preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['cp_r'] ) );
	if ( '' === $token ) {
		return null;
	}
	$data = get_transient( 'cp_flash_' . $token );
	if ( false === $data ) {
		return null;
	}
	delete_transient( 'cp_flash_' . $token );
	return $data;
}

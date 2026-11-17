<?php
/**
 * Staff roles and capabilities. Patient records and appointments are
 * restricted to administrators, doctors and nurses.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function clinic_caps_for( $plural ) {
	return array(
		'edit_' . $plural,
		'edit_others_' . $plural,
		'publish_' . $plural,
		'read_private_' . $plural,
		'delete_' . $plural,
		'delete_private_' . $plural,
		'delete_published_' . $plural,
		'delete_others_' . $plural,
		'edit_private_' . $plural,
		'edit_published_' . $plural,
	);
}

function clinic_add_roles() {
	$patient_caps = clinic_caps_for( 'clinic_patients' );
	$appt_caps    = clinic_caps_for( 'clinic_appointments' );

	// Doctors: full clinical access, no deleting patient records.
	$doctor_caps = array( 'read' => true );
	foreach ( array_merge( $patient_caps, $appt_caps ) as $cap ) {
		if ( 0 === strpos( $cap, 'delete_' ) && false !== strpos( $cap, 'clinic_patients' ) ) {
			continue;
		}
		$doctor_caps[ $cap ] = true;
	}

	// Nurses: read and update records, manage appointments, no deleting.
	$nurse_caps = array( 'read' => true );
	$nurse_list = array(
		'edit_clinic_patients',
		'read_private_clinic_patients',
		'edit_private_clinic_patients',
		'edit_published_clinic_patients',
		'edit_clinic_appointments',
		'edit_others_clinic_appointments',
		'publish_clinic_appointments',
		'read_private_clinic_appointments',
		'edit_private_clinic_appointments',
		'edit_published_clinic_appointments',
	);
	foreach ( $nurse_list as $cap ) {
		$nurse_caps[ $cap ] = true;
	}

	remove_role( 'clinic_doctor' );
	remove_role( 'clinic_nurse' );
	add_role( 'clinic_doctor', 'Clinic Doctor', $doctor_caps );
	add_role( 'clinic_nurse', 'Clinic Nurse', $nurse_caps );

	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( array_merge( $patient_caps, $appt_caps ) as $cap ) {
			$admin->add_cap( $cap );
		}
	}
}

/**
 * Staff members who may use the dashboard.
 */
function clinic_user_can_view_patients() {
	return is_user_logged_in() && current_user_can( 'read_private_clinic_patients' );
}

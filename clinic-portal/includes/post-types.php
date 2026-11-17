<?php
/**
 * Custom post types, taxonomy, meta boxes and admin columns.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function clinic_register_post_types() {

	register_post_type(
		'clinic_physician',
		array(
			'labels'       => array(
				'name'          => 'Physicians',
				'singular_name' => 'Physician',
				'add_new_item'  => 'Add New Physician',
				'edit_item'     => 'Edit Physician',
				'all_items'     => 'All Physicians',
			),
			'public'       => true,
			'has_archive'  => false,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-businessperson',
			'menu_position' => 26,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
			'rewrite'      => array( 'slug' => 'doctor' ),
		)
	);

	register_post_type(
		'clinic_department',
		array(
			'labels'       => array(
				'name'          => 'Departments',
				'singular_name' => 'Department',
				'add_new_item'  => 'Add New Department',
				'edit_item'     => 'Edit Department',
				'all_items'     => 'All Departments',
			),
			'public'       => true,
			'has_archive'  => false,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-plus-alt',
			'menu_position' => 27,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
			'rewrite'      => array( 'slug' => 'department' ),
		)
	);

	register_post_type(
		'clinic_patient',
		array(
			'labels'              => array(
				'name'          => 'Patient Records',
				'singular_name' => 'Patient Record',
				'add_new_item'  => 'Add Patient Record',
				'edit_item'     => 'Edit Patient Record',
				'all_items'     => 'All Patient Records',
				'search_items'  => 'Search Patient Records',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-id-alt',
			'menu_position'       => 28,
			'supports'            => array( 'title' ),
			'capability_type'     => array( 'clinic_patient', 'clinic_patients' ),
			'map_meta_cap'        => true,
		)
	);

	register_post_type(
		'clinic_appointment',
		array(
			'labels'              => array(
				'name'          => 'Appointments',
				'singular_name' => 'Appointment',
				'add_new_item'  => 'Add Appointment',
				'edit_item'     => 'Edit Appointment',
				'all_items'     => 'All Appointments',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-calendar-alt',
			'menu_position'       => 29,
			'supports'            => array( 'title' ),
			'capability_type'     => array( 'clinic_appointment', 'clinic_appointments' ),
			'map_meta_cap'        => true,
		)
	);

	register_post_type(
		'clinic_faq',
		array(
			'labels'       => array(
				'name'          => 'FAQs',
				'singular_name' => 'FAQ',
				'add_new_item'  => 'Add New FAQ',
				'edit_item'     => 'Edit FAQ',
				'all_items'     => 'All FAQs',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-editor-help',
			'menu_position'       => 30,
			'supports'            => array( 'title', 'editor', 'page-attributes' ),
			'rewrite'             => false,
		)
	);

	register_taxonomy(
		'clinic_faq_section',
		'clinic_faq',
		array(
			'labels'            => array(
				'name'          => 'FAQ Sections',
				'singular_name' => 'FAQ Section',
			),
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'rewrite'           => false,
		)
	);
}
add_action( 'init', 'clinic_register_post_types' );

/* ------------------------------------------------------------------ */
/* Meta boxes: physician and department                                */
/* ------------------------------------------------------------------ */

function clinic_add_meta_boxes() {
	add_meta_box( 'cp_physician', 'Physician Details', 'clinic_physician_metabox', 'clinic_physician', 'normal', 'high' );
	add_meta_box( 'cp_department', 'Department Details', 'clinic_department_metabox', 'clinic_department', 'normal', 'high' );
	add_meta_box( 'cp_patient', 'Patient Information', 'clinic_patient_metabox', 'clinic_patient', 'normal', 'high' );
	add_meta_box( 'cp_appointment', 'Appointment Details', 'clinic_appointment_metabox', 'clinic_appointment', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'clinic_add_meta_boxes' );

function clinic_physician_metabox( $post ) {
	wp_nonce_field( 'cp_save_physician', 'cp_physician_nonce' );
	$m = function ( $k ) use ( $post ) {
		return get_post_meta( $post->ID, '_cp_' . $k, true );
	};
	$depts = get_posts( array( 'post_type' => 'clinic_department', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
	?>
	<table class="form-table" role="presentation">
		<tr><th><label for="cp_specialty">Specialty</label></th><td><input type="text" class="regular-text" id="cp_specialty" name="cp_specialty" value="<?php echo esc_attr( $m( 'specialty' ) ); ?>"></td></tr>
		<tr><th><label for="cp_credentials">Credentials</label></th><td><input type="text" class="large-text" id="cp_credentials" name="cp_credentials" value="<?php echo esc_attr( $m( 'credentials' ) ); ?>"><p class="description">For example: MD, FPCP, Diplomate in Internal Medicine.</p></td></tr>
		<tr><th><label for="cp_department_id">Department</label></th><td>
			<select id="cp_department_id" name="cp_department_id">
				<option value="0">None</option>
				<?php foreach ( $depts as $d ) : ?>
					<option value="<?php echo (int) $d->ID; ?>" <?php selected( (int) $m( 'department_id' ), $d->ID ); ?>><?php echo esc_html( $d->post_title ); ?></option>
				<?php endforeach; ?>
			</select></td></tr>
		<tr><th><label for="cp_experience">Years of experience</label></th><td><input type="number" min="0" max="70" id="cp_experience" name="cp_experience" value="<?php echo esc_attr( $m( 'experience' ) ); ?>"></td></tr>
		<tr><th><label for="cp_languages">Languages</label></th><td><input type="text" class="regular-text" id="cp_languages" name="cp_languages" value="<?php echo esc_attr( $m( 'languages' ) ); ?>"></td></tr>
		<tr><th><label for="cp_schedule">Consultation schedule</label></th><td><textarea class="large-text" rows="4" id="cp_schedule" name="cp_schedule"><?php echo esc_textarea( $m( 'schedule' ) ); ?></textarea><p class="description">One line per schedule, for example: Mon, Wed, Fri: 8:00 AM to 12:00 PM</p></td></tr>
	</table>
	<?php
}

function clinic_department_metabox( $post ) {
	wp_nonce_field( 'cp_save_department', 'cp_department_nonce' );
	$m = function ( $k ) use ( $post ) {
		return get_post_meta( $post->ID, '_cp_' . $k, true );
	};
	?>
	<table class="form-table" role="presentation">
		<tr><th><label for="cp_icon">Icon</label></th><td>
			<select id="cp_icon" name="cp_icon">
				<?php foreach ( clinic_icon_names() as $k => $label ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $m( 'icon' ), $k ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select></td></tr>
		<tr><th><label for="cp_head">Department head</label></th><td><input type="text" class="regular-text" id="cp_head" name="cp_head" value="<?php echo esc_attr( $m( 'head' ) ); ?>"></td></tr>
		<tr><th><label for="cp_services">Services offered</label></th><td><textarea class="large-text" rows="6" id="cp_services" name="cp_services"><?php echo esc_textarea( $m( 'services' ) ); ?></textarea><p class="description">One service per line. The excerpt is used as the short description on cards.</p></td></tr>
	</table>
	<?php
}

function clinic_save_physician_meta( $post_id ) {
	if ( ! isset( $_POST['cp_physician_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cp_physician_nonce'] ) ), 'cp_save_physician' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$text = array( 'specialty', 'credentials', 'languages' );
	foreach ( $text as $k ) {
		if ( isset( $_POST[ 'cp_' . $k ] ) ) {
			update_post_meta( $post_id, '_cp_' . $k, sanitize_text_field( wp_unslash( $_POST[ 'cp_' . $k ] ) ) );
		}
	}
	if ( isset( $_POST['cp_department_id'] ) ) {
		update_post_meta( $post_id, '_cp_department_id', absint( $_POST['cp_department_id'] ) );
	}
	if ( isset( $_POST['cp_experience'] ) ) {
		update_post_meta( $post_id, '_cp_experience', absint( $_POST['cp_experience'] ) );
	}
	if ( isset( $_POST['cp_schedule'] ) ) {
		update_post_meta( $post_id, '_cp_schedule', sanitize_textarea_field( wp_unslash( $_POST['cp_schedule'] ) ) );
	}
}
add_action( 'save_post_clinic_physician', 'clinic_save_physician_meta' );

function clinic_save_department_meta( $post_id ) {
	if ( ! isset( $_POST['cp_department_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cp_department_nonce'] ) ), 'cp_save_department' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['cp_icon'] ) ) {
		$icon = sanitize_key( wp_unslash( $_POST['cp_icon'] ) );
		if ( isset( clinic_icon_names()[ $icon ] ) ) {
			update_post_meta( $post_id, '_cp_icon', $icon );
		}
	}
	if ( isset( $_POST['cp_head'] ) ) {
		update_post_meta( $post_id, '_cp_head', sanitize_text_field( wp_unslash( $_POST['cp_head'] ) ) );
	}
	if ( isset( $_POST['cp_services'] ) ) {
		update_post_meta( $post_id, '_cp_services', sanitize_textarea_field( wp_unslash( $_POST['cp_services'] ) ) );
	}
}
add_action( 'save_post_clinic_department', 'clinic_save_department_meta' );

/* ------------------------------------------------------------------ */
/* Meta boxes: patient record and appointment (staff screens)          */
/* ------------------------------------------------------------------ */

function clinic_patient_metabox( $post ) {
	wp_nonce_field( 'cp_save_patient', 'cp_patient_nonce' );
	$groups = clinic_patient_groups();
	$fields = clinic_patient_fields();
	echo '<p class="description">Confidential patient information. Handle in line with the Data Privacy Act of 2012 (RA 10173).</p>';
	foreach ( $groups as $gkey => $glabel ) {
		echo '<h3 style="margin-top:1.2em">' . esc_html( $glabel ) . '</h3><table class="form-table" role="presentation">';
		foreach ( $fields as $key => $f ) {
			if ( $f['group'] !== $gkey ) {
				continue;
			}
			$val = get_post_meta( $post->ID, '_cp_' . $key, true );
			echo '<tr><th><label for="cp_f_' . esc_attr( $key ) . '">' . esc_html( $f['label'] ) . '</label></th><td>';
			if ( 'patient_id' === $key ) {
				echo '<input type="text" class="regular-text" readonly value="' . esc_attr( $val ) . '">';
			} elseif ( 'age' === $key ) {
				$dob = get_post_meta( $post->ID, '_cp_dob', true );
				echo '<input type="text" class="small-text" readonly value="' . esc_attr( clinic_calc_age( $dob ) ) . '"> <span class="description">Calculated from date of birth.</span>';
			} elseif ( 'select' === $f['type'] ) {
				echo '<select id="cp_f_' . esc_attr( $key ) . '" name="cp_f[' . esc_attr( $key ) . ']"><option value="">' . esc_html( isset( $f['placeholder'] ) ? $f['placeholder'] : 'Select' ) . '</option>';
				foreach ( $f['options'] as $o ) {
					echo '<option value="' . esc_attr( $o ) . '"' . selected( $val, $o, false ) . '>' . esc_html( $o ) . '</option>';
				}
				echo '</select>';
			} elseif ( 'textarea' === $f['type'] ) {
				echo '<textarea class="large-text" rows="' . (int) $f['rows'] . '" id="cp_f_' . esc_attr( $key ) . '" name="cp_f[' . esc_attr( $key ) . ']">' . esc_textarea( $val ) . '</textarea>';
			} else {
				$type = in_array( $f['type'], array( 'date', 'email', 'tel' ), true ) ? $f['type'] : 'text';
				echo '<input type="' . esc_attr( $type ) . '" class="regular-text" id="cp_f_' . esc_attr( $key ) . '" name="cp_f[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '">';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}
}

function clinic_save_patient_meta( $post_id ) {
	if ( ! isset( $_POST['cp_patient_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cp_patient_nonce'] ) ), 'cp_save_patient' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) || empty( $_POST['cp_f'] ) || ! is_array( $_POST['cp_f'] ) ) {
		return;
	}
	list( $data, $errors ) = clinic_validate_patient( $_POST['cp_f'] );
	foreach ( $data as $key => $val ) {
		if ( ! isset( $errors[ $key ] ) ) {
			update_post_meta( $post_id, '_cp_' . $key, $val );
		}
	}
	if ( isset( $data['dob'] ) && ! isset( $errors['dob'] ) ) {
		update_post_meta( $post_id, '_cp_age_at_registration', clinic_calc_age( $data['dob'] ) );
	}
}
add_action( 'save_post_clinic_patient', 'clinic_save_patient_meta' );

/**
 * Keep the record title in sync with the name and ID when edited in admin.
 */
function clinic_patient_title( $data, $postarr ) {
	if ( 'clinic_patient' !== $data['post_type'] || empty( $_POST['cp_f'] ) || ! is_array( $_POST['cp_f'] ) || empty( $postarr['ID'] ) ) {
		return $data;
	}
	if ( ! isset( $_POST['cp_patient_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cp_patient_nonce'] ) ), 'cp_save_patient' ) ) {
		return $data;
	}
	$first = isset( $_POST['cp_f']['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cp_f']['first_name'] ) ) : '';
	$last  = isset( $_POST['cp_f']['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cp_f']['last_name'] ) ) : '';
	$pid   = get_post_meta( $postarr['ID'], '_cp_patient_id', true );
	if ( $first || $last ) {
		$data['post_title'] = trim( $last . ', ' . $first ) . ( $pid ? ' (' . $pid . ')' : '' );
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'clinic_patient_title', 10, 2 );

function clinic_appointment_metabox( $post ) {
	wp_nonce_field( 'cp_save_appointment', 'cp_appointment_nonce' );
	$m = function ( $k ) use ( $post ) {
		return get_post_meta( $post->ID, '_cp_' . $k, true );
	};
	$dept = (int) $m( 'department_id' );
	$phys = (int) $m( 'physician_id' );
	$rows = array(
		'Patient name'  => $m( 'name' ),
		'Mobile'        => $m( 'mobile' ),
		'Email'         => $m( 'email' ),
		'Patient ID'    => $m( 'patient_id' ) ? $m( 'patient_id' ) : 'Not provided',
		'Department'    => $dept ? get_the_title( $dept ) : 'Not set',
		'Physician'     => $phys ? get_the_title( $phys ) : 'Any available physician',
		'Date'          => $m( 'date' ),
		'Time'          => $m( 'time' ) ? clinic_time_label( $m( 'time' ) ) : '',
		'Reason'        => $m( 'reason' ),
	);
	echo '<table class="form-table" role="presentation">';
	foreach ( $rows as $label => $val ) {
		echo '<tr><th>' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $val ) ) . '</td></tr>';
	}
	$status = $m( 'status' ) ? $m( 'status' ) : 'pending';
	echo '<tr><th><label for="cp_status">Status</label></th><td><select id="cp_status" name="cp_status">';
	foreach ( array( 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled' ) as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $status, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select></td></tr></table>';
}

function clinic_save_appointment_meta( $post_id ) {
	if ( ! isset( $_POST['cp_appointment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cp_appointment_nonce'] ) ), 'cp_save_appointment' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['cp_status'] ) ) {
		$s = sanitize_key( wp_unslash( $_POST['cp_status'] ) );
		if ( in_array( $s, array( 'pending', 'confirmed', 'completed', 'cancelled' ), true ) ) {
			update_post_meta( $post_id, '_cp_status', $s );
		}
	}
}
add_action( 'save_post_clinic_appointment', 'clinic_save_appointment_meta' );

/* ------------------------------------------------------------------ */
/* Admin list columns                                                  */
/* ------------------------------------------------------------------ */

function clinic_patient_columns( $cols ) {
	return array(
		'cb'         => $cols['cb'],
		'title'      => 'Patient',
		'cp_pid'     => 'Patient ID',
		'cp_age'     => 'Age',
		'cp_sex'     => 'Sex',
		'cp_mobile'  => 'Mobile',
		'date'       => 'Registered',
	);
}
add_filter( 'manage_clinic_patient_posts_columns', 'clinic_patient_columns' );

function clinic_patient_column_data( $col, $post_id ) {
	switch ( $col ) {
		case 'cp_pid':
			echo esc_html( get_post_meta( $post_id, '_cp_patient_id', true ) );
			break;
		case 'cp_age':
			echo esc_html( clinic_calc_age( get_post_meta( $post_id, '_cp_dob', true ) ) );
			break;
		case 'cp_sex':
			echo esc_html( get_post_meta( $post_id, '_cp_sex', true ) );
			break;
		case 'cp_mobile':
			echo esc_html( get_post_meta( $post_id, '_cp_mobile', true ) );
			break;
	}
}
add_action( 'manage_clinic_patient_posts_custom_column', 'clinic_patient_column_data', 10, 2 );

function clinic_appointment_columns( $cols ) {
	return array(
		'cb'        => $cols['cb'],
		'title'     => 'Appointment',
		'cp_when'   => 'Date and time',
		'cp_dept'   => 'Department',
		'cp_phys'   => 'Physician',
		'cp_status' => 'Status',
	);
}
add_filter( 'manage_clinic_appointment_posts_columns', 'clinic_appointment_columns' );

function clinic_appointment_column_data( $col, $post_id ) {
	switch ( $col ) {
		case 'cp_when':
			$t = get_post_meta( $post_id, '_cp_time', true );
			echo esc_html( get_post_meta( $post_id, '_cp_date', true ) . ' ' . ( $t ? clinic_time_label( $t ) : '' ) );
			break;
		case 'cp_dept':
			$id = (int) get_post_meta( $post_id, '_cp_department_id', true );
			echo $id ? esc_html( get_the_title( $id ) ) : '-';
			break;
		case 'cp_phys':
			$id = (int) get_post_meta( $post_id, '_cp_physician_id', true );
			echo $id ? esc_html( get_the_title( $id ) ) : 'Any available';
			break;
		case 'cp_status':
			$s = get_post_meta( $post_id, '_cp_status', true );
			echo esc_html( ucfirst( $s ? $s : 'pending' ) );
			break;
	}
}
add_action( 'manage_clinic_appointment_posts_custom_column', 'clinic_appointment_column_data', 10, 2 );

/* ------------------------------------------------------------------ */
/* Single physician and department pages                               */
/* ------------------------------------------------------------------ */

function clinic_single_content( $content ) {
	if ( ! is_singular( array( 'clinic_physician', 'clinic_department' ) ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$id = get_the_ID();
	ob_start();
	if ( 'clinic_physician' === get_post_type() ) {
		$dept = (int) get_post_meta( $id, '_cp_department_id', true );
		echo '<div class="cp-single-meta">';
		echo '<p><strong>Specialty:</strong> ' . esc_html( get_post_meta( $id, '_cp_specialty', true ) ) . '</p>';
		echo '<p><strong>Credentials:</strong> ' . esc_html( get_post_meta( $id, '_cp_credentials', true ) ) . '</p>';
		if ( $dept ) {
			echo '<p><strong>Department:</strong> ' . esc_html( get_the_title( $dept ) ) . '</p>';
		}
		$sched = get_post_meta( $id, '_cp_schedule', true );
		if ( $sched ) {
			echo '<p><strong>Schedule:</strong></p><ul>';
			foreach ( preg_split( '/\r\n|\r|\n/', $sched ) as $line ) {
				if ( trim( $line ) ) {
					echo '<li>' . esc_html( $line ) . '</li>';
				}
			}
			echo '</ul>';
		}
		echo '<p><a class="cp-btn cp-btn--primary" href="' . esc_url( add_query_arg( 'physician', $id, clinic_page_url( 'book-appointment' ) ) ) . '">Book a consultation</a></p></div>';
	} else {
		$services = get_post_meta( $id, '_cp_services', true );
		if ( $services ) {
			echo '<div class="cp-single-meta"><h2>Services offered</h2><ul>';
			foreach ( preg_split( '/\r\n|\r|\n/', $services ) as $line ) {
				if ( trim( $line ) ) {
					echo '<li>' . esc_html( $line ) . '</li>';
				}
			}
			echo '</ul><p><a class="cp-btn cp-btn--primary" href="' . esc_url( add_query_arg( 'department', $id, clinic_page_url( 'book-appointment' ) ) ) . '">Book in this department</a></p></div>';
		}
	}
	return $content . ob_get_clean();
}
add_filter( 'the_content', 'clinic_single_content' );

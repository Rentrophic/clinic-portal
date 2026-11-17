<?php
/**
 * Sample content created on activation (and by the "Create missing pages" tool).
 * Everything is idempotent: existing content is never overwritten.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Let pages carry a short intro line, shown in the page header.
add_action(
	'init',
	function () {
		add_post_type_support( 'page', 'excerpt' );
	}
);

function clinic_seed_find( $type, $title ) {
	$found = get_page_by_path( sanitize_title( $title ), OBJECT, $type );
	return $found ? (int) $found->ID : 0;
}

function clinic_seed_departments() {
	$list = array(
		array( 'General Medicine', 'stethoscope', 'First point of care for adults: checkups, common illnesses and long term conditions.', 'Dr. Maria Santos', "Adult consultations\nAnnual physical examination\nHypertension and diabetes care\nMedical certificates\nAdult immunization" ),
		array( 'Cardiology', 'heart', 'Heart health screening, diagnosis and ongoing care for blood pressure and heart disease.', 'Dr. Ramon Reyes', "Electrocardiogram (ECG)\n2D echocardiography\nStress testing\nBlood pressure management\nCardiac risk assessment" ),
		array( 'Pediatrics', 'smile', 'Gentle care for babies, children and teens, from newborn checkups to school health.', 'Dr. Angela Cruz', "Well baby checkups\nChildhood immunization\nGrowth and development monitoring\nSick child consultations\nSchool and sports clearance" ),
		array( 'Diagnostics and Laboratory', 'flask', 'Accurate tests with quick turnaround, so your doctor can decide with confidence.', 'Dr. Paolo Villanueva', "Complete blood count and chemistry\nUrinalysis and stool exams\nX-ray\nUltrasound\nPre-employment packages" ),
		array( 'Obstetrics and Gynecology', 'venus', 'Women health care through every stage, from prenatal visits to menopause.', 'Dr. Isabel Garcia', "Prenatal and postnatal care\nFamily planning\nPap smear screening\nPelvic ultrasound\nMenopause consultation" ),
		array( 'Dermatology', 'drop', 'Treatment for skin, hair and nail concerns, for every age.', 'Dr. Teresa Navarro', "Acne and eczema treatment\nSkin allergy evaluation\nMole and skin check\nHair and scalp care\nMinor skin procedures" ),
		array( 'Orthopedics', 'bone', 'Care for bones, joints and muscles, including sports injuries and back pain.', 'Dr. Miguel Torres', "Fracture and sprain care\nJoint pain and arthritis\nSports injury assessment\nBack and neck pain\nCasting and splinting" ),
		array( 'Eye Care', 'eye', 'Complete eye exams and treatment to protect your vision.', 'Dr. Carlos Mendoza', "Comprehensive eye exam\nEyeglass prescription\nCataract screening\nGlaucoma screening\nChildren vision screening" ),
	);
	$ids = array();
	foreach ( $list as $i => $row ) {
		$existing = clinic_seed_find( 'clinic_department', $row[0] );
		if ( $existing ) {
			$ids[ $row[0] ] = $existing;
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'clinic_department',
				'post_status'  => 'publish',
				'post_title'   => $row[0],
				'post_excerpt' => $row[2],
				'post_content' => $row[2],
				'menu_order'   => $i,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_cp_icon', $row[1] );
			update_post_meta( $id, '_cp_head', $row[3] );
			update_post_meta( $id, '_cp_services', $row[4] );
			$ids[ $row[0] ] = (int) $id;
		}
	}
	return $ids;
}

function clinic_seed_physicians( $dept_ids ) {
	$list = array(
		array( 'Dr. Maria Santos', 'Internal Medicine', 'MD, FPCP, Diplomate in Internal Medicine', 'General Medicine', 14, 'English, Filipino', "Mon, Wed, Fri: 8:00 AM to 12:00 PM\nTue, Thu: 1:00 PM to 4:00 PM" ),
		array( 'Dr. Ramon Reyes', 'Cardiologist', 'MD, FPCC, Fellow in Adult Cardiology', 'Cardiology', 18, 'English, Filipino', "Mon, Thu: 9:00 AM to 1:00 PM\nSat: 8:00 AM to 11:00 AM" ),
		array( 'Dr. Angela Cruz', 'Pediatrician', 'MD, FPPS, Diplomate in Pediatrics', 'Pediatrics', 11, 'English, Filipino', "Mon to Fri: 9:00 AM to 12:00 PM\nWed, Fri: 2:00 PM to 4:30 PM" ),
		array( 'Dr. Paolo Villanueva', 'Clinical Pathologist', 'MD, FPSP, Diplomate in Clinical Pathology', 'Diagnostics and Laboratory', 16, 'English, Filipino', "Mon to Sat: 8:00 AM to 4:00 PM (laboratory hours)" ),
		array( 'Dr. Isabel Garcia', 'OB-GYN', 'MD, FPOGS, Fellow in Obstetrics and Gynecology', 'Obstetrics and Gynecology', 13, 'English, Filipino', "Tue, Thu: 9:00 AM to 12:00 PM\nSat: 8:00 AM to 12:00 PM" ),
		array( 'Dr. Teresa Navarro', 'Dermatologist', 'MD, FPDS, Diplomate in Dermatology', 'Dermatology', 9, 'English, Filipino', "Wed: 1:00 PM to 5:00 PM\nFri: 9:00 AM to 12:00 PM" ),
		array( 'Dr. Miguel Torres', 'Orthopedic Surgeon', 'MD, FPOA, Fellow in Orthopedic Surgery', 'Orthopedics', 15, 'English, Filipino', "Tue, Fri: 1:00 PM to 4:30 PM\nSat: 9:00 AM to 12:00 PM" ),
		array( 'Dr. Carlos Mendoza', 'Ophthalmologist', 'MD, FPAO, Diplomate in Ophthalmology', 'Eye Care', 12, 'English, Filipino', "Mon, Wed: 1:00 PM to 4:00 PM\nThu: 9:00 AM to 12:00 PM" ),
	);
	foreach ( $list as $i => $row ) {
		if ( clinic_seed_find( 'clinic_physician', $row[0] ) ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'clinic_physician',
				'post_status'  => 'publish',
				'post_title'   => $row[0],
				'post_excerpt' => $row[1] . ' with ' . $row[4] . ' years of experience.',
				'post_content' => $row[0] . ' is a ' . strtolower( $row[1] ) . ' who believes good care starts with listening. Book a consultation to discuss your concerns in a calm, unhurried visit.',
				'menu_order'   => $i,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_cp_specialty', $row[1] );
			update_post_meta( $id, '_cp_credentials', $row[2] );
			update_post_meta( $id, '_cp_department_id', isset( $dept_ids[ $row[3] ] ) ? $dept_ids[ $row[3] ] : 0 );
			update_post_meta( $id, '_cp_experience', $row[4] );
			update_post_meta( $id, '_cp_languages', $row[5] );
			update_post_meta( $id, '_cp_schedule', $row[6] );
		}
	}
}

function clinic_seed_faqs() {
	$data = array(
		'General' => array(
			array( 'What are your clinic hours?', 'We are open Monday to Friday and on Saturday mornings. The exact hours are shown on the Contact page. Our emergency hotline is open 24 hours.' ),
			array( 'Do you accept walk in patients?', 'Yes, walk in patients are seen as doctors become available. Booking an appointment online guarantees your time slot and shortens your wait.' ),
			array( 'What should I bring to my first visit?', 'Bring a valid government ID, your Patient ID, any recent laboratory results or prescriptions, and your insurance or HMO card if you have one.' ),
		),
		'Appointments' => array(
			array( 'How do I book an appointment?', 'Open the Book an Appointment page, choose a department, a physician if you have a preference, then pick a date and time. We will confirm your slot by email or phone.' ),
			array( 'Can I change or cancel my booking?', 'Yes. Please call the clinic at least a day before your visit and quote your reference number, so we can offer the slot to another patient.' ),
			array( 'How far ahead can I book?', 'You can book up to 90 days ahead. Same day slots are available while the clinic is open.' ),
		),
		'Patient Registration' => array(
			array( 'Do I need to register before my first visit?', 'It is not required, but registering online saves time at the front desk and gives you a Patient ID that speeds up future bookings.' ),
			array( 'What is a Patient ID?', 'It is a unique code created for you when you register. Keep it handy, you can use it when booking appointments and when you call the clinic.' ),
			array( 'Can I register someone else, such as my child or parent?', 'Yes. Use the patient details, not your own, and give your contact number as the emergency contact if you are the guardian or caregiver.' ),
		),
		'Billing and Insurance' => array(
			array( 'What payment methods do you accept?', 'We accept cash, major debit and credit cards and selected e-wallets. Please ask the front desk about current options.' ),
			array( 'Do you accept HMO cards?', 'We work with several HMO providers. Please call us with your card details and we will confirm whether your plan is accepted before your visit.' ),
		),
		'Privacy and Records' => array(
			array( 'How is my information protected?', 'Patient records are stored privately and can only be opened by authorised doctors, nurses and administrators. Every access to a record is logged.' ),
			array( 'How can I get a copy of my records?', 'Visit the front desk with a valid ID or ask your doctor. You may also ask us to correct any detail that is wrong, in line with the Data Privacy Act of 2012.' ),
		),
	);
	foreach ( $data as $section => $items ) {
		$term = term_exists( $section, 'clinic_faq_section' );
		if ( ! $term ) {
			$term = wp_insert_term( $section, 'clinic_faq_section' );
		}
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
		foreach ( $items as $i => $qa ) {
			if ( clinic_seed_find( 'clinic_faq', $qa[0] ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'clinic_faq',
					'post_status'  => 'publish',
					'post_title'   => $qa[0],
					'post_content' => $qa[1],
					'menu_order'   => $i,
				)
			);
			if ( $id && ! is_wp_error( $id ) ) {
				wp_set_object_terms( $id, array( $term_id ), 'clinic_faq_section' );
			}
		}
	}
}

function clinic_seed_news() {
	$cat = get_term_by( 'name', 'Clinic News', 'category' );
	if ( ! $cat ) {
		$res = wp_insert_term( 'Clinic News', 'category' );
		$cat_id = is_wp_error( $res ) ? 0 : (int) $res['term_id'];
	} else {
		$cat_id = (int) $cat->term_id;
	}
	if ( $cat_id ) {
		update_option( 'clinic_portal_news_cat', $cat_id );
	}
	$posts = array(
		array( 'Seasonal flu vaccination is now available', "Our Pediatrics and General Medicine teams now offer seasonal flu vaccination for children and adults.\n\nVaccination takes only a few minutes. Book a slot online, or ask about it during your next visit. Please tell our nurse about any allergies before you receive the vaccine.", 0 ),
		array( 'Extended Saturday consultation hours', "Many of you asked for more weekend options, so selected doctors now see patients on Saturday mornings.\n\nCheck each physician's schedule on the Physicians page and book your preferred time online.", -4 ),
		array( 'Online patient registration is live', "You can now register as a patient from your phone or computer. Fill in your details once, receive your Patient ID, and skip the clipboard at the front desk.\n\nYour information is stored privately and is only visible to authorised clinic staff.", -9 ),
		array( 'Health tip: checking your blood pressure at home', "Sit quietly for five minutes, keep your arm at heart level and take two readings one minute apart. Write the numbers down and bring them to your next consultation.\n\nIf you have a very high reading with chest pain, severe headache or trouble breathing, call the emergency hotline right away.", -15 ),
	);
	foreach ( $posts as $row ) {
		if ( clinic_seed_find( 'post', $row[0] ) ) {
			continue;
		}
		$date = gmdate( 'Y-m-d H:i:s', time() + ( $row[2] * DAY_IN_SECONDS ) );
		$id   = wp_insert_post(
			array(
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'post_title'    => $row[0],
				'post_content'  => $row[1],
				'post_date_gmt' => $date,
				'post_date'     => get_date_from_gmt( $date ),
				'post_category' => $cat_id ? array( $cat_id ) : array(),
			)
		);
	}
}

function clinic_seed_pages() {
	$content = array(
		'home'                 => array( '[clinic_home]', '' ),
		'physicians'           => array( '[clinic_physicians]', 'Meet our doctors, check their schedules and book a consultation in a few clicks.' ),
		'departments-services' => array( '[clinic_departments]', 'Specialist and general care under one roof.' ),
		'book-appointment'     => array( '[clinic_booking]', 'Choose a department, a doctor and a time that works for you.' ),
		'patient-registration' => array( '[clinic_registration]', 'Register once online and receive your Patient ID.' ),
		'staff-dashboard'      => array( '[clinic_staff_dashboard]', 'Secure access for clinic doctors, nurses and administrators.' ),
		'announcements-faq'    => array( '[clinic_news_faq]', 'News, service updates and answers to common questions.' ),
		'contact-us'           => array( '[clinic_contact]', 'We are here to help. Call, message or visit us.' ),
	);
	$ids = array();
	foreach ( clinic_pages() as $slug => $title ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$ids[ $slug ] = (int) $page->ID;
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content[ $slug ][0],
				'post_excerpt' => $content[ $slug ][1],
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			$ids[ $slug ] = (int) $id;
		}
	}
	return $ids;
}

function clinic_seed_menu( $page_ids ) {
	$name = 'Clinic Main Menu';
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu ) {
		update_option( 'clinic_portal_menu_id', (int) $menu->term_id );
		return;
	}
	$menu_id = wp_create_nav_menu( $name );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}
	$items = array(
		'home'                 => 'Home',
		'physicians'           => 'Physicians',
		'departments-services' => 'Services',
		'book-appointment'     => 'Appointments',
		'patient-registration' => 'Register',
		'announcements-faq'    => 'News and FAQ',
		'contact-us'           => 'Contact',
	);
	foreach ( $items as $slug => $label ) {
		if ( empty( $page_ids[ $slug ] ) ) {
			continue;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $label,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page_ids[ $slug ],
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}
	update_option( 'clinic_portal_menu_id', (int) $menu_id );
}

/**
 * @param bool $first_run True when called from plugin activation.
 */
function clinic_portal_seed( $first_run = false ) {
	if ( $first_run && get_option( 'clinic_portal_seeded' ) ) {
		return;
	}

	if ( ! get_option( 'permalink_structure' ) ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
	}

	$dept_ids = clinic_seed_departments();
	clinic_seed_physicians( $dept_ids );
	clinic_seed_faqs();
	clinic_seed_news();
	$page_ids = clinic_seed_pages();
	clinic_seed_menu( $page_ids );

	if ( $first_run && ! empty( $page_ids['home'] ) && 'posts' === get_option( 'show_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_ids['home'] );
	}

	update_option( 'clinic_portal_seeded', 1 );
}

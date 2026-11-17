<?php
/**
 * Public shortcodes: home, physicians, departments, FAQ, announcements, contact.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once CLINIC_PORTAL_DIR . 'includes/forms.php';

/* ------------------------------------------------------------------ */
/* Data helpers                                                        */
/* ------------------------------------------------------------------ */

function clinic_get_departments( $limit = -1 ) {
	return get_posts(
		array(
			'post_type'   => 'clinic_department',
			'post_status' => 'publish',
			'numberposts' => $limit,
			'orderby'     => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		)
	);
}

function clinic_get_physicians( $limit = -1 ) {
	return get_posts(
		array(
			'post_type'   => 'clinic_physician',
			'post_status' => 'publish',
			'numberposts' => $limit,
			'orderby'     => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		)
	);
}

/**
 * Stable position of a physician, used to give every doctor a different avatar.
 */
function clinic_physician_index( $id ) {
	static $map = null;
	if ( null === $map ) {
		$map = array();
		foreach ( clinic_get_physicians() as $i => $p ) {
			$map[ $p->ID ] = $i;
		}
	}
	return isset( $map[ $id ] ) ? $map[ $id ] : 0;
}

function clinic_lines( $text ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line ) {
			$out[] = $line;
		}
	}
	return $out;
}

function clinic_tel( $number ) {
	return 'tel:' . preg_replace( '/[^\d+]/', '', $number );
}

/* ------------------------------------------------------------------ */
/* Cards                                                               */
/* ------------------------------------------------------------------ */

function clinic_physician_card( $p ) {
	$id        = $p->ID;
	$dept_id   = (int) get_post_meta( $id, '_cp_department_id', true );
	$specialty = get_post_meta( $id, '_cp_specialty', true );
	$creds     = get_post_meta( $id, '_cp_credentials', true );
	$sched     = clinic_lines( get_post_meta( $id, '_cp_schedule', true ) );
	$exp       = (int) get_post_meta( $id, '_cp_experience', true );
	$langs     = get_post_meta( $id, '_cp_languages', true );
	$search    = strtolower( $p->post_title . ' ' . $specialty . ' ' . $creds . ' ' . ( $dept_id ? get_the_title( $dept_id ) : '' ) );
	ob_start();
	?>
	<article class="cp-card cp-doc cp-reveal" data-dept="<?php echo (int) $dept_id; ?>" data-search="<?php echo esc_attr( $search ); ?>">
		<div class="cp-doc__photo">
			<?php
			if ( has_post_thumbnail( $id ) ) {
				echo get_the_post_thumbnail( $id, 'medium', array( 'alt' => esc_attr( $p->post_title ), 'loading' => 'lazy' ) );
			} else {
				echo clinic_avatar( clinic_physician_index( $id ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>
		<div class="cp-doc__body">
			<h3 class="cp-doc__name"><?php echo esc_html( $p->post_title ); ?></h3>
			<?php if ( $specialty ) : ?><p class="cp-doc__specialty"><?php echo esc_html( $specialty ); ?></p><?php endif; ?>
			<?php if ( $creds ) : ?><p class="cp-doc__creds"><?php echo clinic_icon( 'shield', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $creds ); ?></p><?php endif; ?>
			<p class="cp-doc__meta">
				<?php if ( $dept_id ) : ?><span><?php echo esc_html( get_the_title( $dept_id ) ); ?></span><?php endif; ?>
				<?php if ( $exp ) : ?><span><?php echo (int) $exp; ?> years experience</span><?php endif; ?>
				<?php if ( $langs ) : ?><span><?php echo esc_html( $langs ); ?></span><?php endif; ?>
			</p>
			<?php if ( $sched ) : ?>
				<ul class="cp-schedule" aria-label="Consultation schedule">
					<?php foreach ( $sched as $line ) : ?>
						<li><?php echo clinic_icon( 'clock', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="cp-actions">
				<a class="cp-btn cp-btn--primary cp-btn--small" href="<?php echo esc_url( add_query_arg( 'physician', $id, clinic_page_url( 'book-appointment' ) ) ); ?>">Book consultation</a>
				<a class="cp-btn cp-btn--ghost cp-btn--small" href="<?php echo esc_url( get_permalink( $id ) ); ?>">View profile</a>
			</div>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

function clinic_department_card( $d, $full = false ) {
	$icon     = get_post_meta( $d->ID, '_cp_icon', true );
	$icon     = $icon ? $icon : 'stethoscope';
	$head     = get_post_meta( $d->ID, '_cp_head', true );
	$services = clinic_lines( get_post_meta( $d->ID, '_cp_services', true ) );
	$excerpt  = has_excerpt( $d->ID ) ? get_the_excerpt( $d ) : wp_trim_words( wp_strip_all_tags( $d->post_content ), 22 );
	$tints    = array(
		'stethoscope' => array( '#e8f3fd', '#0b5cab' ),
		'heart'       => array( '#fde8ec', '#b42318' ),
		'smile'       => array( '#fff4dc', '#9a5b00' ),
		'flask'       => array( '#ece9fb', '#4b3fb0' ),
		'venus'       => array( '#fde8f4', '#a3206b' ),
		'drop'        => array( '#e3f6f1', '#0b7a77' ),
		'bone'        => array( '#eef0f3', '#3d4b5c' ),
		'eye'         => array( '#e3f1fb', '#0b6aa3' ),
	);
	$t = isset( $tints[ $icon ] ) ? $tints[ $icon ] : $tints['stethoscope'];
	ob_start();
	?>
	<article class="cp-card cp-dept cp-reveal">
		<span class="cp-dept__icon" style="background:<?php echo esc_attr( $t[0] ); ?>;color:<?php echo esc_attr( $t[1] ); ?>"><?php echo clinic_icon( $icon, 30 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<h3><?php echo esc_html( $d->post_title ); ?></h3>
		<?php if ( $excerpt ) : ?><p><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
		<?php if ( $full && $services ) : ?>
			<ul class="cp-checklist cp-checklist--tight">
				<?php foreach ( $services as $s ) : ?>
					<li><?php echo clinic_icon( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $s ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( $full && $head ) : ?><p class="cp-muted">Department head: <strong><?php echo esc_html( $head ); ?></strong></p><?php endif; ?>
		<div class="cp-actions">
			<a class="cp-btn cp-btn--primary cp-btn--small" href="<?php echo esc_url( add_query_arg( 'department', $d->ID, clinic_page_url( 'book-appointment' ) ) ); ?>">Book here</a>
			<a class="cp-btn cp-btn--ghost cp-btn--small" href="<?php echo esc_url( add_query_arg( 'department', $d->ID, clinic_page_url( 'physicians' ) ) ); ?>">See doctors</a>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

function clinic_news_cards( $limit = 3 ) {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'numberposts'         => $limit,
		'ignore_sticky_posts' => true,
	);
	$cat = (int) get_option( 'clinic_portal_news_cat' );
	if ( $cat ) {
		$args['category'] = $cat;
	}
	$posts = get_posts( $args );
	if ( ! $posts ) {
		return '<p class="cp-muted">No announcements yet. Please check back soon.</p>';
	}
	ob_start();
	echo '<div class="cp-grid-cards cp-grid-cards--3">';
	foreach ( $posts as $i => $p ) {
		?>
		<article class="cp-card cp-news cp-reveal">
			<div class="cp-news__media">
				<?php
				if ( has_post_thumbnail( $p ) ) {
					echo get_the_post_thumbnail( $p, 'medium_large', array( 'loading' => 'lazy' ) );
				} else {
					echo clinic_news_art( $i ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
			<div class="cp-news__body">
				<p class="cp-news__date"><time datetime="<?php echo esc_attr( get_the_date( 'c', $p ) ); ?>"><?php echo esc_html( get_the_date( 'F j, Y', $p ) ); ?></time></p>
				<h3><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></h3>
				<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $p->post_content ), 24 ) ); ?></p>
				<a class="cp-link" href="<?php echo esc_url( get_permalink( $p ) ); ?>">Read more <?php echo clinic_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
		</article>
		<?php
	}
	echo '</div>';
	return ob_get_clean();
}

function clinic_media_block() {
	$video = clinic_opt( 'video_url' );
	$audio = clinic_opt( 'audio_url' );
	ob_start();
	?>
	<div class="cp-media-grid">
		<figure class="cp-media cp-reveal">
			<?php
			if ( $video ) {
				if ( preg_match( '/\.(mp4|webm|ogv)(\?.*)?$/i', $video ) ) {
					echo '<video controls preload="metadata" playsinline src="' . esc_url( $video ) . '">Your browser does not support video.</video>';
				} else {
					$embed = wp_oembed_get( $video, array( 'width' => 640 ) );
					echo $embed ? '<div class="cp-embed">' . $embed . '</div>' : '<a class="cp-btn cp-btn--primary" href="' . esc_url( $video ) . '">Watch the video</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
			} else {
				?>
				<div class="cp-media__placeholder" role="img" aria-label="Video placeholder">
					<span class="cp-media__play"><?php echo clinic_icon( 'play', 56 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="cp-media__tag">Video placeholder</span>
				</div>
				<?php
			}
			?>
			<figcaption>
				<strong>Welcome to the clinic</strong>
				<span>A short tour of our rooms, our team and what to expect on your first visit.</span>
			</figcaption>
		</figure>
		<figure class="cp-media cp-media--audio cp-reveal">
			<?php
			if ( $audio ) {
				echo '<div class="cp-media__art">' . clinic_icon( 'headphones', 54 ) . '</div><audio controls preload="none" src="' . esc_url( $audio ) . '">Your browser does not support audio.</audio>'; // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				?>
				<div class="cp-media__placeholder cp-media__placeholder--audio" role="img" aria-label="Audio placeholder">
					<span class="cp-media__play"><?php echo clinic_icon( 'headphones', 54 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="cp-media__bars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
					<span class="cp-media__tag">Audio placeholder</span>
				</div>
				<?php
			}
			?>
			<figcaption>
				<strong>Weekly health tips</strong>
				<span>Listen to short, plain language advice from our doctors on staying well.</span>
			</figcaption>
		</figure>
	</div>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------ */
/* Home                                                                */
/* ------------------------------------------------------------------ */

function clinic_sc_home() {
	$reg    = clinic_page_url( 'patient-registration' );
	$book   = clinic_page_url( 'book-appointment' );
	$phone  = clinic_opt( 'phone' );
	$emg    = clinic_opt( 'emergency_phone' );
	$name   = clinic_opt( 'clinic_name' );
	$depts  = clinic_get_departments( 4 );
	$docs   = clinic_get_physicians( 3 );
	$n_dept = (int) wp_count_posts( 'clinic_department' )->publish;
	$n_doc  = (int) wp_count_posts( 'clinic_physician' )->publish;
	$cta    = array(
		'new'      => array(
			'label' => 'Register Patient',
			'href'  => $reg,
			'text'  => 'Create your patient record online in about three minutes and skip the paperwork at the front desk.',
			'alt'   => 'Book Appointment',
			'alth'  => $book,
		),
		'existing' => array(
			'label' => 'Book Appointment',
			'href'  => $book,
			'text'  => 'Pick a department, a doctor and a time that suits you. We will confirm your slot by email or phone.',
			'alt'   => 'Register Patient',
			'alth'  => $reg,
		),
	);
	ob_start();
	?>
	<section class="cp-hero" aria-labelledby="cp-hero-title">
		<div class="cp-container cp-hero__inner">
			<div class="cp-hero__text">
				<p class="cp-eyebrow"><?php echo esc_html( $name ); ?></p>
				<h1 id="cp-hero-title">Caring for you and your family, <span>close to home</span></h1>
				<div class="cp-seg" role="group" aria-label="Choose how you would like to start">
					<button type="button" class="is-active" data-cp-cta="new" aria-pressed="true">I am a new patient</button>
					<button type="button" data-cp-cta="existing" aria-pressed="false">I am a returning patient</button>
				</div>
				<p class="cp-hero__lead" data-cp-cta-text aria-live="polite"><?php echo esc_html( $cta['new']['text'] ); ?></p>
				<div class="cp-actions" data-cp-cta-config="<?php echo esc_attr( wp_json_encode( $cta ) ); ?>">
					<a class="cp-btn cp-btn--primary cp-btn--lg" href="<?php echo esc_url( $cta['new']['href'] ); ?>" data-cp-cta-primary><?php echo esc_html( $cta['new']['label'] ); ?> <?php echo clinic_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<a class="cp-btn cp-btn--ghost cp-btn--lg" href="<?php echo esc_url( $cta['new']['alth'] ); ?>" data-cp-cta-secondary><?php echo esc_html( $cta['new']['alt'] ); ?></a>
				</div>
				<ul class="cp-hero__points">
					<li><?php echo clinic_icon( 'shield', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Licensed physicians</li>
					<li><?php echo clinic_icon( 'lock', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Private records</li>
					<li><?php echo clinic_icon( 'clock', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Open six days a week</li>
				</ul>
			</div>
			<div class="cp-hero__art"><?php echo clinic_illustration( 'hero' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		</div>
	</section>

	<section class="cp-emergency" aria-label="Emergency contact">
		<div class="cp-container cp-emergency__inner">
			<div class="cp-emergency__item cp-emergency__item--main">
				<span class="cp-emergency__icon"><?php echo clinic_icon( 'phone', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<div><strong>Emergency hotline, 24 hours</strong><a class="cp-emergency__num" href="<?php echo esc_url( clinic_tel( $emg ) ); ?>"><?php echo esc_html( $emg ); ?></a></div>
			</div>
			<div class="cp-emergency__item">
				<span class="cp-emergency__icon"><?php echo clinic_icon( 'alert', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<div><strong>Life threatening emergency?</strong><span>Call 911 or go to the nearest emergency room.</span></div>
			</div>
			<div class="cp-emergency__item">
				<span class="cp-emergency__icon"><?php echo clinic_icon( 'clock', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<div><strong>Clinic line</strong><a href="<?php echo esc_url( clinic_tel( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></div>
			</div>
		</div>
	</section>

	<section class="cp-section">
		<div class="cp-container cp-split cp-split--even">
			<div class="cp-split__art cp-reveal"><?php echo clinic_illustration( 'team' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div class="cp-split__text cp-reveal">
				<p class="cp-eyebrow">About the clinic</p>
				<h2>Modern care with a personal touch</h2>
				<p><?php echo esc_html( $name ); ?> brings general and specialist care under one roof. Our doctors and nurses take the time to listen, explain your options in plain language and follow up after every visit.</p>
				<ul class="cp-checklist">
					<li><?php echo clinic_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Online registration and appointment booking</li>
					<li><?php echo clinic_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Laboratory and diagnostics on site</li>
					<li><?php echo clinic_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Secure, private patient records</li>
				</ul>
				<div class="cp-stats cp-stats--inline">
					<div class="cp-stat"><span class="cp-stat__num"><?php echo (int) $n_dept; ?></span><span class="cp-stat__label">Departments</span></div>
					<div class="cp-stat"><span class="cp-stat__num"><?php echo (int) $n_doc; ?></span><span class="cp-stat__label">Physicians</span></div>
					<div class="cp-stat"><span class="cp-stat__num">6</span><span class="cp-stat__label">Days a week</span></div>
				</div>
			</div>
		</div>
	</section>

	<section class="cp-section cp-section--soft">
		<div class="cp-container">
			<div class="cp-section__head">
				<p class="cp-eyebrow">Featured departments</p>
				<h2>Care for every stage of life</h2>
			</div>
			<div class="cp-grid-cards cp-grid-cards--4">
				<?php foreach ( $depts as $d ) { echo clinic_department_card( $d ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<p class="cp-center"><a class="cp-btn cp-btn--ghost" href="<?php echo esc_url( clinic_page_url( 'departments-services' ) ); ?>">See all departments and services</a></p>
		</div>
	</section>

	<?php if ( $docs ) : ?>
	<section class="cp-section">
		<div class="cp-container">
			<div class="cp-section__head">
				<p class="cp-eyebrow">Our physicians</p>
				<h2>Meet the doctors who will care for you</h2>
			</div>
			<div class="cp-grid-cards cp-grid-cards--3">
				<?php foreach ( $docs as $p ) { echo clinic_physician_card( $p ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<p class="cp-center"><a class="cp-btn cp-btn--ghost" href="<?php echo esc_url( clinic_page_url( 'physicians' ) ); ?>">View all physicians</a></p>
		</div>
	</section>
	<?php endif; ?>

	<section class="cp-section cp-section--soft">
		<div class="cp-container">
			<div class="cp-section__head">
				<p class="cp-eyebrow">How it works</p>
				<h2>Three simple steps to your visit</h2>
			</div>
			<ol class="cp-steps">
				<li class="cp-card cp-reveal"><span class="cp-steps__icon"><?php echo clinic_icon( 'clipboard', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3>1. Register</h3><p>Fill in the online form once and receive your Patient ID.</p></li>
				<li class="cp-card cp-reveal"><span class="cp-steps__icon"><?php echo clinic_icon( 'calendar', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3>2. Book</h3><p>Choose a department, doctor, date and time.</p></li>
				<li class="cp-card cp-reveal"><span class="cp-steps__icon"><?php echo clinic_icon( 'stethoscope', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3>3. Visit</h3><p>Arrive a few minutes early with a valid ID. We take it from there.</p></li>
			</ol>
		</div>
	</section>

	<section class="cp-section">
		<div class="cp-container">
			<div class="cp-section__head">
				<p class="cp-eyebrow">Watch and listen</p>
				<h2>Get to know us before you visit</h2>
			</div>
			<?php echo clinic_media_block(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>

	<section class="cp-section cp-section--soft">
		<div class="cp-container">
			<div class="cp-section__head">
				<p class="cp-eyebrow">Latest announcements</p>
				<h2>News and updates from the clinic</h2>
			</div>
			<?php echo clinic_news_cards( 3 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<p class="cp-center"><a class="cp-btn cp-btn--ghost" href="<?php echo esc_url( clinic_page_url( 'announcements-faq' ) ); ?>">All announcements and FAQ</a></p>
		</div>
	</section>

	<section class="cp-cta">
		<div class="cp-container cp-cta__inner">
			<div>
				<h2>Ready to see a doctor?</h2>
				<p>Book online in under two minutes, or call us and we will do it for you.</p>
			</div>
			<div class="cp-actions">
				<a class="cp-btn cp-btn--white cp-btn--lg" href="<?php echo esc_url( $book ); ?>">Book Appointment</a>
				<a class="cp-btn cp-btn--outline-white cp-btn--lg" href="<?php echo esc_url( clinic_tel( $phone ) ); ?>">Call <?php echo esc_html( $phone ); ?></a>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'clinic_home', 'clinic_sc_home' );

/* ------------------------------------------------------------------ */
/* Physicians directory                                                */
/* ------------------------------------------------------------------ */

function clinic_sc_physicians( $atts ) {
	$a      = shortcode_atts( array( 'limit' => -1, 'filters' => 'yes' ), $atts );
	$docs   = clinic_get_physicians( (int) $a['limit'] );
	$depts  = clinic_get_departments();
	// phpcs:ignore WordPress.Security.NonceVerification
	$active = isset( $_GET['department'] ) ? absint( $_GET['department'] ) : 0;
	ob_start();
	?>
	<section class="cp-section">
		<div class="cp-container">
			<?php if ( 'yes' === $a['filters'] ) : ?>
			<div class="cp-card cp-filter" data-cp-docfilter>
				<div class="cp-field cp-field--grow">
					<label for="cp-doc-search">Search by name or specialty</label>
					<input type="search" id="cp-doc-search" data-cp-doc-search placeholder="For example cardiology or Dr. Santos">
				</div>
				<div class="cp-field">
					<label for="cp-doc-dept">Department</label>
					<select id="cp-doc-dept" data-cp-doc-dept>
						<option value="0">All departments</option>
						<?php foreach ( $depts as $d ) : ?>
							<option value="<?php echo (int) $d->ID; ?>" <?php selected( $active, $d->ID ); ?>><?php echo esc_html( $d->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<p class="cp-filter__count" data-cp-doc-count aria-live="polite"></p>
			</div>
			<?php endif; ?>
			<?php if ( ! $docs ) : ?>
				<div class="cp-card cp-empty"><p>No physicians have been added yet.</p></div>
			<?php else : ?>
				<div class="cp-grid-cards cp-grid-cards--3" data-cp-doc-grid>
					<?php foreach ( $docs as $p ) { echo clinic_physician_card( $p ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<p class="cp-empty cp-card" data-cp-doc-empty hidden>No physicians match your search. Try a different word or department.</p>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'clinic_physicians', 'clinic_sc_physicians' );

/* ------------------------------------------------------------------ */
/* Departments                                                         */
/* ------------------------------------------------------------------ */

function clinic_sc_departments() {
	$depts = clinic_get_departments();
	ob_start();
	?>
	<section class="cp-section">
		<div class="cp-container">
			<?php if ( ! $depts ) : ?>
				<div class="cp-card cp-empty"><p>No departments have been added yet.</p></div>
			<?php else : ?>
				<div class="cp-grid-cards cp-grid-cards--2">
					<?php foreach ( $depts as $d ) { echo clinic_department_card( $d, true ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'clinic_departments', 'clinic_sc_departments' );

/* ------------------------------------------------------------------ */
/* FAQ and announcements                                               */
/* ------------------------------------------------------------------ */

function clinic_sc_faq() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'clinic_faq_section',
			'hide_empty' => true,
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		)
	);
	if ( is_wp_error( $terms ) || ! $terms ) {
		return '<p class="cp-muted">No FAQs yet.</p>';
	}
	ob_start();
	?>
	<div class="cp-faq" data-cp-faq>
		<div class="cp-card cp-filter">
			<div class="cp-field cp-field--grow">
				<label for="cp-faq-search">Search the FAQ</label>
				<input type="search" id="cp-faq-search" data-cp-faq-search placeholder="For example payment, documents, records">
			</div>
			<p class="cp-filter__count" data-cp-faq-count aria-live="polite"></p>
		</div>
		<nav class="cp-chips" aria-label="FAQ sections">
			<?php foreach ( $terms as $t ) : ?>
				<a class="cp-chip" href="#faq-<?php echo esc_attr( $t->slug ); ?>"><?php echo esc_html( $t->name ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
		foreach ( $terms as $t ) :
			$items = get_posts(
				array(
					'post_type'   => 'clinic_faq',
					'post_status' => 'publish',
					'numberposts' => -1,
					'orderby'     => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
					'tax_query'   => array( array( 'taxonomy' => 'clinic_faq_section', 'terms' => $t->term_id ) ),
				)
			);
			?>
			<section class="cp-faq__section" id="faq-<?php echo esc_attr( $t->slug ); ?>" data-cp-faq-section>
				<h3><?php echo esc_html( $t->name ); ?></h3>
				<?php foreach ( $items as $it ) : ?>
					<details class="cp-faq__item" data-search="<?php echo esc_attr( strtolower( $it->post_title . ' ' . wp_strip_all_tags( $it->post_content ) ) ); ?>">
						<summary><?php echo esc_html( $it->post_title ); ?></summary>
						<div class="cp-faq__answer"><?php echo wp_kses_post( wpautop( $it->post_content ) ); ?></div>
					</details>
				<?php endforeach; ?>
			</section>
		<?php endforeach; ?>
		<p class="cp-card cp-empty" data-cp-faq-empty hidden>No questions match your search. Try another word, or ask us through the contact page.</p>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'clinic_faq', 'clinic_sc_faq' );

function clinic_sc_announcements( $atts ) {
	$a = shortcode_atts( array( 'limit' => 6 ), $atts );
	return clinic_news_cards( (int) $a['limit'] );
}
add_shortcode( 'clinic_announcements', 'clinic_sc_announcements' );

/**
 * Announcements and FAQ page body.
 */
function clinic_sc_news_faq() {
	ob_start();
	?>
	<section class="cp-section">
		<div class="cp-container">
			<div class="cp-split cp-split--even cp-split--head">
				<div class="cp-split__text">
					<p class="cp-eyebrow">Announcements</p>
					<h2>What is new at the clinic</h2>
					<p>Service updates, schedule changes and health reminders from our team.</p>
				</div>
				<div class="cp-split__art"><?php echo clinic_illustration( 'news' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</div>
			<?php echo clinic_news_cards( 6 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>
	<section class="cp-section cp-section--soft" id="faq">
		<div class="cp-container">
			<div class="cp-split cp-split--even cp-split--head">
				<div class="cp-split__text">
					<p class="cp-eyebrow">Frequently asked questions</p>
					<h2>Answers to common questions</h2>
					<p>Browse by section or search. Cannot find your answer? Reach us from the contact page.</p>
				</div>
				<div class="cp-split__art"><?php echo clinic_illustration( 'faq' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</div>
			<?php echo clinic_sc_faq(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'clinic_news_faq', 'clinic_sc_news_faq' );

/* ------------------------------------------------------------------ */
/* Contact                                                             */
/* ------------------------------------------------------------------ */

function clinic_sc_contact() {
	$flash = clinic_flash_get();
	$flash = ( $flash && isset( $flash['type'] ) && 'contact' === $flash['type'] ) ? $flash : null;
	$old   = $flash && 'error' === $flash['status'] ? (array) $flash['old'] : array();
	$errs  = $flash && 'error' === $flash['status'] ? (array) $flash['errors'] : array();
	$g     = function ( $k ) use ( $old ) {
		return isset( $old[ $k ] ) ? $old[ $k ] : '';
	};
	$e     = function ( $k ) use ( $errs ) {
		return isset( $errs[ $k ] ) ? $errs[ $k ] : '';
	};

	$lat  = (float) clinic_opt( 'map_lat' );
	$lng  = (float) clinic_opt( 'map_lng' );
	$d    = 0.006;
	$bbox = ( $lng - $d ) . ',' . ( $lat - $d ) . ',' . ( $lng + $d ) . ',' . ( $lat + $d );
	$map  = 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode( $bbox ) . '&layer=mapnik&marker=' . rawurlencode( $lat . ',' . $lng );
	$big  = 'https://www.openstreetmap.org/?mlat=' . rawurlencode( $lat ) . '&mlon=' . rawurlencode( $lng ) . '#map=17/' . rawurlencode( $lat ) . '/' . rawurlencode( $lng );
	$crisp_on = (bool) preg_match( '/^[0-9a-fA-F\-]{36}$/', (string) clinic_opt( 'crisp_id' ) );

	ob_start();
	?>
	<section class="cp-section">
		<div class="cp-container">
			<div class="cp-contact-cards">
				<a class="cp-card cp-contact-card cp-reveal" href="<?php echo esc_url( clinic_tel( clinic_opt( 'phone' ) ) ); ?>">
					<span class="cp-contact-card__icon"><?php echo clinic_icon( 'phone', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<strong>Call the clinic</strong><span><?php echo esc_html( clinic_opt( 'phone' ) ); ?></span>
				</a>
				<a class="cp-card cp-contact-card cp-contact-card--alert cp-reveal" href="<?php echo esc_url( clinic_tel( clinic_opt( 'emergency_phone' ) ) ); ?>">
					<span class="cp-contact-card__icon"><?php echo clinic_icon( 'alert', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<strong>Emergency hotline</strong><span><?php echo esc_html( clinic_opt( 'emergency_phone' ) ); ?> (24 hours)</span>
				</a>
				<a class="cp-card cp-contact-card cp-reveal" href="mailto:<?php echo esc_attr( clinic_opt( 'email' ) ); ?>">
					<span class="cp-contact-card__icon"><?php echo clinic_icon( 'mail', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<strong>Email us</strong><span><?php echo esc_html( clinic_opt( 'email' ) ); ?></span>
				</a>
				<a class="cp-card cp-contact-card cp-reveal" href="<?php echo esc_url( $big ); ?>" target="_blank" rel="noopener">
					<span class="cp-contact-card__icon"><?php echo clinic_icon( 'pin', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<strong>Visit us</strong><span><?php echo esc_html( clinic_opt( 'address' ) ); ?></span>
				</a>
			</div>
		</div>
	</section>

	<section class="cp-section cp-section--soft">
		<div class="cp-container cp-split cp-split--even">
			<div class="cp-reveal">
				<div class="cp-card cp-hours-card">
					<h2><?php echo clinic_icon( 'clock', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Operating hours</h2>
					<table class="cp-hours">
						<?php foreach ( clinic_hours_rows() as $row ) : ?>
							<tr><th scope="row"><?php echo esc_html( $row[0] ); ?></th><td><?php echo esc_html( $row[1] ); ?></td></tr>
						<?php endforeach; ?>
					</table>
				</div>
				<?php if ( $crisp_on ) : ?>
					<div class="cp-card cp-chat-card">
						<span class="cp-contact-card__icon"><?php echo clinic_icon( 'chat', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<div>
							<h3>Chat with our team</h3>
							<p>Ask about schedules, services or booking. Our team replies during clinic hours, and our automated assistant answers common questions any time.</p>
							<button type="button" class="cp-btn cp-btn--primary cp-btn--small" data-cp-open-chat>Start live chat</button>
						</div>
					</div>
				<?php elseif ( current_user_can( 'manage_options' ) ) : ?>
					<div class="cp-card cp-chat-card cp-chat-card--setup">
						<span class="cp-contact-card__icon"><?php echo clinic_icon( 'chat', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<div>
							<h3>Live chat setup (visible to admins only)</h3>
							<ol>
								<li>Create a free account at <a href="https://crisp.chat" target="_blank" rel="noopener">crisp.chat</a> and add your website.</li>
								<li>Copy your Website ID from Crisp, Settings, Website Settings, Setup instructions.</li>
								<li>Paste it in <a href="<?php echo esc_url( admin_url( 'options-general.php?page=clinic-portal' ) ); ?>">Settings, Clinic Portal</a>. The widget then loads from the site footer on every page.</li>
								<li>In Crisp, add chatbot scenarios and away messages for automated replies while staff are offline.</li>
							</ol>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<div class="cp-reveal">
				<div class="cp-map">
					<iframe title="Map showing the clinic location" src="<?php echo esc_url( $map ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
				</div>
				<p class="cp-map__link"><a class="cp-link" href="<?php echo esc_url( $big ); ?>" target="_blank" rel="noopener"><?php echo clinic_icon( 'pin', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Open a larger map</a></p>
			</div>
		</div>
	</section>

	<section class="cp-section" id="cp-result">
		<div class="cp-container cp-split cp-split--form">
			<div class="cp-card cp-form-card">
				<h2 class="cp-form-title">Send us a message</h2>
				<p class="cp-muted">For general questions only. Please do not send medical details by email or chat.</p>
				<?php if ( $flash && 'success' === $flash['status'] ) : ?>
					<div class="cp-notice cp-notice--success" role="status" tabindex="-1">
						<?php echo clinic_icon( 'check', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<div><strong>Message sent.</strong> Thank you, we will reply within one working day.</div>
					</div>
				<?php else : ?>
					<?php echo clinic_error_summary( $errs, 'cp-ct-' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<form class="cp-form" method="post" data-cp-validate>
						<?php echo clinic_form_hidden( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<div class="cp-grid">
							<?php
							echo clinic_field( array( 'id' => 'cp-ct-name', 'name' => 'name', 'label' => 'Your name', 'required' => true, 'value' => $g( 'name' ), 'error' => $e( 'name' ), 'autocomplete' => 'name' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
							echo clinic_field( array( 'id' => 'cp-ct-email', 'name' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true, 'value' => $g( 'email' ), 'error' => $e( 'email' ), 'autocomplete' => 'email' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
							echo clinic_field( array( 'id' => 'cp-ct-subject', 'name' => 'subject', 'label' => 'Subject', 'value' => $g( 'subject' ), 'error' => $e( 'subject' ), 'wide' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput
							echo clinic_field( array( 'id' => 'cp-ct-message', 'name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'rows' => 5, 'required' => true, 'wide' => true, 'value' => $g( 'message' ), 'error' => $e( 'message' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
							?>
						</div>
						<div class="cp-actions"><button type="submit" class="cp-btn cp-btn--primary cp-btn--lg">Send message <?php echo clinic_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button></div>
					</form>
				<?php endif; ?>
			</div>
			<aside class="cp-aside"><div class="cp-aside__card"><?php echo clinic_illustration( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3>Prefer to talk?</h3><p>Call <a href="<?php echo esc_url( clinic_tel( clinic_opt( 'phone' ) ) ); ?>"><?php echo esc_html( clinic_opt( 'phone' ) ); ?></a> during clinic hours. For emergencies use the hotline above, any time of day.</p></div></aside>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'clinic_contact', 'clinic_sc_contact' );

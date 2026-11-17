<?php
/**
 * Inline SVG icons, illustrations and avatars. No external image files, so
 * every visual is unique and loads instantly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function clinic_icon_names() {
	return array(
		'stethoscope' => 'Stethoscope',
		'heart'       => 'Heart',
		'smile'       => 'Smile',
		'flask'       => 'Flask',
		'venus'       => 'Women health',
		'drop'        => 'Droplet',
		'bone'        => 'Bone',
		'eye'         => 'Eye',
	);
}

function clinic_icon( $name, $size = 24, $class = '' ) {
	static $paths = null;
	if ( null === $paths ) {
		$paths = array(
			'stethoscope' => '<path d="M5 3v6a4 4 0 0 0 8 0V3"/><path d="M9 13v2a5 5 0 0 0 10 0v-2"/><circle cx="19" cy="11" r="2"/>',
			'heart'       => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
			'smile'       => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01M15 9h.01"/>',
			'flask'       => '<path d="M9 3h6"/><path d="M10 3v6L4.5 19a2 2 0 0 0 1.7 3h11.6a2 2 0 0 0 1.7-3L14 9V3"/><path d="M7.5 15h9"/>',
			'venus'       => '<circle cx="12" cy="9" r="5"/><path d="M12 14v8M9 18h6"/>',
			'drop'        => '<path d="M12 2.7s7 7.2 7 12a7 7 0 0 1-14 0c0-4.8 7-12 7-12z"/>',
			'bone'        => '<path d="M7 17 17 7"/><circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="4.5" cy="15.5" r="2"/><circle cx="19.5" cy="8.5" r="2"/>',
			'eye'         => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
			'phone'       => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
			'clock'       => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
			'pin'         => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
			'mail'        => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
			'shield'      => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
			'calendar'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
			'megaphone'   => '<path d="M3 11v2a1 1 0 0 0 1 1h3l8 4V6L7 10H4a1 1 0 0 0-1 1z"/><path d="M19 9a4 4 0 0 1 0 6"/>',
			'syringe'     => '<path d="m18 2 4 4"/><path d="m17 7 3-3"/><path d="M19 9 8.7 19.3a1 1 0 0 1-1.4 0l-2.6-2.6a1 1 0 0 1 0-1.4L15 5"/><path d="m9 11 4 4"/><path d="m5 19-3 3"/><path d="m14 4 6 6"/>',
			'activity'    => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
			'play'        => '<circle cx="12" cy="12" r="10"/><path d="m10 8 6 4-6 4z"/>',
			'headphones'  => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/><path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>',
			'user'        => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
			'users'       => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
			'plus'        => '<path d="M12 5v14M5 12h14"/>',
			'check'       => '<path d="m5 12 5 5 9-10"/>',
			'arrow'       => '<path d="M5 12h14M13 6l6 6-6 6"/>',
			'alert'       => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
			'lock'        => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
			'clipboard'   => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/>',
			'search'      => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
			'menu'        => '<path d="M3 6h18M3 12h18M3 18h18"/>',
			'close'       => '<path d="M18 6 6 18M6 6l12 12"/>',
			'chat'        => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
			'print'       => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
		);
	}
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="cp-icon %1$s" xmlns="http://www.w3.org/2000/svg" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $class ),
		(int) $size,
		$paths[ $name ]
	);
}

/**
 * Large flat illustrations. Each one is different, and each is meant to sit
 * next to the text it illustrates.
 */
function clinic_illustration( $name ) {
	static $art = null;
	if ( null === $art ) {
		$open = '<svg class="cp-illustration" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 360" aria-hidden="true" focusable="false">';
		$bg   = '<rect width="480" height="360" rx="28" fill="#e8f3fd"/>';
		$art  = array(
			// Clinic building with a cross, sun and trees.
			'hero'         => $open . $bg .
				'<circle cx="396" cy="72" r="34" fill="#fde9a6"/>' .
				'<path d="M0 292c80-30 150-28 240-6s170 18 240-8v82H0z" fill="#d6f0e9"/>' .
				'<rect x="120" y="128" width="240" height="172" rx="12" fill="#fff"/>' .
				'<rect x="120" y="128" width="240" height="36" rx="12" fill="#1470cc"/>' .
				'<rect x="214" y="222" width="52" height="78" rx="5" fill="#0b4f94"/>' .
				'<rect x="146" y="188" width="46" height="40" rx="6" fill="#cfe4fa"/><rect x="288" y="188" width="46" height="40" rx="6" fill="#cfe4fa"/>' .
				'<rect x="208" y="52" width="64" height="64" rx="16" fill="#14a39f"/>' .
				'<path d="M240 66v36M222 84h36" stroke="#fff" stroke-width="9" stroke-linecap="round"/>' .
				'<circle cx="68" cy="262" r="30" fill="#2fb98f"/><rect x="64" y="274" width="8" height="30" fill="#7a5a3a"/>' .
				'<circle cx="418" cy="268" r="24" fill="#2fb98f"/><rect x="414" y="278" width="8" height="26" fill="#7a5a3a"/>' .
				'<path d="M170 308h140" stroke="#bcd6ee" stroke-width="6" stroke-linecap="round"/></svg>',
			// Care team.
			'team'         => $open . '<rect width="480" height="360" rx="28" fill="#e3f6f1"/>' .
				'<circle cx="240" cy="150" r="46" fill="#f2c9a0"/><path d="M180 330c0-60 26-110 60-110s60 50 60 110z" fill="#fff"/>' .
				'<path d="M222 224l18 40 18-40" fill="none" stroke="#1470cc" stroke-width="6"/>' .
				'<path d="M196 130c4-34 84-34 88 0-26-14-62-14-88 0z" fill="#3b2f2a"/>' .
				'<circle cx="120" cy="190" r="36" fill="#e7b48c"/><path d="M68 330c0-46 20-84 52-84s52 38 52 84z" fill="#14a39f"/>' .
				'<path d="M86 178c6-26 62-26 68 0-20-10-48-10-68 0z" fill="#241a17"/>' .
				'<circle cx="360" cy="190" r="36" fill="#f6d6b4"/><path d="M308 330c0-46 20-84 52-84s52 38 52 84z" fill="#1470cc"/>' .
				'<path d="M326 176c4-30 64-30 68 2-22-8-46-8-68-2z" fill="#6b4b2f"/>' .
				'<rect x="226" y="276" width="28" height="8" rx="4" fill="#14a39f"/><rect x="236" y="266" width="8" height="28" rx="4" fill="#14a39f"/></svg>',
			// Clipboard for registration.
			'registration' => $open . '<rect width="480" height="360" rx="28" fill="#eaf2fd"/>' .
				'<rect x="136" y="40" width="208" height="284" rx="18" fill="#fff"/>' .
				'<rect x="198" y="28" width="84" height="40" rx="12" fill="#1470cc"/><circle cx="240" cy="48" r="7" fill="#fff"/>' .
				'<circle cx="176" cy="116" r="14" fill="#cfe4fa"/><rect x="204" y="106" width="110" height="10" rx="5" fill="#bcd6ee"/><rect x="204" y="124" width="70" height="8" rx="4" fill="#dbe8f6"/>' .
				'<circle cx="176" cy="178" r="14" fill="#cfe4fa"/><rect x="204" y="168" width="110" height="10" rx="5" fill="#bcd6ee"/><rect x="204" y="186" width="84" height="8" rx="4" fill="#dbe8f6"/>' .
				'<circle cx="176" cy="240" r="14" fill="#14a39f"/><path d="m169 240 5 5 9-10" stroke="#fff" stroke-width="4" fill="none" stroke-linecap="round"/>' .
				'<rect x="204" y="230" width="110" height="10" rx="5" fill="#bcd6ee"/><rect x="204" y="248" width="60" height="8" rx="4" fill="#dbe8f6"/>' .
				'<circle cx="390" cy="96" r="26" fill="#fde9a6"/><path d="M80 300c20-26 40-26 60 0" stroke="#2fb98f" stroke-width="8" fill="none" stroke-linecap="round"/></svg>',
			// Calendar and clock for booking.
			'booking'      => $open . '<rect width="480" height="360" rx="28" fill="#e6f6f2"/>' .
				'<rect x="90" y="64" width="244" height="232" rx="20" fill="#fff"/><path d="M90 84a20 20 0 0 1 20-20h204a20 20 0 0 1 20 20v44H90z" fill="#1470cc"/>' .
				'<rect x="130" y="44" width="12" height="40" rx="6" fill="#0b4f94"/><rect x="282" y="44" width="12" height="40" rx="6" fill="#0b4f94"/>' .
				'<g fill="#cfe4fa"><rect x="118" y="148" width="36" height="28" rx="6"/><rect x="170" y="148" width="36" height="28" rx="6"/><rect x="222" y="148" width="36" height="28" rx="6"/><rect x="274" y="148" width="36" height="28" rx="6"/><rect x="118" y="192" width="36" height="28" rx="6"/><rect x="274" y="192" width="36" height="28" rx="6"/><rect x="118" y="236" width="36" height="28" rx="6"/><rect x="170" y="236" width="36" height="28" rx="6"/></g>' .
				'<rect x="170" y="192" width="36" height="28" rx="6" fill="#14a39f"/><rect x="222" y="192" width="36" height="28" rx="6" fill="#14a39f"/>' .
				'<circle cx="350" cy="256" r="64" fill="#fff" stroke="#1470cc" stroke-width="10"/><path d="M350 214v44l30 18" stroke="#0b4f94" stroke-width="9" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
			// Map and pin for contact.
			'contact'      => $open . '<rect width="480" height="360" rx="28" fill="#e8f3fd"/>' .
				'<path d="M60 90l110-30 140 40 110-30v210l-110 30-140-40-110 30z" fill="#fff"/>' .
				'<path d="M170 60v210M310 100v210" stroke="#d3e5f8" stroke-width="4"/>' .
				'<path d="M60 220c60-20 110 20 170 0s110-30 160-6" stroke="#9cc6ee" stroke-width="12" fill="none" stroke-linecap="round"/>' .
				'<path d="M240 70c-34 0-60 26-60 58 0 44 60 106 60 106s60-62 60-106c0-32-26-58-60-58z" fill="#14a39f"/>' .
				'<circle cx="240" cy="126" r="22" fill="#fff"/><path d="M240 114v24M228 126h24" stroke="#14a39f" stroke-width="7" stroke-linecap="round"/>' .
				'<ellipse cx="240" cy="258" rx="40" ry="9" fill="#b7d7ee"/></svg>',
			// Lock and chart for staff dashboard.
			'secure'       => $open . '<rect width="480" height="360" rx="28" fill="#e9eefb"/>' .
				'<rect x="70" y="80" width="250" height="190" rx="18" fill="#fff"/>' .
				'<rect x="96" y="110" width="40" height="10" rx="5" fill="#cfe4fa"/><rect x="96" y="132" width="200" height="12" rx="6" fill="#e3edf8"/>' .
				'<rect x="96" y="156" width="200" height="12" rx="6" fill="#e3edf8"/><rect x="96" y="180" width="140" height="12" rx="6" fill="#e3edf8"/>' .
				'<rect x="104" y="222" width="22" height="28" rx="4" fill="#1470cc"/><rect x="140" y="208" width="22" height="42" rx="4" fill="#14a39f"/><rect x="176" y="196" width="22" height="54" rx="4" fill="#1470cc"/>' .
				'<rect x="290" y="170" width="120" height="110" rx="18" fill="#0b4f94"/>' .
				'<path d="M318 170v-26a32 32 0 0 1 64 0v26" stroke="#0b4f94" stroke-width="14" fill="none"/>' .
				'<circle cx="350" cy="218" r="13" fill="#fff"/><rect x="345" y="226" width="10" height="24" rx="5" fill="#fff"/></svg>',
			// Speech bubbles for FAQ.
			'faq'          => $open . '<rect width="480" height="360" rx="28" fill="#e3f6f1"/>' .
				'<path d="M90 70h200a24 24 0 0 1 24 24v90a24 24 0 0 1-24 24H170l-48 40v-40H90a24 24 0 0 1-24-24V94a24 24 0 0 1 24-24z" fill="#1470cc"/>' .
				'<path d="M170 106a28 28 0 1 1 40 25c-8 4-10 8-10 16" stroke="#fff" stroke-width="10" fill="none" stroke-linecap="round"/><circle cx="200" cy="168" r="6" fill="#fff"/>' .
				'<path d="M200 214h190a24 24 0 0 1 24 24v50a24 24 0 0 1-24 24h-30v34l-44-34h-116a24 24 0 0 1-24-24v-50a24 24 0 0 1 24-24z" fill="#fff"/>' .
				'<rect x="226" y="240" width="130" height="10" rx="5" fill="#9fd3c9"/><rect x="226" y="262" width="90" height="10" rx="5" fill="#cdeae4"/></svg>',
			// Megaphone and paper for announcements.
			'news'         => $open . '<rect width="480" height="360" rx="28" fill="#fff4dc"/>' .
				'<rect x="250" y="70" width="170" height="220" rx="14" fill="#fff"/>' .
				'<rect x="272" y="96" width="90" height="12" rx="6" fill="#f4b942"/><rect x="272" y="124" width="126" height="9" rx="4" fill="#ecd9ae"/><rect x="272" y="144" width="110" height="9" rx="4" fill="#f3e5c2"/><rect x="272" y="164" width="126" height="9" rx="4" fill="#ecd9ae"/><rect x="272" y="194" width="60" height="30" rx="8" fill="#14a39f"/>' .
				'<path d="M70 170v50a10 10 0 0 0 10 10h34l96 50V110l-96 50H80a10 10 0 0 0-10 10z" fill="#1470cc"/>' .
				'<rect x="106" y="236" width="30" height="50" rx="10" fill="#0b4f94"/>' .
				'<path d="M240 150a40 40 0 0 1 0 100" stroke="#14a39f" stroke-width="10" fill="none" stroke-linecap="round"/></svg>',
		);
	}
	return isset( $art[ $name ] ) ? $art[ $name ] : '';
}

/**
 * Pool of small distinct illustrations for news cards.
 */
function clinic_news_art( $index ) {
	$pool = array(
		array( 'syringe', '#e8f3fd', '#1470cc' ),
		array( 'clock', '#e3f6f1', '#0b7a77' ),
		array( 'clipboard', '#fff4dc', '#b26a00' ),
		array( 'activity', '#fde8ec', '#b42318' ),
		array( 'megaphone', '#ece9fb', '#4b3fb0' ),
		array( 'shield', '#e3f6f1', '#0b7a77' ),
	);
	$p = $pool[ (int) $index % count( $pool ) ];
	return '<span class="cp-news-art" style="background:' . esc_attr( $p[1] ) . ';color:' . esc_attr( $p[2] ) . '">' . clinic_icon( $p[0], 44 ) . '</span>';
}

/**
 * Doctor avatar: a unique flat portrait per physician.
 */
function clinic_avatar( $index ) {
	$bgs   = array( '#dbeafe', '#d6f0e9', '#fde9d4', '#ece9fb', '#fde8ec', '#e3f6f1', '#e7efc9', '#d9e8f7' );
	$skins = array( '#f2c9a0', '#e7b48c', '#f6d6b4', '#d9a37a', '#f0c19a', '#e0b087', '#f4d2b0', '#cf9a72' );
	$hairs = array( '#3b2f2a', '#241a17', '#6b4b2f', '#1b1b1b', '#4a3426', '#2c2320', '#7a5a3a', '#131313' );
	$coats = array( '#ffffff', '#ffffff', '#f4faff', '#ffffff', '#f6fffb', '#ffffff', '#f5faff', '#ffffff' );
	$i     = (int) $index % 8;
	$long  = in_array( $i, array( 1, 4, 5, 7 ), true );
	$out   = '<svg class="cp-avatar" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 160" aria-hidden="true" focusable="false">';
	$out  .= '<rect width="160" height="160" rx="24" fill="' . $bgs[ $i ] . '"/>';
	if ( $long ) {
		$out .= '<path d="M46 74c0-40 68-40 68 0v52H46z" fill="' . $hairs[ $i ] . '"/>';
	}
	$out .= '<path d="M26 160c0-34 24-58 54-58s54 24 54 58z" fill="' . $coats[ $i ] . '"/>';
	$out .= '<path d="M64 106l16 30 16-30" fill="none" stroke="#1470cc" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>';
	$out .= '<circle cx="80" cy="74" r="28" fill="' . $skins[ $i ] . '"/>';
	$out .= '<path d="M52 70c2-28 54-28 56 0-16-12-40-12-56 0z" fill="' . $hairs[ $i ] . '"/>';
	$out .= '<circle cx="70" cy="76" r="3" fill="#2a2a2a"/><circle cx="90" cy="76" r="3" fill="#2a2a2a"/>';
	$out .= '<path d="M72 88q8 6 16 0" stroke="#7a3b2e" stroke-width="3" fill="none" stroke-linecap="round"/>';
	$out .= '<circle cx="118" cy="128" r="8" fill="#14a39f"/><path d="M118 120v-10" stroke="#14a39f" stroke-width="3"/>';
	$out .= '</svg>';
	return $out;
}

<?php
/**
 * Royal Pet World — build Home, About Us, Contact Us pages (blocks), set front page, menu.
 * Idempotent: pages are matched by slug and their content replaced.
 *
 * Run (staging):  docker cp wp/seed/pages-home-about-contact.php <ctr>:/tmp/ && \
 *                 docker exec -u www-data <ctr> wp eval-file /tmp/pages-home-about-contact.php <dog_img_id> <cat_img_id>
 *
 * Placeholder business details (phone, email, exact address, hours) are marked PLACEHOLDER — see
 * docs/03-config/site-pages.md. Not part of the image (Dockerfile doesn't copy wp/seed).
 *
 * @package RPW_Seed
 */

// phpcs:disable

$dog_img = (int) ( $args[0] ?? 0 );
$cat_img = (int) ( $args[1] ?? 0 );
if ( ! $dog_img || ! $cat_img ) {
	WP_CLI::error( 'Usage: wp eval-file pages-home-about-contact.php <dog_attachment_id> <cat_attachment_id>' );
}

// ---- PLACEHOLDER business details (replace when the client confirms) ----
$biz = array(
	'area'    => 'Manikonda, Hyderabad, Telangana',
	'address' => 'Shop address to be added, Manikonda, Hyderabad, Telangana 500089', // PLACEHOLDER
	'phone'   => '+91 XXXXX XXXXX', // PLACEHOLDER
	'email'   => '', // PLACEHOLDER — no real address yet; never invent one (could belong to someone else)
	'hours'   => 'Open every day, 10 am – 8 pm', // PLACEHOLDER (matches LatePoint hours)
);

// ---- tiny block-markup helpers ----
$attr = static fn( array $a ) => $a ? ' ' . wp_json_encode( $a, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';
$g    = static function ( string $class, string $inner, bool $full = false ) use ( $attr ) {
	$a = array( 'className' => $class, 'layout' => array( 'type' => 'constrained' ) );
	if ( $full ) {
		$a = array( 'align' => 'full' ) + $a;
	}
	return "<!-- wp:group{$attr($a)} -->\n<div class=\"wp-block-group" . ( $full ? ' alignfull' : '' ) . " $class\">$inner</div>\n<!-- /wp:group -->\n";
};
$h = static function ( string $text, int $lvl = 2, string $class = '' ) use ( $attr ) {
	$a = array();
	if ( 2 !== $lvl ) {
		$a['level'] = $lvl;
	}
	if ( $class ) {
		$a['className'] = $class;
	}
	return "<!-- wp:heading{$attr($a)} -->\n<h$lvl class=\"wp-block-heading" . ( $class ? " $class" : '' ) . "\">$text</h$lvl>\n<!-- /wp:heading -->\n";
};
$p = static function ( string $html, string $class = '' ) use ( $attr ) {
	$a = $class ? array( 'className' => $class ) : array();
	return "<!-- wp:paragraph{$attr($a)} -->\n<p" . ( $class ? " class=\"$class\"" : '' ) . ">$html</p>\n<!-- /wp:paragraph -->\n";
};
$btns = static function ( array $buttons ) use ( $attr ) {
	$out = "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\">";
	foreach ( $buttons as [ $label, $url, $outline ] ) {
		$a    = $outline ? array( 'className' => 'is-style-outline' ) : array();
		$out .= "<!-- wp:button{$attr($a)} -->\n<div class=\"wp-block-button" . ( $outline ? ' is-style-outline' : '' ) . "\"><a class=\"wp-block-button__link wp-element-button\" href=\"$url\">$label</a></div>\n<!-- /wp:button -->";
	}
	return $out . "</div>\n<!-- /wp:buttons -->\n";
};
$img = static function ( int $id, string $class, string $href = '' ) use ( $attr ) {
	$src = wp_get_attachment_image_url( $id, 'large' );
	$alt = esc_attr( get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	$a   = array( 'id' => $id, 'sizeSlug' => 'large', 'linkDestination' => $href ? 'custom' : 'none', 'className' => $class );
	$tag = "<img src=\"$src\" alt=\"$alt\" class=\"wp-image-$id\"/>";
	if ( $href ) {
		$tag = "<a href=\"$href\">$tag</a>";
	}
	return "<!-- wp:image{$attr($a)} -->\n<figure class=\"wp-block-image size-large $class\">$tag</figure>\n<!-- /wp:image -->\n";
};
$cols = static function ( array $columns, string $class = '' ) use ( $attr ) {
	$a   = $class ? array( 'className' => $class ) : array();
	$out = "<!-- wp:columns{$attr($a)} -->\n<div class=\"wp-block-columns" . ( $class ? " $class" : '' ) . "\">";
	foreach ( $columns as $c ) {
		$out .= "<!-- wp:column -->\n<div class=\"wp-block-column\">$c</div>\n<!-- /wp:column -->";
	}
	return $out . "</div>\n<!-- /wp:columns -->\n";
};
$ul = static function ( array $items, string $class = '' ) use ( $attr ) {
	$a   = $class ? array( 'className' => $class ) : array();
	$out = "<!-- wp:list{$attr($a)} -->\n<ul class=\"wp-block-list" . ( $class ? " $class" : '' ) . "\">";
	foreach ( $items as $i ) {
		$out .= "<!-- wp:list-item -->\n<li>$i</li>\n<!-- /wp:list-item -->";
	}
	return $out . "</ul>\n<!-- /wp:list -->\n";
};
$sc   = static fn( string $code ) => "<!-- wp:shortcode -->\n$code\n<!-- /wp:shortcode -->\n";
$card = static fn( string $class, string $inner ) => $g( "rpw-card $class", $inner );

$cat_url = static fn( string $slug ) => ( $t = get_term_by( 'slug', $slug, 'product_cat' ) ) ? wp_make_link_relative( get_term_link( $t ) ) : '/shop/';
$tel     = 'tel:' . preg_replace( '/[^+\d]/', '', $biz['phone'] );
$phone   = str_contains( $biz['phone'], 'X' ) ? esc_html( $biz['phone'] ) : '<a href="' . $tel . '">' . esc_html( $biz['phone'] ) . '</a>';
$mail    = $biz['email'] ? '<a href="mailto:' . esc_attr( $biz['email'] ) . '">' . esc_html( $biz['email'] ) . '</a>' : 'Email address to be added';

// ======================= HOME =======================
// Featured products are hand-picked IDs with distinct photos (placeholder catalog reuses 12 images).
// Switch to category="dog"/"cat" + orderby="popularity" once the real catalog is imported.
$home  = $g(
	'rpw-hero',
	$cols(
		array(
			$p( 'Pet store &amp; care · ' . esc_html( $biz['area'] ), 'rpw-eyebrow' )
			. $h( 'Everything your dog or cat needs, under one roof', 1 )
			. $p( 'Food, treats, toys and health care — plus grooming, vet visits, daycare and training. Shop online or book a visit in a few taps.', 'rpw-lead' )
			. $btns( array( array( 'Shop for dogs', $cat_url( 'dog' ), false ), array( 'Shop for cats', $cat_url( 'cat' ), false ), array( 'Book a service', '/services/', true ) ) ),
			$g( 'rpw-hero-photos', $img( $dog_img, 'rpw-hero-dog' ) . $img( $cat_img, 'rpw-hero-cat' ) ),
		),
		'rpw-hero-cols'
	),
	true
);
$home .= $g(
	'rpw-section rpw-shop-by-pet',
	$h( 'Shop by pet' )
	. $cols(
		array(
			$card( 'rpw-pet-tile', $img( $dog_img, 'rpw-tile-img', $cat_url( 'dog' ) ) . $h( '<a href="' . $cat_url( 'dog' ) . '">Dogs</a>', 3 ) . $p( '<a href="' . $cat_url( 'dog-food' ) . '">Food</a> · <a href="' . $cat_url( 'dog-treats' ) . '">Treats</a> · <a href="' . $cat_url( 'dog-toys' ) . '">Toys</a> · <a href="' . $cat_url( 'dog-grooming' ) . '">Grooming</a> · <a href="' . $cat_url( 'dog-health-vet' ) . '">Health &amp; Vet</a>', 'rpw-sublinks' ) ),
			$card( 'rpw-pet-tile', $img( $cat_img, 'rpw-tile-img', $cat_url( 'cat' ) ) . $h( '<a href="' . $cat_url( 'cat' ) . '">Cats</a>', 3 ) . $p( '<a href="' . $cat_url( 'cat-food' ) . '">Food</a> · <a href="' . $cat_url( 'cat-treats' ) . '">Treats</a> · <a href="' . $cat_url( 'cat-toys' ) . '">Toys</a> · <a href="' . $cat_url( 'cat-litter' ) . '">Litter</a> · <a href="' . $cat_url( 'cat-health-vet' ) . '">Health &amp; Vet</a>', 'rpw-sublinks' ) ),
		)
	)
);
$home .= $g(
	'rpw-section rpw-products',
	$h( 'Popular for dogs' ) . $sc( '[products ids="24,43,61,66" columns="4" orderby="post__in"]' )
	. $h( 'Popular for cats' ) . $sc( '[products ids="29,67,50,47" columns="4" orderby="post__in"]' )
	. $btns( array( array( 'Browse the full shop', '/shop/', true ) ) )
);
$svc = static fn( string $icon, string $title, string $text, string $from ) => $card( "rpw-svc rpw-svc-$icon", $h( $title, 3 ) . $p( $text ) . $p( $from, 'rpw-from' ) );
$home .= $g(
	'rpw-band rpw-services',
	$h( 'Pet services' )
	. $p( 'Book online in a minute and pay at the store.', 'rpw-sub' )
	. $cols(
		array(
			$svc( 'grooming', 'Grooming &amp; bathing', 'Baths, haircuts, nail trims and ear cleaning for dogs and cats.', 'From ₹299' ),
			$svc( 'vet', 'Vet consultation', 'Health check-ups and vaccination visits.', 'From ₹300' ),
			$svc( 'boarding', 'Daycare &amp; boarding', 'Day care while you’re at work, or overnight stays.', 'From ₹699' ),
			$svc( 'training', 'Training', 'Puppy basics and obedience sessions.', 'From ₹799' ),
		),
		'rpw-svc-grid'
	)
	. $btns( array( array( 'Book a service', '/services/', false ) ) ),
	true
);
$home .= $g(
	'rpw-section rpw-mypets',
	$cols(
		array(
			$h( 'Your pet’s records, in one place' )
			. $p( 'Add your dog or cat to your account and we’ll keep their history for you — grooming and vet visits, vaccinations with due dates, and what they eat.' )
			. $btns( array( array( 'Add your pet', '/my-account/my-pets/', false ) ) ),
			$ul( array( 'Vaccination due dates at a glance', 'Every visit and vet note in one timeline', 'Pick your pet when you book a service' ), 'rpw-ticks' ),
		)
	)
);
$home .= $g(
	'rpw-section rpw-about-snip',
	$cols(
		array(
			$h( 'About Royal Pet World' )
			. $p( 'We’re a neighbourhood pet store and care centre in Manikonda for dogs, cats and the people who love them. Come in for food and supplies, stay for a groom or a vet check-up — and talk to people who know pets.' )
			. $btns( array( array( 'Read our story', '/about-us/', true ) ) ),
			$ul( array( 'Everything for dogs and cats under one roof', 'Gentle, patient handling for every pet', 'Honest advice on food, health and care' ), 'rpw-ticks' ),
		)
	)
);
$home .= $g(
	'rpw-band rpw-contact-strip',
	$cols(
		array(
			$h( 'Visit us', 3 ) . $p( esc_html( $biz['address'] ) ),
			$h( 'Call or WhatsApp', 3 ) . $p( $phone . '<br>' . $mail ),
			$h( 'Opening hours', 3 ) . $p( esc_html( $biz['hours'] ) ),
		)
	)
	. $btns( array( array( 'Contact us', '/contact-us/', false ) ) ),
	true
);

// ======================= ABOUT =======================
$about  = $g( 'rpw-page-hero', $h( 'About Royal Pet World', 1 ) . $p( 'A pet store and care centre for dogs and cats in Manikonda, Hyderabad.', 'rpw-lead' ), true );
$about .= $g(
	'rpw-section',
	$cols(
		array(
			$h( 'Our story' )
			. $p( 'Royal Pet World started with a simple idea: pet parents shouldn’t have to go to one place for food, another for grooming and a third for a vet check-up. So we brought it together — a store stocked with what dogs and cats actually need, and a care team that treats every pet like their own.' )
			. $p( 'Whether you’re bringing home your first puppy or looking after a senior cat, we’re here to help you choose the right food, keep up with vaccinations and keep your pet looking and feeling their best.' ),
			$img( $dog_img, 'rpw-about-img' ),
		)
	)
);
$about .= $g(
	'rpw-band rpw-services',
	$h( 'What we offer' )
	. $cols(
		array(
			$card( 'rpw-svc rpw-svc-shop', $h( 'Pet shop', 3 ) . $p( 'Food, treats, toys, grooming and health products for dogs and cats.' ) ),
			$card( 'rpw-svc rpw-svc-grooming', $h( 'Grooming', 3 ) . $p( 'Baths, haircuts, nail trims and ear cleaning.' ) ),
			$card( 'rpw-svc rpw-svc-vet', $h( 'Vet care', 3 ) . $p( 'Consultations and vaccinations.' ) ),
			$card( 'rpw-svc rpw-svc-boarding', $h( 'Daycare, boarding &amp; training', 3 ) . $p( 'Day care, overnight stays and training sessions.' ) ),
		),
		'rpw-svc-grid'
	),
	true
);
$about .= $g(
	'rpw-section',
	$h( 'Our promise' )
	. $cols(
		array(
			$card( 'rpw-promise', $h( 'Pets first', 3 ) . $p( 'Gentle, patient handling — we take our time so every visit is calm.' ) ),
			$card( 'rpw-promise', $h( 'Honest advice', 3 ) . $p( 'We recommend what’s right for your pet, not what’s most expensive.' ) ),
			$card( 'rpw-promise', $h( 'One record', 3 ) . $p( 'Visits, vaccinations and notes kept together in your pet’s profile.' ) ),
		)
	)
	. $btns( array( array( 'Shop now', '/shop/', false ), array( 'Book a service', '/services/', true ) ) )
);

// ======================= CONTACT =======================
$map      = '<!-- wp:html -->' . "\n" . '<div class="rpw-map"><iframe title="Map: Manikonda, Hyderabad" src="https://maps.google.com/maps?q=' . rawurlencode( 'Manikonda, Hyderabad' ) . '&amp;z=14&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>' . "\n" . '<!-- /wp:html -->' . "\n";
$contact  = $g( 'rpw-page-hero', $h( 'Contact us', 1 ) . $p( 'Questions about a product, a booking or your pet? We’re happy to help.', 'rpw-lead' ), true );
$contact .= $g(
	'rpw-section',
	$cols(
		array(
			$card( 'rpw-contact-card rpw-c-visit', $h( 'Visit the store', 3 ) . $p( esc_html( $biz['address'] ) ) )
			. $card( 'rpw-contact-card rpw-c-phone', $h( 'Call or WhatsApp', 3 ) . $p( $phone ) )
			. $card( 'rpw-contact-card rpw-c-mail', $h( 'Email', 3 ) . $p( $mail ) )
			. $card( 'rpw-contact-card rpw-c-hours', $h( 'Opening hours', 3 ) . $p( esc_html( $biz['hours'] ) ) )
			. $btns( array( array( 'Book a service', '/services/', false ), array( 'Shop online', '/shop/', true ) ) ),
			$map,
		),
		'rpw-contact-cols'
	)
);

// ---- create / update pages ----
$upsert = static function ( string $slug, string $title, string $content ) {
	$existing = get_page_by_path( $slug );
	$id       = wp_insert_post(
		array(
			'ID'           => $existing ? $existing->ID : 0,
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $title,
			'post_content' => $content,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	foreach ( array( '_kad_post_title' => 'hide', '_kad_post_layout' => 'fullwidth', '_kad_post_content_style' => 'unboxed', '_kad_post_vertical_padding' => 'hide' ) as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	WP_CLI::log( "$title → page $id" );
	return $id;
};
$home_id    = $upsert( 'home', 'Home', $home );
$about_id   = $upsert( 'about-us', 'About Us', $about );
$contact_id = $upsert( 'contact-us', 'Contact Us', $contact );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );

// ---- menu: About Us + Contact Us at the end of Primary (once) ----
$menu = wp_get_nav_menu_object( 'Primary' );
if ( $menu ) {
	$items = wp_get_nav_menu_items( $menu->term_id );
	$have  = array_map( static fn( $i ) => (int) $i->object_id, $items ?: array() );
	foreach ( array( $about_id => 'About Us', $contact_id => 'Contact Us' ) as $pid => $label ) {
		if ( ! in_array( $pid, $have, true ) ) {
			wp_update_nav_menu_item( $menu->term_id, 0, array( 'menu-item-object-id' => $pid, 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-title' => $label, 'menu-item-status' => 'publish' ) );
		}
	}
}
WP_CLI::success( 'Pages built; front page = Home.' );

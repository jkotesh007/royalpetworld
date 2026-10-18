( function () {
	'use strict';

	var items = document.querySelectorAll( '.woocommerce ul.products li.product' );
	if ( ! items.length ) {
		return;
	}

	if ( ! ( 'IntersectionObserver' in window ) ) {
		items.forEach( function ( el ) {
			el.classList.add( 'rpw-visible' );
		} );
		return;
	}

	var observer = new IntersectionObserver(
		function ( entries, obs ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'rpw-visible' );
					obs.unobserve( entry.target );
				}
			} );
		},
		{ threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
	);

	items.forEach( function ( el, i ) {
		el.style.transitionDelay = Math.min( i * 60, 480 ) + 'ms';
		observer.observe( el );
	} );
} )();

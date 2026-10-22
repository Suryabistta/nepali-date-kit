/* Nepali Date Kit: refresh "today" dates on pages served from a cache after midnight. */
( function () {
	'use strict';
	var config = window.ndkToday || {};
	if ( ! config.endpoint || ! window.fetch ) {
		return;
	}

	function url( params ) {
		return config.endpoint + ( config.endpoint.indexOf( '?' ) === -1 ? '?' : '&' ) + new URLSearchParams( params ).toString();
	}

	function get( params ) {
		return fetch( url( params ), { credentials: 'omit' } ).then( function ( r ) {
			return r.ok ? r.json() : null;
		} );
	}

	document.querySelectorAll( '[data-ndk-ymd]' ).forEach( function ( el ) {
		var offset = parseInt( el.getAttribute( 'data-ndk-offset' ), 10 ) || 0;
		var today = new Date( Date.now() + offset * 60000 ).toISOString().slice( 0, 10 );
		if ( today === el.getAttribute( 'data-ndk-ymd' ) ) {
			return;
		}
		get( { from: 'ad', date: today, lang: el.getAttribute( 'data-ndk-lang' ) || 'ne', format: el.getAttribute( 'data-ndk-format' ) || '' } )
			.then( function ( bs ) {
				if ( ! bs || ! bs.formatted ) {
					return;
				}
				var bsEl = el.querySelector( '.ndk-today__bs' );
				if ( bsEl ) {
					bsEl.textContent = bs.formatted;
					bsEl.setAttribute( 'datetime', today );
				}
				el.setAttribute( 'data-ndk-ymd', today );
				var adEl = el.querySelector( '.ndk-today__ad' );
				if ( adEl && bs.date ) {
					return get( { from: 'bs', date: bs.date, format: el.getAttribute( 'data-ndk-adformat' ) || '' } ).then( function ( ad ) {
						if ( ad && ad.formatted ) {
							adEl.textContent = ad.formatted;
						}
					} );
				}
			} )
			.catch( function () {} );
	} );
} )();

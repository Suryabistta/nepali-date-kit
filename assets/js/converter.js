/* Nepali Date Kit – BS <-> AD converter. No dependencies. */
( function () {
	'use strict';

	var config = window.ndkConverter || {};

	function daysInMonth( from, year, month ) {
		if ( from === 'bs' ) {
			var row = config.months && config.months[ year ];
			return row ? row[ month - 1 ] : 32;
		}
		return new Date( Date.UTC( year, month, 0 ) ).getUTCDate();
	}

	function syncDays( form ) {
		var from = form.getAttribute( 'data-from' );
		var year = parseInt( form.elements.year.value, 10 );
		var month = parseInt( form.elements.month.value, 10 );
		var daySelect = form.elements.day;
		var max = daysInMonth( from, year, month );
		var current = parseInt( daySelect.value, 10 ) || 1;

		while ( daySelect.options.length > max ) {
			daySelect.remove( daySelect.options.length - 1 );
		}
		while ( daySelect.options.length < max ) {
			var n = daySelect.options.length + 1;
			daySelect.add( new Option( String( n ), String( n ) ) );
		}
		daySelect.value = String( Math.min( current, max ) );
	}

	function convert( form, root ) {
		var from = form.getAttribute( 'data-from' );
		var output = form.querySelector( '.ndk-converter__result' );
		var date = [ form.elements.year.value, form.elements.month.value, form.elements.day.value ].join( '-' );
		var params = new URLSearchParams( {
			from: from,
			date: date,
			lang: root.getAttribute( 'data-lang' ) || 'ne',
			format: root.getAttribute( from === 'ad' ? 'data-bs-format' : 'data-ad-format' ) || '',
		} );
		var url = config.endpoint + ( config.endpoint.indexOf( '?' ) === -1 ? '?' : '&' ) + params.toString();

		output.classList.remove( 'is-error' );
		output.textContent = '…';

		fetch( url, { credentials: 'omit' } )
			.then( function ( res ) {
				return res.json().then( function ( body ) {
					if ( ! res.ok ) {
						throw new Error( ( body && body.message ) || config.i18n.error );
					}
					return body;
				} );
			} )
			.then( function ( body ) {
				output.textContent = body.formatted;
			} )
			.catch( function ( err ) {
				output.classList.add( 'is-error' );
				output.textContent = err.message || config.i18n.error;
			} );
	}

	function setup( root ) {
		root.querySelectorAll( '.ndk-converter__panel' ).forEach( function ( form ) {
			syncDays( form );
			form.elements.year.addEventListener( 'change', function () {
				syncDays( form );
			} );
			form.elements.month.addEventListener( 'change', function () {
				syncDays( form );
			} );
			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				convert( form, root );
			} );
		} );
	}

	function boot() {
		document.querySelectorAll( '.ndk-converter' ).forEach( setup );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();

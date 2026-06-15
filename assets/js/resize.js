( function () {
	'use strict';

	var DT_ORIGIN = 'https://app.donatotomato.com';

	window.addEventListener( 'message', function ( event ) {
		// Only accept resize messages from the DonatoTomato widget origin.
		if ( event.origin !== DT_ORIGIN ) {
			return;
		}
		if ( ! event.data || event.data.type !== 'dt-resize' ) {
			return;
		}
		var height = parseInt( event.data.height, 10 );
		if ( ! height || isNaN( height ) ) {
			return;
		}
		var wrappers = document.querySelectorAll( '.donatotomato-wrapper' );
		for ( var i = 0; i < wrappers.length; i++ ) {
			var iframe = wrappers[ i ].querySelector( 'iframe' );
			if ( iframe && event.source === iframe.contentWindow ) {
				iframe.style.height = ( height + 24 ) + 'px';
				break;
			}
		}
	} );
}() );

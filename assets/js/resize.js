( function () {
	'use strict';

	window.addEventListener( 'message', function ( event ) {
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

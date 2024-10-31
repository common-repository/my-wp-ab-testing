( function( $ ) {
	'use strict';

	jQuery( document ).ready( function( ) {

		$( '.reblexab-wrapper a' ).on( 'click', function( event ) {

			event.preventDefault();
			
			var target = $( this ).attr( 'href' );
			var id = $( this ).closest( '.reblexab-wrapper' ).data( 'id' );
			var block = $( this ).closest( '.reblexab-wrapper' ).data( 'block' );
			var url = $( this ).closest( '.reblexab-wrapper' ).data( 'url' );
			
			if ( target === url ) {
				var data = {
					'action': 'reblexab_stat',
					'target': target,
					'id': id,
					'block': block,
					'url': url
				}

				$.ajax( {
					url: reblexab_localize.reblexab_ajax_url,
					type: 'post',
					data: data,
					success: function( response ) {
//						console.log( response );
						window.location.href = target;
					},
					error: function( response ) {
//						console.log( response );
//						console.log( id + '|' + block + '|' + url + '|' + target );
						window.location.href = target;
					}
				} );
			}
			window.location.href = target;
		} );
	} );
} )( jQuery );
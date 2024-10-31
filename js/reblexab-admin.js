( function( $ ) {
	'use strict';

	jQuery( document ).ready( function( ) {

		$( '#reblexab_percentage_selector' ).on( 'input change', function( ) {
			var value_a = parseInt( $( this ).val() );
			var value_b = 100 - value_a;
			$( '#abtesting_distribution_a' ).val( value_a );
			$( '#abtesting_distribution_b' ).val( value_b );
		} );

		$( '#abtesting_distribution_a' ).on( 'input change', function( ) {
			var value_a = parseInt( $( this ).val() );
			var value_b = 100 - value_a;
			$( '#reblexab_percentage_selector' ).val( value_a );
			$( '#abtesting_distribution_b' ).val( value_b );
		} );

		$( '#abtesting_distribution_b' ).on( 'input change', function( ) {
			var value_b = parseInt( $( this ).val() );
			var value_a = 100 - value_b;
			$( '#reblexab_percentage_selector' ).val( value_a );
			$( '#abtesting_distribution_a' ).val( value_a );
		} );

		$( '.reblexab_chart' ).each( function( ) {
			var ctx = $( this );
			if ( ctx ) {
				var data_a = parseInt( $( ctx ).attr( 'data-a' ) );
				var data_b = parseInt( $( ctx ).attr( 'data-b' ) );
				var data_title = $( ctx ).attr( 'data-title' );
				var data = {
					labels: [ 'Block B', 'Block A' ],
					datasets: [ {
						data: [ data_b, data_a ],
						backgroundColor: [ 'rgba( 51, 153, 204, 0.7 )', 'rgba( 255, 52, 117, 0.7 )' ],
					} ]
				}
				var chart = new Chart( ctx, {
					type: 'pie',
					data: data,
					options: {
						title: {
							display: true,
							text: data_title,
						},
						legend: {
							display: true,
							position: 'bottom',
							rtl: true,
						},
					}
				} );
			}
		} );
	} );
} )( jQuery );
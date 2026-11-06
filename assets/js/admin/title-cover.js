/**
 * Title cover image picker (inc/upperel-title-cover.php): page editor box
 * and category/tag screens. Uses the WordPress media library.
 */
/* global upperelTitleCover */
( function ( $ ) {
	'use strict';

	function setField( $field, attachment ) {
		const $id = $field.find( '.upperel-title-cover-field__id' );
		const $preview = $field.find( '.upperel-title-cover-field__preview' );
		const $select = $field.find( '.upperel-title-cover-field__select' );
		const $remove = $field.find( '.upperel-title-cover-field__remove' );

		if ( attachment ) {
			const size =
				( attachment.sizes &&
					( attachment.sizes.medium || attachment.sizes.full ) ) ||
				attachment;
			$id.val( attachment.id );
			$preview
				.empty()
				.append( $( '<img>', { src: size.url, alt: '' } ) )
				.prop( 'hidden', false );
			$select.text( $select.data( 'label-replace' ) );
			$remove.prop( 'hidden', false );
		} else {
			$id.val( '' );
			$preview.empty().prop( 'hidden', true );
			$select.text( $select.data( 'label-set' ) );
			$remove.prop( 'hidden', true );
		}

		// Let the block editor know the page has unsaved changes.
		$id.trigger( 'change' );
	}

	$( document ).on(
		'click',
		'.upperel-title-cover-field__select',
		function ( e ) {
			e.preventDefault();
			const $field = $( this ).closest( '.upperel-title-cover-field' );

			const frame = wp.media( {
				title: upperelTitleCover.frameTitle,
				button: { text: upperelTitleCover.frameButton },
				library: { type: 'image' },
				multiple: false,
			} );

			// Pre-select the current image.
			frame.on( 'open', function () {
				const current = parseInt(
					$field.find( '.upperel-title-cover-field__id' ).val(),
					10
				);
				if ( current ) {
					const attachment = wp.media.attachment( current );
					attachment.fetch();
					frame.state().get( 'selection' ).add( attachment );
				}
			} );

			frame.on( 'select', function () {
				setField(
					$field,
					frame.state().get( 'selection' ).first().toJSON()
				);
			} );

			frame.open();
		}
	);

	$( document ).on(
		'click',
		'.upperel-title-cover-field__remove',
		function ( e ) {
			e.preventDefault();
			setField( $( this ).closest( '.upperel-title-cover-field' ), null );
		}
	);

	// "Add new category/tag" submits by AJAX and keeps the form on screen;
	// WordPress only clears its visible text fields, so clear ours too.
	$( document ).ajaxSuccess( function ( event, xhr, settings ) {
		if (
			settings &&
			typeof settings.data === 'string' &&
			settings.data.indexOf( 'action=add-tag' ) !== -1 &&
			xhr.responseXML &&
			! $( xhr.responseXML ).find( 'wp_error' ).length
		) {
			$( '#addtag .upperel-title-cover-field' ).each( function () {
				setField( $( this ), null );
			} );
		}
	} );
} )( jQuery );

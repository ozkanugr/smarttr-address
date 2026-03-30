/**
 * SmartTRAddress — Classic checkout cascade logic.
 *
 * Handles Province → District (client-side) cascade
 * for both billing and shipping address types.
 *
 * @requires jQuery, selectWoo, wc-checkout
 * @global   cecomsmaradData  Localized from PHP via wp_localize_script.
 * @package  SmartTRAddress
 */

/* global jQuery, cecomsmaradData */
(function ( $ ) {
	'use strict';

	if ( typeof cecomsmaradData === 'undefined' ) {
		return;
	}

	/**
	 * Build a provinces lookup: { code: name, ... }
	 */
	var provinceLookup = {};
	$.each( cecomsmaradData.provinces, function ( _i, p ) {
		provinceLookup[ p.code ] = p.name;
	} );

	/**
	 * Initialise (or re-initialise) cascade for a given address type.
	 *
	 * @param {string} type 'billing' or 'shipping'
	 */
	function initCascade( type ) {
		var $country = $( '#' + type + '_country' );

		if ( ! $country.length ) {
			return;
		}

		/* ---------- Country change ---------- */
		$country.off( 'change.smarttr' ).on( 'change.smarttr', function () {
			if ( $( this ).val() === 'TR' ) {
				activateCascade( type );
			} else {
				deactivateCascade( type );
			}
		} );

		/* Activate or deactivate based on the current country. */
		if ( $country.val() === 'TR' ) {
			activateCascade( type );
		} else {
			deactivateCascade( type );
		}
	}

	/**
	 * Activate the Turkish cascade for the given address type.
	 *
	 * Converts city and address_2 text inputs to selects when needed
	 * (e.g. when the user switches to Turkey from another country).
	 *
	 * @param {string} type 'billing' or 'shipping'
	 */
	function activateCascade( type ) {
		var $province = $( '#' + type + '_state' );
		var $district = ensureSelectField( type, 'city', 'cecomsmarad-district' );
		var $postcode = $( '#' + type + '_postcode' );

		/* Mark container as JS-ready. */
		$province.closest( 'form' ).addClass( 'cecomsmarad-js-ready' );

		/* Ensure aria-live region exists for screen reader announcements. */
		if ( ! $( '#cecomsmarad-aria-live-' + type ).length ) {
			$province.closest( 'form' ).append(
				'<div id="cecomsmarad-aria-live-' + type + '" class="cecomsmarad-sr-only" aria-live="polite" aria-atomic="true"></div>'
			);
		}

		/* Province select — WC's country-select.js handles the state field
		 * natively (select/text conversion + selectWoo init). We re-init
		 * after a short delay so WC has already finished its work, then we
		 * add allowClear to the province selectWoo as well. */
		setTimeout( function () {
			if ( $province.is( 'select' ) && $province.data( 'select2' ) ) {
				initSelectWoo( $province, cecomsmaradData.i18n.selectProvince );
			}
		}, 100 );

		/* --- District select --- */
		initSelectWoo( $district, cecomsmaradData.i18n.selectDistrict );

		/* --- Province → District (client-side) --- */
		$province.off( 'change.smarttr' ).on( 'change.smarttr', function () {
			var code      = $( this ).val();
			var districts = cecomsmaradData.districts[ code ] || [];
			var provName  = $( this ).find( 'option:selected' ).text() || '';

			populateSelect( $district, districts, cecomsmaradData.i18n.selectDistrict );
			$postcode.val( '' );

			/* Update ARIA disabled state on downstream fields. */
			setAriaDisabled( $district, ! code );

			/* Announce to screen reader. */
			if ( code && districts.length ) {
				announce( type, districts.length + ' ' + cecomsmaradData.i18n.selectDistrict + ' — ' + provName );
			}
		} );

		/* --- District change — clear postcode --- */
		$district.off( 'change.smarttr' ).on( 'change.smarttr', function () {
			$postcode.val( '' );
		} );

		/* Show turkey-only custom fields for this address type. */
		updateTurkeyOnlyFields( type, true );

		/* Trigger initial cascade only when district is not already populated.
		 * On `updated_checkout` re-init the selects already have options and
		 * a user selection — re-triggering would wipe that selection. */
		if ( $province.val() && $district.find( 'option' ).length <= 1 ) {
			var savedCity     = cecomsmaradData.savedValues && cecomsmaradData.savedValues[ type + '_city' ];
			var savedPostcode = cecomsmaradData.savedValues && cecomsmaradData.savedValues[ type + '_postcode' ];

			if ( savedCity ) {
				/* Restore flow: populate districts and re-select saved values without
				 * triggering the normal change handler that would wipe everything. */
				var code      = $province.val();
				var districts = cecomsmaradData.districts[ code ] || [];

				populateSelect( $district, districts, cecomsmaradData.i18n.selectDistrict );
				$district.val( savedCity );
				if ( $district.data( 'select2' ) ) {
					$district.trigger( 'change.select2' );
				}
				setAriaDisabled( $district, false );

				if ( savedPostcode ) {
					$postcode.val( savedPostcode );
				}
			} else {
				/* No saved values — normal initial population. */
				$province.trigger( 'change.smarttr' );
			}
		}
	}

	/**
	 * Deactivate the Turkish cascade — restore fields to default WooCommerce
	 * text inputs so other countries work normally.
	 *
	 * Converts <select> elements for city and address_2 back to
	 * <input type="text">, destroys selectWoo, unbinds smarttr events,
	 * and removes all cecomsmarad-specific CSS classes.
	 *
	 * @param {string} type 'billing' or 'shipping'
	 */
	function deactivateCascade( type ) {
		var fields = [ 'city', 'address_2' ];

		$.each( fields, function ( _i, field ) {
			restoreTextField( type, field );
		} );

		/* Unbind smarttr events from province (WC handles its own state select). */
		$( '#' + type + '_state' ).off( '.smarttr' );

		/* Remove postcode smarttr classes. */
		$( '#' + type + '_postcode' ).closest( '.form-row' )
			.removeClass( 'cecomsmarad-field cecomsmarad-postcode' );

		/* Remove form-level class and ARIA live region. */
		var $form = $( '#' + type + '_state' ).closest( 'form' );
		$form.removeClass( 'cecomsmarad-js-ready' );
		$( '#cecomsmarad-aria-live-' + type ).remove();

		/* Hide turkey-only custom fields for this address type. */
		updateTurkeyOnlyFields( type, false );
	}

	/**
	 * Show or hide custom fields that are marked "turkey_only" for a given
	 * address type based on whether the selected country is Turkey.
	 *
	 * PHP renders these fields hidden (cecomsmarad-field-hidden class) when the
	 * page-load country is not Turkey.  This function keeps them in sync on
	 * every subsequent country change without a full page reload.
	 *
	 * @param {string}  type     'billing' or 'shipping'.
	 * @param {boolean} isTurkey true when the current country is Turkey.
	 */
	function updateTurkeyOnlyFields( type, isTurkey ) {
		var keys = ( cecomsmaradData.turkeyOnlyFields && cecomsmaradData.turkeyOnlyFields[ type ] ) || [];

		$.each( keys, function ( _i, fieldKey ) {
			var $wrapper = $( '#' + fieldKey + '_field' );
			if ( ! $wrapper.length ) {
				return;
			}
			if ( isTurkey ) {
				$wrapper.removeClass( 'cecomsmarad-field-hidden' );
			} else {
				$wrapper.addClass( 'cecomsmarad-field-hidden' );
			}
		} );
	}

	/**
	 * Convert a <select> field back to <input type="text">.
	 *
	 * Mirrors WooCommerce's own country-select.js behavior for the state
	 * field: destroy selectWoo, replace the DOM element, and preserve id,
	 * name, and WooCommerce-standard CSS classes.
	 *
	 * @param {string} type  'billing' or 'shipping'.
	 * @param {string} field 'city' or 'address_2'.
	 */
	function restoreTextField( type, field ) {
		var fullKey  = type + '_' + field;
		var $el      = $( '#' + fullKey );
		var $wrapper = $el.closest( '.form-row' );

		if ( ! $el.length ) {
			return;
		}

		/* Destroy selectWoo if initialised. */
		if ( $el.data( 'select2' ) ) {
			$el.selectWoo( 'destroy' );
		}

		/* Unbind cecomsmarad-namespaced events. */
		$el.off( '.smarttr' );

		/* If the element is a <select>, replace it with a text input. */
		if ( $el.is( 'select' ) ) {
			var $input = $( '<input type="text" />' )
				.attr( 'id', fullKey )
				.attr( 'name', fullKey )
				.addClass( 'input-text' )
				.val( '' );

			$el.replaceWith( $input );
		}

		/* Remove smarttr CSS classes from the wrapper row. */
		$wrapper.removeClass( 'cecomsmarad-field cecomsmarad-district' );
	}

	/**
	 * Ensure a field is a <select> element — convert from text input if needed.
	 *
	 * When the customer switches to Turkey from another country, city and
	 * address_2 fields are standard <input type="text">. This function
	 * replaces them with <select> elements for the cascade to work.
	 *
	 * @param {string} type        'billing' or 'shipping'.
	 * @param {string} field       'city' or 'address_2'.
	 * @param {string} smarttrCls  smarttr CSS class for the wrapper row.
	 * @return {jQuery} The (possibly new) <select> element.
	 */
	function ensureSelectField( type, field, smarttrCls ) {
		var fullKey  = type + '_' + field;
		var $el      = $( '#' + fullKey );
		var $wrapper = $el.closest( '.form-row' );

		if ( ! $el.length ) {
			return $el;
		}

		if ( $el.is( 'input' ) ) {
			var $select = $( '<select />' )
				.attr( 'id', fullKey )
				.attr( 'name', fullKey )
				.addClass( 'cecomsmarad-select' );

			$el.replaceWith( $select );
			$wrapper.addClass( 'cecomsmarad-field ' + smarttrCls );

			return $select;
		}

		/* Already a select — just ensure classes are present. */
		$wrapper.addClass( 'cecomsmarad-field ' + smarttrCls );
		return $el;
	}

	/* ======================================================================
	 * Helpers
	 * ==================================================================== */

	/**
	 * Check whether the admin has enabled the "clear" (allowClear) option
	 * for a given field key.
	 *
	 * @param {string} fieldId The DOM id of the select element (e.g. 'billing_state').
	 * @return {boolean} True when the admin has enabled clearing for this field.
	 */
	function isFieldClearEnabled( fieldId ) {
		var list = cecomsmaradData.clearFields || [];
		return $.inArray( fieldId, list ) !== -1;
	}

	/**
	 * Initialise selectWoo on an element (destroy first if already active).
	 *
	 * Applies ARIA attributes for accessibility: aria-label from the
	 * associated <label>, aria-required, and aria-disabled.
	 *
	 * @param {jQuery} $el          Select element.
	 * @param {string} placeholder  Placeholder text.
	 */
	function initSelectWoo( $el, placeholder ) {
		if ( ! $el.length ) {
			return;
		}

		if ( $el.data( 'select2' ) ) {
			$el.selectWoo( 'destroy' );
		}

		var fieldId    = $el.attr( 'id' ) || '';
		var allowClear = isFieldClearEnabled( fieldId );

		$el.selectWoo( {
			placeholder:      placeholder,
			allowClear:       allowClear,
			width:            '100%',
			dropdownCssClass: 'cecomsmarad-dropdown',
			language:         {
				noResults: function () {
					return cecomsmaradData.i18n.noResults;
				}
			}
		} );

		/* Add ARIA attributes to selectWoo wrapper. */
		var $container = $el.next( '.select2-container' );
		var $label     = $el.closest( '.form-row' ).find( 'label' );
		var labelText  = $label.length ? $label.text().replace( /\s*\*\s*$/, '' ).trim() : placeholder;
		var isRequired = $el.prop( 'required' ) || $el.closest( '.validate-required' ).length > 0;

		$container.find( '.select2-selection' )
			.attr( 'aria-label', labelText )
			.attr( 'aria-required', isRequired ? 'true' : 'false' );
	}

	/**
	 * Populate a <select> element with option items.
	 *
	 * Option value is always the item name (WooCommerce expects text in
	 * billing_city / billing_address_2). The numeric DB id is stored in
	 * a data-id attribute for AJAX lookups.
	 *
	 * @param {jQuery} $el          Target select element.
	 * @param {Array}  items        Array of { id, name }.
	 * @param {string} placeholder  First empty-option label.
	 */
	function populateSelect( $el, items, placeholder ) {
		var html = '<option value="">' + escapeHtml( placeholder ) + '</option>';

		$.each( items, function ( _i, item ) {
			var text = item.name;

			html += '<option value="' + escapeHtml( text ) + '"';
			if ( item.id ) {
				html += ' data-id="' + escapeHtml( String( item.id ) ) + '"';
			}
			html += '>' + escapeHtml( text ) + '</option>';
		} );

		$el.html( html );

		/* Refresh selectWoo display. */
		if ( $el.data( 'select2' ) ) {
			$el.trigger( 'change.select2' );
		}
	}

	/**
	 * Reset a select to its placeholder-only state.
	 *
	 * @param {jQuery} $el          Target select element.
	 * @param {string} placeholder  Placeholder label.
	 */
	function resetSelect( $el, placeholder ) {
		$el.html( '<option value="">' + escapeHtml( placeholder ) + '</option>' );

		if ( $el.data( 'select2' ) ) {
			$el.val( '' ).trigger( 'change.select2' );
		}
	}

	/**
	 * Set aria-disabled on a selectWoo element's container.
	 *
	 * @param {jQuery}  $el      Select element.
	 * @param {boolean} disabled Whether the field is disabled.
	 */
	function setAriaDisabled( $el, disabled ) {
		var $container = $el.next( '.select2-container' );
		if ( $container.length ) {
			$container.find( '.select2-selection' ).attr( 'aria-disabled', disabled ? 'true' : 'false' );
		}
	}

	/**
	 * Announce a message to the aria-live region for a given address type.
	 *
	 * @param {string} type    'billing' or 'shipping'.
	 * @param {string} message Message to announce.
	 */
	function announce( type, message ) {
		var $live = $( '#cecomsmarad-aria-live-' + type );
		if ( $live.length ) {
			$live.text( message );
		}
	}

	/**
	 * Minimal HTML-entity escaping for safe string interpolation.
	 *
	 * @param {string} str Raw string.
	 * @return {string} Escaped string.
	 */
	function escapeHtml( str ) {
		if ( typeof str !== 'string' ) {
			return '';
		}
		return str
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	/**
	 * Enforce hidden visibility on fields configured as hidden in admin.
	 *
	 * Primary hiding is done via server-rendered inline CSS targeting
	 * wrapper IDs. This function is a safety net: it re-adds the CSS
	 * class after WC's locale processing may have replaced classes.
	 * Deferred with setTimeout so it always runs last.
	 */
	function enforceHiddenFields() {
		setTimeout( function () {
			var hidden = cecomsmaradData.hiddenFields || [];

			$.each( hidden, function ( _i, fieldKey ) {
				$( '#' + fieldKey + '_field' ).addClass( 'cecomsmarad-field-hidden' );
			} );
		}, 0 );
	}

	/* ======================================================================
	 * Bootstrap
	 * ==================================================================== */

	/**
	 * Initialise selectWoo on custom select fields.
	 *
	 * Custom fields of type "select" are rendered as plain <select>
	 * elements by WooCommerce. This applies the same selectWoo styling
	 * used by WC's built-in country/state selects.
	 */
	function initCustomSelects() {
		$( 'select.cecomsmarad-custom-select' ).each( function () {
			var $el          = $( this );
			var placeholder  = $el.find( 'option[value=""]' ).text() || '';
			var fieldId      = $el.attr( 'id' ) || '';
			var allowClear   = isFieldClearEnabled( fieldId );

			if ( $el.data( 'select2' ) ) {
				$el.selectWoo( 'destroy' );
			}

			$el.selectWoo( {
				placeholder: placeholder,
				allowClear:  allowClear,
				width:       '100%'
			} );
		} );
	}

	/**
	 * Set up pre-upload handling for custom file-type fields.
	 *
	 * When the user selects a file, it is immediately uploaded via AJAX
	 * using FormData (multipart). On success, the returned URL is stored
	 * in a companion hidden input ({fieldKey}_url) so it can be submitted
	 * with WooCommerce's AJAX checkout request.
	 */
	function initFileFields() {
		var fileFields = cecomsmaradData.fileFields || [];

		if ( ! fileFields.length ) {
			return;
		}

		$.each( fileFields, function ( _i, fieldKey ) {
			var $input = $( '#' + fieldKey );

			if ( ! $input.length || $input.data( 'cecomsmarad-file-init' ) ) {
				return;
			}

			$input.data( 'cecomsmarad-file-init', true );

			// Ensure a companion hidden URL input exists.
			var $urlInput = $( '#' + fieldKey + '_url' );
			if ( ! $urlInput.length ) {
				$urlInput = $( '<input type="hidden" />' )
					.attr( 'id', fieldKey + '_url' )
					.attr( 'name', fieldKey + '_url' )
					.val( '' );
				$input.after( $urlInput );
			}

			// Ensure a companion hidden attachment ID input exists.
			var $attachmentIdInput = $( '#' + fieldKey + '_attachment_id' );
			if ( ! $attachmentIdInput.length ) {
				$attachmentIdInput = $( '<input type="hidden" />' )
					.attr( 'id', fieldKey + '_attachment_id' )
					.attr( 'name', fieldKey + '_attachment_id' )
					.val( '' );
				$urlInput.after( $attachmentIdInput );
			}

			// Status span for user feedback.
			var $status = $( '#cecomsmarad-file-status-' + fieldKey );
			if ( ! $status.length ) {
				$status = $( '<span class="cecomsmarad-file-status" aria-live="polite"></span>' )
					.attr( 'id', 'cecomsmarad-file-status-' + fieldKey );
				$input.after( $status );
			}

			$input.off( 'change.smarttrFile' ).on( 'change.smarttrFile', function () {
				var file = this.files && this.files[0];

				if ( ! file ) {
					$urlInput.val( '' );
					$attachmentIdInput.val( '' );
					$status.text( '' );
					return;
				}

				var formData = new FormData();
				formData.append( 'action', 'cecomsmarad_upload_file' );
				formData.append( '_wpnonce', cecomsmaradData.uploadNonce );
				formData.append( 'field_key', fieldKey );
				formData.append( 'file', file );

				$status.text( '⏳' );
				$urlInput.val( '' );

				$.ajax( {
					url:         cecomsmaradData.ajaxUrl,
					type:        'POST',
					data:        formData,
					processData: false,
					contentType: false,
					success: function ( response ) {
						if ( response.success && response.data && response.data.url ) {
							$urlInput.val( response.data.url );
							$attachmentIdInput.val( response.data.attachment_id || '' );
							$status.text( '✓ ' + file.name );
						} else {
							var msg = ( response.data && response.data.message ) ? response.data.message : '';
							$status.text( msg );
							$input.val( '' );
							$attachmentIdInput.val( '' );
						}
					},
					error: function () {
						$status.text( escapeHtml( '⚠ Yükleme başarısız.' ) );
						$input.val( '' );
						$attachmentIdInput.val( '' );
					}
				} );
			} );
		} );
	}

	/**
	 * Initialise both address cascades.
	 */
	function init() {
		/* Swap noscript body class to signal JS is active. */
		$( document.body ).removeClass( 'cecomsmarad-no-js' ).addClass( 'cecomsmarad-js-ready' );

		initCascade( 'billing' );
		initCascade( 'shipping' );

		/* Apply selectWoo to custom select fields. */
		initCustomSelects();

		/* Set up pre-upload for custom file fields. */
		initFileFields();

		/* Re-apply hidden state after WC locale processing. */
		enforceHiddenFields();
	}

	/* Run on DOM ready. */
	$( document ).ready( init );

	/* Re-run when WooCommerce refreshes checkout fragments via AJAX. */
	$( document.body ).on( 'updated_checkout', init );

	/* WC's country-select.js fires this AFTER applying locale overrides.
	 * Re-enforce hidden fields so locale class/hidden swaps don't reveal them. */
	$( document.body ).on( 'country_to_state_changed', enforceHiddenFields );

}( jQuery ));

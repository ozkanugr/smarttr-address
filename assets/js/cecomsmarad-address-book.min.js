/**
 * SmartTRAddress — Address Book UI.
 *
 * Handles: load dropdown cascade re-fill (checkout), save checkbox toggle (checkout),
 * edit and delete interactions (My Account).
 *
 * @requires jQuery
 * @global   smarttrAddressBook  Localized via wp_localize_script.
 * @package  SmartTRAddress
 */

/* global jQuery, smarttrAddressBook */
( function ( $ ) {
	'use strict';

	if ( typeof smarttrAddressBook === 'undefined' ) {
		return;
	}

	var cfg = smarttrAddressBook;

	// ──────────────────────────────────────────────────────────────────────────
	// Utility
	// ──────────────────────────────────────────────────────────────────────────

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

	// ──────────────────────────────────────────────────────────────────────────
	// Checkout — Load
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Re-fill the Province → District → address_2 / postcode / address_1 cascade
	 * from a saved address object.
	 *
	 * District data is already embedded in cecomsmaradData.districts (synchronous
	 * client-side lookup), so no Promise chain is required: triggering the province
	 * change handler populates the district select immediately before we set its value.
	 *
	 * @param {string} type    'billing' or 'shipping'.
	 * @param {Object} address Saved address data returned by ajax_load.
	 */
	function fillCascade( type, address ) {
		var $country = $( '#' + type + '_country' );

		if ( ! address.country || ! $country.length || $country.val() === address.country ) {
			// Country already matches → its locale + cascade settings are applied.
			fillAddressFields( type, address );
			return;
		}

		// Switch the country through WooCommerce's normal change pipeline so the
		// locale (field priorities, required, labels, classes and the plugin's Field
		// Editor settings injected via woocommerce_get_country_locale) AND the
		// plugin's Turkish cascade are applied — identical to a manual country
		// selection, which also fires WC's update_checkout AJAX.
		$country.val( address.country );
		if ( $country.data( 'select2' ) ) {
			$country.trigger( 'change.select2' );
		}
		$country.trigger( 'change' );

		// The country change rebuilds the state/city fields and the plugin re-applies
		// the cascade across the update_checkout cycle. Fill the values as soon as
		// those fields are ready (polled), then re-assert once after the checkout
		// refresh settles so an in-flight update_checkout cannot wipe them.
		waitForCascade( type, address, 0 );
		$( document.body ).one( 'updated_checkout', function () {
			fillAddressFields( type, address );
		} );
	}

	/**
	 * Poll until the address fields for the just-applied country are ready, then
	 * fill them. For Türkiye this means the district <select> has been created by
	 * the cascade; other countries use a plain city text input and are ready at once.
	 *
	 * @param {string} type    'billing' or 'shipping'.
	 * @param {Object} address Saved address data.
	 * @param {number} attempt Current poll attempt (bounded to ~2s total).
	 */
	function waitForCascade( type, address, attempt ) {
		var ready = ( 'TR' !== address.country ) || $( '#' + type + '_city' ).is( 'select' );

		if ( ready || attempt >= 40 ) {
			fillAddressFields( type, address );
			return;
		}

		window.setTimeout( function () {
			waitForCascade( type, address, attempt + 1 );
		}, 50 );
	}

	/**
	 * Fill the Province → District → neighborhood cascade and the remaining
	 * address fields from a saved address object. The correct country (and thus
	 * its locale / cascade settings) is assumed to be already applied.
	 *
	 * @param {string} type    'billing' or 'shipping'.
	 * @param {Object} address Saved address data.
	 */
	function fillAddressFields( type, address ) {
		var $province = $( '#' + type + '_state' );
		var $district = $( '#' + type + '_city' );
		var $postcode = $( '#' + type + '_postcode' );
		var $address1 = $( '#' + type + '_address_1' );
		var $address2 = $( '#' + type + '_address_2' );

		if ( ! $province.length ) {
			return;
		}

		// 1. Province value + selectWoo refresh, then notify the plugin cascade
		//    handler so it populates the district list. The handler is bound now
		//    that the cascade has settled; populate directly as a fallback so the
		//    fill stays deterministic.
		$province.val( address.province_code );
		if ( $province.data( 'select2' ) ) {
			$province.trigger( 'change.select2' );
		}
		$province.trigger( 'change.smarttr' );
		if ( $district.is( 'select' ) && $district.find( 'option' ).length <= 1 ) {
			populateDistrictOptions( $district, address.province_code );
		}

		// 2. District value.
		$district.val( address.district_name );
		if ( $district.data( 'select2' ) ) {
			$district.trigger( 'change.select2' );
		}

		// 3. Fill remaining free-text fields.
		$postcode.val( address.postcode );
		$address1.val( address.address_1 );

		// address_2 may be a text input (free) or a selectWoo (premium).
		if ( $address2.is( 'select' ) && typeof window.cecomsmaradLoadNeighborhoods === 'function' ) {
			// Premium: populate the neighborhood dropdown via AJAX, then select the
			// saved neighborhood and re-fill the postcode.
			var districtId = $district.find( 'option:selected' ).data( 'id' );
			if ( districtId ) {
				window.cecomsmaradLoadNeighborhoods( districtId, $address2, $postcode, {
					hood:     address.neighborhood,
					postcode: address.postcode,
				} );
			}
		} else {
			$address2.val( address.neighborhood );
		}

		// Simple text fields — fill directly, no cascade dependency.
		$( '#' + type + '_first_name' ).val( address.first_name || '' );
		$( '#' + type + '_last_name'  ).val( address.last_name  || '' );
		$( '#' + type + '_company'    ).val( address.company    || '' );

		// Billing-only fields.
		if ( type === 'billing' ) {
			$( '#billing_phone' ).val( address.phone || '' );
			$( '#billing_email' ).val( address.email || '' );
		}
	}

	/**
	 * Populate a Turkish district <select> directly from the embedded district
	 * data (cecomsmaradData.districts, localized by the checkout script), mirroring
	 * the checkout cascade's own option markup (value = name, data-id = numeric id).
	 *
	 * Used when restoring a saved address so the district list is filled
	 * deterministically, without depending on the cascade change-handler binding.
	 *
	 * @param {jQuery} $district    The city/district <select> element.
	 * @param {string} provinceCode Province (state) code.
	 */
	function populateDistrictOptions( $district, provinceCode ) {
		var data = window.cecomsmaradData;
		var list = ( data && data.districts && data.districts[ provinceCode ] ) || [];

		if ( ! list.length ) {
			return;
		}

		var html = '<option value=""></option>';
		$.each( list, function ( _i, d ) {
			html += '<option value="' + escapeHtml( d.name ) + '" data-id="' + escapeHtml( String( d.id ) ) + '">' + escapeHtml( d.name ) + '</option>';
		} );
		$district.html( html );
	}

	/**
	 * Initialize the address-book dropdown as a selectWoo widget.
	 * Called on DOM ready and after every updated_checkout so the widget
	 * survives WooCommerce's checkout fragment replacement.
	 */
	function initAddressBookSelects() {
		$( '.cecomsmarad-address-book-select' ).each( function () {
			var $select = $( this );

			if ( typeof $select.selectWoo !== 'function' ) {
				return;
			}

			if ( $select.data( 'select2' ) ) {
				$select.selectWoo( 'destroy' );
			}

			var placeholder = $select.find( 'option[value=""]' ).first().text() || '';

			$select.selectWoo( {
				placeholder:      placeholder,
				allowClear:       false,
				width:            '100%',
				dropdownCssClass: 'cecomsmarad-dropdown',
			} );
		} );
	}

	/**
	 * Fire an AJAX address load for the given type and address ID,
	 * then fill the cascade on success.
	 */
	function loadAddress( type, id ) {
		$.ajax( {
			url:    cfg.ajaxUrl,
			method: 'POST',
			data:   {
				action:     'cecomsmarad_address_book_load',
				nonce:      cfg.nonce,
				address_id: id,
				context:    'checkout',
			},
			success: function ( response ) {
				if ( response.success ) {
					fillCascade( type, response.data );
				}
			},
		} );
	}

	/**
	 * Wire up the address-book load: auto-apply on selectWoo change.
	 * Addresses load automatically when an option is selected — no Load button needed.
	 */
	function initCheckoutLoad() {
		$( document.body ).on( 'change', '.cecomsmarad-address-book-select', function () {
			var $select = $( this );
			var type    = $select.data( 'type' );
			var id      = $select.val();

			if ( ! id ) {
				return;
			}

			loadAddress( type, id );
		} );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Checkout — Save checkbox toggle
	// ──────────────────────────────────────────────────────────────────────────

	function initSaveCheckbox() {
		$( document.body ).on( 'change', '.cecomsmarad-address-book-save-checkbox', function () {
			var $wrap = $( this ).closest( '.cecomsmarad-address-book-save-row' )
				.find( '.cecomsmarad-address-book-nickname-wrap' );
			if ( $( this ).is( ':checked' ) ) {
				// The wrapper is a <span> (it must live inside WooCommerce's <p>
				// form-row). jQuery's slideDown() would restore a <span>'s default
				// `inline` display, which collapses floating-label / flex themes and
				// leaves the field looking hidden. Capture `block` via .hide() first
				// so the revealed field lays out correctly in any theme.
				$wrap.css( 'display', 'block' ).hide().slideDown( 150 );
			} else {
				$wrap.slideUp( 150 );
			}
		} );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// My Account — Delete
	// ──────────────────────────────────────────────────────────────────────────

	function initAccountDelete() {
		// Show inline confirm.
		$( document.body ).on( 'click', '.cecomsmarad-address-book-delete-btn', function () {
			var $card = $( this ).closest( '.cecomsmarad-address-book-card' );
			$card.find( '.cecomsmarad-address-book-delete-confirm' ).slideDown( 100 );
		} );

		// Confirm: delete via AJAX, remove card.
		$( document.body ).on( 'click', '.cecomsmarad-address-book-delete-confirm-yes', function () {
			var $card = $( this ).closest( '.cecomsmarad-address-book-card' );
			var id    = $card.data( 'id' );

			$.ajax( {
				url:    cfg.ajaxUrl,
				method: 'POST',
				data:   {
					action:     'cecomsmarad_address_book_delete',
					nonce:      cfg.nonce,
					address_id: id,
				},
				success: function ( response ) {
					if ( response.success ) {
						$card.slideUp( 200, function () {
							$( this ).remove();
						} );
					}
				},
			} );
		} );

		// Cancel.
		$( document.body ).on( 'click', '.cecomsmarad-address-book-delete-confirm-no', function () {
			$( this ).closest( '.cecomsmarad-address-book-delete-confirm' ).slideUp( 100 );
		} );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// My Account — Edit
	// ──────────────────────────────────────────────────────────────────────────

	function initAccountEdit() {
		var provinces = cfg.provinces || [];
		var districts = cfg.districts || {};

		function initSelectWooField( $select, placeholder ) {
			if ( typeof $select.selectWoo !== 'function' ) {
				return;
			}
			if ( $select.data( 'select2' ) ) {
				$select.selectWoo( 'destroy' );
			}
			$select.selectWoo( {
				placeholder: placeholder || '',
				allowClear:  false,
				width:       '100%',
			} );
		}

		// Reproduce WooCommerce's per-country field behaviour, scoped to one card.
		// TR shows the Province→District(→Neighborhood) cascade; every other country
		// shows the generic State/City/Address-2 group reshaped by that country's
		// WooCommerce locale (state select/text/hidden, labels, required, visibility,
		// priority order) — matching the checkout page.
		function applyCountryLocale( $form, country ) {
			var isTR = ( 'TR' === country );

			$form.find( '.cecomsmarad-tr-only' ).toggle( isTR );
			$form.find( '.cecomsmarad-generic-only' ).toggle( ! isTR );

			if ( ! isTR ) {
				buildStateControl( $form, country );
				applyLocaleFields( $form, country );
			}

			reorderRows( $form );
		}

		// Build the generic state control for a country, mirroring WC country-select.js:
		// non-empty states → <select>; empty-object states → hidden + cleared; no states
		// key → free-text input. The value is stored in province_code on save.
		function buildStateControl( $form, country ) {
			var $row     = $form.find( '.cecomsmarad-edit-state-row' );
			var $control = $row.find( '.cecomsmarad-edit-state-control' );
			var states   = ( cfg.states && cfg.states[ country ] ) || null;
			var $current = $control.find( '.cecomsmarad-edit-state' );
			var current  = $current.length ? ( $current.val() || '' ) : ( $row.data( 'current-state' ) || '' );

			if ( $current.length && $current.data( 'select2' ) ) {
				$current.selectWoo( 'destroy' );
			}

			if ( states && ! $.isEmptyObject( states ) ) {
				var html = '<option value=""></option>';
				$.each( states, function ( code, name ) {
					html += '<option value="' + escapeHtml( code ) + '">' + escapeHtml( name ) + '</option>';
				} );
				var $select = $( '<select class="cecomsmarad-edit-state"></select>' ).html( html ).val( current );
				$control.empty().append( $select );
				initSelectWooField( $select, cfg.i18n.selectState || '' );
				$row.show();
			} else if ( states ) {
				// Country defines no states → hide and clear, like WC's hidden input.
				$control.empty().append( $( '<input type="hidden" class="cecomsmarad-edit-state" value="" />' ) );
				$row.hide();
			} else {
				// No state data for this country → free-text input.
				$control.empty().append(
					$( '<input type="text" class="input-text cecomsmarad-edit-state" />' ).val( current )
				);
				$row.show();
			}
		}

		// Apply a country's WooCommerce locale (label / required / visibility / priority)
		// to the generic + shared locale-managed rows, mirroring address-i18n.js.
		function applyLocaleFields( $form, country ) {
			var locale  = cfg.locale || {};
			var def     = locale['default'] || {};
			var byCntry = locale[ country ] || {};
			var keys    = [ 'address_1', 'address_2', 'city', 'state', 'postcode', 'company' ];

			$.each( keys, function ( _i, key ) {
				var $row = $form.find( '.cecomsmarad-locale-field[data-field="' + key + '"]' );
				if ( ! $row.length ) {
					return;
				}

				var fl = $.extend( true, {}, def[ key ] || {}, byCntry[ key ] || {} );

				// Label text (preserve the trailing required asterisk node).
				if ( typeof fl.label !== 'undefined' ) {
					$row.find( '.cecomsmarad-label-text' ).text( fl.label );
				}

				// Placeholder.
				if ( typeof fl.placeholder !== 'undefined' ) {
					$row.find( ':input' ).attr( 'placeholder', fl.placeholder );
				}

				// Required asterisk + validate-required class.
				var required = ( typeof fl.required !== 'undefined' ) ? !! fl.required : false;
				$row.toggleClass( 'validate-required', required );
				$row.find( 'abbr.required' ).toggle( required );

				// Visibility. The state row's visibility is owned by buildStateControl.
				if ( 'state' !== key ) {
					if ( true === fl.hidden ) {
						$row.hide().find( ':input' ).val( '' );
					} else {
						$row.show();
					}
				}

				// Priority for re-sorting.
				if ( typeof fl.priority !== 'undefined' ) {
					$row.attr( 'data-priority', fl.priority ).data( 'priority', fl.priority );
				}
			} );
		}

		// Sort the form's rows by data-priority (ascending), mirroring address-i18n.js.
		function reorderRows( $form ) {
			// Rows lacking a data-priority sort last (high sentinel) rather than
			// jumping to the top, so any untagged row stays out of the address block.
			var rows = $form.children( '.form-row' ).get();
			rows.sort( function ( a, b ) {
				var pa = parseInt( $( a ).data( 'priority' ), 10 );
				var pb = parseInt( $( b ).data( 'priority' ), 10 );
				return ( isNaN( pa ) ? 9999 : pa ) - ( isNaN( pb ) ? 9999 : pb );
			} );
			$.each( rows, function ( _i, el ) {
				$form.append( el );
			} );
		}

		function populateProvinceSelect( $select ) {
			var html = '<option value="">' + escapeHtml( cfg.i18n.selectProvince || '' ) + '</option>';
			$.each( provinces, function ( _i, p ) {
				html += '<option value="' + escapeHtml( p.code ) + '">' + escapeHtml( p.name ) + '</option>';
			} );
			$select.html( html );
		}

		// Returns the ID of the selected district, or null.
		function populateDistrictSelect( $select, provinceCode, selectedName ) {
			var list       = districts[ provinceCode ] || [];
			var html       = '<option value="">' + escapeHtml( cfg.i18n.selectDistrict || '' ) + '</option>';
			var selectedId = null;
			$.each( list, function ( _i, d ) {
				html += '<option value="' + escapeHtml( d.name ) + '" data-id="' + d.id + '">' + escapeHtml( d.name ) + '</option>';
				if ( d.name === selectedName ) {
					selectedId = d.id;
				}
			} );
			$select.html( html );
			if ( selectedName ) {
				$select.val( selectedName );
			}
			return selectedId;
		}

		// Restore an Edit button to its default "Edit" label.
		function resetEditLabel( $btn ) {
			$btn.text( $btn.data( 'edit-label' ) );
		}

		// Open / close edit form. The card's Edit button doubles as the Cancel
		// control: while the form is open it shows "Cancel", and closing the form
		// (via this button or after a successful save) restores the "Edit" label.
		$( document.body ).on( 'click', '.cecomsmarad-address-book-edit-btn', function () {
			var $btn  = $( this );
			var $card = $btn.closest( '.cecomsmarad-address-book-card' );
			var $form = $card.find( '.cecomsmarad-address-book-edit-form' );

			// Already open → act as Cancel: close and restore the Edit label.
			if ( $form.is( ':visible' ) ) {
				$form.slideUp( 100 );
				resetEditLabel( $btn );
				return;
			}

			// Close other open edit forms and restore their Edit labels.
			$( '.cecomsmarad-address-book-edit-form' ).not( $form ).slideUp( 100 );
			$( '.cecomsmarad-address-book-edit-btn' ).not( $btn ).each( function () {
				resetEditLabel( $( this ) );
			} );
			$card.find( '.cecomsmarad-address-book-delete-confirm' ).slideUp( 100 );

			// Populate province select on first open.
			var $prov = $form.find( '.cecomsmarad-edit-province' );
			if ( $prov.find( 'option' ).length <= 1 ) {
				populateProvinceSelect( $prov );
				var currentProv = $prov.data( 'current-province' );
				$prov.val( currentProv );
				initSelectWooField( $prov, cfg.i18n.selectProvince || '' );

				var $dist       = $form.find( '.cecomsmarad-edit-district' );
				var currentDist = $dist.data( 'current-district' );
				populateDistrictSelect( $dist, currentProv, currentDist );
				initSelectWooField( $dist, cfg.i18n.selectDistrict || '' );

				// Enhance the country dropdown as a selectWoo widget, matching the
				// province/district fields. The placeholder is taken from its own
				// empty option so no extra localized string is required.
				var $country = $form.find( '.cecomsmarad-edit-country' );
				initSelectWooField( $country, $country.find( 'option[value=""]' ).first().text() || '' );
			}

			// Reshape the fields for the saved country, matching the checkout page.
			applyCountryLocale( $form, $form.find( '.cecomsmarad-edit-country' ).val() || '' );

			$form.slideDown( 150 );
			$btn.text( $btn.data( 'cancel-label' ) );
		} );

		// Country change — reshape the fields for the new country (state morph, labels,
		// required, visibility, order), mirroring the checkout page.
		$( document.body ).on( 'change', '.cecomsmarad-edit-country', function () {
			var $form = $( this ).closest( '.cecomsmarad-address-book-edit-form' );
			applyCountryLocale( $form, $( this ).val() || '' );
		} );

		// Province change — repopulate districts.
		$( document.body ).on( 'change', '.cecomsmarad-edit-province', function () {
			var $form = $( this ).closest( '.cecomsmarad-address-book-edit-form' );
			var $dist = $form.find( '.cecomsmarad-edit-district' );
			populateDistrictSelect( $dist, $( this ).val(), '' );
			initSelectWooField( $dist, cfg.i18n.selectDistrict || '' );
		} );

		// Save edit.
		$( document.body ).on( 'click', '.cecomsmarad-address-book-edit-confirm', function () {
			var $card        = $( this ).closest( '.cecomsmarad-address-book-card' );
			var $form        = $( this ).closest( '.cecomsmarad-address-book-edit-form' );
			var id           = $card.data( 'id' );
			var nickname     = $.trim( $form.find( '.cecomsmarad-edit-nickname' ).val() );
			var country      = $form.find( '.cecomsmarad-edit-country' ).val() || '';
			var isTR         = ( 'TR' === country );
			var provinceCode = $form.find( '.cecomsmarad-edit-province' ).val();
			var districtName = $form.find( '.cecomsmarad-edit-district' ).val();
			var city         = $.trim( $form.find( '.cecomsmarad-edit-city' ).val() || '' );
			var state        = $form.find( '.cecomsmarad-edit-state' ).val() || '';
			var address2     = $form.find( '.cecomsmarad-edit-address2' ).val() || '';

			if ( ! nickname ) {
				return;
			}
			if ( isTR ) {
				if ( ! provinceCode || ! districtName ) {
					return;
				}
			} else {
				if ( ! city ) {
					return;
				}
				// When the country defines states, a state selection is required
				// (matches WooCommerce's default required state behaviour).
				var cntryStates = ( cfg.states && cfg.states[ country ] ) || null;
				if ( cntryStates && ! $.isEmptyObject( cntryStates ) && ! state ) {
					return;
				}
			}

			$.ajax( {
				url:    cfg.ajaxUrl,
				method: 'POST',
				data:   {
					action:        'cecomsmarad_address_book_update',
					nonce:         cfg.nonce,
					address_id:    id,
					nickname:      nickname,
					province_code: provinceCode,
					district_name: districtName,
					city:          city,
					state:         state,
					address_2:     address2,
					neighborhood:  $form.find( '.cecomsmarad-edit-neighborhood' ).val() || '',
					postcode:      $form.find( '.cecomsmarad-edit-postcode' ).val() || '',
					address_1:     $form.find( '.cecomsmarad-edit-address1' ).val() || '',
					first_name:    $form.find( '.cecomsmarad-edit-first-name' ).val() || '',
					last_name:     $form.find( '.cecomsmarad-edit-last-name' ).val() || '',
					company:       $form.find( '.cecomsmarad-edit-company' ).val() || '',
					phone:         $form.find( '.cecomsmarad-edit-phone' ).val() || '',
					email:         $form.find( '.cecomsmarad-edit-email' ).val() || '',
					country:       $form.find( '.cecomsmarad-edit-country' ).val() || '',
				},
				success: function ( response ) {
					if ( response.success ) {
						var d = response.data;

						// Update nickname in card header.
						$card.find( '.cecomsmarad-address-book-nickname' ).text( d.nickname );

						// Rebuild the address body.
						var $body = $card.find( '.cecomsmarad-address-book-card-body' );
						var html  = '';
						var name  = $.trim( ( d.first_name || '' ) + ' ' + ( d.last_name || '' ) );
						if ( name ) {
							html += escapeHtml( name ) + '<br>';
						}
						if ( d.company ) {
							html += escapeHtml( d.company ) + '<br>';
						}
						html += escapeHtml( d.province_name ? d.province_name + ' › ' + d.district_name : d.district_name );
						if ( d.neighborhood ) {
							html += escapeHtml( ' › ' + d.neighborhood );
						}
						html += '<br>';
						if ( d.address_1 ) {
							html += escapeHtml( d.address_1 );
							if ( d.postcode ) {
								html += escapeHtml( ' — ' + d.postcode );
							}
							html += '<br>';
						}
						if ( d.country_name ) {
							html += escapeHtml( d.country_name ) + '<br>';
						}
						$body.html( html );

						$form.slideUp( 100 );
						resetEditLabel( $card.find( '.cecomsmarad-address-book-edit-btn' ) );
					}
				},
			} );
		} );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Init
	// ──────────────────────────────────────────────────────────────────────────

	$( function () {
		initAddressBookSelects();
		initCheckoutLoad();
		initSaveCheckbox();
		initAccountDelete();
		initAccountEdit();
	} );

	$( document.body ).on( 'updated_checkout', function () {
		initAddressBookSelects();
	} );

} )( jQuery );

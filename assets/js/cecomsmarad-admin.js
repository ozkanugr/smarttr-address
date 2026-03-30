/**
 * SmartTRAddress — Admin settings page JavaScript.
 *
 * Client-side tab switching, AJAX save, toast notifications, custom modal
 * dialogs, drag-and-drop reordering (jQuery UI Sortable), expand/collapse
 * cards, chip-based class editors, custom tag input, type badge updates,
 * field search/filter, dirty state tracking, and export summary.
 *
 * @requires jQuery, jQuery UI Sortable
 * @global   cecomsmaradAdmin  Localized from PHP via wp_localize_script.
 * @package  SmartTRAddress
 */

/* global jQuery, cecomsmaradAdmin */

( function ( $ ) {
	'use strict';

	/* ======================================================================
	 * State
	 * ==================================================================== */

	var formDirty = false;

	/* ======================================================================
	 * Utilities
	 * ==================================================================== */

	var icons = {
		success: '<i class="bi bi-check-circle-fill cecomsmarad-toast-icon"></i>',
		error:   '<i class="bi bi-x-circle-fill cecomsmarad-toast-icon"></i>',
		info:    '<i class="bi bi-info-circle-fill cecomsmarad-toast-icon"></i>'
	};

	/* ======================================================================
	 * Tab Switching (client-side)
	 * ==================================================================== */

	function initTabs() {
		var $wrap = $( '.cecomsmarad-admin-wrap' );
		var $tabs = $wrap.find( '.cecomsmarad-tab[role="tab"]' );
		var $panels = $wrap.find( '.cecomsmarad-panel' );

		/* Mark JS as active to hide noscript fallback links. */
		$wrap.addClass( 'cecomsmarad-js-active' );

		/* Restore tab from URL hash. */
		var hash = window.location.hash.replace( '#', '' );
		if ( hash && $( '#cecomsmarad-panel-' + hash ).length ) {
			switchTab( hash );
		}

		$tabs.on( 'click', function () {
			var tab = $( this ).data( 'tab' );
			switchTab( tab );
		} );

		function switchTab( tab ) {
			$tabs.removeClass( 'active' ).attr( 'aria-selected', 'false' );
			$tabs.filter( '[data-tab="' + tab + '"]' ).addClass( 'active' ).attr( 'aria-selected', 'true' );

			$panels.addClass( 'cecomsmarad-panel-hidden' );
			$( '#cecomsmarad-panel-' + tab ).removeClass( 'cecomsmarad-panel-hidden' );

			history.replaceState( null, '', '#' + tab );
		}
	}

	/* ======================================================================
	 * Toast Notification System
	 * ==================================================================== */

	/**
	 * Show a toast notification.
	 *
	 * @param {string} type    'success', 'error', or 'info'.
	 * @param {string} message Toast message.
	 * @param {number} duration Auto-dismiss in ms (0 = manual).
	 */
	function showToast( type, message, duration ) {
		duration = duration || 4000;

		var $container = $( '#cecomsmarad-toast-container' );
		var icon = icons[ type ] || icons.info;

		var $toast = $( '<div class="cecomsmarad-toast cecomsmarad-toast-' + type + '"></div>' )
			.append( icon )
			.append( '<span>' + $( '<span/>' ).text( message ).html() + '</span>' )
			.append( '<button type="button" class="cecomsmarad-toast-close">&times;</button>' );

		$container.append( $toast );

		/* Auto-remove. */
		var timer;
		if ( duration > 0 ) {
			timer = setTimeout( function () {
				removeToast( $toast );
			}, duration );
		}

		/* Manual close. */
		$toast.find( '.cecomsmarad-toast-close' ).on( 'click', function () {
			clearTimeout( timer );
			removeToast( $toast );
		} );
	}

	function removeToast( $toast ) {
		$toast.addClass( 'cecomsmarad-toast-removing' );
		setTimeout( function () {
			$toast.remove();
		}, 150 );
	}

	/* ======================================================================
	 * Custom Modal Dialog
	 * ==================================================================== */

	var modalCallback = null;

	/**
	 * Show a modal dialog.
	 *
	 * @param {Object} options Modal configuration.
	 * @param {string} options.title     Dialog title.
	 * @param {string} options.message   Dialog message.
	 * @param {string} options.confirmText Confirm button text.
	 * @param {string} options.confirmClass CSS class for confirm button.
	 * @param {Function} options.onConfirm Callback on confirm.
	 */
	function showModal( options ) {
		var $overlay = $( '#cecomsmarad-modal-overlay' );

		$( '#cecomsmarad-modal-title' ).text( options.title || '' );
		$( '#cecomsmarad-modal-message' ).text( options.message || '' );
		$( '#cecomsmarad-modal-confirm' ).text( options.confirmText || cecomsmaradAdmin.i18n.confirm );

		if ( options.confirmClass ) {
			$( '#cecomsmarad-modal-confirm' )
				.removeClass( 'cecomsmarad-btn-danger cecomsmarad-btn-primary' )
				.addClass( options.confirmClass );
		}

		modalCallback = options.onConfirm || null;

		$overlay.css( 'display', 'flex' ).attr( 'aria-hidden', 'false' );

		/* Focus cancel button for safety. */
		$( '#cecomsmarad-modal-cancel' ).trigger( 'focus' );
	}

	function hideModal() {
		$( '#cecomsmarad-modal-overlay' ).hide().attr( 'aria-hidden', 'true' );
		modalCallback = null;
	}

	$( document ).on( 'click', '#cecomsmarad-modal-cancel', function () {
		hideModal();
	} );

	$( document ).on( 'click', '#cecomsmarad-modal-confirm', function () {
		if ( typeof modalCallback === 'function' ) {
			modalCallback();
		}
		hideModal();
	} );

	$( document ).on( 'click', '#cecomsmarad-modal-overlay', function ( e ) {
		if ( e.target === this ) {
			hideModal();
		}
	} );

	$( document ).on( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && $( '#cecomsmarad-modal-overlay' ).is( ':visible' ) ) {
			hideModal();
		}
	} );

	/* ======================================================================
	 * AJAX Form Save
	 * ==================================================================== */

	function initAjaxSave() {
		$( '.cecomsmarad-ajax-form' ).on( 'submit', function ( e ) {
			e.preventDefault();

			var $form   = $( this );
			var action  = $form.data( 'ajax-action' );

			if ( 'cecomsmarad_reimport_data' === action ) {
				return; // handled by initReimport()
			}

			var $btn    = $form.find( '.cecomsmarad-btn-primary' );
			var data    = $form.serialize();

			data += '&action=' + encodeURIComponent( action );
			data += '&_cecomsmarad_nonce=' + encodeURIComponent( cecomsmaradAdmin.nonce );

			$btn.addClass( 'cecomsmarad-btn-loading' );

			$.post( cecomsmaradAdmin.ajaxUrl, data )
				.done( function ( response ) {
					if ( response.success ) {
						showToast( 'success', response.data.message );
						formDirty = false;
						$( '.cecomsmarad-dirty-indicator' ).hide();

						if ( response.data.reload ) {
							setTimeout( function () {
								window.location.reload();
							}, 800 );
						}
					} else {
						showToast( 'error', ( response.data && response.data.message ) || cecomsmaradAdmin.i18n.error );
					}
				} )
				.fail( function ( jqXHR ) {
					var msg = ( jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message )
						? jqXHR.responseJSON.data.message
						: cecomsmaradAdmin.i18n.error;
					showToast( 'error', msg );
				} )
				.always( function () {
					$btn.removeClass( 'cecomsmarad-btn-loading' );
				} );
		} );
	}

	/* ======================================================================
	 * Sortable — drag-and-drop priority reordering
	 * ==================================================================== */

	/**
	 * Recalculate priority hidden inputs after a sort.
	 *
	 * Preserves the existing priority values but re-assigns them in the new
	 * visual order so that each section's priority sequence stays independent
	 * of the other section's values.
	 *
	 * @param {jQuery} $container The `.cecomsmarad-sortable-list` that was sorted.
	 */
	function recalcPriorities( $container ) {
		/* Collect the current priority values before the re-assignment. */
		var priorities = [];
		$container.children( '.cecomsmarad-field-card' ).each( function () {
			var val = parseInt( $( this ).find( '.cecomsmarad-priority-input' ).val(), 10 );
			priorities.push( isNaN( val ) ? 10 : val );
		} );

		/* Sort the collected values so order reflects position. */
		priorities.sort( function ( a, b ) { return a - b; } );

		/* Write sorted values back in visual order. */
		$container.children( '.cecomsmarad-field-card' ).each( function ( idx ) {
			$( this ).find( '.cecomsmarad-priority-input' ).val( priorities[ idx ] );
		} );
	}

	/**
	 * Sortable configuration (shared between init and re-init).
	 */
	var sortableOpts = {
		handle:      '.cecomsmarad-drag-handle',
		items:       '> .cecomsmarad-field-card',
		axis:        'y',
		cursor:      'grabbing',
		tolerance:   'pointer',
		placeholder: 'cecomsmarad-field-card ui-sortable-placeholder',
		update:      function () {
			recalcPriorities( $( this ) );
			markDirty();
		}
	};

	/**
	 * Initialise jQuery UI Sortable on all sortable lists.
	 */
	function initSortable() {
		if ( ! $.fn.sortable ) {
			return;
		}
		$( '.cecomsmarad-sortable-list' ).sortable( sortableOpts );
	}

	/**
	 * Re-initialise Sortable on a specific list so new cards
	 * are fully registered for drag-and-drop.
	 *
	 * @param {jQuery} $list The `.cecomsmarad-sortable-list` element.
	 */
	function reinitSortable( $list ) {
		if ( ! $.fn.sortable ) {
			return;
		}
		try {
			$list.sortable( 'destroy' );
		} catch ( e ) { /* not yet initialised — ignore */ }
		$list.sortable( sortableOpts );
	}

	/* ======================================================================
	 * Expand / Collapse field cards
	 * ==================================================================== */

	/**
	 * Toggle expanded state of a field card.
	 *
	 * @param {jQuery} $card The `.cecomsmarad-field-card` element.
	 */
	function toggleCard( $card ) {
		var isExpanded = $card.hasClass( 'expanded' );
		$card.toggleClass( 'expanded' );
		$card.find( '.cecomsmarad-expand-btn' ).attr( 'aria-expanded', ! isExpanded );
	}

	$( document ).on( 'click', '.cecomsmarad-expand-btn', function ( e ) {
		e.stopPropagation();
		toggleCard( $( this ).closest( '.cecomsmarad-field-card' ) );
	} );

	$( document ).on( 'click', '.cecomsmarad-fc-header', function ( e ) {
		/* Ignore clicks on the drag handle — let sortable handle those. */
		if ( $( e.target ).closest( '.cecomsmarad-drag-handle' ).length ) {
			return;
		}
		toggleCard( $( this ).closest( '.cecomsmarad-field-card' ) );
	} );

	/* ======================================================================
	 * Bulk Expand / Collapse
	 * ==================================================================== */

	function initBulkCardToggle() {
		$( '#cecomsmarad-expand-all' ).on( 'click', function () {
			$( '.cecomsmarad-field-card:visible' ).each( function () {
				if ( ! $( this ).hasClass( 'expanded' ) ) {
					$( this ).addClass( 'expanded' );
					$( this ).find( '.cecomsmarad-expand-btn' ).attr( 'aria-expanded', 'true' );
				}
			} );
		} );

		$( '#cecomsmarad-collapse-all' ).on( 'click', function () {
			$( '.cecomsmarad-field-card' ).each( function () {
				$( this ).removeClass( 'expanded' );
				$( this ).find( '.cecomsmarad-expand-btn' ).attr( 'aria-expanded', 'false' );
			} );
		} );
	}

	/* ======================================================================
	 * Field Search
	 * ==================================================================== */

	function initFieldSearch() {
		$( '#cecomsmarad-field-search' ).on( 'input', function () {
			var query = $.trim( $( this ).val() ).toLowerCase();
			var visibleCount = 0;

			$( '.cecomsmarad-field-card' ).each( function () {
				var $card = $( this );
				var name  = ( $card.find( '.cecomsmarad-fc-name' ).text() || '' ).toLowerCase();
				var key   = ( $card.data( 'key' ) || '' ).toLowerCase();

				if ( ! query || name.indexOf( query ) !== -1 || key.indexOf( query ) !== -1 ) {
					$card.removeClass( 'cecomsmarad-card-hidden' );
					visibleCount++;
				} else {
					$card.addClass( 'cecomsmarad-card-hidden' );
				}
			} );

			/* Also apply current filter. */
			applyCurrentFilter();

			/* Show/hide no-results. */
			updateNoResults();
		} );
	}

	/* ======================================================================
	 * Field Filter (Billing / Shipping)
	 * ==================================================================== */

	function initFieldFilter() {
		$( '.cecomsmarad-filter-btn' ).on( 'click', function () {
			$( '.cecomsmarad-filter-btn' ).removeClass( 'active' );
			$( this ).addClass( 'active' );
			applyCurrentFilter();
			updateNoResults();
		} );
	}

	function applyCurrentFilter() {
		var filter = $( '.cecomsmarad-filter-btn.active' ).data( 'filter' ) || 'all';

		$( '.cecomsmarad-section' ).each( function () {
			var sectionType = $( this ).data( 'section-type' );

			if ( 'all' === filter || sectionType === filter ) {
				$( this ).show();
			} else {
				$( this ).hide();
			}
		} );
	}

	function updateNoResults() {
		var anyVisible = false;

		$( '.cecomsmarad-section:visible' ).each( function () {
			if ( $( this ).find( '.cecomsmarad-field-card:not(.cecomsmarad-card-hidden)' ).length > 0 ) {
				anyVisible = true;
				return false;
			}
		} );

		if ( anyVisible ) {
			$( '#cecomsmarad-no-results' ).hide();
		} else {
			$( '#cecomsmarad-no-results' ).show();
		}
	}

	/* ======================================================================
	 * Chip-based class editors
	 * ==================================================================== */

	/**
	 * Sync the hidden input value from active chips + tags.
	 *
	 * @param {jQuery} $editor The `.cecomsmarad-class-editor` container.
	 */
	function syncClassValue( $editor ) {
		var parts = [];

		$editor.find( '.cecomsmarad-chip.active' ).each( function () {
			parts.push( $( this ).data( 'class' ) );
		} );

		$editor.find( '.cecomsmarad-tag' ).each( function () {
			parts.push( $( this ).data( 'class' ) );
		} );

		$editor.find( '.cecomsmarad-class-value' ).val( parts.join( ' ' ) );
	}

	/**
	 * Layout chip toggle (mutually exclusive within their group).
	 */
	$( document ).on( 'click', '.cecomsmarad-layout-chip', function () {
		var $chip   = $( this );
		var $editor = $chip.closest( '.cecomsmarad-class-editor' );
		var wasActive = $chip.hasClass( 'active' );

		$editor.find( '.cecomsmarad-layout-chip' ).removeClass( 'active' );

		if ( ! wasActive ) {
			$chip.addClass( 'active' );
		}

		syncClassValue( $editor );
		markDirty();
	} );

	/**
	 * Regular chip toggle (non-layout, independent on/off).
	 */
	$( document ).on( 'click', '.cecomsmarad-chip:not(.cecomsmarad-layout-chip)', function () {
		$( this ).toggleClass( 'active' );
		syncClassValue( $( this ).closest( '.cecomsmarad-class-editor' ) );
		markDirty();
	} );

	/* ======================================================================
	 * Custom tag input
	 * ==================================================================== */

	function isValidClassName( name ) {
		return /^[a-zA-Z0-9\-_]+$/.test( name );
	}

	function createTag( name ) {
		return $( '<span class="cecomsmarad-tag" />' )
			.attr( 'data-class', name )
			.text( name )
			.append( '<button type="button" class="cecomsmarad-tag-remove">&times;</button>' );
	}

	$( document ).on( 'keydown', '.cecomsmarad-tag-input', function ( e ) {
		if ( 'Enter' !== e.key ) {
			return;
		}

		e.preventDefault();

		var $input  = $( this );
		var $editor = $input.closest( '.cecomsmarad-class-editor' );
		var raw     = $.trim( $input.val() );

		if ( ! raw || ! isValidClassName( raw ) ) {
			$input.val( '' );
			return;
		}

		var exists = false;
		$editor.find( '.cecomsmarad-chip, .cecomsmarad-tag' ).each( function () {
			if ( $( this ).data( 'class' ) === raw ) {
				exists = true;
				return false;
			}
		} );

		if ( exists ) {
			$input.val( '' );
			return;
		}

		$input.before( createTag( raw ) );
		$input.val( '' );

		syncClassValue( $editor );
		markDirty();
	} );

	$( document ).on( 'click', '.cecomsmarad-tag-remove', function ( e ) {
		e.preventDefault();
		var $editor = $( this ).closest( '.cecomsmarad-class-editor' );
		$( this ).closest( '.cecomsmarad-tag' ).remove();
		syncClassValue( $editor );
		markDirty();
	} );

	/* ======================================================================
	 * Type select — options textarea & badge update
	 * ==================================================================== */

	var noPlaceholderTypes = [ 'radio', 'checkbox', 'file', 'date', 'datetime-local' ];

	function handleTypeChange( $select ) {
		var val   = $select.val();
		var $card = $select.closest( '.cecomsmarad-field-card' );

		var $optionsWrap = $card.find( '.cecomsmarad-options-wrap' );
		if ( $optionsWrap.length ) {
			if ( 'select' === val || 'radio' === val || 'checkbox' === val ) {
				$optionsWrap.removeClass( 'hidden' );
			} else {
				$optionsWrap.addClass( 'hidden' );
			}
		}

		var $extensionsWrap = $card.find( '.cecomsmarad-extensions-wrap' );
		if ( $extensionsWrap.length ) {
			if ( 'file' === val ) {
				$extensionsWrap.removeClass( 'hidden' );
			} else {
				$extensionsWrap.addClass( 'hidden' );
			}
		}

		var $multipleWrap = $card.find( '.cecomsmarad-multiple-wrap' );
		if ( $multipleWrap.length ) {
			if ( 'checkbox' === val ) {
				$multipleWrap.removeClass( 'hidden' );
			} else {
				$multipleWrap.addClass( 'hidden' );
			}
		}

		/* Placeholder: disable for types that don't support it. */
		var $placeholderWrap = $card.find( '.cecomsmarad-placeholder-wrap' );
		if ( $placeholderWrap.length ) {
			var noPlaceholder = noPlaceholderTypes.indexOf( val ) !== -1;
			$placeholderWrap.find( 'input' ).prop( 'disabled', noPlaceholder );
		}

		/* Clear: available for select type and for built-in select-like fields. */
		var $clearWrap = $card.find( '.cecomsmarad-clear-wrap' );
		if ( $clearWrap.length ) {
			var clearKeySuffixes = [ '_country', '_address_2', '_city', '_state' ];
			var fieldKey         = String( $card.data( 'key' ) || '' );
			var keyAllowsClear   = clearKeySuffixes.some( function ( s ) {
				return fieldKey.slice( -s.length ) === s;
			} );
			if ( 'select' === val || keyAllowsClear ) {
				$clearWrap.removeClass( 'hidden' );
			} else {
				$clearWrap.addClass( 'hidden' );
			}
		}

		var $badge = $card.find( '.cecomsmarad-type-badge' );
		if ( $badge.length ) {
			$badge[ 0 ].className = $badge[ 0 ].className.replace( /cecomsmarad-badge-\S+/g, '' );
			$badge.addClass( 'cecomsmarad-badge-' + val ).text( val );
		}
	}

	$( document ).on( 'change', '.cecomsmarad-type-select', function () {
		handleTypeChange( $( this ) );
		markDirty();
	} );

	/* ======================================================================
	 * Visibility Select — toggle card appearance
	 * ==================================================================== */

	$( document ).on( 'change', '.cecomsmarad-visibility-select', function () {
		var $select = $( this );
		var $card   = $select.closest( '.cecomsmarad-field-card' );
		var val     = $select.val();

		$card.removeClass( 'cecomsmarad-card-vis-hidden cecomsmarad-card-unset' );

		if ( val === 'hidden' ) {
			$card.addClass( 'cecomsmarad-card-vis-hidden' );
		} else if ( val === 'unset' ) {
			$card.addClass( 'cecomsmarad-card-unset' );
		}

		markDirty();
	} );

	/* ======================================================================
	 * Confirmation dialogs (modal-based)
	 * ==================================================================== */

	function initReimport() {
		$( document ).on( 'click', '.cecomsmarad-reimport-btn', function ( e ) {
			e.preventDefault();

			var $btn      = $( this );
			if ( $btn.is( ':disabled' ) ) { return; }

			var $progress = $( '#cecomsmarad-reimport-progress' );

			showModal( {
				title:        cecomsmaradAdmin.i18n.reimportTitle,
				message:      cecomsmaradAdmin.i18n.confirmReimport,
				confirmText:  cecomsmaradAdmin.i18n.reimportTitle,
				confirmClass: 'cecomsmarad-btn-danger',
				onConfirm:    function () {
					$btn.addClass( 'cecomsmarad-btn-loading' ).prop( 'disabled', true );
					$progress.show();

					$.post( cecomsmaradAdmin.ajaxUrl, {
						action:         'cecomsmarad_reimport_data',
						_cecomsmarad_nonce: cecomsmaradAdmin.nonce
					} )
					.done( function ( response ) {
						$btn.removeClass( 'cecomsmarad-btn-loading' ).prop( 'disabled', false );
						$progress.hide();

						if ( response.success ) {
							showToast( 'success', response.data.message );
							if ( response.data.reload ) {
								setTimeout( function () {
									window.location.reload();
								}, 1500 );
							}
						} else {
							showToast( 'error', ( response.data && response.data.message ) || cecomsmaradAdmin.i18n.error );
						}
					} )
					.fail( function ( jqXHR ) {
						$btn.removeClass( 'cecomsmarad-btn-loading' ).prop( 'disabled', false );
						$progress.hide();

						var msg = ( jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message )
							? jqXHR.responseJSON.data.message
							: cecomsmaradAdmin.i18n.error;
						showToast( 'error', msg );
					} );
				}
			} );
		} );
	}

	$( document ).on( 'click', '.cecomsmarad-reset-btn', function ( e ) {
		e.preventDefault();

		showModal( {
			title:        cecomsmaradAdmin.i18n.resetTitle,
			message:      cecomsmaradAdmin.i18n.confirmReset,
			confirmText:  cecomsmaradAdmin.i18n.resetTitle,
			confirmClass: 'cecomsmarad-btn-danger',
			onConfirm:    function () {
				var data = 'action=cecomsmarad_reset_fields&_cecomsmarad_nonce=' + encodeURIComponent( cecomsmaradAdmin.nonce );

				var $btn = $( '.cecomsmarad-reset-btn' );
				$btn.addClass( 'cecomsmarad-btn-loading' );

				$.post( cecomsmaradAdmin.ajaxUrl, data )
					.done( function ( response ) {
						if ( response.success ) {
							showToast( 'success', response.data.message );
							formDirty = false;
							setTimeout( function () {
								window.location.reload();
							}, 800 );
						} else {
							showToast( 'error', ( response.data && response.data.message ) || cecomsmaradAdmin.i18n.error );
						}
					} )
					.fail( function ( jqXHR ) {
						var msg = ( jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message )
							? jqXHR.responseJSON.data.message
							: cecomsmaradAdmin.i18n.error;
						showToast( 'error', msg );
					} )
					.always( function () {
						$btn.removeClass( 'cecomsmarad-btn-loading' );
					} );
			}
		} );
	} );

	/* ======================================================================
	 * Dirty State Tracking
	 * ==================================================================== */

	function markDirty() {
		if ( ! formDirty ) {
			formDirty = true;
			$( '.cecomsmarad-dirty-indicator' ).show();
		}
	}

	function initDirtyTracking() {
		$( window ).on( 'beforeunload', function () {
			if ( formDirty ) {
				return cecomsmaradAdmin.i18n.unsavedChanges;
			}
		} );
	}

	/* ======================================================================
	 * Export Summary
	 * ==================================================================== */

	function initExportSummary() {
		$( '#cecomsmarad-export-summary' ).on( 'click', function () {
			var lines = [];
			lines.push( 'SmartTRAddress — ' + cecomsmaradAdmin.i18n.exportedAt + ': ' + new Date().toLocaleString() );
			lines.push( '============================================' );
			lines.push( '' );

			/* Collect stat cards from data panel. */
			$( '#cecomsmarad-panel-data .cecomsmarad-data-stat' ).each( function () {
				var val   = $.trim( $( this ).find( '.cecomsmarad-stat-value' ).text() );
				var label = $.trim( $( this ).find( '.cecomsmarad-stat-label' ).text() );
				lines.push( label + ': ' + val );
			} );

			lines.push( '' );

			/* Collect detail items. */
			$( '#cecomsmarad-panel-data .cecomsmarad-detail-item' ).each( function () {
				var dt = $.trim( $( this ).find( 'dt' ).text() );
				var dd = $.trim( $( this ).find( 'dd' ).text() );
				lines.push( dt + ': ' + dd );
			} );

			lines.push( '' );

			/* Collect health items. */
			$( '#cecomsmarad-panel-data .cecomsmarad-health-item' ).each( function () {
				var ok   = $( this ).hasClass( 'cecomsmarad-health-ok' );
				var name = $.trim( $( this ).find( 'code' ).text() );
				lines.push( ( ok ? '[OK]' : '[ERR]' ) + ' ' + name );
			} );

			/* General tab stats. */
			lines.push( '' );
			lines.push( '--- General ---' );
			$( '#cecomsmarad-panel-general .cecomsmarad-stat-card' ).each( function () {
				var val   = $.trim( $( this ).find( '.cecomsmarad-stat-value' ).text() );
				var label = $.trim( $( this ).find( '.cecomsmarad-stat-label' ).text() );
				lines.push( label + ': ' + val );
			} );

			var blob = new Blob( [ lines.join( '\n' ) ], { type: 'text/plain' } );
			var url  = URL.createObjectURL( blob );
			var a    = document.createElement( 'a' );
			a.href     = url;
			a.download = 'cecomsmarad-summary.txt';
			document.body.appendChild( a );
			a.click();
			document.body.removeChild( a );
			URL.revokeObjectURL( url );

			showToast( 'info', 'cecomsmarad-summary.txt' );
		} );
	}

	/* ======================================================================
	 * Custom Field Modal
	 * ==================================================================== */

	function initCustomFieldModal() {
		var $overlay = $( '#cecomsmarad-create-field-overlay' );

		/* Open modal. */
		$( '#cecomsmarad-add-custom-field' ).on( 'click', function () {
			/* Reset form. */
			$overlay.find( 'input[name="cecomsmarad_cf_address_type"][value="billing"]' ).prop( 'checked', true );
			$( '#cecomsmarad-cf-slug' ).val( '' );
			$( '#cecomsmarad-cf-label' ).val( '' );
			$( '#cecomsmarad-cf-type' ).val( 'text' );
			$( '.cecomsmarad-cf-key-preview' ).text( 'billing_cecomsmarad_' );
			$( '.cecomsmarad-cf-slug-error' ).hide();

			$overlay.css( 'display', 'flex' ).attr( 'aria-hidden', 'false' );
			$( '#cecomsmarad-cf-slug' ).trigger( 'focus' );
		} );

		/* Close modal. */
		$( '#cecomsmarad-create-field-cancel' ).on( 'click', function () {
			$overlay.hide().attr( 'aria-hidden', 'true' );
		} );

		$overlay.on( 'click', function ( e ) {
			if ( e.target === this ) {
				$overlay.hide().attr( 'aria-hidden', 'true' );
			}
		} );

		/* Address type radio — update key preview. */
		$overlay.on( 'change', 'input[name="cecomsmarad_cf_address_type"]', function () {
			var addrType = $( this ).val();
			var slug = $.trim( $( '#cecomsmarad-cf-slug' ).val() );
			$( '.cecomsmarad-cf-key-preview' ).text( addrType + '_cecomsmarad_' + slug );
		} );

		/* Slug input — live preview + validation. */
		$( '#cecomsmarad-cf-slug' ).on( 'input', function () {
			var slug = $.trim( $( this ).val() ).toLowerCase().replace( /[^a-z0-9_]/g, '' );
			$( this ).val( slug );
			var addrType = $overlay.find( 'input[name="cecomsmarad_cf_address_type"]:checked' ).val();
			$( '.cecomsmarad-cf-key-preview' ).text( addrType + '_cecomsmarad_' + slug );
			$( '.cecomsmarad-cf-slug-error' ).hide();
		} );

		/* Submit. */
		$( '#cecomsmarad-create-field-submit' ).on( 'click', function () {
			var $btn      = $( this );
			var addrType  = $overlay.find( 'input[name="cecomsmarad_cf_address_type"]:checked' ).val();
			var slug      = $.trim( $( '#cecomsmarad-cf-slug' ).val() );
			var label     = $.trim( $( '#cecomsmarad-cf-label' ).val() );
			var fieldType = $( '#cecomsmarad-cf-type' ).val();
			var $error    = $( '.cecomsmarad-cf-slug-error' );

			/* Validate slug. */
			if ( ! slug || ! /^[a-z0-9_]+$/.test( slug ) || slug.length > 30 ) {
				$error.text( cecomsmaradAdmin.i18n.invalidSlug ).show();
				$( '#cecomsmarad-cf-slug' ).trigger( 'focus' );
				return;
			}

			if ( ! label ) {
				$( '#cecomsmarad-cf-label' ).trigger( 'focus' );
				return;
			}

			$btn.addClass( 'cecomsmarad-btn-loading' );

			$.post( cecomsmaradAdmin.ajaxUrl, {
				action:       'cecomsmarad_add_custom_field',
				_cecomsmarad_nonce: cecomsmaradAdmin.nonce,
				address_type: addrType,
				slug:         slug,
				label:        label,
				type:         fieldType
			} )
			.done( function ( response ) {
				if ( response.success ) {
					/* Insert card HTML into the unified sortable list at the correct priority position. */
					var $sortableList = $( '.cecomsmarad-sortable-list[data-type="' + addrType + '"]' );
					var $newCard      = $( response.data.card_html );
					var newPriority   = parseInt( $newCard.find( '.cecomsmarad-priority-input' ).val(), 10 ) || 200;
					var inserted      = false;

					$sortableList.children( '.cecomsmarad-field-card' ).each( function () {
						var existingPriority = parseInt( $( this ).find( '.cecomsmarad-priority-input' ).val(), 10 ) || 100;
						if ( existingPriority > newPriority ) {
							$( this ).before( $newCard );
							inserted = true;
							return false;
						}
					} );

					if ( ! inserted ) {
						$sortableList.append( $newCard );
					}

					/* Re-init sortable so the new card is fully draggable. */
					reinitSortable( $sortableList );

					/* Recalculate priorities so the new card gets a consistent value. */
					recalcPriorities( $sortableList );

					/* Init type select on new card. */
					$newCard.find( '.cecomsmarad-type-select' ).each( function () {
						handleTypeChange( $( this ) );
					} );

					/* Update filter counts. */
					updateFilterCounts();

					/* Close modal, show toast. */
					$overlay.hide().attr( 'aria-hidden', 'true' );
					showToast( 'success', response.data.message );
					markDirty();
				} else {
					$error.text( ( response.data && response.data.message ) || cecomsmaradAdmin.i18n.error ).show();
				}
			} )
			.fail( function ( jqXHR ) {
				var msg = ( jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message )
					? jqXHR.responseJSON.data.message
					: cecomsmaradAdmin.i18n.error;
				showToast( 'error', msg );
			} )
			.always( function () {
				$btn.removeClass( 'cecomsmarad-btn-loading' );
			} );
		} );
	}

	/* ======================================================================
	 * Custom Field Delete
	 * ==================================================================== */

	function initCustomFieldDelete() {
		$( document ).on( 'click', '.cecomsmarad-delete-field-btn', function ( e ) {
			e.stopPropagation();

			var $btn = $( this );
			var fieldKey = $btn.data( 'key' );
			var $card = $btn.closest( '.cecomsmarad-field-card' );

			showModal( {
				title:        cecomsmaradAdmin.i18n.deleteField,
				message:      cecomsmaradAdmin.i18n.confirmDelete,
				confirmText:  cecomsmaradAdmin.i18n.deleteField,
				confirmClass: 'cecomsmarad-btn-danger',
				onConfirm:    function () {
					$.post( cecomsmaradAdmin.ajaxUrl, {
						action:         'cecomsmarad_delete_custom_field',
						_cecomsmarad_nonce: cecomsmaradAdmin.nonce,
						field_key:      fieldKey
					} )
					.done( function ( response ) {
						if ( response.success ) {
							$card.slideUp( 200, function () {
								$card.remove();
								updateFilterCounts();
							} );
							showToast( 'success', response.data.message );
						} else {
							showToast( 'error', ( response.data && response.data.message ) || cecomsmaradAdmin.i18n.error );
						}
					} )
					.fail( function ( jqXHR ) {
						var msg = ( jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message )
							? jqXHR.responseJSON.data.message
							: cecomsmaradAdmin.i18n.error;
						showToast( 'error', msg );
					} );
				}
			} );
		} );
	}

	/* ======================================================================
	 * Filter Count Update Utility
	 * ==================================================================== */

	function updateFilterCounts() {
		var allCount     = 0;
		var billingCount = 0;
		var shippingCount = 0;

		$( '.cecomsmarad-field-card' ).each( function () {
			var key = $( this ).data( 'key' ) || '';
			allCount++;
			if ( key.indexOf( 'billing' ) === 0 ) {
				billingCount++;
			} else if ( key.indexOf( 'shipping' ) === 0 ) {
				shippingCount++;
			}
		} );

		$( '.cecomsmarad-filter-btn[data-filter="all"] .cecomsmarad-filter-count' ).text( allCount );
		$( '.cecomsmarad-filter-btn[data-filter="billing"] .cecomsmarad-filter-count' ).text( billingCount );
		$( '.cecomsmarad-filter-btn[data-filter="shipping"] .cecomsmarad-filter-count' ).text( shippingCount );

		/* Update section header field counts. */
		$( '.cecomsmarad-section' ).each( function () {
			var count = $( this ).find( '.cecomsmarad-field-card' ).length;
			$( this ).find( '.cecomsmarad-field-count' ).text( count + ' ' + cecomsmaradAdmin.i18n.fieldWord );
		} );
	}

	/* ======================================================================
	 * Shortcode Copy Button
	 * ==================================================================== */

	function initShortcodeCopy() {
		$( document ).on( 'click', '.cecomsmarad-shortcode-copy', function ( e ) {
			e.preventDefault();
			e.stopPropagation();

			var $btn      = $( this );
			var shortcode = $btn.data( 'shortcode' );
			var $label    = $btn.find( '.cecomsmarad-copy-label' );
			var original  = $label.text();
			var copied    = ( cecomsmaradAdmin && cecomsmaradAdmin.i18n && cecomsmaradAdmin.i18n.shortcodeCopied )
				? cecomsmaradAdmin.i18n.shortcodeCopied
				: 'Shortcode copied!';

			function markCopied() {
				$btn.addClass( 'cecomsmarad-copy-success' );
				$label.text( copied );
				setTimeout( function () {
					$btn.removeClass( 'cecomsmarad-copy-success' );
					$label.text( original );
				}, 2000 );
			}

			// Primary: execCommand runs synchronously within the user gesture — works on
			// HTTP, all browsers, iframe contexts. Async Clipboard API is only used as a
			// secondary path because its rejection callback may fire after the gesture
			// window expires (Firefox/Safari), making execCommand fail there too.
			var el = document.createElement( 'input' );
			el.type          = 'text';
			el.value         = shortcode;
			el.readOnly      = true;
			el.style.cssText = 'position:fixed;top:0;left:-9999px;font-size:16px;';
			document.body.appendChild( el );
			el.focus();
			el.select();
			el.setSelectionRange( 0, 99999 );

			var didCopy = false;
			try {
				didCopy = document.execCommand( 'copy' );
			} catch ( err ) { /* silent */ }
			document.body.removeChild( el );

			if ( didCopy ) {
				markCopied();
				return;
			}

			// Secondary: async Clipboard API for browsers that have removed execCommand.
			if ( window.navigator && window.navigator.clipboard && window.navigator.clipboard.writeText ) {
				window.navigator.clipboard.writeText( shortcode ).then( markCopied ).catch( function () {} );
			}
		} );
	}

	/* ======================================================================
	 * Initialisation
	 * ==================================================================== */

	$( function () {
		var inits = [
			initTabs,
			initAjaxSave,
			initReimport,
			initSortable,
			initBulkCardToggle,
			initFieldSearch,
			initFieldFilter,
			initDirtyTracking,
			initExportSummary,
			initCustomFieldModal,
			initCustomFieldDelete,
			initShortcodeCopy
		];

		$.each( inits, function ( _, fn ) {
			try {
				fn();
			} catch ( e ) {
				/* eslint-disable-next-line no-console */
				if ( window.console && console.error ) {
					console.error( 'SmartTR init error:', e );
				}
			}
		} );

		/* Init type selects — set correct options textarea visibility. */
		$( '.cecomsmarad-type-select' ).each( function () {
			handleTypeChange( $( this ) );
		} );
	} );

} )( jQuery );

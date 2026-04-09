/**
 * SmartTR Address — Admin settings page JavaScript.
 *
 * Tab navigation (vanilla JS, framework pattern) + AJAX form submission
 * + Bootstrap Modal/Toast + copy-to-clipboard + dirty-state tracking.
 *
 * @requires jQuery (WP admin), Bootstrap 5 (bundled)
 * @global   cecomsmaradAdmin  Localized from PHP via wp_localize_script.
 * @package  CecomsmaradAddress
 */

/* global jQuery, cecomsmaradAdmin, bootstrap */

/* ======================================================================
 * Part 0 — Confine Bootstrap offcanvas backdrop to #wpcontent
 *
 * By default Bootstrap appends `.offcanvas-backdrop` to <body> with
 * position:fixed, so the dim overlay covers the entire WP admin —
 * including the admin menu. Re-parenting the backdrop into #wpcontent
 * on show keeps it scoped to the settings area (matched by CSS rule
 * `#wpcontent > .offcanvas-backdrop`). Click-outside-to-close still
 * works because the backdrop element is unchanged — only its parent.
 * ==================================================================== */

( function () {
	'use strict';

	var sidebar   = document.getElementById( 'sidebarTabs' );
	var wpcontent = document.getElementById( 'wpcontent' );
	if ( ! sidebar || ! wpcontent ) {
		return;
	}

	sidebar.addEventListener( 'show.bs.offcanvas', function () {
		// Defer until Bootstrap has appended the backdrop to <body>.
		requestAnimationFrame( function () {
			var backdrop = document.querySelector( 'body > .offcanvas-backdrop' );
			if ( backdrop ) {
				wpcontent.appendChild( backdrop );
			}
		} );
	} );
}() );

/* ======================================================================
 * Part 1 — Vanilla JS Tab Navigation (framework standard)
 * ==================================================================== */

( function () {
	'use strict';

	var tabs   = document.querySelectorAll( '.admin-tab[role="tab"]' );
	var panels = document.querySelectorAll( '.tab-panel' );

	tabs.forEach( function ( tab ) {
		tab.addEventListener( 'click', function () {
			if ( tab.classList.contains( 'disabled' ) ) {
				return;
			}

			// Deactivate all tabs and hide all panels.
			tabs.forEach( function ( t ) {
				t.classList.remove( 'active' );
				t.setAttribute( 'aria-selected', 'false' );
			} );
			panels.forEach( function ( p ) {
				p.classList.add( 'd-none' );
			} );

			// Activate the clicked tab.
			tab.classList.add( 'active' );
			tab.setAttribute( 'aria-selected', 'true' );

			// Show target panel with re-triggered fade-in animation.
			var target = document.getElementById( tab.dataset.panel );
			if ( target ) {
				target.classList.remove( 'd-none' );
				target.style.animation = 'none';
				void target.offsetHeight; // reflow to re-trigger keyframe
				target.style.animation  = '';
			}

			// Close offcanvas sidebar on mobile after tab selection.
			var oc = document.getElementById( 'sidebarTabs' );
			if ( oc ) {
				var offcanvas = bootstrap.Offcanvas.getInstance( oc );
				if ( offcanvas ) {
					offcanvas.hide();
				}
			}
		} );
	} );
}() );

/* ======================================================================
 * Part 2 — jQuery: AJAX, Bootstrap Modal/Toast, Clipboard, Dirty State
 * ==================================================================== */

( function ( $ ) {
	'use strict';

	if ( typeof cecomsmaradAdmin === 'undefined' ) {
		return;
	}

	var cfg       = cecomsmaradAdmin;
	var formDirty = false;

	/* ── Toast helper ──────────────────────────────────── */

	var TOAST_ICONS = {
		success: 'bi-check-circle-fill text-success',
		danger:  'bi-x-circle-fill text-danger',
		info:    'bi-info-circle-fill text-info'
	};

	function showToast( type, message ) {
		var toastEl   = document.getElementById( 'cecomsmaradToast' );
		var iconEl    = document.getElementById( 'cecomsmaradToastIcon' );
		var bodyEl    = document.getElementById( 'cecomsmaradToastBody' );

		if ( ! toastEl || ! iconEl || ! bodyEl ) {
			return;
		}

		iconEl.className  = 'bi me-2 ' + ( TOAST_ICONS[ type ] || TOAST_ICONS.info );
		bodyEl.textContent = message;

		new bootstrap.Toast( toastEl, { delay: 4000 } ).show();
	}

	/* ── Confirmation modal (returns Promise) ──────────── */

	function showModal( title, message ) {
		return new Promise( function ( resolve ) {
			var modalEl    = document.getElementById( 'cecomsmaradModal' );
			var titleEl    = document.getElementById( 'cecomsmaradModalTitle' );
			var messageEl  = document.getElementById( 'cecomsmaradModalMessage' );
			var confirmBtn = document.getElementById( 'cecomsmaradModalConfirm' );

			if ( ! modalEl || ! confirmBtn ) {
				resolve( false );
				return;
			}

			titleEl.textContent   = title;
			messageEl.textContent = message;

			var modal = new bootstrap.Modal( modalEl );
			modal.show();

			function onConfirm() {
				cleanup();
				modal.hide();
				resolve( true );
			}

			function onDismiss() {
				cleanup();
				resolve( false );
			}

			function cleanup() {
				confirmBtn.removeEventListener( 'click', onConfirm );
				modalEl.removeEventListener( 'hidden.bs.modal', onDismiss );
			}

			confirmBtn.addEventListener( 'click', onConfirm );
			modalEl.addEventListener( 'hidden.bs.modal', onDismiss, { once: true } );
		} );
	}

	/* ── AJAX form submission ──────────────────────────── */

	$( document ).on( 'submit', '.cecomsmarad-ajax-form', function ( e ) {
		e.preventDefault();

		var $form  = $( this );
		var action = $form.data( 'ajax-action' );
		var $btn   = $form.find( '[type="submit"]' );

		if ( 'cecomsmarad_reimport_data' === action ) {
			showModal( cfg.i18n.reimportTitle, cfg.i18n.confirmReimport ).then( function ( confirmed ) {
				if ( confirmed ) {
					doAjax( $form, action, $btn );
				}
			} );
			return;
		}

		doAjax( $form, action, $btn );
	} );

	function doAjax( $form, action, $btn ) {
		var originalHtml = $btn.html();

		$btn.prop( 'disabled', true ).html(
			'<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' +
			$( '<span/>' ).text( cfg.i18n.saving ).html()
		);

		var data = $form.serialize();
		data += '&action=' + encodeURIComponent( action ) + '&_ajax_nonce=' + encodeURIComponent( cfg.nonce );

		$.post( cfg.ajaxUrl, data )
			.done( function ( response ) {
				if ( response.success ) {
					formDirty = false;
					var msg = ( response.data && response.data.message ) ? response.data.message : cfg.i18n.saved;
					showToast( 'success', msg );
					if ( response.data && response.data.reload ) {
						setTimeout( function () {
							window.location.reload();
						}, 800 );
					}
				} else {
					var errMsg = ( response.data && response.data.message ) ? response.data.message : cfg.i18n.error;
					showToast( 'danger', errMsg );
				}
			} )
			.fail( function () {
				showToast( 'danger', cfg.i18n.error );
			} )
			.always( function () {
				$btn.prop( 'disabled', false ).html( originalHtml );
			} );
	}

	/* ── Dirty state tracking ──────────────────────────── */

	$( document ).on( 'change', 'input, select, textarea', function () {
		formDirty = true;
	} );

	$( window ).on( 'beforeunload', function () {
		if ( formDirty ) {
			return cfg.i18n.unsavedChanges;
		}
	} );

	/* ── Shortcode copy-to-clipboard ───────────────────── */

	$( document ).on( 'click', '.shortcode-copy-btn', function () {
		var shortcode = $( this ).data( 'shortcode' );
		if ( navigator.clipboard && shortcode ) {
			navigator.clipboard.writeText( shortcode ).then( function () {
				showToast( 'success', cfg.i18n.shortcodeCopied );
			} );
		}
	} );

}( jQuery ) );

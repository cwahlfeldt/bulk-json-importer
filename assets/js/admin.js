/**
 * Admin JavaScript for Bulk JSON Importer
 *
 * @package Bulk_JSON_Importer
 */

( function( $ ) {
	'use strict';

	/**
	 * Custom fields table management
	 */
	const CustomFieldsTable = {
		/**
		 * Initialize
		 */
		init: function() {
			this.table = $( '#bji-custom-fields-table' );
			if ( ! this.table.length ) {
				return;
			}

			this.tableBody = this.table.find( 'tbody' );
			this.addButton = $( '#bji-add-custom-field' );
			this.rowIndex = this.tableBody.find( 'tr' ).length;

			this.bindEvents();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			// Add row button click
			this.addButton.on( 'click', this.addRow.bind( this ) );

			// Remove row button click (event delegation)
			this.tableBody.on( 'click', '.bji-remove-row', this.removeRow.bind( this ) );
		},

		/**
		 * Add a new row
		 */
		addRow: function() {
			const newRow = this.createRow( this.rowIndex );
			this.tableBody.append( newRow );
			this.rowIndex++;
		},

		/**
		 * Remove a row
		 */
		removeRow: function( e ) {
			e.preventDefault();
			$( e.target ).closest( 'tr' ).remove();
		},

		/**
		 * Create a new row HTML
		 */
		createRow: function( index ) {
			const jsonKeySelect = this.createJsonKeySelect( index );
			const metaKeyInput = this.createMetaKeyInput( index );
			const removeButton = this.createRemoveButton();

			return `
				<tr valign="top" class="bji-custom-field-row">
					<td>${ jsonKeySelect }</td>
					<td>${ metaKeyInput }</td>
					<td>${ removeButton }</td>
				</tr>
			`;
		},

		/**
		 * Create JSON key select dropdown
		 */
		createJsonKeySelect: function( index ) {
			const options = this.getJsonKeyOptions();
			return `<select name="mapping[custom][${ index }][json_key]">${ options }</select>`;
		},

		/**
		 * Create meta key input
		 */
		createMetaKeyInput: function( index ) {
			return `<input type="text" name="mapping[custom][${ index }][meta_key]" placeholder="${ bjiAdmin.strings.enterMetaKey }" />`;
		},

		/**
		 * Create remove button
		 */
		createRemoveButton: function() {
			return `<button type="button" class="button bji-remove-row">${ bjiAdmin.strings.removeRow }</button>`;
		},

		/**
		 * Get JSON key options from existing select
		 */
		getJsonKeyOptions: function() {
			const firstSelect = this.tableBody.find( 'select:first' );
			return firstSelect.length ? firstSelect.html() : `<option value="">${ bjiAdmin.strings.doNotMap }</option>`;
		}
	};

	/**
	 * Form validation
	 */
	const FormValidation = {
		/**
		 * Initialize
		 */
		init: function() {
			this.form = $( 'form[enctype="multipart/form-data"]' );
			if ( ! this.form.length ) {
				return;
			}

			this.bindEvents();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			this.form.on( 'submit', this.validateForm.bind( this ) );
		},

		/**
		 * Validate form before submission
		 */
		validateForm: function( e ) {
			const fileInput = $( '#bji_json_file' );
			
			if ( ! fileInput.length ) {
				return true;
			}

			const file = fileInput[0].files[0];
			
			if ( ! file ) {
				return true; // Let HTML5 validation handle this
			}

			// Check file size (10MB limit)
			const maxSize = 10 * 1024 * 1024; // 10MB in bytes
			if ( file.size > maxSize ) {
				alert( bjiAdmin.strings.fileSizeError );
				e.preventDefault();
				return false;
			}

			// Check file type
			if ( file.type !== 'application/json' && ! file.name.toLowerCase().endsWith( '.json' ) ) {
				alert( bjiAdmin.strings.fileTypeError );
				e.preventDefault();
				return false;
			}

			return true;
		}
	};

	/**
	 * Auto-mapping suggestions
	 */
	const AutoMapping = {
		/**
		 * Initialize
		 */
		init: function() {
			this.mappingTable = $( '.bji-mapping-table' );
			if ( ! this.mappingTable.length ) {
				return;
			}

			this.suggestMappings();
		},

		/**
		 * Suggest automatic mappings based on field names
		 */
		suggestMappings: function() {
			// This is already handled server-side, but we could add
			// client-side enhancements here if needed
		}
	};

	/**
	 * Progress indicator
	 */
	const ProgressIndicator = {
		/**
		 * Initialize
		 */
		init: function() {
			this.form = $( 'form' );
			this.submitButton = this.form.find( 'input[type="submit"]' );
			
			this.bindEvents();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			this.form.on( 'submit', this.showProgress.bind( this ) );
		},

		/**
		 * Show progress indicator
		 */
		showProgress: function() {
			if ( this.submitButton.length ) {
				this.submitButton.prop( 'disabled', true );
				this.submitButton.val( this.submitButton.val() + '...' );
			}
		}
	};

	/**
	 * Initialize when document is ready
	 */
	$( document ).ready( function() {
		CustomFieldsTable.init();
		FormValidation.init();
		AutoMapping.init();
		ProgressIndicator.init();
	} );

} )( jQuery );
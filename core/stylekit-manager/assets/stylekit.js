( function( wp, $ ) {

	"use strict";

	// Check if udpates exists
	if ( ! wp || ! wp.updates ) return;
	
	// On document ready
	$( function() {
		
		/**
		 * ----------------------
		 *        IMPORT
		 * ----------------------
		 */
		
		// STEP-1 - Plupload WP
		if( $( '.layers-stylekit-drop-uploader-ui' ).length > 0 ) {

			var options = false;
			var container = $( '.layers-stylekit-drop-uploader-ui' );

			options = JSON.parse( JSON.stringify( layers_stylekit_uploader_options ) );
			options['multipart_params']['_ajax_nonce'] = container.find( '.ajaxnonce' ).attr( 'id' );

			if( container.hasClass( 'multiple' ) ) {
				options['multi_selection'] = true;
			}

			var uploader = new plupload.Uploader( options );
			uploader.init();

			uploader.bind( 'Init', function( up ) {
				// Init.
				//console.log( 'Init', up );
			});
			uploader.bind( 'FilesAdded', function( up, files ) {
				// File added.
				$.each( files, function( i, file ) {
					//console.log( 'File Added', i, file );
				} );
				
				$.layerswp
				.queue( function(){
					go_to_slide( 2, $uploader_slides );
					show_loader();
					add_loader_text( 'Uploading StyleKit<br />Please wait...' );
				});

				up.refresh();
				up.start();
			});
			uploader.bind( 'UploadProgress', function( up, file ) {
				// Upload progress.
				//console.log( 'Progress', up, file );
				loader_progress( file['percent'] );
			});
			uploader.bind( 'FileUploaded', function( up, file, response ) {
				// File uploading done.
				response = $.parseJSON( response.response );

				if( response['status'] == 'success' ) {
					
					//console.log( 'Success', up, file, response );
					$('input[name="layers-stylekit-source-path"]').val( response['attachment']['src'] );
					$('input[name="layers-stylekit-source-id"]').val( response['attachment']['id'] );
					
					$.layerswp
					.queue( 1000 )
					.queue( function(){
						hide_loader();
						$('#layers-stylekit-plupload-info-form').submit();
					});
				}
				else {
					//console.log( 'Error', up, file, response );
					go_to_slide( 1, $uploader_slides );
				}

			});
		}
		
		var $stylkeit_json = [];

		// STEP-2 - Unpack the StyleKit Zip
		if ( $('input[name="layers-stylekit-package"]').val() ) {
			
			$.layerswp
			.queue( function(){
				show_loader();
			})
			.queue( 1000 )
			.queue( function(){
				add_loader_text( 'Unpacking StyleKit<br />Please wait...' );
			})
			.queue( 1000 )
			.queue( function(){
				
				loader_progress( 100 );
				
				// Break .zip package location (in the media library, added by the previous page)
				var $package = $('input[name="layers-stylekit-package"]').val();
				
				// Break if no .zip package is location is posted.
				//if ( '' == $package ) return false;
				
				var data = {
					package:         $package,
					_ajax_nonce:     wp.updates.ajaxNonce,
					plugin:          null,
					slug:            null,
					username:        wp.updates.filesystemCredentials.ftp.username,
					password:        wp.updates.filesystemCredentials.ftp.password,
					hostname:        wp.updates.filesystemCredentials.ftp.hostname,
					connection_type: wp.updates.filesystemCredentials.ftp.connectionType,
					public_key:      wp.updates.filesystemCredentials.ssh.publicKey,
					private_key:     wp.updates.filesystemCredentials.ssh.privateKey
				};

				wp.ajax
					.post( 'layers_stylekit_zip_unpack_ajax', data )
					.done( function( response ){
						
						/**
						 * On a StyleKit Unpack success, update the UI appropriately.
						 * Adapted from WP's - updates.js - wp.updates.updateSuccess()
						 */
						
						$.layerswp
						.queue( 1000 )
						.queue( function(){
							hide_loader();
						})
						.queue( 1000 )
						.queue( function(){
							
							$('.layers-stylekit-import-slide-2').append( response.ui );
							$('.layers-stylekit-form-import').prepend( response.ui2 );
							
							$stylkeit_json = response.stylekit_json;
							
							console.log( $stylkeit_json );
							
							go_to_slide( 2, $importer_slides );
						});
					})
					.fail( function( response ){
						
						/**
						 * On a StyleKit Unpack error, update the UI appropriately.
						 * Adapted from WP's - updates.js - wp.updates.updateError()
						 */
						
						if ( response.errorCode && response.errorCode == 'unable_to_connect_to_filesystem' ) {
							wp.updates.credentialError( response, 'update-plugin' );
							return;
						}
						
						// Feedback
						$( '.layers-column.layers-span-8' ).append( 'StyleKit Unpack Failed :( <br />' );
					});
				
			});
		}

		// @TODO Implement file system access checking later
		/*
		if ( wp.updates.shouldRequestFilesystemCredentials && ! wp.updates.updateLock ) {
			wp.updates.requestFilesystemCredentials( e );
		}
		*/
		
		// Show/Hide advanced options on Import & Export
		$( document ).on( 'change', 'input[name="layers-stylekit-import-all"]', function(){

			var $that = $(this);

			if( $that.is(':checked') ) {
				$( '.layers-stylekit-import-choices' ).animate({ height: 0 }, { easing: 'layersEaseInOut' });
			}
			else{
				$( '.layers-stylekit-import-choices' ).animate({ height: $('.layers-stylekit-import-choices-holder').outerHeight(true) }, { easing: 'layersEaseInOut' });
			}

		});
		
		// Un-Check/Check All on Import & Export
		$( document ).on( 'click', '.layers-stylekit-import-check-all, .layers-stylekit-import-uncheck-all', function(){

			var $that = $(this);

			var $inputs = $that.closest( '.layers-row' ).find( 'input[type="checkbox"]' )

			if ( $that.hasClass( 'layers-stylekit-import-check-all' ) ){
				$inputs.attr( 'checked', true );
			}
			if ( $that.hasClass( 'layers-stylekit-import-uncheck-all' ) ){
				$inputs.attr( 'checked', false );
			}
			$inputs.eq(0).change();

		});
		
		// Handle ticking of 'Import All'
		$( document ).on( 'change', 'input[name="layers-stylekit-import-all"]', function(){

			var $input = $( this );
			
			if ( $input.is(':checked') ) {
				// If is checked then simply check all the settings
				$('.stylekit-statement .tick').removeClass().addClass( 'tick ticked-all' );
			}
			else{
				// If is not checked then ping a chnage on all the inputs in the choices so they update the statement display
				var $inputs = $( '.layers-stylekit-import-choices' ).find( 'input[type="checkbox"]' );
				$inputs.change();
			}

		});
		
		// Handle ticking of the advanced choices
		$( document ).on( 'change', '[data-layers-link^="tick-"] input[type="checkbox"]', function(){
			
			var $container = $( this ).parents( '.layers-list' );
			var $checkboxes = $container.find( 'input[type="checkbox"]' );
			var $checkboxes_ticked = $container.find( 'input[type="checkbox"]:checked' );

			var $linked_statement_item_selector = '#' + $( this ).parents( '.layers-list' ).data('layers-link');
			var $linked_statement_item_element = $( $linked_statement_item_selector );

			if ( 0 == $checkboxes_ticked.length ){
				$linked_statement_item_element.removeClass().addClass( 'tick ticked-none' );
			}
			else if ( $checkboxes_ticked.length < $checkboxes.length ) {
				$linked_statement_item_element.removeClass().addClass( 'tick ticked-some' );
			}
			else {
				$linked_statement_item_element.removeClass().addClass( 'tick ticked-all' );
			}
		});

		$( document ).on( 'click', '.layers-back-a-step', function(){
			$('.layers-stylekit-import-slide-4 .layers-row').remove();
			
			go_to_slide( 2, $importer_slides );
			return false;
		});
		
		// Handle final click of confirm import
		$( document ).on( 'click', '.layers-stylekit-import-step-2-submit', function(){
			
			// Reset
			total_pages = 0;
			current_page = 0;
			reported_page = 0;
			total_images = 0;
			current_image = 0;
			reported_image = 0;
			
			// Invoke the first step in the Ajax chain.
			layers_stylekit_import_step_2_ajax();
			
			return false;
		});
		
		function layers_stylekit_import_step_2_ajax() {
			
			// Get the user to Confirm this operation.
			// if ( !window.confirm("This StyleKit Import will:\n\n- Change your settings.\n\n- Add 3 pages.\n\n- Add Custom CSS.") ) {
			// 	return false;
			// }
			
			// Sequence in the chnage of slides and showing of the loader.
			$.layerswp
			.queue( function(){
				go_to_slide( 3, $importer_slides );
			})
			.queue( 800 )
			.queue( function(){
				show_loader();
				add_loader_text( 'Preparing StyleKit<br />Please wait...' );
			})
			.queue( 800 );
			
			// Collect the form data. Holds the user selections and the whole StyleKit json.
			// var form_data = $( 'form.layers-stylekit-form-import' ).serializeArray();
			
			// var $data = {
			// 	'action'        : 'layers_stylekit_import_step_2_ajax',
			// 	'stylekit_json' : $stylkeit_json,
			// 	'form_data'     : $.param( form_data ),
			// };
			
			// form_data.push({ name: 'stylekit_json', value: $( $stylkeit_json ).serializeArray() });
			// form_data.push({ name: 'action', value: 'layers_stylekit_import_step_2_ajax' });
			
			// console.log( $.param( form_data ) );
			
			var form_data = $( 'form.layers-stylekit-form-import' ).serialize() + '&action=layers_stylekit_import_step_2_ajax';
			
			// Ajax
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: ajaxurl,
				data: form_data,
				success: layers_stylekit_import_step_3_ajax,
			});
		}
		
		function layers_stylekit_import_step_3_ajax( response ) {
			
			// User Feedback
			
			$.layerswp
			.queue( function(){
				show_loader();
				add_loader_text( 'Importing Settings & CSS<br />Please wait...' );
			})
			.queue( 800 );
			
			// Debugging
			//console.log( response );
			if( response.stylekit_json_pretty ) {
				$('[name="layers-stylekit-import-stylekit-prettyprint"]').val( response.stylekit_json_pretty );
			}
			
			// Ajax
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: ajaxurl,
				data: {
					action: 'layers_stylekit_import_step_3_ajax',
					//stylekit_json: response.stylekit_json,
				},
				success: layers_stylekit_import_step_4_ajax,
			});
		}
		
		var total_pages = 0;
		var current_page = 0;
		var reported_page = 0;
		var page_success_function;

		function layers_stylekit_import_step_4_ajax( response ) {
			
			current_page++;
			total_pages = 0;
			for ( var property in response.stylekit_json.pages ) if ( response.stylekit_json.pages.hasOwnProperty( property ) ) total_pages++;
			
			// User Feedback
			$.layerswp
			.queue( function(){
				
				reported_page++;
				show_loader();
				add_loader_text( 'Importing Page ' + reported_page + ' of ' + total_pages + '<br />Please wait...' );
			})
			.queue( 800 );
			
			// This puts the page import into a loop.
			if ( current_page >= total_pages ) page_success_function = layers_stylekit_import_step_5_ajax;
			else page_success_function = layers_stylekit_import_step_4_ajax;
			
			// Debugging
			//console.log( response );
			if( response.stylekit_json_pretty ) {
				$('[name="layers-stylekit-import-stylekit-prettyprint"]').val( response.stylekit_json_pretty );
			}
			
			// Ajax
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: ajaxurl,
				data: {
					action: 'layers_stylekit_import_step_4_ajax',
					//stylekit_json: response.stylekit_json,
				},
				success: page_success_function,
			});
		};
		
		var total_images = 0;
		var current_image = 0;
		var reported_image = 0;
		var image_success_function;
		
		function layers_stylekit_import_step_5_ajax( response ) {

			current_image++;
			total_images = 0;
			for ( var property in response.stylekit_json.internal_data.images_in_widgets ) if ( response.stylekit_json.internal_data.images_in_widgets.hasOwnProperty( property ) ) total_images++;
			
			// User Feedback
			$.layerswp
			.queue( function(){
				
				reported_image++;
				show_loader();
				add_loader_text( 'Importing Image ' + reported_image + ' of ' + total_images + '<br />Please wait...' );
			})
			.queue( 800 );
			
			// This puts the page import into a loop.
			if ( current_image >= total_images ) image_success_function = layers_stylekit_import_step_6_ajax;
			else image_success_function = layers_stylekit_import_step_5_ajax;
			
			// Debugging
			//console.log( response );
			if( response.stylekit_json_pretty ) {
				$('[name="layers-stylekit-import-stylekit-prettyprint"]').val( response.stylekit_json_pretty );
			}
			
			// Ajax
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: ajaxurl,
				data: {
					action: 'layers_stylekit_import_step_5_ajax',
					//stylekit_json: response.stylekit_json,
				},
				success: image_success_function,
			});
		};
		
		function layers_stylekit_import_step_6_ajax( response ) {
			
			// User Feedback
			$.layerswp
			.queue( function(){
				show_loader();
				add_loader_text( 'Finishing<br />Thanks for waiting :)' );
			})
			.queue( 800 );

			// Debugging
			//console.log( response );
			if( response.stylekit_json_pretty ) {
				$('[name="layers-stylekit-import-stylekit-prettyprint"]').val( response.stylekit_json_pretty );
			}
			
			// Ajax
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: ajaxurl,
				data: {
					action: 'layers_stylekit_import_step_6_ajax',
					//stylekit_json: response.stylekit_json,
				},
				success: layers_stylekit_import_ajax_step_7,
			});
		};
		
		
		function layers_stylekit_import_ajax_step_7( response ) {
			
			// Sequence in the chnage of slides and showing of the loader.
			$.layerswp
			.queue( 800 )
			.queue( function(){
				hide_loader();
			})
			.queue( 800 )
			.queue( function(){
				$( '.layers-stylekit-import-slide-4' ).append( response.ui );
				go_to_slide( 4, $importer_slides );
			});
			
			// Debugging
			//console.log( response );
			if( response.stylekit_json_pretty ) {
				$('[name="layers-stylekit-import-stylekit-prettyprint"]').val( response.stylekit_json_pretty );
			}
		}
		
		
		// Restore Settings
		$( document ).on( 'click', '.layers-stylekit-remove-stylekit-button', function(){
			
			go_to_slide( 2, $restore_slides );
			
			layers_stylekit_restore_settings();
			
			return false;
		});
		
		function layers_stylekit_restore_settings( /*response*/ ) {
			
			$.layerswp
			.queue( function(){
				show_loader();
				add_loader_text( 'Rolling back your settings<br />Please wait...' );
			})
			.queue( 800 );
			
			/*
			// Debugging
			//console.log( response );
			if( response.stylekit_json_pretty ) {
							$('[name="layers-stylekit-import-stylekit-prettyprint"]').val( response.stylekit_json_pretty );
			}
			*/
			
			// Ajax
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: ajaxurl,
				data: {
					action: 'layers_stylekit_remove_ajax',
				},
				success: layers_stylekit_restore_settings_success,
			});
		}

		function layers_stylekit_restore_settings_success( response ) {
			
			$.layerswp
			.queue( 800 )
			.queue( function(){
				hide_loader();
				go_to_slide( 1, $restore_slides );
			})
			.queue( 800 );
		}

		
// 		$( document ).on( 'hover', '.layers-stylekit-rollback', function(){
// 			$( '.layers-stylekit-history-container' ).toggleClass( 'hover-remove' );
// 			return false;
// 		});


		/**
		 * ----------------------
		 *        EXPORT
		 * ----------------------
		 */
		
		// Handle click to Export
		$( '#layers-stylekit-export-action' ).click( function(){
			
			// Check user has ticked 'permission to distribute' checkbox
			if ( ! $('input[name="layers-stylekit-export-confirm-permission"]').is(":checked") ){
				$( '.layers-alert' ).removeClass( 'flash animated shake' );
				setTimeout( function(){
					$( '.layers-alert' ).addClass( 'flash animated shake' );
				}, 1 );
				return false;
			}
			
			go_to_slide( 2, $exporter_slides );
			show_loader();
			
			// Ajax to export StyleKit
			$.post(
				ajaxurl,
				$( 'form.layers-stylekit-form-export' ).serialize() + '&action=layers_stylekit_export_ajax', // Convert form data to json
				function( response ){
					
					$('.layers-stylekit-export-slide-3').append( response.ui );
					go_to_slide( 3, $exporter_slides );

					// Debugging
					console.log( response );
					$('[name="layers-stylekit-export-stylekit-prettyprint"]').val( response.stylekit_json_pretty );

				},
				'json'
			);
			
			return false;
		});
		
		
		
		/**
		 * Loader functionality
		 */
		
		function show_loader(){
			
			// Remove any legacy text.
			remove_loader_text();
			
			var $loader_bar = $( '.layers-load-bar' );
			//$loader_bar.parent().append( $loader_bar );
			$loader_bar.removeClass( 'layers-hide' ).fadeIn();
		};

		function hide_loader(){
			
			// Remove any legacy text.
			remove_loader_text();
			
			var $loader = $( '.layers-load-bar' );
			$('.layers-load-bar-floater').fadeOut(function(){
				$loader.hide();
			});
		};
		
		function loader_progress( $progress ){

			var $loader = $('.layers-load-bar');

			// If loaders hidden the un-hide it
			if( $loader.hasClass('layers-load-bar-hide') ) {
				$loader.removeClass('layers-load-bar-hide');
			}

			// Set the progress bar width
			$loader.find('.layers-progress').removeClass('zero').css({ width: $progress + '%' });

			// The first time the progress reaches 100% then mark it as done,
			// and only in a few mili's so it has time to animate.
			if ( !$loader.hasClass('done') && 100 == $progress ) {
				setTimeout( function(){
					$loader.addClass('done');
				}, 500 );
			}
		};

		function add_loader_text( $text ){
			
			var $loader = $( '.layers-load-bar' );
			var $old_load_text = $loader.find( '.loading-text' );
			var $load_text = $( '<div class="loading-text loading-text-lead-in">' + $text + '</div>' );
			
			if ( $old_load_text.length != 0 ) {
				$old_load_text.addClass('loading-text-lead-out');
				setTimeout( function() {
					$old_load_text.remove();
				}, 500 );
			}

			if ( '' == $text || null == $text ) return;
			
			$loader.append( $load_text );
			setTimeout( function() {
				$load_text.removeClass('loading-text-lead-in');
			}, 100 );

		};

		function remove_loader_text(){
			add_loader_text('');
		};
		
		
		/**
		 * Slides
		 */
		
		var $uploader_slides = [
			'.layers-stylekit-upload-slide-1',
			'.layers-stylekit-upload-slide-2',
			'.layers-stylekit-upload-slide-3',
		];
		
		var $restore_slides = [
			'.layers-restore-slide-1',
			'.layers-restore-slide-2',
		];
		
		var $importer_slides = [
			'.layers-stylekit-import-slide-1',
			'.layers-stylekit-import-slide-2',
			'.layers-stylekit-import-slide-3',
			'.layers-stylekit-import-slide-4',
		];

		var $exporter_slides = [
			'.layers-stylekit-export-slide-1',
			'.layers-stylekit-export-slide-2',
			'.layers-stylekit-export-slide-3',
		];

		function go_to_slide( $to_slide, $slides_array ){

			var $slide_index = $to_slide - 1;
			var $slides_array = $slides_array.slice();
			var $slide_selector = $slides_array.splice( $slide_index, 1 ).join(', ');
			var $other_slides_selectors = $slides_array.join(', ');
			var $slide = $( $slide_selector );
			var $other_slides = $( $other_slides_selectors );
			var $container = $slide.parent();

			// Bail is already the current slide
			if ( $slide.hasClass('layers-stylekit-slide-current') ) return false;

			//$container.css({ height: $container.outerHeight() });

			// Fade Out all except current slide
			$other_slides
				.removeClass('layers-stylekit-slide-current')
				.addClass('layers-stylekit-slide-inactive');

			// Move destination slide to the front of the container and fade in
			$slide
				.removeClass('layers-stylekit-slide-inactive')
				.addClass('layers-stylekit-slide-current');

			/*
			$container.animate({ height: $slide.outerHeight() }, { easing: 'layersEaseInOut', duration: 300, complete: function(){
				$container.css({ height: ''});
			}});
			*/

			// Scroll to top of page, incase of a long slide having been scrolled by user
			$('html, body').animate({ scrollTop: 0 }, 200 );
		}
		
	} );

} )( wp, jQuery );


/**
 * Queue jQuery Plugin
 *
 * Plugin for layers that allows the queuing of events so they happen in a set sequence.
 * Uses setTimeout at it's core, but provides a mroe linear syntax when defining the code.
 *
 * e.g.

	$.layerswp
	.stop_queue( 'name' )
	.queue( 'name', 2000 )
	.queue( 'name', function(){
		//console.log('ONE!');
	})
	.queue( 'name', 2000 )
	.queue( 'name', function(){
		//console.log('TWO!');
});

 *
 */

(function( $ ) {

	// Setup or get layerswp.
	$.fn.layerswp = $.fn.layerswp || {};

	$.layerswp = $.extend({

		_queue: {

			main_queue_collection: [],

			queue_busy: [],

			add_to_queue: function( $args, $name ) {

				var $defaults = {
					delay: ( 'number' === typeof $args ) ? $args : 1,
					function: ( 'function' === typeof $args ) ? $args : function(){},
				};

				$args = $.extend( $defaults, $args );

				if ( !this.main_queue_collection[$name] ) this.main_queue_collection[$name] = [];
				this.main_queue_collection[$name].push( $args );

				this.check_queue( $name );
			},

			check_queue: function( $name ) {

				$queue_collection = this.main_queue_collection[$name];

				// Bail if nothing is in queue
				if ( this.queue_busy[$name] || $queue_collection.length <= 0 ) return;

				// Lock the queue to prevent overlapping
				this.queue_busy[$name] = true;

				// Get current item off the start of the queue
				var $current_item = $queue_collection.shift();

				// Apply : --- DELAY ---
				setTimeout( this.next_step.bind( this, $name, $current_item ), $current_item.delay );
			},

			next_step: function() {

				$name = arguments[0];
				$current_item = arguments[1];

				// Apply : --- FUNCTION ---
				if( typeof( $current_item.function ) === "function" ) $current_item.function();

				// Un-lock the queue
				this.queue_busy[$name] = false;

				// Recheck this queue
				this.check_queue( $name );
			}

		}

	}, $.layerswp );

	// Make 'queue' call a default function 'add_to_queue' in '_queue' so it can be added easy.
	$.layerswp = $.extend({

		queue: function( $arg1, $arg2 ){

			if( $.type( $arg1 ) === "string" ){
				$name = $arg1; $args = $arg2;
			}
			else{
				$name = '_general_'; $args = $arg1;
			}

			if( typeof $.layerswp._queue.queue_busy[$name] === 'undefined' ){
				$.layerswp._queue.queue_busy[$name] = false;
			}

			$.layerswp._queue.add_to_queue( $args, $name );

			return this;
		},

		stop_queue: function( $name ) {

			if( !$name ) $name = '_general_';

			if ( ! typeof $.layerswp._queue.main_queue_collection[ $name ] === 'undefined' ){

				$.layerswp._queue.main_queue_collection[$name] = [];
				$.layerswp._queue.queue_busy[$name] = false;
			}

			return this;
		}

	}, $.layerswp );

}( jQuery ));
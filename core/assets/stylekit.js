( function( wp, $ ) {

	"use strict";

	// Check if udpates exists
	if ( ! wp || ! wp.updates ) return;

	/**
	 * Send an Ajax request to the server to unpack StyleKit.
	 * Adapted from WP's - updates.js - wp.updates.updatePlugin()
	 */
	wp.updates.updateStyleKit = function( plugin, slug ) {

		layers.loader.add_to_queue( 1500 );
		layers.loader.add_to_queue( function(){
			layers.loader.show_loader();
			layers.loader.add_loader_text( 'Unpacking StyleKit. Please wait...' );
		});
		layers.loader.add_to_queue( function(){
			
			layers.loader.loader_progress( 50 );
			
			var data = {
				package:         $('input[name="layers-stylekit-package"]').val(),
				_ajax_nonce:     wp.updates.ajaxNonce,
				plugin:          plugin,
				slug:            slug,
				username:        wp.updates.filesystemCredentials.ftp.username,
				password:        wp.updates.filesystemCredentials.ftp.password,
				hostname:        wp.updates.filesystemCredentials.ftp.hostname,
				connection_type: wp.updates.filesystemCredentials.ftp.connectionType,
				public_key:      wp.updates.filesystemCredentials.ssh.publicKey,
				private_key:     wp.updates.filesystemCredentials.ssh.privateKey
			};

			wp.ajax.post( 'layers_stylekit_unpack_ajax', data )
			.done( function( response ){
				
				/**
				 * On a StyleKit Unpack success, update the UI appropriately.
				 * Adapted from WP's - updates.js - wp.updates.updateSuccess()
				 */
				
				layers.loader.add_to_queue( 1500 );
				layers.loader.add_to_queue( function(){
					layers.loader.loader_progress( 100 );
					layers.loader.add_loader_text( '' );
				});
				layers.loader.add_to_queue( 1500 );
				layers.loader.add_to_queue( function(){
					layers.loader.hide_loader();
				});
				layers.loader.add_to_queue( 500 );
				layers.loader.add_to_queue( function(){
					
					$('.layers-stylekit-import-step-2 .layers-stylekit-slide-2').append( response.ui );
					$('.layers-stylekit-form-import').prepend( response.ui2 );
					
					layers.slider.go_to_slide( 2, $importer_slides );
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
				$( '.layers-column.layers-span-8' ).append( 'StyleKit Unpack Failed :( <br>' );
			});
			
		});
	};
	

	var layers = { loader : {} };

	layers.loader.loading_text_queue_collection = [];

	layers.loader.queue_busy = false;

	layers.loader.loader_progress = function( $progress ){

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

	layers.loader.show_loader = function(){
		var $loader_bar = $( '.layers-load-bar' );
		//$loader_bar.parent().append( $loader_bar );
		$loader_bar.removeClass( 'layers-hide' ).fadeIn();
	};

	layers.loader.hide_loader = function(){
		var $loader = $( '.layers-load-bar' );
		$('.layers-load-bar-floater').fadeOut(function(){
			$loader.hide();
		});
	};

	layers.loader.check_queue = function() {
		
		// Bail if nothing is in queue
		if ( layers.loader.queue_busy || layers.loader.loading_text_queue_collection.length <= 0 ) return;
		
		// Lock the queue to prevent overlapping
		layers.loader.queue_busy = true;
		
		// Get current item off the start of the queue
		var $current_item = layers.loader.loading_text_queue_collection.shift();

		// Apply : --- DELAY ---
		setTimeout( function(){
			
			// Apply : --- FUNCTION ---
			if( $current_item.function ) $current_item.function();
			
			// Testing:
			$( '.event-notification' ).remove();
			var $notification;
			$( 'body' ).append( $notification = $( '<div class="event-notification" style="background: #F00; position: fixed; top: 60px; right: 60px; z-index: 1000; width: 30px; height: 30px; border-radius: 100px; ">&nbsp;</div>' ) );
			setTimeout(function() {
				$notification.fadeOut( '500' );
			}, 50 );
			
			// Un-lock the queue
			layers.loader.queue_busy = false;
			
			// Recheck this queue
			layers.loader.check_queue();

		}, $current_item.delay );

	};

	layers.loader.add_to_queue = function( $args ){
		
		var $defaults = {
			delay: ( 'number' === typeof $args ) ? $args : 1,
			function: ( 'function' === typeof $args ) ? $args : function(){},
		};

		$args = $.extend( $defaults, $args );

		layers.loader.loading_text_queue_collection.push( $args );
		layers.loader.check_queue();
	};

	layers.loader.add_loader_text = function( $text ){
		
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

	layers.loader.remove_loader_text = function(){
		layers.loader.add_loader_text('');
	};

	

	layers.slider = {};
	
	//layers.loader.loading_text_queue_collection = [];
	
	var $uploader_slides = [
		'.layers-stylekit-import-step-1 .layers-stylekit-slide-1',
		'.layers-stylekit-import-step-1 .layers-stylekit-slide-2',
		'.layers-stylekit-import-step-1 .layers-stylekit-slide-3',
	];

	var $exporter_slides = [
		'.layers-stylekit-export-step-1 .layers-stylekit-slide-1',
		'.layers-stylekit-export-step-1 .layers-stylekit-slide-2',
		'.layers-stylekit-export-step-1 .layers-stylekit-slide-3',
	];

	var $importer_slides = [
		'.layers-stylekit-import-step-2 .layers-stylekit-slide-1',
		'.layers-stylekit-import-step-2 .layers-stylekit-slide-2',
		'.layers-stylekit-import-step-2 .layers-stylekit-slide-3',
		'.layers-stylekit-import-step-2 .layers-stylekit-slide-4',
	];

	layers.slider.go_to_slide = function( $to_slide, $slides_array ){

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

			options = JSON.parse( JSON.stringify( global_uploader_options ) );
			options['multipart_params']['_ajax_nonce'] = container.find( '.ajaxnonce' ).attr( 'id' );

			if( container.hasClass( 'multiple' ) ) {
				options['multi_selection'] = true;
			}

			var uploader = new plupload.Uploader( options );
			uploader.init();

			// EVENTS
			// init
			uploader.bind( 'Init', function( up ) {
				//console.log( 'Init', up );
			} );

			// file added
			uploader.bind( 'FilesAdded', function( up, files ) {
				$.each( files, function( i, file ) {
					//console.log( 'File Added', i, file );
				} );
				
				layers.slider.go_to_slide(0, $uploader_slides );
				layers.loader.show_loader();

				up.refresh();
				up.start();
			} );

			// upload progress
			uploader.bind( 'UploadProgress', function( up, file ) {
				//console.log( 'Progress', up, file );
				
				layers.loader.loader_progress( file['percent'] );

			} );

			// file uploaded
			uploader.bind( 'FileUploaded', function( up, file, response ) {
				response = $.parseJSON( response.response );

				if( response['status'] == 'success' ) {
					//console.log( 'Success', up, file, response );
					$('input[name="layers-stylekit-source-path"]').val( response['attachment']['src'] );
					$('input[name="layers-stylekit-source-id"]').val( response['attachment']['id'] );
					
					layers.loader.add_to_queue( 100 );
					layers.loader.add_to_queue( function(){
						layers.loader.add_loader_text('StyleKit Uploaded. Please wait...');
					});
					layers.loader.add_to_queue( 1000 );
					layers.loader.add_to_queue( function(){
						$('#layers-stylekit-plupload-info-form').submit();
					});
				}
				else {
					//console.log( 'Error', up, file, response );
				}

			} );

		}

		// STEP-2 - Unpack the StyleKit Zip
		if ( $('input[name="layers-stylekit-package"]').val() ) {
			wp.updates.updateStyleKit();
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
		
		// Handle final click of confirm import
		$( document ).on( 'click', '.layers-stylekit-import-step-2-submit', function(){
			
			
			// Get the user to Confirm this operation.
			// if ( !window.confirm("This StyleKit Import will:\n\n- Change your settings.\n\n- Add 3 pages.\n\n- Add Custom CSS.") ) {
			// 	return false;
			// }
			
			
			// User Feedback
			layers.loader.add_to_queue( function(){
				layers.slider.go_to_slide( 3, $importer_slides );
				layers.loader.add_loader_text('');
			});
			layers.loader.add_to_queue( 1000 );
			layers.loader.add_to_queue( function(){
				layers.loader.show_loader('');
			});
			layers.loader.add_to_queue( 500 );
			layers.loader.add_to_queue( function(){
				
				layers.loader.loader_progress( 50 );
				layers.loader.add_loader_text( 'Importing StyleKit. Please wait...' );
				
			});
			
			
			// Get the operations started.
			$.post(
				ajaxurl,
				$( 'form.layers-stylekit-form-import' ).serialize() + '&action=layers_stylekit_import_ajax_step_1',
				function( response ){
					
					layers.loader.add_to_queue( function(){
						layers.loader.loader_progress( 100 );
						layers.loader.add_loader_text( '' );
					});
					layers.loader.add_to_queue( 1000 );
					layers.loader.add_to_queue( function(){
						layers.loader.hide_loader();
					});
					layers.loader.add_to_queue( 500 );
					layers.loader.add_to_queue( function(){
						
						// $( '.layers-stylekit-import-step-2 .layers-stylekit-slide-4' ).append( response.result );
						// layers.slider.go_to_slide( 4, $importer_slides );
						
					});
					
					// Start next ajax operation
					$.post(
						ajaxurl,
						response.stylekit,
						function( response ){
							
							layers.loader.add_to_queue( function(){
								layers.loader.loader_progress( 100 );
								layers.loader.add_loader_text( '' );
							});
							layers.loader.add_to_queue( 1000 );
							layers.loader.add_to_queue( function(){
								layers.loader.hide_loader();
							});
							layers.loader.add_to_queue( 500 );
							layers.loader.add_to_queue( function(){
								
								// $( '.layers-stylekit-import-step-2 .layers-stylekit-slide-4' ).append( response.result );
								// layers.slider.go_to_slide( 4, $importer_slides );
								
							});
							
							
							
							
						},
						'json'
					);
					
				},
				'json'
			);
			
			return false;
		});


		/**
		 * ----------------------
		 *        EXPORT
		 * ----------------------
		 */
		
		// Handle click to Export
		$( '#layers-stylekit-export-action' ).click(function(){
			
			// Check user has ticked 'permission to distribute' checkbox
			if ( ! $('input[name="layers-stylekit-export-confirm-permission"]').is(":checked") ){
				$( '.layers-alert' ).removeClass( 'flash animated shake' );
				setTimeout( function(){
					$( '.layers-alert' ).addClass( 'flash animated shake' );
				}, 1 );
				return false;
			}
			
			layers.slider.go_to_slide( 2, $exporter_slides );
			layers.loader.show_loader();
			
			// Ajax to export StyleKit
			$.post(
				ajaxurl,
				$( 'form.layers-stylekit-form-export' ).serialize(), // Convert form data to json
				function( response ){
					
					$('.layers-stylekit-export-step-1 .layers-stylekit-slide-3').append( response.ui );
					layers.slider.go_to_slide( 3, $exporter_slides );
				},
				'json'
			);
			
			return false;
		});

		
	} );

} )( wp, jQuery );
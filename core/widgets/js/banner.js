/**
* Slider Widget JS file
*
* This file contains functions relating to the Slider Widget
 *
 * @package Hatch
 * @since Hatch 1.0
 * Contents
 * 1 - Sortable items
 * 2 - Slide Removal & Additions
 * 3 - Slide Title Update
*/

jQuery(document).ready(function($){

	/**
	* 1 - Sortable items
	*/
	hatch_set_slide_sorable();

	$(document).on ( 'widget-added' , function(){
		hatch_set_slide_sorable();
	});

	function hatch_set_slide_sorable(){

		var $banner_lists = $( 'ul[id^="banner_list_"]' );

		$banner_lists.sortable({
			placeholder: "hatch-sortable-drop",
			handle: ".hatch-accordion-title",
			stop: function(e , li){
				// Banner UL, looking up from our current target
				$bannerList = li.item.closest( 'ul' );

				// Banners <input>
				$bannerInput = $( '#banner_ids_input_' + $bannerList.data( 'number' ) );

				// Apply new banner order
				$banner_guids = [];
				$bannerList.find( 'li.hatch-accordion-item' ).each(function(){
					$banner_guids.push( $(this).data( 'guid' ) );
				});

				// Trigger change for ajax save
				$bannerInput.val( $banner_guids.join() ).hatch_trigger_change();
			}
		});
	};

	/**
	* 2 - Banner Removal & Additions
	*/

	$(document).on( 'click' , 'ul[id^="banner_list_"] .icon-trash' , function(e){
		e.preventDefault();

		// "Hi Mom"
		var $that = $(this);

		// Confirmation message @TODO: Make JS confirmation module
		var $remove_slide = confirm( "Are you sure you want to remove this slide?" );

		if( false === $remove_slide ) return;

		// Banner UL
		$bannerList = $( '#banner_list_' + $that.data( 'number' ) );

		// Banners <input>
		$bannerInput = $( '#banner_ids_input_' + $bannerList.data( 'number' ) );

		// Remove this banner
		$that.closest( '.hatch-accordion-item' ).remove();

		// Curate banner IDs
		$banner_guids = [];

		$bannerList.find( 'li.hatch-accordion-item' ).each(function(){
			$banner_guids.push( $(this).data( 'guid' ) );
		});

		// Trigger change for ajax save
		$bannerInput.val( $banner_guids.join() ).hatch_trigger_change();
	});

	$(document).on( 'click' , '.hatch-add-widget-banner' , function(e){
		e.preventDefault();

		// "Hi Mom"
		$that = $(this);

		// Create the list selector
		$bannerListId = '#banner_list_' + $that.data( 'number' );

		// Banner UL
		$bannerList = $( $bannerListId );

		// Banners <input>
		$bannerInput = $( '#banner_ids_input_' + $bannerList.data( 'number' ) );

		// Serialize input data
		$serialized_inputs = [];
		$.each(
			$bannerList.find( 'li.hatch-accordion-item' ).first().find( 'textarea, input, select' ),
			function( i, input ){
				$serialized_inputs.push( $(input).serialize() );
		});

		$.post(
			hatch_widget_params.ajaxurl,
			{
				action: 'hatch_banner_widget_actions',
				widget_action: 'add',
				id_base: $bannerList.data( 'id_base' ),
				instance: $serialized_inputs.join( '&' ),
				last_guid: $bannerList.find( 'li.hatch-accordion-item' ).first().data( 'guid' ),
				number: $bannerList.data( 'number' ),
				nonce: hatch_widget_params.nonce

			},
			function(data){

				// Append module HTML
				$bannerList.prepend( data );
				//$( data ).insertBefore( $bannerListId + ' .hatch-add-widget-banner' );

				// Append banner IDs to the banners input
				$banner_guids = [];
				$bannerList.find( 'li.hatch-accordion-item' ).each(function(){
					$banner_guids.push( $(this).data( 'guid' ) );
				});

				// Trigger change for ajax save
				$bannerInput.val( $banner_guids.join() ).hatch_trigger_change();

				// Trigger color selectors
				jQuery('.hatch-color-selector').wpColorPicker();
			}
		) // $.post
	});

	/**
	* 3 - Banner Title Update
	*/

	$(document).on( 'keyup' , 'ul[id^="banner_list_"] input[id*="-title"]' , function(e){

		// "Hi Mom"
		$that = $(this);

		// Set the Title
		$string = ': ' + $that.val();

		// Update the accordian title
		$that.closest( '.hatch-accordion-item' ).find( 'span.hatch-detail' ).text( $string );

	});

}); //jQuery
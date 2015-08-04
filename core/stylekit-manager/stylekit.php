<?php

/*
 * Style Kit Export / Import
 */

class Layers_StyleKit_Exporter {
	
	// Global
	private static $instance; // stores singleton class
	
	private $config;
	
	private $migrator;
	
	private $control_groups;
	
	private $controls_to_exclude;
	
	private $check_image_locations;
	
	private $check_images;
	
	/**
	*  Get Instance creates a singleton class that's cached to stop duplicate instances
	*/
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	*  Construct empty on purpose
	*/
	private function __construct() {}

	/**
	*  Init behaves like, and replaces, construct
	*/
	public function init() {
		
		add_action( 'admin_menu', array( $this, 'layers_stylekit_menu'), 100 );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'stylekit_enqueue_script' ) );
		
		// Export:
		add_action( 'wp_ajax_layers_stylekit_export_ajax', array( $this, 'layers_stylekit_export_ajax' ) );
		
		// Import:
		// Drag&Drop plupload interface
		add_action( 'admin_head', array( $this, 'file_upload_settings' ) );
		add_action( 'wp_ajax_layers_stylekit_upload_ajax', array( $this, 'layers_stylekit_upload_ajax' ) );
		// Unpack the zip
		add_action( 'wp_ajax_layers_stylekit_zip_unpack_ajax', array( $this, 'layers_stylekit_zip_unpack_ajax' ) );
		// Ajax for each step of the import process
		add_action( 'wp_ajax_layers_stylekit_import_step_2_ajax', array( $this, 'layers_stylekit_import_step_2_ajax' ) );
		add_action( 'wp_ajax_layers_stylekit_import_step_3_ajax', array( $this, 'layers_stylekit_import_step_3_ajax' ) );
		add_action( 'wp_ajax_layers_stylekit_import_step_4_ajax', array( $this, 'layers_stylekit_import_step_4_ajax' ) );
		add_action( 'wp_ajax_layers_stylekit_import_step_5_ajax', array( $this, 'layers_stylekit_import_step_5_ajax' ) );
		add_action( 'wp_ajax_layers_stylekit_import_step_6_ajax', array( $this, 'layers_stylekit_import_step_6_ajax' ) );
		
		// Restore:
		add_action( 'wp_ajax_layers_stylekit_remove_ajax', array( $this, 'layers_stylekit_remove_ajax' ) );
		
		// Post type for collecting StyleKits.
		$this->register_post_type();
		
		/**
		 * Init Vars
		 */
		
		// Init Config so the controls can be used in the StyleKit
		$this->config = Layers_Customizer_Config::get_instance();
		
		// Init Migrator so can be used in the StyleKit
		$this->migrator = new Layers_Widget_Migrator();
		
		// Define groups of Settings that will be used in the Export/Import
		$this->control_groups = array(
			'header' => array(
								'title'    => 'Header Settings',
								'desc'     => 'Settings from the header etc',
								'contains' => array(
									'header-layout',
								),
							),
			'footer' => array(
								'title'    => 'Footer Settings',
								'desc'     => 'Settings from the footer etc',
								'contains' => array(
									'footer-layout',
									'footer-text',
								),
							),
			'colors' => array(
								'title'    => 'Colors',
								'desc'     => 'Settings from the colors etc',
								'contains' => array(
									'site-colors',
								),
							),
		);
		
		// Exclude controls of these types while Export/Import
		$this->controls_to_exclude = array(
			'layers-seperator',
			'layers-heading',
		);
	}
	
	/**
	 * Enqueue Scripts
	 */
	
	function stylekit_enqueue_script() {
		
		wp_enqueue_style(
			'layers-stylekit-export-css',
			LAYERS_TEMPLATE_URI . '/core/stylekit-manager/assets/stylekit.css',
			array(
				'layers-admin'
			)
		);
		
		wp_enqueue_script(
			'layers-stylekit-export-js',
			LAYERS_TEMPLATE_URI . '/core/stylekit-manager/assets/stylekit.js',
			array(
				'jquery',
				'plupload-all',
				'updates',
			)
		);
	}
	
	/**
	 * Add StyleKit Manager menu item
	 */
	
	function layers_stylekit_menu(){
		
		add_submenu_page(
			'layers-dashboard',
			__( 'StyleKit Manager' , 'layerswp' ),
			__( 'StyleKit Manager' , 'layerswp' ),
			'edit_theme_options',
			'layers_stylekit_manager',
			array( $this, 'layers_stylekit_manager_page' )
		);
	}
	
	/**
	 * Get Controls helper.
	 *
	 * Used to get specific controls from the layers-controls config.
	 */
	
	function get_controls( $args = array() ){
		
		$defaults = array(
			'panels'        => array(), // @TODO
			'sections'      => array(),
			'exclude_types' => array(),
			'include_types' => array(), // @TODO
			'id'            => array(), // @TODO
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		foreach ( $args as $key => $value ) {
			if( !is_array( $args[ $key ] ) ){
				$args[ $key ] = explode( ',', $value ) ;
			}
		}
		
		$controls = array();
		
		foreach ( $args['sections'] as $section_key ) {
			if( isset( $this->config->controls[ $section_key ] ) ){
				$controls = array_merge( $controls, $this->config->controls[ $section_key ] );
			}
		}
		
		foreach ( $controls as $control_key => $control ) {
			if ( in_array( $control[ 'type' ], $args['exclude_types'] ) ) {
				unset( $controls[ $control_key ] );
			}
		}
		
		return $controls;
	}
	
	/**
	* Recursively delete a directory
	*
	* @param string $dir Directory name
	* @param boolean $delete_root_too Delete specified top-level directory as well
	*/
	function delete_recursive( $dir, $delete_root_too = TRUE ) {
		
		if( !$dh = @opendir( $dir ) ) {
			return;
		}
		while ( false !== ( $obj = readdir( $dh ) ) ) {
			if( $obj == '.' || $obj == '..' ) {
				continue;
			}

			if ( !@unlink( $dir . '/' . $obj ) ) {
				unlinkRecursive( $dir.'/'.$obj, true );
			}
		}
		
		closedir($dh);
		
		if ( $delete_root_too ) {
			@rmdir( $dir );
		}
		
		return;
	}
	
	/**
	 * Re-usable checking all interface to use in both Import/Export
	 */
	
	function check_all_ui() {
		?>
		<div class="layers-stylekit-import-check-actions">
			<a class="layers-stylekit-import-uncheck-all"><?php _e( 'Un-check All', 'layerswp' ) ?></a><a class="layers-stylekit-import-check-all"><?php _e( 'Check All', 'layerswp' ) ?></a>
		</div>
		<?php
	}
	
	/**
	 * Pretty Print json
	 *
	 * Helper to pretty print JSON
	 *
	 * @param  string $json Un-Pretty Json
	 * @return string       Pretty Json
	 */
	
	function prettyPrint( $json ) {
		
		$result = '';
		$level = 0;
		$in_quotes = false;
		$in_escape = false;
		$ends_line_level = NULL;
		$json_length = strlen( $json );

		for( $i = 0; $i < $json_length; $i++ ) {
			$char = $json[$i];
			$new_line_level = NULL;
			$post = "";
			if( $ends_line_level !== NULL ) {
				$new_line_level = $ends_line_level;
				$ends_line_level = NULL;
			}
			if ( $in_escape ) {
				$in_escape = false;
			} else if( $char === '"' ) {
				$in_quotes = !$in_quotes;
			} else if( ! $in_quotes ) {
				switch( $char ) {
					case '}': case ']':
						$level--;
						$ends_line_level = NULL;
						$new_line_level = $level;
						break;
					case '{': case '[':
						$level++;
					case ',':
						$ends_line_level = $level;
						break;
					case ':':
						$post = " ";
						break;
					case " ": case "\t": case "\n": case "\r":
						$char = "";
						$ends_line_level = $new_line_level;
						$new_line_level = NULL;
						break;
				}
			} else if ( $char === '\\' ) {
				$in_escape = true;
			}
			if( $new_line_level !== NULL ) {
				$result .= "\n".str_repeat( "\t", $new_line_level );
			}
			$result .= $char.$post;
		}

		return $result;
	}
	
	/**
	 * Register StyleKit post type
	 */
	public function register_post_type() {
		
		register_post_type(
			'layers_stylekits',
			array(
				'labels' => array(
					'name' => 'StyleKits',
					'singular_name' => 'StyleKit',
					'add_new' => 'Add New',
					'add_new_item' => 'Add New StyleKit',
					'edit' => 'Edit',
					'edit_item' => 'Edit StyleKit',
					'new_item' => 'New StyleKit',
					'view' => 'View',
					'view_item' => 'View StyleKit',
					'search_items' => 'Search StyleKits',
					'not_found' => 'No StyleKits found',
					'not_found_in_trash' => 'No StyleKits found in Trash',
					'parent' => 'Parent StyleKit'
				),
				'public' => true,
				'menu_position' => 15,
				//'supports' => array( 'title', 'editor', 'comments', 'thumbnail', 'custom-fields' ),
				'supports' => array( 'title', 'editor', 'custom-fields' ),
				'taxonomies' => array(),
				//'menu_icon' => plugins_url( 'images/image.png', __FILE__ ),
				'has_archive' => true
			)
		);
	}
	
	/**
	 * ------------------------------------------------------------------
	 *
	 *
	 *
	 *
	 *
	 *
	 *                          MAIN INTERFACE
	 *
	 *
	 *
	 *
	 *
	 *
	 * ------------------------------------------------------------------
	 */
	
	function layers_stylekit_manager_page() {
		
		$tabs = array(
			'layers-stylekit-import' => __( 'Import' , 'layerswp' ),
			'layers-stylekit-export' => __( 'Export' , 'layerswp' ),
		);

		$current_tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : FALSE ;
		$current_step = ( isset( $_GET['step'] ) ) ? $_GET['step'] : FALSE ;
		
		include( get_template_directory() . '/core/stylekit-manager/partials/main.php' );
	}
	
	/**
	 * ------------------------------------------------------------------
	 *
	 *
	 *
	 *
	 *
	 *
	 *                            E X P O R T
	 *
	 *
	 *
	 *
	 *
	 *
	 * ------------------------------------------------------------------
	 */
	
	/**
	 * Ajax for Export Child Theme
	 */
	
	public function layers_stylekit_export_ajax(){
		
		//if( !check_ajax_referer( 'layers-backup-pages', 'layers_backup_pages_nonce', false ) ) die( 'You threw a Nonce exception' ); // Nonce
		//if( ! isset( $_POST[ 'pageid' ] ) ) wp_die( __( 'You shall not pass' , 'layerswp' ) );
		
		// Get the settings json.
		$stylekit_json = $this->get_settings_json();
		
		/**
		 * Pages
		 */
		
		// Start preset page bucket
		$stylekit_pages = array();
		
		if ( isset( $_POST['layers_pages'] ) ) {
			
			$chosen_pages = ( isset( $_POST['layers_pages'] ) ) ? $_POST['layers_pages'] : array() ;
			
			$builder_pages = layers_get_builder_pages();
			
			$theme_name = esc_html( str_replace( ' ' , '_' , strtolower( get_bloginfo( 'name' ) ) ) );
			$theme_lang_slug = 'layers-' . esc_html( str_replace( ' ' , '-' , strtolower( get_bloginfo( 'name' ) ) ) );
			
			foreach ( $builder_pages as $page ) {
				if ( in_array( $page->ID, $chosen_pages ) ) {
					
					//$preset_name = $theme_name . '-' . $page->post_name;
					//$post_title = esc_html( get_bloginfo( 'name' ) . '-' . esc_attr( $page->post_title ) );
					
					$stylekit_pages[ $page->post_name ] = array(
						'post_title' => esc_html( get_bloginfo( 'name' ) . '-' . esc_attr( $page->post_title ) ),
						'widget_data' => $this->migrator->export_data( $page ),
					);
				}
			}
		}
		
		// Ready for us to be able to access filestytem and grab the images.
		$this->migrator->init_filesystem();
		
		// Check that the user has write permission on a folder
		$access_type = get_filesystem_method();
		if ( $access_type === 'direct' ) {
			
			/* you can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL */
			$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );

			/* initialize the API */
			if ( ! WP_Filesystem( $creds ) ) {
				/* any problems and we exit */
				return false;
			}
			
			// echo 'you can write files!';
			global $wp_filesystem;
		}
		else {
			
			/* don't have direct write access. Prompt user with our notice */
			add_action( 'admin_notice', "You don't have the file writing permession that you need create this zip" );
		}
		
		$zip_name = isset( $_POST[ 'layers-stylekit-name' ] ) ? $_POST[ 'layers-stylekit-name' ] : str_replace( ' ' , '-' , get_bloginfo( 'name' ) ) /* incase input is emptied by mistake */ ;
		$zip_name = sanitize_title_with_dashes( $zip_name );
		$zip_file_name = "{$zip_name}.zip";
		
		// $upload_dir = wp_upload_dir(); // "wp-content/2015/07/"
		// $upload_base_dir = trailingslashit( $upload_dir['basedir'] ); // "wp-content"
		
		$upload_base_dir = trailingslashit( WP_CONTENT_DIR );
		
		/* replace the 'direct' absolute path with the Filesystem API path */
		$export_path = "{$upload_base_dir}/upgrade/{$zip_name}/";

		/* Now we can use $plugin_path in all our Filesystem API method calls */
		if( ! $wp_filesystem->is_dir( $export_path ) ) {
			
			/* directory didn't exist, so let's create it */
			$wp_filesystem->mkdir( $export_path );
		}
		
		// Add Extra Info to the JSON
		global $wp_version;
		$stylekit_json[ 'info' ] = array();
		$stylekit_json[ 'info' ][ 'layers-version' ] = LAYERS_VERSION;
		$stylekit_json[ 'info' ][ 'php-version' ] = phpversion();
		$stylekit_json[ 'info' ][ 'wp-version' ] = $wp_version;
		
		// Prettyfy the JSON
		//$stylekit_json = $this->prettyPrint( json_encode( $stylekit_json ) );
		
		// Prep stylekit.json
		$json_file_name = "stylekit.json";
		$wp_filesystem->put_contents( "{$export_path}{$json_file_name}", json_encode( $stylekit_json ) ); // Finally, store the file :)
		$files_to_zip[ "{$zip_name}/{$json_file_name}" ] = "{$export_path}{$json_file_name}";
		
		// Prep pages .json files
		if ( !empty( $stylekit_pages ) ) {
			foreach ( $stylekit_pages as $page_preset_key => $page_preset_value ) {
				
				// Prettyfy the JSON
				//$widget_data = $this->prettyPrint( json_encode( $page_preset_value['widget_data'] ) );
				$widget_data = json_encode( $page_preset_value['widget_data'] );
				
				//post_title, widget_data
				$page_file_name = "{$page_preset_key}.json";
				$wp_filesystem->put_contents( "{$export_path}{$page_file_name}", $widget_data );
				$files_to_zip[ "{$zip_name}/{$page_file_name}" ] = "{$export_path}{$page_file_name}";
			}
		}
		
		// Prep image files
		if ( isset( $this->migrator->images_collected ) ) {
			
			// if ( !$wp_filesystem->is_dir( $export_path . 'assets/' ) ) $wp_filesystem->mkdir( $export_path . 'assets/' );
			// if ( !$wp_filesystem->is_dir( $export_path . 'assets/images/' ) ) $wp_filesystem->mkdir( $export_path . 'assets/images/' );
			
			foreach ( $this->migrator->images_collected as $image_collected ) {
				
				$image_pieces = explode( '/', $image_collected['url'] );
				$image_file_name = $image_pieces[count($image_pieces)-1];
				$files_to_zip["{$zip_name}/assets/images/{$image_file_name}"] = $image_collected['path'];
			}
		}
		
		// Clear older versions of this export
		//$wp_filesystem->delete( "{$export_path}{$zip_name}.zip" );
		//$wp_filesystem->delete( "{$export_path}{$zip_name}" );
		
		// If true, good; if false, zip creation failed
		$zip_file = $this->create_zip( $files_to_zip, "{$export_path}{$zip_file_name}" );
		
		// Fake files array
		$file_array = array(
			'name'     => $zip_file_name, //"layers10-NEW.zip"
			'type'     => 'application/zip', //"application/octet-stream"
			'tmp_name' => $zip_file, //"C:\wamp\tmp\php3978.tmp"
		);
		
		// Allow uploading of .zip type files
		add_filter( 'upload_mimes', array( $this, 'add_allowed_mimes' ) );
		
		// Upload the file
		$id = media_handle_sideload( $file_array, 0 );
		
		// Delete the temp files @TODO - clear out all the temp files
		//$wp_filesystem->delete( "{$export_path}" );
		$this->delete_recursive( "{$export_path}", TRUE );

		// send the file' url as response
		if( is_wp_error( $id ) ) {
			$response['status'] = 'error';
			$response['error'] = $id->get_error_messages();
		}
		else {
			$response['status'] = 'success';
			$src = get_attached_file( $id );
			$response['attachment'] = array();
			$response['attachment']['id'] = $id;
			$response['attachment']['src'] = $src;
		}
		
		// Prep the download URI
		$download_uri = wp_get_attachment_url( $id );
		
		// Collect the interface.
		ob_start();
		?>
		
		<div class="layers-row">
			
			<div class="layers-column layers-span-8 layers-panel">
			
				<div class="layers-row">
					
					<div class="layers-column layers-span-4 layers-content">
							
						<div class="stylekit-statement-holder">
							<i class="layers-button-icon-dashboard layers-stylekit-icon"></i>
						</div>
						
					</div>
					<div class="layers-column layers-span-8 layers-content">
						
						<div class="stylekit-statement">
							
							<div class="layers-section-title layers-small">
								<h3 class="layers-heading"><?php _e( 'Your StyleKit is ready!', 'layerswp' ) ?></h3>
							</div>
							
							<div class="layers-panel layers-push-bottom" style="/*display: none;*/">
								<ul class="layers-list">
									<?php
									if ( isset( $stylekit_json['settings'] ) ) {
										?>
										<li class="tick ticked-all"><?php _e( 'Settings', 'layerswp' ) ?></li>
										<?php
									}
									
									if ( !empty( $stylekit_pages ) ) {
										?>
										<li class="tick ticked-all"><?php count( $stylekit_pages ) ?> <?php echo esc_html( __( 'Pages', 'layerswp' ) ); ?></li>
										<?php
									}
									
									if ( isset( $stylekit_json['css'] ) ) {
										?>
										<li class="tick ticked-all"><?php _e( 'Custom CSS', 'layerswp' ); ?></li>
										<?php
									}
									?>
								</ul>
							</div>
							
							<a class="layers-button btn-large btn-primary layers-pull-right-NOT" download="<?php echo $zip_file_name ?>" href="<?php echo $download_uri ?>" >
								<?php _e( 'Download StyleKit' , 'layerswp' ) ?>
							</a>
							
						</div>
					</div>
				</div>
		
			</div>
			<div class="layers-column layers-span-4 no-gutter">
				<div class="layers-content">
					<!-- Your helpful tips go here -->
					<ul class="layers-help-list">
						<li>
							<?php _e( 'If you\'re ever stuck or need help with your Layers site please visit our <a href="http://docs.layerswp.com" rel="nofollow">helpful documentation.</a>', 'layerswp' ) ?>
						</li>
						<li class="pro-tip">
							<?php _e( 'For the Pros: Layers will automatically assign the tagline to Settings → General.', 'layerswp' ) ?>
						</li>
					</ul>
				</div>
			</div>
		
		</div>
		
		<?php if ( FALSE ) : ?>
		<form id="layers-stylekit-export-json-results" class="layers-stylekit-form" action=""  method="post">
			
			<div class="layers-row layers-push-top">
				
				<div class="layers-column layers-span-4 layers-content">
					<h3><?php _e( 'Your StyleKit is ready!', 'layerswp' ) ?></h3>
					<p><?php _e( 'Simply copy &amp; paste this StyleKit code into the StyleKit Import and proceed.', 'layerswp' ) ?></p>
				</div>
				
				<div class="layers-column layers-span-8 layers-content">
			
					<div class="json-code">
<textarea>
<?php
if ( !empty( $stylekit_json ) ) {
echo esc_attr( json_encode( $stylekit_json ) );
}
?></textarea>
					</div>
					
				</div>
			</div>
		</form>
		<?php
		endif;
		
		$ui = ob_get_clean();
		
		// Return
		echo json_encode( array(
			'download_uri'         => $download_uri,
			'ui'                   => $ui,
			'stylekit_json'        => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
		) );
		
		die();
	}
	
	public function get_settings_json( $backup_all_settings = FALSE ){
		
		// Start preset page bucket
		$stylekit_json = array();
		
		/**
		 * Settings
		 */
		
		if ( isset( $_POST['layers_settings_groups'] ) || $backup_all_settings ) {
			
			$sections_to_get = array();
			
			if ( $backup_all_settings ) {
				
				foreach ( $this->control_groups as $control_group_key => $control_group ) {
					$sections_to_get = array_merge( $control_group[ 'contains' ], $sections_to_get );
				}
			}
			elseif ( isset( $_POST['layers_settings_groups'] ) ) {
				
				$chosen_settings_groups = $_POST['layers_settings_groups'];
			
				foreach ( $chosen_settings_groups as $chosen_settings_group ) {
					$sections_to_get = array_merge( $this->control_groups[ $chosen_settings_group ][ 'contains' ], $sections_to_get );
				}
			}
			
			$controls = $this->get_controls( array(
				'sections' => $sections_to_get,
				'exclude_types' => $this->controls_to_exclude,
			) );
			
			if ( !empty( $controls ) ) {
				
				$stylekit_json['settings'] = array();
				
				foreach ( $controls as $control_key => $control ) {
					
					// @TODO: write a get field data function that does all this
					// @TODO: perhaps also a get_field_name that looks at type and gets either the lable or subtitle as a result
					
					$name = '';
					if ( isset( $control['subtitle'] ) ) $name = $control['subtitle'];
					if ( '' == $name && isset(  $control['label'] ) ) $name = $control['label'];
					
					$stylekit_json['settings'][ LAYERS_THEME_SLUG . '-' . $control_key ] = array(
						'title'   => $name,
						'type'    => $control['type'],
						'value'   => layers_get_theme_mod( $control_key, FALSE ),
						'default' => layers_get_default( $control_key ),
					);
				}
			}
			
		}
		
		/**
		 * CSS
		 */
		
		if ( isset( $_POST['layers_css'] ) || $backup_all_settings ) {
			
			$stylekit_json['css'] = layers_get_theme_mod( 'custom-css' );
		}
		
		// Return the json.
		return $stylekit_json;
	}
	
	/**
	 * Creates a compressed zip file
	 *
	 * @param  array   $files       [description]
	 * @param  string  $destination [description]
	 * @param  boolean $overwrite   [description]
	 * @return [type]               [description]
	 */
	function create_zip( $files = array(), $destination = '', $overwrite = false ) {
		
		//if the zip file already exists and overwrite is false, return false
		if( file_exists( $destination ) && !$overwrite ) { return false; }
		
		//vars
		$valid_files = array();
		
		//if files were passed in...
		if( is_array( $files ) ) {
			
			//cycle through each file
			foreach( $files as $file_destination => $file_source ) {
				
				//make sure the file exists
				if( file_exists( $file_source ) ) {
					
					$valid_files[ $file_destination ] = $file_source;
				}
			}
		}
		
		//if we have good files...
		if( count( $valid_files ) ) {
			
			//create the archive
			$zip = new ZipArchive();
			
			if( $zip->open( $destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE ) !== true ) {
				return false;
			}
			
			//add the files
			foreach( $valid_files as $file_destination => $file_source ) {
				
				$zip->addFile( $file_source, $file_destination );
			}
			
			//close the zip -- done!
			$zip->close();
			
			if ( file_exists( $destination ) ) {
				// if file exists then return it's location.
				return $destination;
			}
			else{
				return FALSE;
			}
		}
		else {
			return false;
		}
	}
	
	/**
	 * ------------------------------------------------------------------
	 *
	 *
	 *
	 *
	 *
	 *
	 *                            I M P O R T
	 *
	 *
	 *
	 *
	 *
	 *
	 * ------------------------------------------------------------------
	 */
	
	/**
	 * File Upload Settings
	 */
	
	function file_upload_settings() {
		
		$uploader_options = array(
			'runtimes'          => 'html5,silverlight,flash,html4',
			'browse_button'     => 'layers-stylekit-drop-uploader-ui-button',
			'container'         => 'layers-stylekit-drop-uploader-ui',
			'drop_element'      => 'layers-stylekit-drop-uploader-ui',
			'file_data_name'    => 'async-upload',
			'multiple_queues'   => true,
			'max_file_size'     => wp_max_upload_size() . 'b',
			'url'               => admin_url( 'admin-ajax.php' ),
			'flash_swf_url'     => includes_url( 'js/plupload/plupload.flash.swf' ),
			'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
			'filters'           => array(
			   array(
				  'title' => __( 'Allowed Files', 'layerswp' ),
				  'extensions' => '*',
			   )
			),
			'multipart'         => true,
			'urlstream_upload'  => true,
			'multi_selection'   => true,
			'multipart_params' => array(
				'_ajax_nonce' => '',
				'action'      => 'layers_stylekit_upload_ajax'
			)
		);
		?>
		<script type="text/javascript">
			var layers_stylekit_uploader_options=<?php echo json_encode( $uploader_options ); ?>;
		</script>
		<?php
	}
	
	/**
	 * Change File Upload Mime Types
	 */
	
	function add_allowed_mimes( $mimes ) {
		$mimes['zip'] = 'application/zip';
		return $mimes;
	}
	
	/**
	 * File Upload Ajax
	 */
	
	function layers_stylekit_upload_ajax() {
		
		// check ajax nonce
		//check_ajax_referer( __FILE__ );

		$response = array();
		
		// Allow uploading of .zip files
		add_filter( 'upload_mimes', array( $this, 'add_allowed_mimes' ) );

		// handle file upload
		$id = media_handle_upload( 'async-upload', 0, array(
			'test_form' => true,
			'action'    => 'layers_stylekit_upload_ajax',
		) );

		// send the file' url as response
		if( is_wp_error( $id ) ) {
			$response['status'] = 'error';
			$response['error'] = $id->get_error_messages();
		} else {
			$response['status'] = 'success';
			
			$src = get_attached_file( $id );
			$response['attachment'] = array();
			$response['attachment']['id'] = $id;
			$response['attachment']['src'] = $src;
		}
		
		echo json_encode( $response );
		
		die();
	}
	
	/**
	 * AJAX handler for updating a plugin.
	 *
	 * @since 4.2.0
	 *
	 * @see Plugin_Upgrader
	 */
	
	function layers_stylekit_zip_unpack_ajax() {
		
		// Security
		//check_ajax_referer( 'updates' );
		
		// Get the location of the $package .zip
		$package = urldecode( $_POST['package'] );
		
		include_once( LAYERS_TEMPLATE_DIR . '/core/stylekit-manager/classes/class-stylekit-upgrader.php' );
		
		// $current = get_site_transient( 'update_plugins' );
		// if ( empty( $current ) ) {
		// 	wp_update_plugins();
		// }
		
		$upgrader = new StyleKit_Importer_Upgrader();
		
		$result = $upgrader->install( $package, array(
			'clear_update_cache' => true,
		) );
		
		if ( is_array( $result ) ) {
			$unpack_results = $this->layers_stylekit_import_options_interface( array(
				'source' => $result['source'],
				'name'   => basename( $result['source'], "" ),
			) );
			$result = wp_parse_args( $result, $unpack_results );
			wp_send_json_success( $result );
		}
		else if ( is_wp_error( $result ) ) {
			$status['error'] = $result->get_error_message();
			wp_send_json_error( $status );
		}
		else if ( is_bool( $result ) && FALSE == $result ) {
			
			// A general error feedback to cover any case.
			$status['errorCode'] = 'unable_to_connect_to_filesystem-NEW';
			$status['error'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'layerswp' );
			wp_send_json_error( $status );
		}
	}
	
	function layers_stylekit_import_options_interface( $args ) {
		
		global $wp_filesystem;
		
		/**
		 * Checks - to see we're good to proceed.
		 */
		
		// Initialize the WP filesystem if not yet
		if ( empty( $wp_filesystem ) ) {
			require_once ( ABSPATH . '/wp-admin/includes/file.php' );
			WP_Filesystem();
		}
		
		$source = str_replace( '\\', '/', $args['source'] );
		$wp_content_dir = str_replace('\\', '/', trailingslashit( WP_CONTENT_DIR ) );
		$wp_content_url = str_replace('\\', '/', trailingslashit( WP_CONTENT_URL ) );
		
		// Get the Path and URL of the Temp directory
		$temp_directory_path = $source;
		$temp_directory_url = str_replace( $wp_content_dir, $wp_content_url, $source );
		
		// Check if the above str_replace works.
		if ( ! is_dir( $temp_directory_path ) ) {
			return $temp_directory_path;
		}

		// A proper StyleKit should have at least a stylekit.json file in the single subdirectory.
		if ( ! file_exists( $temp_directory_path . 'stylekit.json' ) ){
			return new WP_Error( 'incompatible_stylekit_no_json', __('The package could not be installed.'), __( 'The StyleKit is missing the stylekit.json file.', 'layerswp' ) );
		}
		
		// Get StyleKit Json
		$stylekit_content = file_get_contents( $temp_directory_path . 'stylekit.json' );
		$stylekit_json = json_decode( $stylekit_content, TRUE );
		
		// Get Page Json
		$files = scandir( $temp_directory_path );
		$page_files = array();
		
		$reserved_files = array( 'stylekit.json' );
		
		foreach ( $files as $file ) {
			if ( is_file( $temp_directory_path . $file ) && !in_array( $file, $reserved_files ) &&  rtrim( $file, '.json' ) !== $file ) {
				$page_files[ rtrim( $file, '.json' ) ] = array( 'page-data' => json_decode( file_get_contents( $temp_directory_path . $file ), TRUE ) );
			}
		}
		if ( !empty( $page_files ) ) {
			$stylekit_json['pages'] = $page_files;
		}
		
		
		/**
		 * Internal Data
		 *
		 * Data that will be added to the StyleKit json as it steps through the various ajax calls.
		 */
		$stylekit_json['internal_data'] = array();
		
		// Image locations - to search for images in
		$stylekit_json['internal_data']['image_locations'] = array(
			array(
				'path' => $temp_directory_path . 'assets/images/',
				'url'  => $temp_directory_url . 'assets/images/',
			)
		);
		
		// Images - to use later to import images
		$image_assets_path = $temp_directory_path . 'assets/images/';
		$image_assets_url = $temp_directory_url . 'assets/images/';
		$image_array = array();
		if ( is_dir( $image_assets_path ) ) {
			if ( $dh = opendir( $image_assets_path ) ) {
				while ( ( $file = readdir( $dh ) ) !== false ) {
					if ( "." != $file && ".." != $file ) {
						$image_array[$file] = array( 'url' => $image_assets_url . $file );
					}
				}
				closedir( $dh );
			}
		}
		if ( !empty( $image_array ) ) {
			$stylekit_json['internal_data']['images_on_disk'] = $image_array;
		}
		
		// Put the file information in.
		$stylekit_json['internal_data']['zip_folder_name'] = $args['name'];
		
		
		$post_id = $this->layers_stylekit_save_over_stylekit( $stylekit_json, 'processing' );
		
		
		/**
		 * Get Advanced Options UI. the seocnd one.
		 */
		
		ob_start();
		?>
		
		<form class="layers-stylekit-form layers-stylekit-form-import" method="post" action="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_manager', 'step' => 'layers-stylekit-import-step-3' ), get_admin_url() . 'admin.php' ) ?>">
			
			<div class="layers-stylekit-import-choices">
			
				<div class="layers-stylekit-import-choices-holder">
				
					<?php if ( isset( $stylekit_json['settings'] ) ) { ?>
					
						<div class="layers-row layers-push-top">
							
							<div class="layers-column layers-span-4 layers-content">
								<h3 class="layers-heading"><?php _e( 'Settings', 'layerswp' ) ?></h3>
								<p class="layers-excerpt"><?php _e( 'Be aware that unchecking these may chnange the intended look from this StyleKit', 'layerswp' ) ?></p>
								<?php $this->check_all_ui(); ?>
							</div>
							
							<div class="layers-column layers-span-8 layers-content">
								
								<div class="layers-panel layers-no-push-bottom layers-stylekit-select-group">
									
									<ul class="layers-list layers-list-complex layers-list-stylekit-settings" data-layers-link="tick-settings" >
										
										<?php
										foreach ( $this->control_groups as $control_group_key => $control_group ) {
											?>
											<li>
												<label>
													<input id="<?php echo $control_group_key; ?>" type="checkbox" checked="checked" name="layers_settings_groups[]" <?php if( isset( $_POST[ 'layers_settings_groups' ] ) ) checked( in_array( $control_group_key, $_POST[ 'layers_settings_groups' ] ), TRUE ); ?> value="<?php echo $control_group_key; ?>" >
													<?php echo $control_group['title']; ?>
												</label>
											</li>
											<?php
										}
										?>
									
									</ul>
									
								</div>
							</div>
						</div>
					
					<?php } ?>
					
					<?php
					// Create builder pages dropdown.
					if ( isset( $stylekit_json['pages'] ) ) {
						?>
						
						<div class="layers-row layers-push-top">
							
							<div class="layers-column layers-span-4 layers-content">
								<h3 class="layers-heading"><?php _e( 'Pages', 'layerswp' ) ?></h3>
								<p class="layers-excerpt"><?php _e( 'These pages will be imported', 'layerswp' ) ?></p>
								<?php $this->check_all_ui(); ?>
							</div>
							
							<div class="layers-column layers-span-8 layers-content">
								<div class="layers-panel layers-no-push-bottom layers-stylekit-select-group">
								
									<ul class="layers-list layers-list-complex layers-list-stylekit-pages"  data-layers-link="tick-pages">
										<?php foreach( $stylekit_json['pages'] as $page_slug => $page ) { ?>
											<li>
												<label>
													<input id="page-<?php echo $page_slug ?>" type="checkbox" checked="checked" name="layers_pages[]" value="<?php echo $page_slug ?>" >
													<?php echo $page_slug ?>
												</label>
											</li>
										<?php } ?>
									</ul>
								
								</div>
							</div>
							
						</div>
						
					<?php }	?>
					
					<?php if ( isset( $stylekit_json['css'] ) ) { ?>
					
						<div class="layers-row layers-push-top">
							
							<div class="layers-column layers-span-4 layers-content">
								<h3 class="layers-heading"><?php _e( 'CSS', 'layerswp' ) ?></h3>
								<p class="layers-excerpt"><?php _e( "This will add your CSS in a commented block of it's own dedicated to StyleKits, and will be overwritten by any other StyleKit you import. So your you hand coded initial CSS is protected at all time.", 'layerswp' ) ?></p>
								<?php $this->check_all_ui(); ?>
							</div>
							
							<div class="layers-column layers-span-8 layers-content">
								<div class="layers-panel layers-no-push-bottom layers-stylekit-select-group">
								
									<ul class="layers-list layers-list-complex layers-list-stylekit-css" data-layers-link="tick-css" >
										
										<li>
											<label>
												<input id="css-check" type="checkbox" checked="checked" name="layers_css" value="yes">
												<?php _e( 'CSS', 'layerswp' ) ?>
											</label>
										</li>
									
									</ul>
									
								</div>
							</div>
							
						</div>
						
					<?php } ?>
				
				</div>
				
			</div>
			
			<div class="layers-button-well">
				<input type="submit" class="layers-button btn-large btn-primary layers-pull-right layers-stylekit-import-step-2-submit" value="Import StyleKit" >
			</div>
			
			<!-- Required Textarea -->
			<!-- <div class="layers-row layers-push-top layers-hide">
				<div class="layers-column layers-span-12 layers-content">
					<div class="json-code">
						<textarea name="layers-stylekit-import-stylekit"><?php echo json_encode( $stylekit_json ); ?></textarea>
					</div>
				</div>
			</div> -->
			
		</form>
		
		<?php
		$ui = ob_get_clean();
		
		
		/**
		 * Get Adviced Options UI. the first one.
		 */
		
		ob_start();
		?>
		
		<?php if ( isset( $stylekit_json['settings'] ) || isset( $stylekit_json['pages'] ) || isset( $stylekit_json['css'] ) ) { ?>
		
			<div class="layers-row layers-stylekit-import-main-graphic">
			
				<div class="layers-column layers-span-4 layers-content">
					
					<div class="stylekit-statement-holder">
						<i class="layers-button-icon-dashboard layers-stylekit-icon"></i>
					</div>
					
				</div>
				<div class="layers-column layers-span-8 layers-content">
					
					<div class="stylekit-statement">
						
						<div class="layers-section-title layers-small">
							<h3 class="layers-heading"><?php _e( 'StyleKit <em>Three.zip</em>', 'layerswp' ) ?></h3>
						</div>
						
						<div class="layers-panel layers-push-bottom">
							<ul class="layers-list">
								
								<?php if ( isset( $stylekit_json['settings'] ) ) { ?>
									<li class="tick ticked-all" id="tick-settings">Settings</li>
								<?php } ?>
								
								<?php if ( isset( $stylekit_json['pages'] ) ) { ?>
									<li class="tick ticked-all" id="tick-pages"><?php echo count( $stylekit_json['pages'] ); ?> Pages</li>
								<?php } ?>
								
								<?php if ( isset( $stylekit_json['css'] ) ) { ?>
									<li class="tick ticked-all" id="tick-css">Custom CSS</li>
								<?php } ?>
								
							</ul>
						</div>
						
						<p class="layers-excerpt">
							<label>
								<input type="checkbox" name="layers-stylekit-import-all" value="yes" <?php checked( true, true ); ?> >
								<?php _e( 'Confirm import all <span class="hidden-choice">or untick to customize</span>', 'layerswp' ) ?>
							</label>
						</p>
						
						<!-- <input type="hidden" name="layers-stylekit-temp-directory" value="<?php echo $source; ?>"> -->
						
					</div>
				
				</div>
				
			</div>
			
		<?php } else { ?>
		
			<div class="layers-row layers-stylekit-import-main-graphic">
			
				<div class="layers-column layers-span-4 layers-content">
					
					<div class="stylekit-statement-holder">
						<i class="layers-button-icon-dashboard layers-stylekit-icon"></i>
					</div>
					
				</div>
				<div class="layers-column layers-span-8 layers-content">
					
					<div class="stylekit-statement">
						
						<div class="layers-section-title layers-small">
							<h3 class="layers-heading"><?php _e( 'This StyleKit is empty :(', 'layerswp' ) ?></h3>
						</div>
						
					</div>
				
				</div>
				
			</div>
		
		<?php } ?>
		
		<?php
		$ui2 = ob_get_clean();
		
		return array(
			'ui'            => $ui,
			'ui2'           => $ui2,
			'stylekit_json' => $stylekit_json,
		);
	}
	
	/**
	 * Import - Modify / Filter the stylkit json
	 *
	 * Receives the the posted stylekit json and the chosen options from that
	 * stylekit and filters it so that only the chosen options prevail.
	 */
	function layers_stylekit_import_step_2_ajax() {
		
		// Get and decode json.
		// $stylekit_json = ( isset( $_POST['layers-stylekit-import-stylekit'] ) ) ? stripslashes( $_POST['layers-stylekit-import-stylekit'] ) : '' ;
		// $stylekit_json = json_decode( $stylekit_json, TRUE );
		
		//$stylekit_json = ( isset( $_POST['stylekit_json'] ) ) ? $_POST['stylekit_json'] : array() ;
		
		$stylekit_json = $this->layers_stylekit_get_processing_stylekit();
		
		/**
		 * Settings
		 */
		
		$filtered_settings = array();
		
		// Modify json so only the chosen previal.
		if ( isset( $stylekit_json['settings'] ) && ( isset( $_POST['layers_settings_groups'] ) || isset( $_POST['layers-stylekit-import-all'] ) ) ) {
			
			$get_sections = array();
			foreach ( $this->control_groups as $control_group_key => $control_group ) {
				
				if (
					( isset( $_POST['layers_settings_groups'] ) && in_array( $control_group_key, $_POST['layers_settings_groups'] ) )
					||
					isset( $_POST['layers-stylekit-import-all'] )
				) {
					$get_sections = array_merge( $control_group['contains'], $get_sections );
				}
			}
			
			$get_controls = $this->get_controls( array( 'sections' => $get_sections ) );
			foreach ( $stylekit_json['settings'] as $setting_key => $setting ) {
				if ( array_key_exists( str_replace( LAYERS_THEME_SLUG . '-', '', $setting_key ), $get_controls ) ) {
					$filtered_settings[ $setting_key ] = $setting;
				}
			}
			
			$stylekit_json['settings'] = $filtered_settings;
		}
		
		// Unset if none are chosen
		if ( empty( $filtered_settings ) ) unset( $stylekit_json['settings'] );
		
		
		/**
		 * Pages
		 */
		
		$filtered_pages = array();
		
		// Modify json so only the chosen previal.
		if ( isset( $stylekit_json['pages'] ) && ( isset( $_POST['layers_pages'] ) || isset( $_POST['layers-stylekit-import-all'] ) ) ) {
			
			foreach ( $stylekit_json['pages'] as $page_slug => $page_data ) {
				if ( isset( $_POST['layers-stylekit-import-all'] ) || in_array( $page_slug, $_POST['layers_pages'] ) ) {
					
					$filtered_pages[ $page_slug ] = $page_data;
				}
			}
			
			$stylekit_json['pages'] = $filtered_pages;
		}
		
		// Unset if none are chosen
		if ( empty( $filtered_pages ) ) unset( $stylekit_json['pages'] );
		
		
		/**
		 * Custom CSS
		 */
		
		// Unset if none are chosen
		if ( isset( $stylekit_json['css'] ) && ( isset( $_POST['layers-stylekit-import-all'] ) || isset( $_POST['layers_css'] ) ) ) {
		}
		else {
			unset( $stylekit_json['css'] );
		}
		
		
		$post_id = $this->layers_stylekit_save_over_stylekit( $stylekit_json, 'processing' );
		
		
		// Return the StyleKit json
		echo json_encode( array(
			'stylekit_json' => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
		) );
		
		die();
	}
	
	/**
	 * Import - Settings & Custom CSS
	 *
	 * Receives the the posted stylekit json and saves the settings and custom css.
	 */
	public function layers_stylekit_import_step_3_ajax() {
		
		// Get and decode json.
		//$stylekit_json = isset( $_POST['stylekit_json'] ) ? $_POST['stylekit_json'] : array() ;
		$stylekit_json = $this->layers_stylekit_get_processing_stylekit();
		
		// Backup current settings.
		// Get the current settings json so we can back it up.
		$current_setings_json = $this->get_settings_json( TRUE );
		$this->layers_stylekit_save_stylekit( $current_setings_json, 'backup' );
		
		// Save the Settings & CSS.
		$stylekit_json = $this->import_stylekit( $stylekit_json );
		
		
		$post_id = $this->layers_stylekit_save_over_stylekit( $stylekit_json, 'processing' );
		
		
		// Return the StyleKit JSON
		echo json_encode( array(
			'stylekit_json' => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
		) );
		
		die();
	}
	
	public function layers_stylekit_import_step_4_ajax() {
		
		//$stylekit_json = ( isset( $_POST['stylekit_json'] ) ) ? $_POST['stylekit_json'] : array() ;
		$stylekit_json = $this->layers_stylekit_get_processing_stylekit();
		
		/**
		 * Pages
		 */
		
		// If there are pages in the StyleKit and user has chosen to import some.
		if ( isset( $stylekit_json['pages'] ) ) {
			
			// Prep internal data for collection page id's
			if ( !isset( $stylekit_json['internal_data']['page_ids'] ) ) {
				$stylekit_json['internal_data']['page_ids'] = array();
			}
			
			// Set locations to search for images during 'create_builder_page_from_preset'
			if ( isset( $stylekit_json['internal_data']['image_locations'] ) ){
				foreach ( $stylekit_json['internal_data']['image_locations'] as $image_location ) {
					$this->check_image_locations = $image_location;
				}
				add_filter( 'layers_check_image_locations', array( $this, 'check_image_locations' ) );
			}
			
			// Add the pages
			foreach ( $stylekit_json['pages'] as $page_slug => $page_array ) {
				
				if( !isset( $stylekit_json['pages'][$page_slug]['status'] ) ){
					
					// Import the page
					$result = $this->migrator->create_builder_page_from_preset( array(
						'post_title'                      => $page_slug,
						'widget_data'                     => $page_array['page-data'],
						'create_new_image_if_name_exists' => TRUE,
						'download_images'                 => FALSE,
					) );
					
					$stylekit_json['pages'][$page_slug]['status'] = 'done';
					
					$stylekit_json['internal_data']['page_ids'][] = $result['post_id'];
					
					break;
				}
			}
			
			// Populate data into stylekit for next step - importing images
			if ( !empty( $this->migrator->images_in_widgets ) ){
				
				// Get the existing images_in_widgets.
				$images_in_widgets = ( isset( $stylekit_json['internal_data']['images_in_widgets'] ) ) ? $stylekit_json['internal_data']['images_in_widgets'] : array();
				
				// Merge it with new images_in_widgets.
				$images_in_widgets = array_merge( $this->migrator->images_in_widgets, $images_in_widgets );
				
				// Re-set the new images_in_widgets.
				$stylekit_json['internal_data']['images_in_widgets'] = $images_in_widgets;
			}
			
			if ( !empty( $this->migrator->images_report ) ){
				//$stylekit_json['internal_data']['images_report'] = $this->migrator->images_report;
			}
			
		}
		
		
		$post_id = $this->layers_stylekit_save_over_stylekit( $stylekit_json, 'processing' );
		
		
		// Return the StyleKit JSON
		echo json_encode( array(
			'stylekit_json' => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
		) );
		
		die();
	}
	
	public function layers_stylekit_import_step_5_ajax() {
		
		//$stylekit_json = ( isset( $_POST['stylekit_json'] ) ) ? $_POST['stylekit_json'] : array() ;
		$stylekit_json = $this->layers_stylekit_get_processing_stylekit();
		
		/**
		 * Import Images
		 */
		
		$images_in_widgets = ( isset( $stylekit_json['internal_data']['images_in_widgets'] ) ) ? $stylekit_json['internal_data']['images_in_widgets'] : array() ;
		$images_on_disk = ( isset( $stylekit_json['internal_data']['images_on_disk'] ) ) ? $stylekit_json['internal_data']['images_on_disk'] : array() ;
		$pages = ( isset( $stylekit_json['internal_data']['page_ids'] ) ) ? $stylekit_json['internal_data']['page_ids'] : array() ;
			
		// Loop images
		foreach ( $images_in_widgets as $image_name => $image_array ) {
			
			// Debug disable.
			//$stylekit_json['internal_data']['images_on_disk'][$image_name]['status'] = 'done';
			
			if( array_key_exists( $image_name, $images_on_disk ) && !isset( $stylekit_json['internal_data']['images_on_disk'][$image_name]['status'] ) ){
				
				// Upload the image and get the ID.
				$image_id = $this->migrator->get_attachment_id_from_url( media_sideload_image( $images_on_disk[$image_name]['url'], 0 ) );
				
				// Add the new id to an array to use in the following filter.
				if ( !is_array( $this->check_images ) ) $this->check_images = array();
				$this->check_images[$image_name] = array( 'id' => $image_id );
				
				// Add filter to be used during the following Widget mod
				// filter will replace any image of this name with this new ID
				add_filter( 'layers_filter_widgets', array( $this, 'search_and_replace_images' ), 10, 2 );
				
				// Loop through all the pages and modify their widgets
				foreach ( $pages as $page_id ) {
					$this->migrator->process_widgets_in_page( $page_id );
				}
				
				// Mark this image as having been done.
				$stylekit_json['internal_data']['images_on_disk'][$image_name]['status'] = 'done';
				
				// Break so only one image is imported at time.
				break;
			}
			else{
				// Mark this image as having been done.
				$stylekit_json['internal_data']['images_on_disk'][$image_name]['status'] = 'done';
			}
		}
		
		
		$post_id = $this->layers_stylekit_save_over_stylekit( $stylekit_json, 'processing' );
		
		
		// Return the StyleKit JSON
		echo json_encode( array(
			'stylekit_json'        => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
		) );
		
		die();
	}
	
	public function layers_stylekit_import_step_6_ajax() {
		
		//$stylekit_json = ( isset( $_POST['stylekit_json'] ) ) ? $_POST['stylekit_json'] : array() ;
		$stylekit_json = $this->layers_stylekit_get_processing_stylekit();
		
		// Get the most recent stylekit backup - this one we created earlier on in this process.
		$posts = get_posts( array(
			'posts_per_page' => 1,
			'post_type'      => 'layers_stylekits',
			'post_status'    => array( 'publish' ),
			'meta_key'       => 'type',
			'meta_value'     => 'backup',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		
		foreach( $posts as $post ) :
			setup_postdata( $post );
			$backup_stylekit_json = get_post_meta( $post->ID, 'settings_json', TRUE );
			$post_id = $post->ID;
		endforeach;
		
		if ( isset( $stylekit_json['internal_data']['zip_folder_name'] ) ) {
			$backup_stylekit_json['internal_data']['zip_folder_name'] = $stylekit_json['internal_data']['zip_folder_name'];
		}
		if ( isset( $stylekit_json['internal_data']['page_ids'] ) ) {
			$backup_stylekit_json['internal_data']['page_ids'] = $stylekit_json['internal_data']['page_ids'];
		}
		
		// Create a post with the current settings backed up in it's meta.
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => $this->prettyPrint( json_encode( $backup_stylekit_json ) ),
		) );
		
		// Save the settings json to the post.
		update_post_meta( $post_id, 'settings_json', $backup_stylekit_json );
		
		
		ob_start();
		?>
		<div class="layers-row">
			
			<div class="layers-column layers-span-4 layers-content">
					
				<div class="stylekit-statement-holder">
					<i class="layers-button-icon-dashboard layers-stylekit-icon"></i>
				</div>
				
			</div>
			<div class="layers-column layers-span-8 layers-content">
				
				<div class="stylekit-statement">
					
					<div class="layers-section-title layers-small">
						<h3 class="layers-heading"><?php _e( 'StyleKit Imported Successfully', 'layerswp' ) ?></h3>
					</div>
					
					<div class="layers-panel layers-push-bottom">
						<ul class="layers-list">
							
							<?php
							if ( isset( $stylekit_json['settings'] ) ) {
								?>
								<li class="tick ticked-all">
									<?php _e( 'Settings', 'layerswp' ) ?>
								</li>
								<?php
							}
							
							if ( isset( $stylekit_json['internal_data']['page_ids'] ) ) {
								foreach ( $stylekit_json['internal_data']['page_ids'] as $page_id ) {
									
									$title = get_the_title( $page_id );
									$permalink = get_permalink( $page_id );
									?>
									<li class="tick ticked-all layers-stylekit-link">
										<em>"<?php echo $title ?>"</em> <?php _e( 'Page' , 'layerwp' ) ?>
										
										<a class="layers-complex-action preview-page" target="blank" href="<?php echo esc_url( $permalink ) ?>">
											<span><?php _e( 'Preview' , 'layerwp' ) ?></span> <i class=" icon-display"></i>
										</a>
									</li>
									<?php
								}
							}
							
							if ( isset( $stylekit_json['css'] ) ) {
								?>
								<li class="tick ticked-all">
									<?php _e( 'Custom CSS', 'layerswp' ) ?>
								</li>
								<?php
							}
							?>
							
						</ul>
					</div>
					
					<a class="layers-button btn-primary layers-pull-right-NOT" target="blank" href="<?php echo get_home_url(); ?>">
						<?php _e( 'Visit your Site' , 'layerswp' ) ?>
					</a>
					
					<a class="layers-button btn-primary layers-pull-right-NOT" target="blank" href="<?php echo wp_customize_url() ?>">
						<?php _e( 'Customize your Site' , 'layerswp' ) ?>
					</a>
					
					<a class="layers-back-a-step" href="#">
						&#8592; <?php _e( 'Back' , 'layerswp' ) ?>
					</a>
					
				</div>
				
			</div>
		
		</div>
		<?php
		
		$ui = ob_get_clean();
		
		
		$post_id = $this->layers_stylekit_save_over_stylekit( $stylekit_json, 'processing' );
		
		
		// Return the StyleKit JSON
		echo json_encode( array(
			'stylekit_json' => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
			'ui' => $ui,
		) );
		
		die();
	}
	
	public function layers_stylekit_save_stylekit( $stylekit_json = array(), $stylekit_type = 'backup', $post_id = FALSE ) {
		
		$post = array(
			'post_content' => $this->prettyPrint( json_encode( $stylekit_json ) ),
			'post_title'   => 'Processing...',
			'post_status'  => 'publish',
			'post_type'    => 'layers_stylekits',
		);
		
		// If a $post_id was passed then the post will be updated.
		if ( $post_id ) $post['ID'] = $post_id;
		
		// Create a post with the current settings backed up in it's meta.
		$post_id = wp_insert_post( $post );
		
		// Save the settings json to the post.
		update_post_meta( $post_id, 'settings_json', $stylekit_json );
		
		// Save that Type that lets us knwo this is a backup.
		update_post_meta( $post_id, 'type', $stylekit_type );
		
		return $post_id;
	}
	
	public function layers_stylekit_save_over_stylekit( $stylekit_json = array(), $stylekit_type = 'processing' ) {
		
		// Get the most recent stylekit backup - this one we created earlier on in this process.
		$posts = get_posts( array(
			'posts_per_page' => 1,
			'post_type'      => 'layers_stylekits',
			'post_status'    => array( 'publish' ),
			'meta_key'       => 'type',
			'meta_value'     => $stylekit_type,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		
		$post_id = FALSE;
		
		foreach( $posts as $post ) :
			setup_postdata( $post );
			$post_id = $post->ID;
		endforeach;
		
		return $this->layers_stylekit_save_stylekit( $stylekit_json, $stylekit_type, $post_id );
	}
	
	public function layers_stylekit_get_processing_stylekit() {
		
		// Get the most recent stylekit backup - this one we created earlier on in this process.
		$posts = get_posts( array(
			'posts_per_page' => 1,
			'post_type'      => 'layers_stylekits',
			'post_status'    => array( 'publish' ),
			'meta_key'       => 'type',
			'meta_value'     => 'processing',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		
		foreach( $posts as $post ) :
			setup_postdata( $post );
			$stylekit_json = get_post_meta( $post->ID, 'settings_json', TRUE );
			$post_id = $post->ID;
		endforeach;
		
		return $stylekit_json;
	}
	
	public function import_stylekit( $stylekit_json ) {
		
		/**
		 * Settings
		 */
		
		// Import the Settings
		if ( isset( $stylekit_json['settings'] ) ) {
			
			foreach ( $stylekit_json['settings'] as $setting_key => $setting ) {
				
				// Set theme mod
				set_theme_mod( $setting_key, $setting['value'] );
			}
		}
		
		/**
		 * Custom CSS
		 */
		
		// Import the CSS
		if ( isset( $stylekit_json['css'] ) ) {
			
			// Set theme mod
			set_theme_mod( 'layers-custom-css', $stylekit_json['css'] );
		}
		
		return $stylekit_json;
	}
	
	public function search_and_replace_images( $widgets, $page_id ) {
		
		if ( is_array( $this->check_images ) && !empty( $this->check_images ) ){
			
			// // Loop through the widgets modify them.
			// foreach ( $widgets as $widget ) {
			// 	$widget = $this->migrator->search_and_replace_images_in_widget( $widgets, $this->check_images );
			// }
			
			$widgets = $this->migrator->search_and_replace_images_in_widget( $widgets, $this->check_images );
			
		}
		
		return $widgets;
	}
	
	public function check_image_locations( $locations ) {
		$locations[] = $this->check_image_locations;
		return $locations;
	}
	
	
	/**
	 * ------------------------------------------------------------------
	 *
	 *
	 *
	 *
	 *
	 *
	 *                           RESTORE
	 *
	 *
	 *
	 *
	 *
	 *
	 * -----------------------------------------------------------------
	 */
	
	/**
	 * Restore Ajax
	 */
	public function layers_stylekit_remove_ajax() {
		
		// Get the most recent stylekit backup.
		$posts = get_posts( array(
			'posts_per_page' => 1,
			'post_type'      => 'layers_stylekits',
			'post_status'    => array( 'publish' ),
			'meta_key'       => 'type',
			'meta_value'     => 'backup',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		
		foreach( $posts as $post ) :
			setup_postdata( $post );
			$stylekit_json = get_post_meta( $post->ID, 'settings_json', TRUE );
			$post_id = $post->ID;
		endforeach;
		
		// Re-apply the previous settings.
		$this->import_stylekit( $stylekit_json );
		
		// Remove the pages.
		if ( isset( $stylekit_json['internal_data']['page_ids'] ) ) {
			foreach ( $stylekit_json['internal_data']['page_ids'] as $page_id ) {
				wp_delete_post( $page_id, TRUE );
			}
		}
		
		// Remove the backup
		wp_delete_post( $post_id, TRUE );
		
		// Return the StyleKit JSON
		echo json_encode( array(
			// 'stylekit_json' => $stylekit_json,
			// 'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
			// 'ui' => $ui,
		) );
		
		die();
	}
	
}

/**
*  Kicking this off with the 'widgets_init' hook
*/

function layers_stylekit_exporter_init(){
	$layers_widget = Layers_StyleKit_Exporter::get_instance();
}
add_action( 'init', 'layers_stylekit_exporter_init', 90 );

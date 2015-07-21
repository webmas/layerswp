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
		
		// Export
		add_action( 'wp_ajax_layers_stylekit_export_ajax', array( $this, 'layers_stylekit_export_ajax' ) );
		
		// Import - file drag&drop plupload interface
		add_action( 'admin_head', array( $this, 'file_upload_settings' ) );
		add_action( 'wp_ajax_layers_stylekit_file_upload_ajax', array( $this, 'file_upload_ajax' ) );
		
		// Import - Unpack the zip
		add_action( 'wp_ajax_layers_stylekit_unpack_ajax', array( $this, 'layers_stylekit_unpack_ajax' ) );
		
		// Import - Ajax for each step of the import process
		add_action( 'wp_ajax_layers_stylekit_import_ajax_step_2', array( $this, 'layers_stylekit_import_ajax_step_2' ) );
		add_action( 'wp_ajax_layers_stylekit_import_ajax_step_3', array( $this, 'layers_stylekit_import_ajax_step_3' ) );
		add_action( 'wp_ajax_layers_stylekit_import_ajax_step_4', array( $this, 'layers_stylekit_import_ajax_step_4' ) );
		add_action( 'wp_ajax_layers_stylekit_import_ajax_step_5', array( $this, 'layers_stylekit_import_ajax_step_5' ) );
		add_action( 'wp_ajax_layers_stylekit_import_ajax_step_6', array( $this, 'layers_stylekit_import_ajax_step_6' ) );
		
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
			'layers_stylekit_export',
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
				'action'      => 'layers_stylekit_file_upload_ajax'
			)
		);
		?>
		<script type="text/javascript">
			var global_uploader_options=<?php echo json_encode( $uploader_options ); ?>;
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
	
	function file_upload_ajax() {
		// check ajax nonce
		check_ajax_referer( __FILE__ );

		if( current_user_can( 'upload_files' ) ) {
			$response = array();
			
			add_filter( 'upload_mimes', array( $this, 'add_allowed_mimes' ) );

			// handle file upload
			$id = media_handle_upload(
			   'async-upload',
			   0,
			   array(
				  'test_form' => true,
				  'action' => 'layers_stylekit_file_upload_ajax',
			   )
			);

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

		}

		echo json_encode( $response );
		exit;
	}
	
	function layers_stylekit_manager_page() {
		
		$tabs = array(
			'layers-stylekit-import' => __( 'Import' , 'layerswp' ),
			'layers-stylekit-export' => __( 'Export' , 'layerswp' ),
		);

		$current_tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : 'layers-stylekit-import' ;
		$current_step = ( isset( $_GET['step'] ) ) ? $_GET['step'] : false ;
		?>
		
		<div class="layers-area-wrapper">
			<div class="layers-onboard-wrapper layers-stylekit-onboard-wrapper">
				
				<header class="layers-page-title layers-section-title layers-large layers-content-large layers-no-push-bottom layers-no-inset">
					
					<a href="http://layerswp.com" class="layers-logo"><?php _e( 'Layers' , 'layerswp' ); ?></a>
					<h2 class="layers-heading" id="layers-options-header"><?php _e( 'StyleKit Manager' , 'layerswp' ); ?></h2>
					
					<?php if ( FALSE !== $current_step ): ?>
						<nav class="layers-nav-horizontal layers-dashboard-nav">
							<ul>
								<?php foreach( $tabs as $tab_key => $tab_label ) { ?>
									<li class="<?php if ( $tab_key == $current_tab ) echo 'active'; ?>">
										<a href="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_export', 'tab' => $tab_key, 'step' => $tab_key . '-step-1' ), get_admin_url() . 'admin.php' ); ?>"><?php echo $tab_label; ?></a>
									</li>
								<?php } ?>
							</ul>
						</nav>
					<?php endif; ?>
					
				</header>
				
				<?php if ( FALSE == $current_step ): ?>
					
					<!-- ------------------------------------
					
					
									 SPLASH
								
								
					------------------------------------- -->
					
					<div></div>
					
					<div class="layers-row">
						<div class="layers-column layers-span-6">
						
							<a class="layers-button layers-stylekit-button" href="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_export', 'tab' => 'layers-stylekit-import', 'step' => 'layers-stylekit-import-step-1' ), get_admin_url() . 'admin.php' ); ?>" >Import StyleKit</a>
							
						</div>
						<div class="layers-column layers-span-6">
						
							<a class="layers-button layers-stylekit-button" href="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_export', 'tab' => 'layers-stylekit-export', 'step' => 'layers-stylekit-export-step-1' ), get_admin_url() . 'admin.php' ); ?>" >Export StyleKit</a>
							
						</div>
					</div>
					
				<?php elseif ( 'layers-stylekit-import-step-1' == $current_step ): ?>
					
					<!-- ------------------------------------
					
					
								IMPORT : STEP-1
					
							
					------------------------------------- -->
				
					<div class="layers-onboard-slider">
						<div class="layers-onboard-slide layers-animate layers-onboard-slide-current layers-stylekit-import-step-1">
							
							<div class="layers-column layers-span-8">
								<div class="layers-stylekit-form layers-stylekit-form-import">
									
									<!-- WordPress Plupload drag&drop interface -->
									<div id="layers-stylekit-drop-uploader-ui" class="layers-stylekit-drop-uploader-ui multiple">
										
										<div class="layers-load-bar layers-load-bar-floater layers-stylekit-load-bar layers-hide">
											<span class="layers-progress zero"></span>
										</div>
										
										<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-current layers-stylekit-slide-1">
										
											<!-- ------------------------------------
													 IMPORT : STEP-1, SLIDE-1
											------------------------------------- -->
											
											<span class="ajaxnonce" id="<?php echo wp_create_nonce( __FILE__ ); ?>"></span>
									
											<?php if ( ! _device_can_upload() ) : ?>
												<h3 class="upload-instructions"><?php printf( __( 'The web browser on your device cannot be used to upload files.', 'layerswp' ) ); ?></h3>
											<?php elseif ( is_multisite() && ! is_upload_space_available() ) : ?>
												<h3 class="upload-instructions"><?php _e( 'Upload Limit Exceeded.', 'layerswp' ); ?></h3>
												<?php
												/** This action is documented in wp-admin/includes/media.php */
												do_action( 'upload_ui_over_quota' );
												?>
											<?php else : ?>
											
												<div class="upload-ui">
													<h3 class="upload-instructions drop-instructions"><?php _e( 'Drop a StyleKit here', 'layerswp' ); ?></h3>
													<p class="upload-instructions drop-instructions"><?php _ex( 'or', 'Uploader: Drop files here - or - Select Files', 'layerswp' ); ?></p>
													<a href="#" id="layers-stylekit-drop-uploader-ui-button" class="layers-stylekit-drop-uploader-ui-button browser button button-hero"><?php _e( 'Select StyleKit', 'layerswp' ); ?></a>
												</div>

												<div class="upload-inline-status"></div>

												<div class="post-upload-ui">
													<?php
													/** This action is documented in wp-admin/includes/media.php */
													do_action( 'pre-upload-ui' );
													/** This action is documented in wp-admin/includes/media.php */
													do_action( 'pre-plupload-upload-ui' );

													if ( 10 === remove_action( 'post-plupload-upload-ui', 'media_upload_flash_bypass' ) ) {
														/** This action is documented in wp-admin/includes/media.php */
														do_action( 'post-plupload-upload-ui' );
														add_action( 'post-plupload-upload-ui', 'media_upload_flash_bypass' );
													} else {
														/** This action is documented in wp-admin/includes/media.php */
														do_action( 'post-plupload-upload-ui' );
													}

													$max_upload_size = wp_max_upload_size();
													if ( ! $max_upload_size ) {
														$max_upload_size = 0;
													}
													?>

													<p class="max-upload-size">
														<?php printf( __( 'Maximum upload file size: %s.', 'layerswp' ), esc_html( size_format( $max_upload_size ) ) ); ?>
													</p>
													
													<?php
													/** This action is documented in wp-admin/includes/media.php */
													do_action( 'post-upload-ui' ); ?>
												</div>
												
												<!-- Get uploaded info from pupload and pass to next page -->
												<form id="layers-stylekit-plupload-info-form" method="post" action="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_export', 'step' => 'layers-stylekit-import-step-2' ), get_admin_url() . 'admin.php' ) ?>">
													<input type="hidden" name="layers-stylekit-source-path">
													<input type="hidden" name="layers-stylekit-source-id" >
												</form>
												
											<?php endif; ?>
										
										</div>
									
										<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-2 layers-stylekit-slide-inactive">
											
											<!-- ------------------------------------
													 IMPORT : STEP-1, SLIDE-2
											------------------------------------- -->
											
										</div>
										
										<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-3 layers-stylekit-slide-inactive">
											
											<!-- ------------------------------------
													IMPORT : STEP-1, SLIDE-3
											------------------------------------- -->
											
										</div>
									
									</div>
									<!-- /WordPress Plupload -->
									
									<!-- Old-school browser file upload -->
									<form method="post" enctype="multipart/form-data" class="layers-stylekit-form-uploader-ui wp-upload-form layers-push-bottom" action="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_export', 'step' => 'layers-stylekit-import-step-2' ), get_admin_url() . 'admin.php' ) ?>">
										<?php wp_nonce_field( 'layers-stylekit-import'); ?>
										<input type="file" name="layers-stylekit-themezip" />
										<?php submit_button( __( 'Import StyleKit', 'layerswp' ), 'button', 'layers-stylekit-submit', false, array( 'class' => 'button button-primary button-large' ) ); ?>
									</form>
									<!-- /Old School -->
									
								</div>
							</div>
							<div class="layers-column layers-span-4 no-gutter">
								<div class="layers-content">
									<!-- Your helpful tips go here -->
									<ul class="layers-help-list">
										<li>
											StyleKits are standardised collections of settings, CSS and pages for Layers sites. You can export and import them into any other Layers installation. 										</li>
										<li class="pro-tip">
											StyleKits are an easy way of transferring the look of your site or selling it as a theme for others to use.
										</li>
										<li class="pro-tip">
											For more information and documentation, <a href="#">click here</a>.
										</li>
									</ul>
								</div>
							</div>
							
						</div>
					</div>
					
				<?php elseif ( 'layers-stylekit-import-step-2' == $current_step ): ?>
					
					<!-- ------------------------------------
					
					
								IMPORT : STEP-2
								
								
					------------------------------------- -->
					
					<div class="layers-onboard-slider">
						<div class="layers-onboard-slide layers-animate layers-onboard-slide-current layers-stylekit-import-step-2">
							
							<div class="layers-row">
								<div class="layers-column layers-span-8 layers-panel">
								
									<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-1 layers-stylekit-slide-current">
								
										<!-- ------------------------------------
												 IMPORT : STEP-2, SLIDE-1
										------------------------------------- -->
										
										<?php
										global $wp_filesystem;
										
										include_once( ABSPATH . '/wp-admin/includes/class-wp-upgrader.php' ); // WordPress's
										include_once( LAYERS_TEMPLATE_DIR . '/core/stylekit-manager/classes/class-stylekit-installer-skin.php' );
										include_once( LAYERS_TEMPLATE_DIR . '/core/stylekit-manager/classes/class-stylekit-upgrader.php' );
										
										if ( isset( $_POST['layers-stylekit-source-path'] ) ) {
											
											$file_upload = array(
												'id' => $_POST['layers-stylekit-source-id'],
												'package' => $_POST['layers-stylekit-source-path'],
												'filename' => basename( $_POST['layers-stylekit-source-path'] ),
											);
											
											$file_upload = (object) $file_upload;
										}
										else {
											
											if ( ! current_user_can( 'upload_themes' ) ) {
												wp_die( __( 'You do not have sufficient permissions to install themes on this site.', 'layerswp' ) );
											}

											// Security Check.
											//check_admin_referer('layers-stylekit-import');
											
											$file_upload = new File_Upload_Upgrader( 'layers-stylekit-themezip', 'package' ); // This uploads the file in Media.
										}
										?>
									
										<input type="hidden" name="layers-stylekit-package" value="<?php echo $file_upload->package; ?>">
									
										<div class="layers-hold-open"></div>
										
									</div>
									<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-2 layers-stylekit-slide-inactive">
									
										<!-- ------------------------------------
												IMPORT : STEP-2, SLIDE-2
										------------------------------------- -->
										
									
									</div>
									<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-3 layers-stylekit-slide-inactive">
									
										<!-- ------------------------------------
												IMPORT : STEP-2, SLIDE-3
										------------------------------------- -->
									
										<div class="layers-hold-open"></div>
										
									</div>
									<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-4 layers-stylekit-slide-inactive">
										
										<!-- ------------------------------------
												IMPORT : STEP-2, SLIDE-4
										------------------------------------- -->
									
									</div>
									
									<div class="layers-load-bar layers-load-bar-floater layers-stylekit-load-bar">
										<span class="layers-progress zero"></span>
									</div>
									
								</div>
								<div class="layers-column layers-span-4 no-gutter">
									<div class="layers-content">
										<!-- Your helpful tips go here -->
										<ul class="layers-help-list">
											<li>
												If you're ever stuck or need help with your Layers site please visit our <a href="http://docs.layerswp.com" rel="nofollow">helpful documentation.</a>
											</li>
											<li class="pro-tip">
												For the Pros: Layers will automatically assign the tagline to Settings → General.
											</li>
										</ul>
									</div>
								</div>
							</div>
							
							<!-- Debugging Textarea -->
							<div class="layers-row layers-push-top NOT-layers-hide">
								<div class="layers-column layers-span-12">
									<div class="json-code">
										<textarea name="layers-stylekit-import-stylekit-prettyprint"></textarea>
									</div>
								</div>
							</div>
							<!-- /Debugging Textarea -->
							
						</div>
					</div>
				
				<?php elseif ( 'layers-stylekit-export-step-1' == $current_step ): ?>
					
					<!-- ------------------------------------
					
				
								EXPORT: STEP-1
									 
					
					------------------------------------- -->
				
					<div class="layers-onboard-slider">
						
						<div class="layers-onboard-slide layers-animate layers-onboard-slide-current layers-stylekit-export-step-1">
							
							<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-1 layers-stylekit-slide-current">
								
								<!-- ------------------------------------
										 EXPORT : STEP-1, SLIDE-1
								------------------------------------- -->
								
								<div class="layers-row">
									
									<div class="layers-column layers-span-8 layers-panel">
										
										<form class="layers-stylekit-form layers-stylekit-form-export" action="" method="post">
											
											<div class="layers-row layers-push-top ">
													
												<div class="layers-column layers-span-12 layers-content">
													<div class="layers-section-title layers-small">
														<h3 class="layers-heading">StyleKit Export</h3>
														<p class="layers-excerpt">
															<?php _e( 'Choose what will be exported in your StyleKit below.', 'layerswp' ); ?>
														</p>
													</div>
													
												</div>
														
											</div>
											
											<hr class="layers-push-bottom">
											
											<div class="layers-row">
												
												<div class="layers-column layers-span-4 layers-content">
													<h3 class="layers-heading">Name</h3>
													<p class="layers-excerpt">name your Stylit. You can leave it as you SiteName, or name it something like 'Happy Store'.</p>
												</div>
												
												<div class="layers-column layers-span-8 layers-content">
													<div class="layers-no-push-bottom layers-stylekit-select-group">
														<?php
														$theme_name = str_replace( ' ' , '-' , get_bloginfo( 'name' ) );
														?>
														<input type="text" name="layers-stylekit-name" value="<?php echo esc_attr( $theme_name ); ?>" placeholder="<?php echo esc_attr( $theme_name ); ?>">
													</div>
												</div>
												
											</div>
										
											<div class="layers-row">
												
												<div class="layers-column layers-span-4 layers-content">
													<h3 class="layers-heading">Settings</h3>
													<p class="layers-excerpt">Select which Layers settings you'd like export with your StyleKit. These are set in the Customizer.</p>
													<?php $this->check_all_ui(); ?>
												</div>
												
												<div class="layers-column layers-span-8 layers-content">
													<div class="layers-panel layers-no-push-bottom layers-stylekit-select-group">
														
														<ul class="layers-list layers-list-stylekit-settings layers-list-complex">
															
															<?php
															foreach ( $this->control_groups as $control_group_key => $control_group ) {
																
																$controls = $this->get_controls( array(
																	'sections' => $control_group['contains'],
																	'exclude_types' => $this->controls_to_exclude,
																) );
																
																$settings_collection = array();
																
																foreach ( $controls as $control_key => $control ) {
																	
																	// @TODO: write a get field data function that does all this
																	// @TODO: perhaps also a get_field_name that looks at type and gets either the lable or subtitle as a result
																	
																	$name = '';
																	if ( isset( $control['subtitle'] ) ) $name = $control['subtitle'];
																	if ( '' == $name && isset(  $control['label'] ) ) $name = $control['label'];
																	
																	//if ( NULL != get_theme_mod( LAYERS_THEME_SLUG . '-' . $control_key, NULL ) ){
																	
																		$settings_collection[ $control_group_key ][ $control_key ] = array(
																			'title'    => $name,
																			'type'     => $control['type'],
																			'settings' => layers_get_theme_mod( $control_key, FALSE ),
																			'default'  => layers_get_default( $control_key ),
																		);
																	//}
																}
																
																$collect_titles = array();
																foreach ( $settings_collection[ $control_group_key ] as $setting_key => $setting ) {
																	$collect_titles[] = $setting['title'];
																	/*
																	?>
																	<span class="setting-group">
																		<span class="setting-title"><?php echo $setting['title'] ?></span>
																		<!-- <div class="setting-value">Value: <?php echo $setting['settings'] ?></div>
																		<div class="setting-default">Default: <?php echo $setting['default'] ?></div>
																		<div class="setting-type">Type: <?php echo $setting['type'] ?></div> -->
																	</span>
																	<?php
																	*/
																}
																//echo implode( ', ', $collect_titles );
																?>
																
																<li title="<?php echo esc_attr( implode( ', ', $collect_titles ) ); ?>">
																	<label for="<?php echo $control_group_key ?>" class="group-title">
																		<input id="<?php echo $control_group_key ?>" type="checkbox" checked="checked" name="layers_settings_groups[]" <?php if( isset( $_POST[ 'layers_settings_groups' ] ) ) checked( in_array( $control_group_key, $_POST[ 'layers_settings_groups' ] ), TRUE ); ?> value="<?php echo $control_group_key; ?>" >
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
											
											
											<?php
											//Get builder pages.
											$layers_pages = layers_get_builder_pages();
											
											// Create builder pages dropdown.
											if( $layers_pages ){
												?>
												
												<div class="layers-row">
													
													<div class="layers-column layers-span-4 layers-content">
														<h3 class="layers-heading">Pages</h3>
														<p class="layers-excerpt">Choose which Layers pages you'd like to export in your StyleKit.</p>
														<?php $this->check_all_ui(); ?>
													</div>
													
													<div class="layers-column layers-span-8 layers-content">
														<div class="layers-panel layers-no-push-bottom layers-stylekit-select-group">
															
															<ul class="layers-list layers-list-complex layers-list-stylekit-pages">
																<?php foreach( $layers_pages as $page ) { ?>
																
																	<?php
																	$page_id = $page->ID;
																	$page_title = $page->post_title;
																	$page_url = get_permalink( $page->ID );
																	?>
																	
																	<li>
																		<label for="page-<?php echo $page_id ?>">
																			<input id="page-<?php echo $page_id ?>" type="checkbox" checked="checked" name="layers_pages[]" <?php if( isset( $_POST[ 'layers_pages' ] ) ) checked( in_array( $page_id, $_POST[ 'layers_pages' ] ), TRUE ); ?> value="<?php echo $page_id ?>" >
																			<?php echo $page_title ?>
																		</label>
																		
																		<a class="layers-complex-action preview-page" target="blank" href="<?php echo $page_url; ?>">
																			<span>Preview</span> <i class=" icon-display"></i>
																		</a>
																	</li>
																	
																<?php } ?>
															</ul>
														
														</div>
													</div>
													
												</div>
												
												<?php
											}
											?>
											
											<div class="layers-row">
												
												<div class="layers-column layers-span-4 layers-content">
													<h3 class="layers-heading">Custom CSS</h3>
													<p class="layers-excerpt">Choose whether to export your custom CSS with your StyleKit.</p>
													<?php $this->check_all_ui(); ?>
												</div>
												
												<div class="layers-column layers-span-8 layers-content">
													<div class="layers-panel layers-no-push-bottom layers-stylekit-select-group">
													
														
														<ul class="layers-list layers-list-complex layers-list-stylekit-css">
															<li>
																<label for="css-check" class="group-title">
																	<input id="css-check" type="checkbox" checked="checked" name="layers_css" <?php if( isset( $_POST[ 'layers_css' ] ) ) checked( 'yes', $_POST[ 'layers_css' ], TRUE ); ?> value="yes">
																	CSS
																</label>
															</li>
														</ul>
														
													</div>
												</div>
												
											</div>
											
											<div class="layers-alert">
														
												<span class="layers-stylekit-confrim">
													<label>
														<input type="checkbox" name="layers-stylekit-export-confirm-permission" />
														Please confirm you have permission to distribute images enclosed in your StyleKit
													</label>
													<a class="more-info" href="#" target="blank">(more info)</a>
												</span>
													
											</div>
											
											<div id="layers-stylekit-export-action-row" class="layers-button-well layers-button-well-content-NOT">
												<input type="submit" id="layers-stylekit-export-action" class="layers-button btn-large btn-primary layers-pull-right" value="Export StyleKit" >
											</div>
											
										</form>
										
									</div>
									<div class="layers-column layers-span-4 no-gutter">
										<div class="layers-content">
											<!-- Your helpful tips go here -->
											<ul class="layers-help-list">
												<li>
													If you ever need help with your Layers site please visit our <a href="http://docs.layerswp.com" rel="nofollow">helpful documentation.</a>
												</li>
												<!--<li class="pro-tip">-->
												<!--	For the Pros: Layers will automatically assign the tagline to Settings → General.-->
												<!--</li>-->
											</ul>
										</div>
									</div>
							
								</div>
					
							</div>
							<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-2 layers-stylekit-slide-inactive">
								
								<!-- ------------------------------------
										EXPORT : STEP-1, SLIDE-2
								------------------------------------- -->
								
								<div class="layers-row">
									
									<div class="layers-column layers-span-8 layers-panel">
									
										<div class="layers-hold-open">
											
											<!-- Exporting... -->
											
											<div class="layers-load-bar layers-load-bar-floater layers-stylekit-load-bar layers-hide">
												<span class="layers-progress zero"></span>
											</div>
											
										</div>
										
									</div>
									<div class="layers-column layers-span-4 no-gutter">
										<div class="layers-content">
											<!-- Your helpful tips go here -->
											<ul class="layers-help-list">
												<li>
													If you're ever stuck or need help with your Layers site please visit our <a href="http://docs.layerswp.com" rel="nofollow">helpful documentation.</a>
												</li>
												<li class="pro-tip">
													For the Pros: Layers will automatically assign the tagline to Settings → General.
												</li>
											</ul>
										</div>
									</div>
								
								</div>
								
							</div>
							<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-3 layers-stylekit-slide-inactive">
								
								<!-- ------------------------------------
										EXPORT : STEP-1, SLIDE-3
								------------------------------------- -->
					
							</div>
				
						</div>
						
						<!-- Debugging Textarea -->
						<div class="layers-row layers-push-top NOT-layers-hide">
							<div class="layers-column layers-span-12">
								<div class="json-code">
									<textarea name="layers-stylekit-export-stylekit-prettyprint"></textarea>
								</div>
							</div>
						</div>
						<!-- /Debugging Textarea -->
					
					</div>
					
				<?php endif; ?>
				
			</div>
		</div>
		
		<?php
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
		
		$backup_settings_only = isset( $_POST['backup_settings_only'] );
		
		// Ready for us to be able to access filestytem and grab the images.
		$this->migrator->init_filesystem();
		
		$stylekit_json = array();
		
		ob_start();
		?>
		
		<div class="layers-row">
			
			<div class="layers-column layers-span-8 layers-panel">
			
				<div class="layers-row">
					
					<div class="layers-column layers-span-4 layers-content">
							
						<div class="stylekit-statement-holder">
							<i class="layers-button-icon-dashboard"></i>
						</div>
						
					</div>
					<div class="layers-column layers-span-8 layers-content">
						
						<div class="stylekit-statement">
							
							<div class="layers-section-title layers-small">
								<h3 class="layers-heading">Your StyleKit is ready!</h3>
							</div>
							
							<div class="layers-panel layers-push-bottom" style="display: none;">
								<ul class="layers-list">
									
									<?php
									if ( isset( $_POST['layers_settings_groups'] ) || $backup_settings_only ) {
										
										$sections_to_get = array();
										
										if ( $backup_settings_only ) {
											
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
										?>
										<li class="tick ticked-all"><?php _e( 'Settings', 'layerswp' ) ?></li>
										<?php
									}
									
									if ( isset( $_POST['layers_pages'] ) ) {
										
										$chosen_pages = ( isset( $_POST['layers_pages'] ) ) ? $_POST['layers_pages'] : array() ;
										
										// Start preset page bucket
										$page_presets = array();
										
										$builder_pages = layers_get_builder_pages();
										
										$theme_name = esc_html( str_replace( ' ' , '_' , strtolower( get_bloginfo( 'name' ) ) ) );
										$theme_lang_slug = 'layers-' . esc_html( str_replace( ' ' , '-' , strtolower( get_bloginfo( 'name' ) ) ) );
										
										foreach ( $builder_pages as $page ) {
											if ( in_array( $page->ID, $chosen_pages ) ) {
												
												//$preset_name = $theme_name . '-' . $page->post_name;
												//$post_title = esc_html( get_bloginfo( 'name' ) . '-' . esc_attr( $page->post_title ) );
												
												$page_presets[ $page->post_name ] = array(
													'post_title' => esc_html( get_bloginfo( 'name' ) . '-' . esc_attr( $page->post_title ) ),
													'widget_data' => $this->migrator->export_data( $page ),
												);
											}
										}
										
										?>
										<li class="tick ticked-all"><?php count( $page_presets ) ?> <?php echo esc_html( __( 'Pages', 'layerswp' ) ); ?></li>
										<?php
									}
									
									if ( isset( $_POST['layers_css'] ) || $backup_settings_only ) {
										
										$stylekit_json['css'] = layers_get_theme_mod( 'custom-css' );
										
										?>
										<li class="tick ticked-all"><?php _e( 'Custom CSS', 'layerswp' ); ?></li>
										<?php
									}
									
									/*
									 * Check that the user has write permission on a folder
									 */
									$access_type = get_filesystem_method();
									
									if ( $access_type === 'direct' ) {
										
										/* you can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL */
										$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );

										/* initialize the API */
										if ( ! WP_Filesystem($creds) ) {
											
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
									$zip_sanitized_name = sanitize_title_with_dashes( $zip_name );
									
									
									// Stash CSS in uploads directory
									//$upload_dir = wp_upload_dir(); // Grab uploads folder array
									//$dir = trailingslashit( $upload_dir['basedir'] ) . 'some-folder/'; // Set storage directory path
									
									/* replace the 'direct' absolute path with the Filesystem API path */
									$plugin_path = str_replace( ABSPATH, $wp_filesystem->abspath(), LAYERS_TEMPLATE_DIR );
									$export_path = $plugin_path . '/export/';
									$download_path = LAYERS_TEMPLATE_URI . '/export/';

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
									
									// Compile stylekit.json, put it, then add it to the zip collection.
									$file_name = "stylekit.json";
									$wp_filesystem->put_contents( $export_path . $file_name, json_encode( $stylekit_json ) ); // Finally, store the file :)
									$files_to_zip[$zip_sanitized_name . "/" . $file_name] = $export_path . $file_name;
									
									
									foreach ( $page_presets as $page_preset_key => $page_preset_value ) {
										
										// Prettyfy the JSON
										//$widget_data = $this->prettyPrint( json_encode( $page_preset_value['widget_data'] ) );
										$widget_data = json_encode( $page_preset_value['widget_data'] );
										
										//post_title, widget_data
										$file_name = $page_preset_key . ".json";
										$wp_filesystem->put_contents( $export_path . $file_name, $widget_data );
										$files_to_zip[$zip_sanitized_name . "/" . $file_name] = $export_path . $file_name;
									}
									
									// Create image assets
									if ( isset( $this->migrator->images_collected ) ) {
										
										// if ( !$wp_filesystem->is_dir( $export_path . 'assets/' ) ) $wp_filesystem->mkdir( $export_path . 'assets/' );
										// if ( !$wp_filesystem->is_dir( $export_path . 'assets/images/' ) ) $wp_filesystem->mkdir( $export_path . 'assets/images/' );
										
										foreach ( $this->migrator->images_collected as $image_collected ) {
											
											$image_pieces = explode( '/', $image_collected['url'] );
											$file_name = $image_pieces[count($image_pieces)-1];
											$files_to_zip["$zip_sanitized_name/assets/images/$file_name"] = $image_collected['path'];
										}
									}
									
									$wp_filesystem->delete( $export_path . $zip_sanitized_name . '.zip' );
									$wp_filesystem->delete( $export_path . $zip_sanitized_name );
									
									//if true, good; if false, zip creation failed
									$result = $this->create_zip( $files_to_zip, $export_path . $zip_sanitized_name . '.zip' );
									
									$wp_filesystem->delete( $export_path . 'stylekit.json' );
									
									$download_uri = $download_path . $zip_sanitized_name . '.zip';
									?>
								
								</ul>
							</div>
							
							<a class="layers-button btn-large btn-primary layers-pull-right-NOT" download="<?php echo $zip_sanitized_name ?>" href="<?php echo $download_uri ?>" >
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
							If you're ever stuck or need help with your Layers site please visit our <a href="http://docs.layerswp.com" rel="nofollow">helpful documentation.</a>
						</li>
						<li class="pro-tip">
							For the Pros: Layers will automatically assign the tagline to Settings → General.
						</li>
					</ul>
				</div>
			</div>
		
		</div>
		
		<?php if ( FALSE ) : ?>
		<form id="layers-stylekit-export-json-results" class="layers-stylekit-form" action=""  method="post">
			
			<div class="layers-row layers-push-top">
				
				<div class="layers-column layers-span-4 layers-content">
					<h3>Your StyleKit is ready!</h3>
					<p>Simply copy &amp; paste this StyleKit code into the StyleKit Import and proceed.</p>
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
			'download_uri' => $download_path . $zip_sanitized_name . '.zip',
			'ui' => $ui,
			'stylekit_json' => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
		) );
		
		die();
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
			
			//check to make sure the file exists
			return file_exists( $destination );
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
	 * AJAX handler for updating a plugin.
	 *
	 * @since 4.2.0
	 *
	 * @see Plugin_Upgrader
	 */
	
	function layers_stylekit_unpack_ajax() {
		
		//return json_encode( array( 'test' => 'test' ) );
		
		$package = urldecode( $_POST['package'] );
		
		//check_ajax_referer( 'updates' );
		
		include_once( ABSPATH . '/wp-admin/includes/class-wp-upgrader.php' );
		include_once( LAYERS_TEMPLATE_DIR . '/core/stylekit-manager/classes/class-stylekit-installer-skin.php' );
		include_once( LAYERS_TEMPLATE_DIR . '/core/stylekit-manager/classes/class-stylekit-upgrader.php' );
		
		// $current = get_site_transient( 'update_plugins' );
		// if ( empty( $current ) ) {
		// 	wp_update_plugins();
		// }
		
		$upgrader = new StyleKit_Importer_Upgrader( new Automatic_Upgrader_Skin() );
		$result = $upgrader->install( $package );
		
		if ( is_array( $result ) ) {
			$unpack_results = $this->get_stylekit_import_advanced_ui( $result['source'] );
			
			if ( isset( $unpack_results['ui'] ) ) $result['ui'] = $unpack_results['ui'];
			if ( isset( $unpack_results['ui2'] ) ) $result['ui2'] = $unpack_results['ui2'];
			
			wp_send_json_success( $result );
		}
		else if ( is_wp_error( $result ) ) {
			$status['error'] = $result->get_error_message();
			wp_send_json_error( $status );
		}
		else if ( is_bool( $result ) && ! $result ) {
			$status['errorCode'] = 'unable_to_connect_to_filesystem';
			$status['error'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'layerswp' );
			wp_send_json_error( $status );
		}
	}
	
	function get_stylekit_import_advanced_ui( $source ) {
		
		global $wp_filesystem;
		
		/**
		 * Checks - to see we're good to proceed.
		 */
		
		// Initialize the WP filesystem if not yet
		if ( empty( $wp_filesystem ) ) {
			require_once ( ABSPATH . '/wp-admin/includes/file.php' );
			WP_Filesystem();
		}
		
		// Get the Path and URL of the Temp directory
		$temp_directory_path = str_replace( $wp_filesystem->wp_content_dir(), trailingslashit( WP_CONTENT_DIR ), $source );
		$temp_directory_url = str_replace( $wp_filesystem->wp_content_dir(), trailingslashit( WP_CONTENT_URL ), $source );
		
		// Check if the above str_replace works.
		if ( ! is_dir( $temp_directory_path ) ) {
			return $temp_directory_path;
		}

		// A proper StyleKit should have at least a stylekit.json file in the single subdirectory.
		if ( ! file_exists( $temp_directory_path . 'stylekit.json' ) ){
			return new WP_Error( 'incompatible_stylekit_no_json', $this->strings['incompatible_archive'], __( 'The StyleKit is missing the stylekit.json file.', 'layerswp' ) );
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
		
		
		/**
		 * Get Adviced Options UI. the seocnd one.
		 */
		
		ob_start();
		?>
		
		<form class="layers-stylekit-form layers-stylekit-form-import" method="post" action="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_export', 'step' => 'layers-stylekit-import-step-3' ), get_admin_url() . 'admin.php' ) ?>">
			
			<div class="layers-stylekit-import-choices">
			
				<div class="layers-stylekit-import-choices-holder">
				
					<?php if ( isset( $stylekit_json['settings'] ) ) { ?>
					
						<div class="layers-row layers-push-top">
							
							<div class="layers-column layers-span-4 layers-content">
								<h3 class="layers-heading">Settings</h3>
								<p class="layers-excerpt">Be aware that unchecking these may chnange the intended look from this StyleKit</p>
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
								<h3 class="layers-heading">Pages</h3>
								<p class="layers-excerpt">These pages will be imported</p>
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
			<div class="layers-row layers-push-top layers-hide">
				<div class="layers-column layers-span-12 layers-content">
					<div class="json-code">
						<textarea name="layers-stylekit-import-stylekit"><?php echo json_encode( $stylekit_json ); ?></textarea>
					</div>
				</div>
			</div>
			
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
						<i class="layers-button-icon-dashboard"></i>
					</div>
					
				</div>
				<div class="layers-column layers-span-8 layers-content">
					
					<div class="stylekit-statement">
						
						<div class="layers-section-title layers-small">
							<h3 class="layers-heading">StyleKit <em>Three.zip</em></h3>
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
								Confirm import all <span class="hidden-choice">or untick to customize</span>
							</label>
						</p>
						
						<input type="hidden" name="layers-stylekit-temp-directory" value="<?php echo $source; ?>">
						
					</div>
				
				</div>
				
			</div>
			
		<?php } else { ?>
		
			<div class="layers-row layers-stylekit-import-main-graphic">
			
				<div class="layers-column layers-span-4 layers-content">
					
					<div class="stylekit-statement-holder">
						<i class="layers-button-icon-dashboard"></i>
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
			'ui' => $ui,
			'ui2' => $ui2,
		);
	}
	
	function layers_stylekit_import_ajax_step_1() {
		
		/**
		 * @TODO backup the current settings so that can be rolled back.
		 */
		
		$stylekit_json = ( isset( $_POST['layers-settings-stylekit'] ) ) ? stripslashes( $_POST['layers-settings-stylekit'] ) : '' ;
	}
	
	function layers_stylekit_import_ajax_step_2() {
		
		/**
		 * Modify Stylekit Json - so that only the chosen elements prevail.
		 */
		
		
		// Get and decode json.
		$stylekit_json = ( isset( $_POST['layers-stylekit-import-stylekit'] ) ) ? stripslashes( $_POST['layers-stylekit-import-stylekit'] ) : '' ;
		$stylekit_json = json_decode( $stylekit_json, TRUE );
		
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
		
		
		// Return the StyleKit json
		echo json_encode( array(
			'stylekit_json' => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
		) );
		
		die();
	}
	
	public function layers_stylekit_import_ajax_step_3() {
		
		$stylekit_json = ( isset( $_POST['stylekit_json'] ) ) ? $_POST['stylekit_json'] : array() ;
		
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
		
		// Return the StyleKit JSON
		echo json_encode( array(
			'stylekit_json' => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
		) );
		
		die();
	}
	
	public function layers_stylekit_import_ajax_step_4() {
		
		$stylekit_json = ( isset( $_POST['stylekit_json'] ) ) ? $_POST['stylekit_json'] : array() ;
		
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
			
			// Poplulate data into stylekit for next step - importing images
			if ( !empty( $this->migrator->images_in_widgets ) ){
				
				// Get the existing images_in_widgets.
				$images_in_widgets = ( isset( $stylekit_json['internal_data']['images_in_widgets'] ) ) ? $stylekit_json['internal_data']['images_in_widgets'] : array();
				
				// Merge it with new images_in_widgets.
				$images_in_widgets = array_merge( $this->migrator->images_in_widgets, $images_in_widgets );
				
				// Re-set the new images_in_widgets.
				$stylekit_json['internal_data']['images_in_widgets'] = $images_in_widgets;
			}
			
			if ( !empty( $this->migrator->images_report ) ){
				$stylekit_json['internal_data']['images_report'] = $this->migrator->images_report;
			}
			
		}
		
		// Return the StyleKit JSON
		echo json_encode( array(
			'stylekit_json' => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
		) );
		
		die();
	}
	
	public function layers_stylekit_import_ajax_step_5() {
		
		$stylekit_json = ( isset( $_POST['stylekit_json'] ) ) ? $_POST['stylekit_json'] : array() ;
		
		/**
		 * Import Images
		 */
		
		$images_in_widgets = ( isset( $stylekit_json['internal_data']['images_in_widgets'] ) ) ? $stylekit_json['internal_data']['images_in_widgets'] : array() ;
		$images_on_disk = ( isset( $stylekit_json['internal_data']['images_on_disk'] ) ) ? $stylekit_json['internal_data']['images_on_disk'] : array() ;
		$pages = ( isset( $stylekit_json['internal_data']['page_ids'] ) ) ? $stylekit_json['internal_data']['page_ids'] : array() ;
			
		// Loop images
		foreach ( $images_in_widgets as $image_name => $image_array ) {
			
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
		
		// Return the StyleKit JSON
		echo json_encode( array(
			'stylekit_json' => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
		) );
		
		die();
	}
	
	public function layers_stylekit_import_ajax_step_6() {
		
		$stylekit_json = ( isset( $_POST['stylekit_json'] ) ) ? $_POST['stylekit_json'] : array() ;
		
		ob_start();
		
		if ( TRUE ) :
		?>
		
		<div class="layers-row">
			
			<div class="layers-column layers-span-4 layers-content">
					
				<div class="stylekit-statement-holder">
					<i class="layers-button-icon-dashboard"></i>
				</div>
				
			</div>
			<div class="layers-column layers-span-8 layers-content">
				
				<div class="stylekit-statement">
					
					<div class="layers-section-title layers-small">
						<h3 class="layers-heading">StyleKit Imported Successfully</h3>
					</div>
					
					<div class="layers-panel layers-push-bottom">
						<ul class="layers-list">
					
							<?php
							
							$collect_results = array(
								'settings' => array(),
								'pages' => array(),
								'css' => array(),
							);
							
							/**
							 * Settings
							 */
							
							// If user has chosen some settings groups, and there are some settings in the StyleKit
							if ( isset( $stylekit_json['settings'] ) && ( isset( $_POST['layers_settings_groups'] ) || isset( $_POST['layers-stylekit-import-all'] ) ) ) {
								
								// Get all the sections in the groups that the user chose.
								$collect_sections_to_get = array();
								foreach ( $this->control_groups as $control_group_key => $control_group ) {
									if (
											isset( $control_group['contains'] )
											&&
											( isset( $_POST['layers-stylekit-import-all'] ) || in_array( $control_group_key, $_POST['layers_settings_groups'] ) )
										) {
										$collect_sections_to_get = array_merge( $control_group['contains'], $collect_sections_to_get );
									}
								}
								
								// Get all the controls in the required sections.
								$controls = $this->get_controls( array(
									'sections' => $collect_sections_to_get,
									'exclude_types' => $this->controls_to_exclude,
								) );
								?>
								
								<li class="tick ticked-all">
								
									Settings
									
									<?php
									
									// Loop through required controls and save value if it exists in StyleKit settings json.
									foreach ( $controls as $control_key => $control ) {
										if( isset( $stylekit_json['settings']['layers-' . $control_key]['value'] ) ){
											
											$title = $stylekit_json['settings']['layers-' . $control_key]['title'];
											$value = $stylekit_json['settings']['layers-' . $control_key]['value'];
											
											// Set theme mod
											set_theme_mod( $control_key, $value );
											
											// Collect result so we can display in report
											$collect_results['settings'][] = '<span title="' . esc_attr( $value ) . '">' . $title . '</span>';
										}
									}
									
									//echo implode( ', ', $collect_results['settings'] ) . '<br /><br />';
									?>
								
								</li>
								
								<?php
							}
							
							/**
							 * Pages
							 */
							
							// If there are pages in the StyleKit and user has chosen to import some.
							if ( isset( $stylekit_json['pages'] ) && ( isset( $_POST['layers_pages'] ) || isset( $_POST['layers-stylekit-import-all'] ) ) ) {
								
								// Set locations to search for images during 'create_builder_page_from_preset'
								$this->check_image_locations = array(
									'path' => $temp_directory_path . 'assets/images/',
									'url'  => $temp_directory_url . 'assets/images/',
								);
								add_filter( 'layers_check_image_locations', array( $this, 'check_image_locations' ) );
								?>
								
								<?php
								// Add the pages
								$pages = $stylekit_json['pages'];
								foreach ( $pages as $page_slug => $page_data ) {
									if ( isset( $_POST['layers-stylekit-import-all'] ) || in_array( $page_slug, $_POST['layers_pages'] ) ) {
										
										$title = ( isset( $page_data[ 'post_title' ] ) ) ? $page_data[ 'post_title' ] : NULL ;
										$widget_data = ( isset( $page_data[ 'widget_data' ] ) ) ? json_decode( $page_data[ 'widget_data' ], TRUE ) : NULL ;
										
										// Import the page
										$result = $this->migrator->create_builder_page_from_preset( array(
											'post_title'                => $title,
											'widget_data'               => $widget_data,
											'create_new_image_if_name_exists' => TRUE,
										) );
										
										$post_id = $result['post_id'];
										$permalink = get_permalink( $post_id );
										
										// Collect result so we can display in report
										$collect_results['pages'][] = '<li class="tick layers-stylekit-link">' . __( 'Page:' , 'layerwp' ) . ' <em>' . $title . '</em><a href="' . esc_url( $permalink ) . '" target="blank"><i class=" icon-display"></i></a></li>';
									}
								}
								echo implode( '', $collect_results['pages'] );
								
							}
							
							/**
							 * Custom CSS
							 */
							
							// If there are pages in the StyleKit and user has chosen to import some.
							if ( isset( $stylekit_json['css'] ) && ( isset( $_POST['layers-stylekit-import-all'] ) || isset( $_POST['layers_css'] ) ) ) {
								
								?>
								
								<li class="tick ticked-all">
									<?php _e( 'Custom CSS', 'layerswp' ) ?>
									<?php
									
									// Set theme mod
									set_theme_mod( 'layers-custom-css', $stylekit_json['css'] );
									
									// Collect result so we can display in report
									$collect_results['css'] = $stylekit_json['css'];
									
									//echo $collect_results['css'] . '<br /><br />';
									?>
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
		
		endif ;
		
		//echo 'PEEEG!!';
		
		$ui = ob_get_clean();
		
		// Return the StyleKit JSON
		echo json_encode( array(
			'stylekit_json' => $stylekit_json,
			'stylekit_json_pretty' => $this->prettyPrint( json_encode( $stylekit_json ) ),
			'ui' => $ui,
		) );
		
		die();
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
}

/**
*  Kicking this off with the 'widgets_init' hook
*/

function layers_stylekit_exporter_init(){
	$layers_widget = Layers_StyleKit_Exporter::get_instance();
}
add_action( 'init', 'layers_stylekit_exporter_init', 90 );

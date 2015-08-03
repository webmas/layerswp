
<?php if ( 'layers-stylekit-import-step-1' == $current_step ): ?>
	
	<!-- ------------------------------------
	
	
				IMPORT: STEP-1
	
			
	------------------------------------- -->

	<div class="layers-onboard-slider">
		<div class="layers-onboard-slide layers-animate layers-onboard-slide-current layers-stylekit-import-step-1">
			
			<div class="layers-row">
				
				<div class="layers-column layers-span-8">
				
					<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-current layers-import-slide-1">
						
						<div class="layers-stylekit-form layers-stylekit-form-import">
						
							<!-- WordPress Plupload drag&drop interface -->
							<div id="layers-stylekit-drop-uploader-ui" class="layers-stylekit-drop-uploader-ui multiple">
								
								<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-current layers-stylekit-upload-slide layers-stylekit-upload-slide-1">
								
									<!-- ------------------------------------
											 IMPORT: STEP-1, SLIDE-1
									------------------------------------- -->
									
									<div class="layers-plupload-inner">
										
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
												}
												else {
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
											<form id="layers-stylekit-plupload-info-form" method="post" action="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_manager', 'tab' => 'layers-stylekit-import', 'step' => 'layers-stylekit-import-step-2' ), get_admin_url() . 'admin.php' ) ?>">
												<input type="hidden" name="layers-stylekit-source-path">
												<input type="hidden" name="layers-stylekit-source-id" >
											</form>
											
										<?php endif; ?>
										
									</div>
								
								</div>
							
								<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-inactive layers-stylekit-upload-slide layers-stylekit-upload-slide-2">
									
									<!-- ------------------------------------
											 IMPORT: STEP-1, SLIDE-2
									------------------------------------- -->
									
									<div class="layers-load-bar layers-load-bar-floater layers-stylekit-load-bar layers-hide">
										<span class="layers-progress zero"></span>
									</div>
									
								</div>
								
								<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-inactive layers-stylekit-upload-slide layers-stylekit-upload-slide-3">
									
									<!-- ------------------------------------
											IMPORT: STEP-1, SLIDE-3
									------------------------------------- -->
									
								</div>
							
							</div>
							<!-- /WordPress Plupload -->
							
							<!-- Old-school browser file upload -->
							<form method="post" enctype="multipart/form-data" class="layers-stylekit-form-uploader-ui wp-upload-form layers-push-bottom" action="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_manager', 'tab' => 'layers-stylekit-import', 'step' => 'layers-stylekit-import-step-2' ), get_admin_url() . 'admin.php' ) ?>">
								<?php wp_nonce_field( 'layers-stylekit-import'); ?>
								<input type="file" name="layers-stylekit-themezip" />
								<?php submit_button( __( 'Import StyleKit', 'layerswp' ), 'button', 'layers-stylekit-submit', false, array( 'class' => 'button button-primary button-large' ) ); ?>
							</form>
							<!-- /Old School -->
							
						</div>
						
					</div>
					<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-inactive layers-import-slide-2">
					
						<!-- ------------------------------------
								 RESTORE: SLIDE-1
						------------------------------------- -->
						
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
								<?php _e( 'StyleKits are standardised collections of settings, CSS and pages for Layers sites. You can export and import them into any other Layers installation.', 'layerswp' ) ?>
							</li>
							<li class="pro-tip">
								<?php _e( 'StyleKits are an easy way of transferring the look of your site or selling it as a theme for others to use.', 'layerswp' ) ?>
							</li>
							<li class="pro-tip">
								<?php _e( 'For more information and documentation, <a href="#">click here</a>.', 'layerswp' ) ?>
							</li>
						</ul>
					</div>
				</div>
				
			</div>
			
		</div>
	</div>
	
<?php elseif ( 'layers-stylekit-import-step-2' == $current_step ): ?>
	
	<!-- ------------------------------------
	
	
				IMPORT: STEP-2
				
				
	------------------------------------- -->
	
	<div class="layers-onboard-slider">
		<div class="layers-onboard-slide layers-animate layers-onboard-slide-current layers-stylekit-import-step-2">
			
			<div class="layers-row">
				
				<div class="layers-column layers-span-8 layers-panel">
				
					<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-current layers-stylekit-import-slide-1">
				
						<!-- ------------------------------------
								 IMPORT: STEP-2, SLIDE-1
						------------------------------------- -->
						
						<?php
						global $wp_filesystem;
						
						//include_once( ABSPATH . '/wp-admin/includes/class-wp-upgrader.php' ); // WordPress's
						//include_once( ABSPATH . '/wp-admin/includes/class-wp-upgrader-skins.php' );
						//include_once( LAYERS_TEMPLATE_DIR . '/core/stylekit-manager/classes/class-stylekit-upgrader-skin.php' );
						include_once( LAYERS_TEMPLATE_DIR . '/core/stylekit-manager/classes/class-stylekit-upgrader.php' );
						
						if ( isset( $_POST['layers-stylekit-source-path'] ) ) {
							
							// Backup for those that don't support Plupload Drag&Drop
							$file_upload = array(
								'id'       => $_POST['layers-stylekit-source-id'],					// "219"
								'package'  => $_POST['layers-stylekit-source-path'],				// "C:\\wamp\\www\\layers/wp-content/uploads/sites/11/2015/07/layers10-1146.zip"
								'filename' => basename( $_POST['layers-stylekit-source-path'] ),	// "layers10-1146.zip"
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
					<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-inactive layers-stylekit-import-slide-2">
					
						<!-- ------------------------------------
								IMPORT: STEP-2, SLIDE-2
						------------------------------------- -->
						
					
					</div>
					<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-inactive layers-stylekit-import-slide-3">
					
						<!-- ------------------------------------
								IMPORT: STEP-2, SLIDE-3
						------------------------------------- -->
					
						<div class="layers-hold-open"></div>
						
					</div>
					<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-inactive layers-stylekit-import-slide-4">
						
						<!-- ------------------------------------
								IMPORT: STEP-2, SLIDE-4
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
								<?php _e( 'If you\'re ever stuck or need help with your Layers site please visit our <a href="http://docs.layerswp.com" rel="nofollow">helpful documentation.</a>', 'layerswp' ) ?>
							</li>
							<li class="pro-tip">
								<?php _e( 'For the Pros: Layers will automatically assign the tagline to Settings → General.', 'layerswp' ) ?>
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

<?php endif; ?>

<?php if ( 'layers-stylekit-export-step-1' == $current_step ): ?>
	
	<!-- ------------------------------------
	

				EXPORT: STEP-1
					 
	
	------------------------------------- -->

	<div class="layers-onboard-slider">
		
		<div class="layers-onboard-slide layers-animate layers-onboard-slide-current layers-stylekit-export-step-1">
			
			<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-current layers-stylekit-export-slide-1">
				
				<!-- ------------------------------------
						 EXPORT: STEP-1, SLIDE-1
				------------------------------------- -->
				
				<div class="layers-row">
					
					<div class="layers-column layers-span-8 layers-panel">
						
						<form class="layers-stylekit-form layers-stylekit-form-export" action="" method="post">
							
							<div class="layers-row layers-push-top ">
									
								<div class="layers-column layers-span-12 layers-content">
									<div class="layers-section-title layers-small">
										<h3 class="layers-heading"><?php _e( 'StyleKit Export', 'layerswp' ) ?></h3>
										<p class="layers-excerpt">
											<?php _e( 'Choose what will be exported in your StyleKit below.', 'layerswp' ); ?>
										</p>
									</div>
									
								</div>
										
							</div>
							
							<hr class="layers-push-bottom">
							
							<div class="layers-row">
								
								<div class="layers-column layers-span-4 layers-content">
									<h3 class="layers-heading"><?php _e( 'Name', 'layerswp' ) ?></h3>
									<p class="layers-excerpt"><?php _e( 'name your Stylit. You can leave it as you SiteName, or name it something like "Happy Store".', 'layerswp' ) ?></p>
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
									<h3 class="layers-heading"><?php _e( 'Settings', 'layerswp' ) ?></h3>
									<p class="layers-excerpt"><?php _e( 'Select which Layers settings you\'d like export with your StyleKit. These are set in the Customizer.', 'layerswp' ) ?></p>
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
										<h3 class="layers-heading"><?php _e( 'Pages', 'layerswp' ) ?></h3>
										<p class="layers-excerpt"><?php _e( 'Choose which Layers pages you\'d like to export in your StyleKit.', 'layerswp' ) ?></p>
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
														
														<a class="layers-complex-action preview-page" target="_blank" href="<?php echo $page_url; ?>">
															<span><?php _e( 'Preview' , 'layerwp' ) ?></span> <i class=" icon-display"></i>
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
									<h3 class="layers-heading"><?php _e( 'Custom CSS', 'layerswp' ) ?></h3>
									<p class="layers-excerpt"><?php _e( 'Choose whether to export your custom CSS with your StyleKit.', 'layerswp' ) ?></p>
									<?php $this->check_all_ui(); ?>
								</div>
								
								<div class="layers-column layers-span-8 layers-content">
									<div class="layers-panel layers-no-push-bottom layers-stylekit-select-group">
									
										
										<ul class="layers-list layers-list-complex layers-list-stylekit-css">
											<li>
												<label for="css-check" class="group-title">
													<input id="css-check" type="checkbox" checked="checked" name="layers_css" <?php if( isset( $_POST[ 'layers_css' ] ) ) checked( 'yes', $_POST[ 'layers_css' ], TRUE ); ?> value="yes">
													<?php _e( 'CSS', 'layerswp' ) ?>
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
									<a class="more-info" href="#" target="_blank">(more info)</a>
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
									<?php _e( 'If you ever need help with your Layers site please visit our <a href="http://docs.layerswp.com" rel="nofollow">helpful documentation.</a>', 'layerswp' ) ?>
								</li>
							</ul>
						</div>
					</div>
			
				</div>
	
			</div>
			<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-inactive layers-stylekit-export-slide-2">
				
				<!-- ------------------------------------
						EXPORT: STEP-1, SLIDE-2
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
									<?php _e( 'If you\'re ever stuck or need help with your Layers site please visit our <a href="http://docs.layerswp.com" rel="nofollow">helpful documentation.</a>', 'layerswp' ) ?>
								</li>
								<li class="pro-tip">
									<?php _e( 'For the Pros: Layers will automatically assign the tagline to Settings → General.', 'layerswp' ) ?>
								</li>
							</ul>
						</div>
					</div>
				
				</div>
				
			</div>
			<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-inactive layers-stylekit-export-slide-3">
				
				<!-- ------------------------------------
						EXPORT: STEP-1, SLIDE-3
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
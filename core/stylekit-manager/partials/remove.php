
<?php if ( 'layers-stylekit-remove-step-1' == $current_step ): ?>
	
	<div class="layers-onboard-slider">
		<div class="layers-onboard-slide layers-animate layers-onboard-slide-current layers-stylekit-restore-step-1">
			<div class="layers-row">
				<div class="layers-column layers-span-8 layers-panel">
				
					<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-current layers-restore-slide-1">
						
						<div class="layers-row">
			
							<div class="layers-column layers-span-4 layers-content">
									
								<div class="stylekit-statement-holder">
									<i class="layers-button-icon-dashboard layers-stylekit-icon"></i>
								</div>
								
							</div>
							<div class="layers-column layers-span-8 layers-content">
								
								<div class="stylekit-statement">
									
									<div class="layers-section-title layers-small">
										<h3 class="layers-heading"><?php _e( 'Remove StyleKit', 'layerswp' ) ?></h3>
									</div>
									
									<div class="layers-panel layers-push-bottom">
										<ul class="layers-list">
											
											<?php
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
											endforeach;
											
											if ( isset( $stylekit_json['settings'] ) ) {
												?>
												<li class="tick crossed">
													<?php _e( 'Settings', 'layerswp' ) ?> - <em><?php _e( 'will be rolled back' , 'layerwp' ) ?></em>
												</li>
												<?php
											}
											
											if ( isset( $stylekit_json['internal_data']['page_ids'] ) ) {
												foreach ( $stylekit_json['internal_data']['page_ids'] as $page_id ) {
													
													$title = get_the_title( $page_id );
													$permalink = get_permalink( $page_id );
													?>
													<li class="tick crossed layers-stylekit-link">
														<em>"<?php echo $title ?>"</em> <?php _e( 'Page' , 'layerwp' ) ?> - <em><?php _e( 'will be deleted' , 'layerwp' ) ?></em>
														
														<a class="layers-complex-action preview-page" target="blank" href="<?php echo esc_url( $permalink ) ?>">
															<span><?php _e( 'Preview' , 'layerwp' ) ?></span> <i class=" icon-display"></i>
														</a>
													</li>
													<?php
												}
											}
											
											if ( isset( $stylekit_json['css'] ) ) {
												?>
												<li class="tick crossed">
													<?php _e( 'Custom CSS', 'layerswp' ) ?> - <em><?php _e( 'will be rolled back' , 'layerwp' ) ?></em>
												</li>
												<?php
											}
											?>
											
										</ul>
									</div>
									
									<a class="layers-button btn-primary layers-stylekit-remove-stylekit-button" target="blank" href="#">
										<?php _e( 'Remove StyleKit' , 'layerswp' ) ?>
									</a>
									
								</div>
								
							</div>
						
						</div>
					
					</div>
					<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-inactive layers-restore-slide-2">
						
						<div class="layers-load-bar layers-load-bar-floater layers-stylekit-load-bar layers-hide">
							<span class="layers-progress zero"></span>
						</div>
						
						<div class="layers-hold-open"></div>
					
					</div>
					<div class="layers-animate layers-stylekit-slide layers-stylekit-slide-inactive layers-restore-slide-3">
						
						<div class="layers-hold-open"></div>
						
					</div>
					
				</div>
			</div>
		</div>
	</div>

<?php endif; ?>
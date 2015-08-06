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
								
								<a class="layers-complex-action preview-page" target="_blank" href="<?php echo esc_url( $permalink ) ?>">
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
			
			<a class="layers-button btn-primary layers-pull-right-NOT" target="_blank" href="<?php echo get_home_url(); ?>">
				<?php _e( 'Visit your Site' , 'layerswp' ) ?>
			</a>
			
			<a class="layers-button btn-primary layers-pull-right-NOT" target="_blank" href="<?php echo wp_customize_url() ?>">
				<?php _e( 'Customize your Site' , 'layerswp' ) ?>
			</a>
			
			<a class="layers-back-a-step" href="#">
				&#8592; <?php _e( 'Back' , 'layerswp' ) ?>
			</a>
			
		</div>
		
	</div>

</div>

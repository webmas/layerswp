
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

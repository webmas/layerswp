<?php
if ( isset( $stylekit_json['settings'] ) || isset( $stylekit_json['pages'] ) || isset( $stylekit_json['css'] ) ) {
	
	// If this is a stylkit with options
	
	?>
	
	<form class="layers-stylekit-form layers-stylekit-form-import" method="post" action="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_manager', 'step' => 'layers-stylekit-import-step-3' ), get_admin_url() . 'admin.php' ) ?>">
		
		<!-- Simple UI -->
		
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
		
		<!-- /Simple UI -->
		
		<!-- Advanced Options UI -->
		
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
		
		<!-- /Advanced Options UI -->
		
		<div class="layers-button-well">
			<input type="submit" class="layers-button btn-large btn-primary layers-pull-right layers-stylekit-import-step-2-submit" value="Import StyleKit" >
		</div>
		
	</form>
	<?php
}
else {
	
	// If this is not a stylekit
	?>
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
	
	<?php
}
?>

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
								<a href="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_manager', 'tab' => $tab_key, 'step' => $tab_key . '-step-1' ), get_admin_url() . 'admin.php' ); ?>"><?php echo $tab_label; ?></a>
							</li>
						<?php } ?>
					</ul>
				</nav>
			<?php endif; ?>
			
		</header>
		
		<?php if ( FALSE == $current_tab ) : ?>
			
			<!-- ------------------------------------
			
			
							 SPLASH
						
						
			------------------------------------- -->
			
			<div></div>
			
			<div class="layers-row">
				<div class="layers-column layers-span-6">
				
					<a class="layers-button layers-stylekit-button" href="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_manager', 'tab' => 'layers-stylekit-import', 'step' => 'layers-stylekit-import-step-1' ), get_admin_url() . 'admin.php' ); ?>" >Import StyleKit</a>
					
				</div>
				<div class="layers-column layers-span-6">
				
					<a class="layers-button layers-stylekit-button" href="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_manager', 'tab' => 'layers-stylekit-export', 'step' => 'layers-stylekit-export-step-1' ), get_admin_url() . 'admin.php' ); ?>" >Export StyleKit</a>
					
				</div>
			</div>
			
		<?php elseif ( 'layers-stylekit-import' == $current_tab ) : ?>
		
			<?php include( get_template_directory() . '/core/stylekit-manager/partials/import.php' ); ?>
		
		<?php elseif ( 'layers-stylekit-export' == $current_tab ) : ?>
			
			<?php include( get_template_directory() . '/core/stylekit-manager/partials/export.php' ); ?>
		
		<?php elseif ( 'layers-stylekit-remove' == $current_tab ) : ?>
			
			<?php include( get_template_directory() . '/core/stylekit-manager/partials/remove.php' ); ?>
			
		<?php endif; ?>
		
		
		<?php if ( TRUE || 'layers-stylekit-remove' !== $current_tab ) : ?>
		
			<div class="layers-row layers-middled layers-stylekit-history">
				<div class="layers-column layers-span-12">
					<!-- <i class="layers-button-icon-dashboard layers-stylekit-icon"></i> -->
					<span class="layers-stylekit-history-container">
						<span class="layers-stylekit-current">
							<small class="layers-label label-secondary">Current StyleKit</small>&nbsp; <span class="stylekit-history-label"><strong>Pinkerkit</strong> - Settings &bull;&bull;&bull;, 3 Pages &uarr;, Custom CSS.</span>
						</span>
						<a href="<?php echo add_query_arg( array( 'page' => 'layers_stylekit_manager', 'tab' => 'layers-stylekit-remove', 'step' => 'layers-stylekit-remove-step-1' ), get_admin_url() . 'admin.php' ); ?>" class="layers-stylekit-rollback"><span class="layers-stylekit-rollback-times">&times;</span>Remove</a>
						<div class="layers-stylekit-previous">
							<small class="layers-label label-secondary">Replace with previous</small>&nbsp; <span class="stylekit-history-label"><strong>Pinkerkit</strong> - Settings &bull;&bull;&bull;, 3 Pages &uarr;, Custom CSS.</span>
						</div>
					</span>
				</div>
			</div>
		
		<?php endif; ?>
		
	</div>
</div>

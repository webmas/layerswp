<?php
/**
 * Theme Upgrader class for WordPress Themes, It is designed to upgrade/install themes from a local zip, remote zip URL, or uploaded zip file.
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 2.8.0
 */
class StyleKit_Importer_Upgrader extends WP_Upgrader {
	
	/**
	 * Result of the theme upgrade offer.
	 *
	 * @since 2.8.0
	 * @var array|WP_Erorr $result
	 * @see WP_Upgrader::$result
	 */
	public $result;

	/**
	 * Whether multiple plugins are being upgraded/installed in bulk.
	 *
	 * @since 2.9.0
	 * @var bool $bulk
	 */
	public $bulk = false;

	/**
	 * Initialize the upgrade strings.
	 *
	 * @since 2.8.0
	 */
	public function upgrade_strings() {
		$this->strings['up_to_date'] = __('The theme is at the latest version.', 'layerswp' );
		$this->strings['no_package'] = __('Update package not available.', 'layerswp' );
		$this->strings['downloading_package'] = __('Downloading update from <span class="code">%s</span>&#8230;', 'layerswp' );
		$this->strings['unpack_package'] = __('Unpacking the update&#8230;', 'layerswp' );
		$this->strings['remove_old'] = __('Removing the old version of the theme&#8230;', 'layerswp' );
		$this->strings['remove_old_failed'] = __('Could not remove the old theme.', 'layerswp' );
		$this->strings['process_failed'] = __('Theme update failed.', 'layerswp' );
		$this->strings['process_success'] = __('Theme updated successfully.', 'layerswp' );
	}

	/**
	 * Initialize the install strings.
	 *
	 * @since 2.8.0
	 */
	public function install_strings() {
		$this->strings['no_package'] = __('StyleKit not available.', 'layerswp' );
		$this->strings['downloading_package'] = __('Downloading StyleKit from <span class="code">%s</span>&#8230;', 'layerswp' );
		$this->strings['unpack_package'] = __('Unpacking the StyleKit&#8230;', 'layerswp' );
		$this->strings['installing_package'] = __('Importing the StyleKit&#8230;', 'layerswp' );
		$this->strings['no_files'] = __('The StyleKit contains no files.', 'layerswp' );
		$this->strings['process_failed'] = __('StyleKit import failed.', 'layerswp' );
		$this->strings['process_success'] = __('StyleKit installed successfully.', 'layerswp' );
		/* translators: 1: StyleKit name, 2: version */
		$this->strings['process_success_specific'] = __('Successfully installed the StyleKit <strong>%1$s %2$s</strong>.', 'layerswp' );
		$this->strings['parent_theme_search'] = __('This StyleKit requires a parent theme. Checking if it is installed&#8230;', 'layerswp' );
		/* translators: 1: StyleKit name, 2: version */
		$this->strings['parent_theme_prepare_install'] = __('Preparing to import <strong>%1$s %2$s</strong>&#8230;', 'layerswp' );
		/* translators: 1: StyleKit name, 2: version */
		$this->strings['parent_theme_currently_installed'] = __('The parent theme, <strong>%1$s %2$s</strong>, is currently installed.', 'layerswp' );
		/* translators: 1: StyleKit name, 2: version */
		$this->strings['parent_theme_install_success'] = __('Successfully installed the parent theme, <strong>%1$s %2$s</strong>.', 'layerswp' );
		$this->strings['parent_theme_not_found'] = __('<strong>The parent StyleKit could not be found.</strong> You will need to import the parent theme, <strong>%s</strong>, before you can use this child theme.', 'layerswp' );
	}

	/**
	 * Install a theme package.
	 *
	 * @since 2.8.0
	 * @since 3.7.0 The `$args` parameter was added, making clearing the update cache optional.
	 *
	 * @param string $package The full local path or URI of the package.
	 * @param array  $args {
	 *     Optional. Other arguments for installing a theme package. Default empty array.
	 *
	 *     @type bool $clear_update_cache Whether to clear the updates cache if successful.
	 *                                    Default true.
	 * }
	 *
	 * @return bool|WP_Error True if the install was successful, false or a {@see WP_Error} object otherwise.
	 */
	public function install( $package, $args = array() ) {

		$defaults = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->install_strings();

		//add_filter('upgrader_source_selection', array($this, 'import_stylekit_package') );

		$result = $this->run( array(
			'package' => $package,
			'clear_working' => false,
			'hook_extra' => array(
				'type' => 'stylekit',
				'action' => 'install',
			),
		) );

		//remove_filter('upgrader_source_selection', array($this, 'import_stylekit_package') );

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

		// Refresh the Theme Update information
		wp_clean_themes_cache( $parsed_args['clear_update_cache'] );

		return $result;
	}
	
	/**
	 * Install a package.
	 *
	 * Copies the contents of a package form a source directory, and installs them in
	 * a destination directory. Optionally removes the source. It can also optionally
	 * clear out the destination folder if it already exists.
	 *
	 * @since 2.8.0
	 *
	 * @param array|string $args {
	 *     Optional. Array or string of arguments for installing a package. Default empty array.
	 *
	 *     @type string $source                      Required path to the package source. Default empty.
	 *     @type bool   $clear_working               Whether to delete the files form the working directory
	 *                                               after copying to the destination. Default false.
	 *     @type bool   $abort_if_destination_exists Whether to abort the installation if
	 *                                               the destination folder already exists. Default true.
	 *     @type array  $hook_extra                  Extra arguments to pass to the filter hooks called by
	 *                                               {@see WP_Upgrader::install_package()}. Default empty array.
	 * }
	 *
	 * @return array|WP_Error The result (also stored in `WP_Upgrader:$result`), or a {@see WP_Error} on failure.
	 */
	public function install_package( $args = array() ) {
		global $wp_filesystem, $wp_theme_directories;

		$defaults = array(
			'source' => '', // Please always pass this
			'clear_working' => false,
			'hook_extra' => array()
		);

		$args = wp_parse_args($args, $defaults);

		// These were previously extract()'d.
		$source = $args['source'];

		@set_time_limit( 300 );

		if ( empty( $source ) ) {
			return new WP_Error( 'bad_request', $this->strings['bad_request'] );
		}
		$this->skin->feedback( 'installing_package' );

		/**
		 * Filter the install response before the installation has started.
		 *
		 * Returning a truthy value, or one that could be evaluated as a WP_Error
		 * will effectively short-circuit the installation, returning that value
		 * instead.
		 *
		 * @since 2.8.0
		 *
		 * @param bool|WP_Error $response   Response.
		 * @param array         $hook_extra Extra arguments passed to hooked filters.
		 */
		$res = apply_filters( 'upgrader_pre_install', true, $args['hook_extra'] );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		//Retain the Original source and destinations
		$remote_source = $args['source'];
		$source_files = array_keys( $wp_filesystem->dirlist( $remote_source ) );

		//Locate which directory to copy to the new folder, This is based on the actual folder holding the files.
		if ( 1 == count( $source_files ) && $wp_filesystem->is_dir( trailingslashit( $args['source'] ) . $source_files[0] . '/' ) ) { //Only one folder? Then we want its contents.
			$source = trailingslashit( $args['source'] ) . trailingslashit( $source_files[0] );
		} elseif ( count( $source_files ) == 0 ) {
			return new WP_Error( 'incompatible_archive_empty', $this->strings['incompatible_archive'], $this->strings['no_files'] ); // There are no files?
		} else { //It's only a single file, the upgrader will use the foldername of this file as the destination folder. foldername is based on zip filename.
			$source = trailingslashit( $args['source'] );
		}

		/**
		 * Filter the source file location for the upgrade package.
		 *
		 * @since 2.8.0
		 *
		 * @param string      $source        File source location.
		 * @param string      $remote_source Remove file source location.
		 * @param WP_Upgrader $this          WP_Upgrader instance.
		 */
		$source = apply_filters( 'upgrader_source_selection', $source, $remote_source, $this );
		if ( is_wp_error( $source ) ) {
			return $source;
		}
		
		//Clear the Working folder?
		if ( $args['clear_working'] ) {
			$wp_filesystem->delete( $remote_source, true );
		}

		$this->result = compact( 'source', 'source_files' );

		/**
		 * Filter the install response after the installation has finished.
		 *
		 * @since 2.8.0
		 *
		 * @param bool  $response   Install response.
		 * @param array $hook_extra Extra arguments passed to hooked filters.
		 * @param array $result     Installation result data.
		 */
		$res = apply_filters( 'upgrader_post_install', true, $args['hook_extra'], $this->result );

		if ( is_wp_error($res) ) {
			$this->result = $res;
			return $res;
		}
		
		//s( 'DONESKIE!!!!!!', $this->result );

		//Bombard the calling function will all the info which we've just used.
		return $this->result;
	}
	

}

<?php
/**
 * Theme Upgrader class for WordPress Themes, It is designed to upgrade/install themes from a local zip, remote zip URL, or uploaded zip file.
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 2.8.0
 */
//class StyleKit_Importer_Upgrader extends WP_Upgrader {
class StyleKit_Importer_Upgrader_NEW {
	
	
	/**
	 * The error/notification strings used to update the user on the progress.
	 *
	 * @since 2.8.0
	 * @var string $strings
	 */
	public $strings = array();

	/**
	 * The upgrader skin being used.
	 *
	 * @since 2.8.0
	 * @var WP_Upgrader_Skin $skin
	 */
	public $skin = null;

	/**
	 * The result of the installation.
	 *
	 * This is set by {@see WP_Upgrader::install_package()}, only when the package is installed
	 * successfully. It will then be an array, unless a {@see WP_Error} is returned by the
	 * {@see 'upgrader_post_install'} filter. In that case, the `WP_Error` will be assigned to
	 * it.
	 *
	 * @since 2.8.0
	 * @var WP_Error|array $result {
	 *      @type string $source             The full path to the source the files were installed from.
	 *      @type string $source_files       List of all the files in the source directory.
	 *      @type string $destination        The full path to the install destination folder.
	 *      @type string $destination_name   The name of the destination folder, or empty if `$destination`
	 *                                       and `$local_destination` are the same.
	 *      @type string $local_destination  The full local path to the destination folder. This is usually
	 *                                       the same as `$destination`.
	 *      @type string $remote_destination The full remote path to the destination folder
	 *                                       (i.e., from `$wp_filesystem`).
	 *      @type bool   $clear_destination  Whether the destination folder was cleared.
	 * }
	 */
	public $result = array();

	/**
	 * The total number of updates being performed.
	 *
	 * Set by the bulk update methods.
	 *
	 * @since 3.0.0
	 * @var int $update_count
	 */
	public $update_count = 0;

	/**
	 * The current update if multiple updates are being performed.
	 *
	 * Used by the bulk update methods, and incremented for each update.
	 *
	 * @since 3.0.0
	 * @var int
	 */
	public $update_current = 0;

	/**
	 * Whether multiple plugins are being upgraded/installed in bulk.
	 *
	 * @since 2.9.0
	 * @var bool $bulk
	 */
	public $bulk = false;
	
	/**
	 * Initialize the upgrader.
	 *
	 * This will set the relationship between the skin being used and this upgrader,
	 * and also add the generic strings to `WP_Upgrader::$strings`.
	 *
	 * @since 2.8.0
	 */
	public function init() {
		//$this->skin->set_upgrader($this);
		//$this->generic_strings();
	}

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
	 * Run an upgrade/install.
	 *
	 * Attempts to download the package (if it is not a local file), unpack it, and
	 * install it in the destination folder.
	 *
	 * @since 2.8.0
	 *
	 * @param array $options {
	 *     Array or string of arguments for upgrading/installing a package.
	 *
	 *     @type string $package                     The full path or URI of the package to install.
	 *                                               Default empty.
	 *     @type string $destination                 The full path to the destination folder.
	 *                                               Default empty.
	 *     @type bool   $clear_destination           Whether to delete any files already in the
	 *                                               destination folder. Default false.
	 *     @type bool   $clear_working               Whether to delete the files form the working
	 *                                               directory after copying to the destination.
	 *                                               Default false.
	 *     @type bool   $abort_if_destination_exists Whether to abort the installation if the destination
	 *                                               folder already exists. When true, `$clear_destination`
	 *                                               should be false. Default true.
	 *     @type bool   $is_multi                    Whether this run is one of multiple upgrade/install
	 *                                               actions being performed in bulk. When true, the skin
	 *                                               {@see WP_Upgrader::header()} and {@see WP_Upgrader::footer()}
	 *                                               aren't called. Default false.
	 *     @type array  $hook_extra                  Extra arguments to pass to the filter hooks called by
	 *                                               {@see WP_Upgrader::run()}.
	 * }
	 *
	 * @return array|false|WP_error The result from self::install_package() on success, otherwise a WP_Error,
	 *                              or false if unable to connect to the filesystem.
	 */
	public function run( $options ) {

		$defaults = array(
			'package' => '', // Please always pass this.
			'destination' => '', // And this
			'clear_destination' => false,
			'abort_if_destination_exists' => true, // Abort if the Destination directory exists, Pass clear_destination as false please
			'clear_working' => true,
			'is_multi' => false,
			'hook_extra' => array() // Pass any extra $hook_extra args here, this will be passed to any hooked filters.
		);

		$options = wp_parse_args( $options, $defaults );

		if ( ! $options['is_multi'] ) { // call $this->header separately if running multiple times
			//$this->skin->header();
		}

		// Connect to the Filesystem first.
		$res = $this->fs_connect( array( WP_CONTENT_DIR, $options['destination'] ) );
		// Mainly for non-connected filesystem.
		if ( ! $res ) {
			if ( ! $options['is_multi'] ) {
				//$this->skin->footer();
			}
			return false;
		}

		//$this->skin->before();

		if ( is_wp_error($res) ) {
			//$this->skin->error($res);
			//$this->skin->after();
			if ( ! $options['is_multi'] ) {
				//$this->skin->footer();
			}
			return $res;
		}

		//Download the package (Note, This just returns the filename of the file if the package is a local file)
		$download = $this->download_package( $options['package'] );
		if ( is_wp_error($download) ) {
			//$this->skin->error($download);
			//$this->skin->after();
			if ( ! $options['is_multi'] ) {
				//$this->skin->footer();
			}
			return $download;
		}

		$delete_package = ( $download != $options['package'] ); // Do not delete a "local" file

		//Unzips the file into a temporary directory
		$working_dir = $this->unpack_package( $download, $delete_package );
		if ( is_wp_error($working_dir) ) {
			//$this->skin->error($working_dir);
			//$this->skin->after();
			if ( ! $options['is_multi'] ) {
				//$this->skin->footer();
			}
			return $working_dir;
		}

		//With the given options, this installs it to the destination directory.
		$result = $this->install_package( array(
			'source' => $working_dir,
			'destination' => $options['destination'],
			'clear_destination' => $options['clear_destination'],
			'abort_if_destination_exists' => $options['abort_if_destination_exists'],
			'clear_working' => $options['clear_working'],
			'hook_extra' => $options['hook_extra']
		) );

		//$this->skin->set_result($result);
		if ( is_wp_error($result) ) {
			//$this->skin->error($result);
			//$this->skin->feedback('process_failed');
		} else {
			//Install Succeeded
			//$this->skin->feedback('process_success');
		}

		//$this->skin->after();

		if ( ! $options['is_multi'] ) {

			/** This action is documented in wp-admin/includes/class-wp-upgrader.php */
			do_action( 'upgrader_process_complete', $this, $options['hook_extra'] );
			//$this->skin->footer();
		}

		return $result;
	}
	
	
	/**
	 * Connect to the filesystem.
	 *
	 * @since 2.8.0
	 *
	 * @param array $directories                  Optional. A list of directories. If any of these do
	 *                                            not exist, a {@see WP_Error} object will be returned.
	 *                                            Default empty array.
	 * @param bool  $allow_relaxed_file_ownership Whether to allow relaxed file ownership.
	 *                                            Default false.
	 * @return bool|WP_Error True if able to connect, false or a {@see WP_Error} otherwise.
	 */
	public function fs_connect( $directories = array(), $allow_relaxed_file_ownership = false ) {
		global $wp_filesystem;

		if ( false === ( $credentials = $this->request_filesystem_credentials( false, $directories[0], $allow_relaxed_file_ownership ) ) ) {
			return false;
		}

		if ( ! WP_Filesystem( $credentials, $directories[0], $allow_relaxed_file_ownership ) ) {
			$error = true;
			if ( is_object($wp_filesystem) && $wp_filesystem->errors->get_error_code() )
				$error = $wp_filesystem->errors;
			// Failed to connect, Error and request again
			$this->skin->request_filesystem_credentials( $error, $directories[0], $allow_relaxed_file_ownership );
			return false;
		}

		if ( ! is_object($wp_filesystem) )
			return new WP_Error('fs_unavailable', $this->strings['fs_unavailable'] );

		if ( is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code() )
			return new WP_Error('fs_error', $this->strings['fs_error'], $wp_filesystem->errors);

		foreach ( (array)$directories as $dir ) {
			switch ( $dir ) {
				case ABSPATH:
					if ( ! $wp_filesystem->abspath() )
						return new WP_Error('fs_no_root_dir', $this->strings['fs_no_root_dir']);
					break;
				case WP_CONTENT_DIR:
					if ( ! $wp_filesystem->wp_content_dir() )
						return new WP_Error('fs_no_content_dir', $this->strings['fs_no_content_dir']);
					break;
				case WP_PLUGIN_DIR:
					if ( ! $wp_filesystem->wp_plugins_dir() )
						return new WP_Error('fs_no_plugins_dir', $this->strings['fs_no_plugins_dir']);
					break;
				case get_theme_root():
					if ( ! $wp_filesystem->wp_themes_dir() )
						return new WP_Error('fs_no_themes_dir', $this->strings['fs_no_themes_dir']);
					break;
				default:
					if ( ! $wp_filesystem->find_folder($dir) )
						return new WP_Error( 'fs_no_folder', sprintf( $this->strings['fs_no_folder'], esc_html( basename( $dir ) ) ) );
					break;
			}
		}
		return true;
	}
	
	
	public function request_filesystem_credentials( $error = false, $context = '', $allow_relaxed_file_ownership = false ) {
		if ( $context ) {
			$this->options['context'] = $context;
		}
		// TODO: fix up request_filesystem_credentials(), or split it, to allow us to request a no-output version
		// This will output a credentials form in event of failure, We don't want that, so just hide with a buffer
		ob_start();
		
		$url = $this->options['url'];
		if ( ! $context ) {
			$context = $this->options['context'];
		}
		if ( !empty($this->options['nonce']) ) {
			$url = wp_nonce_url($url, $this->options['nonce']);
		}

		$extra_fields = array();

		$result = request_filesystem_credentials( $url, '', $error, $context, $extra_fields, $allow_relaxed_file_ownership );
		
		ob_end_clean();
		
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
		//$this->skin->feedback( 'installing_package' );

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
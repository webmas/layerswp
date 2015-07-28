<?php
/**
 * Theme Upgrader class for WordPress Themes, It is designed to upgrade/install themes from a local zip, remote zip URL, or uploaded zip file.
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 2.8.0
 */
class StyleKit_Importer_Upgrader {

	/**
	 * Whether multiple plugins are being upgraded/installed in bulk.
	 *
	 * @var bool $bulk
	 */
	public $bulk = false;
	
	/**
	 * The error/notification strings used to update the user on the progress.
	 *
	 * @var string $strings
	 */
	public $strings = array();
	
	/**
	 * The upgrader skin being used.
	 *
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
	 * @var int $update_count
	 */
	public $update_count = 0;
	
	/**
	 * The current update if multiple updates are being performed.
	 *
	 * Used by the bulk update methods, and incremented for each update.
	 *
	 * @var int
	 */
	public $update_current = 0;
	
	
	public $options = array();
	
	/**
	 * Construct the upgrader with a skin.
	 *
	 * @since 2.8.0
	 *
	 * @param WP_Upgrader_Skin $skin The upgrader skin to use. Default is a {@see WP_Upgrader_Skin}
	 *                               instance.
	 */
	public function __construct() {
	}
	
	/**
	 * Initialize the upgrader.
	 *
	 * This will set the relationship between the skin being used and this upgrader,
	 * and also add the generic strings to `WP_Upgrader::$strings`.
	 */
	public function init() {
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
		
		//$this->skin = new StyleKit_Importer_Skin();
		
		// Init Upgrader Skin
		//$this->skin->set_upgrader( $this );
		
		$defaults = array(
			'package' => $package,
			'clear_working' => false,
			'hook_extra' => array( // Pass any extra $hook_extra args here, this will be passed to any hooked filters.
			'type' => 'stylekit',
			'action' => 'install',
			),
			'destination' => '', // And this
			'clear_destination' => false,
			'abort_if_destination_exists' => true, // Abort if the Destination directory exists, Pass clear_destination as false please
		);
		$args = wp_parse_args( $args, $defaults );
		
		// Header
		//$this->skin->header();

		// Connect to the Filesystem first.
		$res = $this->fs_connect( array( WP_CONTENT_DIR, $args['destination'] ) );
		
		// Mainly for non-connected filesystem.
		if ( !$res ) {
			//$this->skin->footer();
			return false;
		}

		if ( is_wp_error( $res ) ) {
			//$this->skin->error($res);
			//$this->skin->footer();
			return $res;
		}
		
		// This first checks to make sure that the file is local and does not need to be downloaded.
		// WP - Download the package (Note, This just returns the filename of the file if the package is a local file)
		$download = $this->download_package( $args['package'] );
		if ( is_wp_error( $download ) ) {
			// $this->skin->error( $download );
			// $this->skin->footer();
			return $download;
		}
		
		// Unzips the file into a temporary directory
		// upgrade/StyleKitName
		$delete_package = ( $download != $args['package'] ); // Do not delete a "local" file
		$working_dir = $this->unpack_package( $download, $delete_package );
		if ( is_wp_error($working_dir) ) {
			// $this->skin->error($working_dir);
			// $this->skin->footer();
			return $working_dir;
		}
		
		// This returns a list of what's in the folder, in StyleKit this is just the internal folder eg /StylkeitName/.
		// WP - With the given options, this installs it to the destination directory.
		$result = $this->install_package( array(
			'source'                      => $working_dir,
			'destination'                 => $args['destination'],
			'clear_destination'           => $args['clear_destination'],
			'abort_if_destination_exists' => $args['abort_if_destination_exists'],
			'clear_working'               => $args['clear_working'],
			'hook_extra'                  => $args['hook_extra']
		) );

		//$this->skin->set_result( $result );
		
		if ( is_wp_error( $result ) ) {
			// $this->skin->error($result);
			// $this->skin->feedback('process_failed');
			return $result;
		} else {
			//Install Succeeded
			//$this->skin->feedback('process_success');
		}
		
		/** This action is documented in wp-admin/includes/class-wp-upgrader.php */
		
		if ( !$this->result || is_wp_error($this->result) )
			return $this->result;

		// Refresh the Theme Update information
		//wp_clean_themes_cache( $args['clear_update_cache'] );

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
			$this->request_filesystem_credentials( $error, $directories[0], $allow_relaxed_file_ownership );
			
			return false;
		}

		if ( ! is_object($wp_filesystem) )
			return new WP_Error('fs_unavailable', __('Could not access filesystem.') );

		if ( is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code() )
			return new WP_Error('fs_error', __('Filesystem error.'), $wp_filesystem->errors );

		foreach ( (array)$directories as $dir ) {
			switch ( $dir ) {
				case ABSPATH:
					if ( ! $wp_filesystem->abspath() )
						return new WP_Error('fs_no_root_dir', __('Unable to locate WordPress Root directory.') );
					break;
				case WP_CONTENT_DIR:
					if ( ! $wp_filesystem->wp_content_dir() )
						return new WP_Error('fs_no_content_dir', __('Unable to locate WordPress Content directory (wp-content).') );
					break;
				case WP_PLUGIN_DIR:
					if ( ! $wp_filesystem->wp_plugins_dir() )
						return new WP_Error('fs_no_plugins_dir', __('Unable to locate WordPress Plugin directory.') );
					break;
				case get_theme_root():
					if ( ! $wp_filesystem->wp_themes_dir() )
						return new WP_Error('fs_no_themes_dir', __('Unable to locate WordPress Theme directory.') );
					break;
				default:
					if ( ! $wp_filesystem->find_folder($dir) )
						return new WP_Error( 'fs_no_folder', sprintf( __('Unable to locate needed folder (%s).'), esc_html( basename( $dir ) ) ) );
					break;
			}
		}
		return true;
	}
	
	public function request_filesystem_credentials( $error = false, $context = '', $allow_relaxed_file_ownership = false ) {
		
		$args = array(
			'url' => '',
			'nonce' => '',
			'title' => '',
			'context' => false
		);
		
		if ( $context ) {
			$args['context'] = $context;
		}
		
		// TODO: fix up request_filesystem_credentials(), or split it, to allow us to request a no-output version
		// This will output a credentials form in event of failure, We don't want that, so just hide with a buffer
		ob_start();
		
		$url = $args['url'];
		
		if ( ! $context ) {
			$context = $args['context'];
		}
		if ( !empty($args['nonce']) ) {
			$url = wp_nonce_url( $url, $args['nonce'] );
		}

		$extra_fields = array();
		
		//s($args);
		
		$result = request_filesystem_credentials( $url, '', $error, $context, $extra_fields, $allow_relaxed_file_ownership );
		
		//s($result);
		
		ob_end_clean();
		
		return $result;
	}
	
	/**
	 * Download a package.
	 *
	 * @since 2.8.0
	 *
	 * @param string $package The URI of the package. If this is the full path to an
	 *                        existing local file, it will be returned untouched.
	 * @return string|WP_Error The full path to the downloaded package file, or a {@see WP_Error} object.
	 */
	public function download_package( $package ) {

		/**
		 * Filter whether to return the package.
		 *
		 * @since 3.7.0
		 *
		 * @param bool        $reply   Whether to bail without returning the package.
		 *                             Default false.
		 * @param string      $package The package file name.
		 * @param WP_Upgrader $this    The WP_Upgrader instance.
		 */
		$reply = apply_filters( 'upgrader_pre_download', false, $package, $this );
		
		if ( false !== $reply )
			return $reply;

		if ( !preg_match('!^(http|https|ftp)://!i', $package) && file_exists($package) ) //Local file or remote?
			return $package; //must be a local file..

		if ( empty($package) )
			return new WP_Error( 'no_package', __('StyleKit not available.', 'layerswp' ) );

		//$this->skin->feedback('downloading_package', $package);

		$download_file = download_url( $package );

		if ( is_wp_error($download_file) )
			return new WP_Error('download_failed', __('Download failed.'), $download_file->get_error_message());

		return $download_file;
	}
	
	/**
	 * Unpack a compressed package file.
	 *
	 * @since 2.8.0
	 *
	 * @param string $package        Full path to the package file.
	 * @param bool   $delete_package Optional. Whether to delete the package file after attempting
	 *                               to unpack it. Default true.
	 * @return string|WP_Error The path to the unpacked contents, or a {@see WP_Error} on failure.
	 */
	public function unpack_package( $package, $delete_package = true ) {
		global $wp_filesystem;

		//$this->skin->feedback('unpack_package');

		$upgrade_folder = $wp_filesystem->wp_content_dir() . 'upgrade/';

		//Clean up contents of upgrade directory beforehand.
		$upgrade_files = $wp_filesystem->dirlist($upgrade_folder);
		if ( !empty($upgrade_files) ) {
			foreach ( $upgrade_files as $file )
				$wp_filesystem->delete($upgrade_folder . $file['name'], true);
		}

		// We need a working directory - Strip off any .tmp or .zip suffixes
		$working_dir = $upgrade_folder . basename( basename( $package, '.tmp' ), '.zip' );

		// Clean up working directory
		if ( $wp_filesystem->is_dir($working_dir) )
			$wp_filesystem->delete($working_dir, true);

		// Unzip package to working directory
		$result = unzip_file( $package, $working_dir );

		// Once extracted, delete the package if required.
		if ( $delete_package )
			unlink($package);

		if ( is_wp_error($result) ) {
			$wp_filesystem->delete($working_dir, true);
			if ( 'incompatible_archive' == $result->get_error_code() ) {
				return new WP_Error( 'incompatible_archive', __('The package could not be installed.'), $result->get_error_data() );
			}
			return $result;
		}

		return $working_dir;
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
			return new WP_Error( 'bad_request', __('Invalid Data provided.') );
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
		}
		elseif ( count( $source_files ) == 0 ) {
			return new WP_Error( 'incompatible_archive_empty', __('The package could not be installed.'), __('The StyleKit contains no files.', 'layerswp' ) ); // There are no files?
		}
		else { //It's only a single file, the upgrader will use the foldername of this file as the destination folder. foldername is based on zip filename.
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

		//Bombard the calling function will all the info which we've just used.
		return $this->result;
	}
	

}

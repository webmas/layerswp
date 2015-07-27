<?php

/**
 * Generic Skin for the WordPress Upgrader classes. This skin is designed to be extended for specific purposes.
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 2.8.0
 */
class StyleKit_Importer_Skin {

	public $upgrader;
	public $done_header = false;
	public $done_footer = false;
	public $result = false;
	public $options = array();

	public function __construct($args = array()) {
		$defaults = array(
			'url' => '',
			'nonce' => '',
			'title' => '',
			'context' => false
		);
		$this->options = wp_parse_args($args, $defaults);
	}

	/**
	 * @param WP_Upgrader $upgrader
	 */
	public function set_upgrader(&$upgrader) {
		
		if ( is_object($upgrader) )
			$this->upgrader =& $upgrader;
	}

	public function set_result($result) {
		$this->result = $result;
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
			$url = wp_nonce_url( $url, $this->options['nonce'] );
		}

		$extra_fields = array();
		
		$result = request_filesystem_credentials( $url, '', $error, $context, $extra_fields, $allow_relaxed_file_ownership );
		
		ob_end_clean();
		
		return $result;
	}

	public function header() {
		ob_start();
	}
	
	public function footer() {
		$output = ob_get_contents();
		
		if ( ! empty( $output ) )
			$this->feedback( $output );
		
		ob_end_clean();
	}

	public function error($errors) {
		if ( ! $this->done_header )
			$this->header();
		if ( is_string($errors) ) {
			$this->feedback($errors);
		} elseif ( is_wp_error($errors) && $errors->get_error_code() ) {
			foreach ( $errors->get_error_messages() as $message ) {
				if ( $errors->get_error_data() && is_string( $errors->get_error_data() ) )
					$this->feedback($message . ' ' . esc_html( strip_tags( $errors->get_error_data() ) ) );
				else
					$this->feedback($message);
			}
		}
	}

	/**
	 * @param string|array|WP_Error $data
	 */
	public function feedback( $data ) {
		if ( is_wp_error( $data ) ) {
			$string = $data->get_error_message();
		} elseif ( is_array( $data ) ) {
			return;
		} else {
			$string = $data;
		}
		if ( ! empty( $this->upgrader->strings[ $string ] ) )
			$string = $this->upgrader->strings[ $string ];

		if ( strpos( $string, '%' ) !== false ) {
			$args = func_get_args();
			$args = array_splice( $args, 1 );
			if ( ! empty( $args ) )
				$string = vsprintf( $string, $args );
		}

		$string = trim( $string );

		// Only allow basic HTML in the messages, as it'll be used in emails/logs rather than direct browser output.
		$string = wp_kses( $string, array(
			'a' => array(
				'href' => true
			),
			'br' => true,
			'em' => true,
			'strong' => true,
		) );

		if ( empty( $string ) )
			return;

		$this->messages[] = $string;
	}
	
	public function get_upgrade_messages() {
		return $this->messages;
	}

	/**
	 * Output JavaScript that calls function to decrement the update counts.
	 *
	 * @since 3.9.0
	 *
	 * @param string $type Type of update count to decrement. Likely values include 'plugin',
	 *                     'theme', 'translation', etc.
	 */
	protected function decrement_update_count( $type ) {
		if ( ! $this->result || is_wp_error( $this->result ) || 'up_to_date' === $this->result ) {
			return;
		}

		if ( defined( 'IFRAME_REQUEST' ) ) {
			echo '<script type="text/javascript">
					if ( window.postMessage && JSON ) {
						window.parent.postMessage( JSON.stringify( { action: "decrementUpdateCount", upgradeType: "' . $type . '" } ), window.location.protocol + "//" + window.location.hostname );
					}
				</script>';
		} else {
			echo '<script type="text/javascript">
					(function( wp ) {
						if ( wp && wp.updates.decrementCount ) {
							wp.updates.decrementCount( "' . $type . '" );
						}
					})( window.wp );
				</script>';
		}
	}

	public function bulk_header() {}
	public function bulk_footer() {}
}
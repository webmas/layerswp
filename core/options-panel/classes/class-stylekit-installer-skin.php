<?php
/**
 * Theme Installer Skin for the WordPress Theme Installer.
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 2.8.0
 */
class StyleKit_Importer_Upgrader_Skin extends WP_Upgrader_Skin {
	
	public $api;
	
	public $type;

	public function __construct($args = array()) {
		$defaults = array( 'type' => 'web', 'url' => '', 'theme' => '', 'nonce' => '', 'title' => '' );
		$args = wp_parse_args($args, $defaults);

		$this->type = $args['type'];
		$this->api = isset($args['api']) ? $args['api'] : array();

		parent::__construct($args);
	}

	public function before() {
		if ( !empty($this->api) )
			$this->upgrader->strings['process_success'] = sprintf( $this->upgrader->strings['process_success_specific'], $this->api->name, $this->api->version);
	}

	public function after() {
		if ( empty($this->upgrader->result['destination_name']) )
			return;

		$name       = 'Themeason!';
		$stylesheet = $this->upgrader->result['destination_name'];
		$template   = 'Templatio!';

		$preview_link = add_query_arg( array(
			'preview'    => 1,
			'template'   => urlencode( $template ),
			'stylesheet' => urlencode( $stylesheet ),
		), trailingslashit( home_url() ) );

		$activate_link = add_query_arg( array(
			'action'     => 'activate',
			'template'   => urlencode( $template ),
			'stylesheet' => urlencode( $stylesheet ),
		), admin_url('themes.php') );
		$activate_link = wp_nonce_url( $activate_link, 'switch-theme_' . $stylesheet );

		$install_actions = array();
		$install_actions['preview']  = '<a href="' . esc_url( $preview_link ) . '" class="hide-if-customize" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;', 'layerswp' ), $name ) ) . '">' . __( 'Preview', 'layerswp' ) . '</a>';
		if ( current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) {
			$install_actions['preview'] .= '<a href="' . wp_customize_url( $stylesheet ) . '" class="hide-if-no-customize load-customize" title="' . esc_attr( sprintf( __('Preview &#8220;%s&#8221;', 'layerswp' ), $name ) ) . '">' . __('Live Preview', 'layerswp' ) . '</a>';
		}
		$install_actions['activate'] = '<a href="' . esc_url( $activate_link ) . '" class="activatelink" title="' . esc_attr( sprintf( __('Activate &#8220;%s&#8221;', 'layerswp' ), $name ) ) . '">' . __('Activate', 'layerswp' ) . '</a>';

		if ( is_network_admin() && current_user_can( 'manage_network_themes' ) )
			$install_actions['network_enable'] = '<a href="' . esc_url( wp_nonce_url( 'themes.php?action=enable&amp;theme=' . urlencode( $stylesheet ), 'enable-theme_' . $stylesheet ) ) . '" title="' . esc_attr__( 'Enable this theme for all sites in this network', 'layerswp' ) . '" target="_parent">' . __( 'Network Enable', 'layerswp' ) . '</a>';

		if ( $this->type == 'web' )
			$install_actions['themes_page'] = '<a href="' . self_admin_url('theme-install.php') . '" title="' . esc_attr__('Return to Theme Installer', 'layerswp' ) . '" target="_parent">' . __('Return to Theme Installer', 'layerswp' ) . '</a>';
		elseif ( current_user_can( 'switch_themes' ) || current_user_can( 'edit_theme_options' ) )
			$install_actions['themes_page'] = '<a href="' . self_admin_url('themes.php') . '" title="' . esc_attr__('Themes page', 'layerswp' ) . '" target="_parent">' . __('Return to Themes page', 'layerswp' ) . '</a>';

		if ( ! $this->result || is_wp_error($this->result) || is_network_admin() || ! current_user_can( 'switch_themes' ) )
			unset( $install_actions['activate'], $install_actions['preview'] );

		/**
		 * Filter the list of action links available following a single theme installation.
		 *
		 * @since 2.8.0
		 *
		 * @param array    $install_actions Array of theme action links.
		 * @param object   $api             Object containing WordPress.org API theme data.
		 * @param string   $stylesheet      Theme directory name.
		 * @param WP_Theme $theme_info      Theme object.
		 */
		$install_actions = apply_filters( 'install_theme_complete_actions', $install_actions, $this->api, $stylesheet );
		if ( ! empty($install_actions) )
			$this->feedback(implode(' | ', (array)$install_actions));
	}
}
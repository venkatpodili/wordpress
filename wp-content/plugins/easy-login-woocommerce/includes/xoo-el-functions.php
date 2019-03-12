<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//Menu items filter
if( !function_exists( 'xoo_el_nav_menu_items' ) ):
	function xoo_el_nav_menu_items( $items ) {

		if ( ! empty( $items ) && is_array( $items ) && is_user_logged_in()) {

			$actions_classes = array(
				'xoo-el-login-tgr',
				'xoo-el-reg-tgr',
				'xoo-el-lostpw-tgr'
			);

			foreach ( $items as $key => $item ) {
				$classes = $item->classes;
				if(!empty($action_class = array_intersect($actions_classes, $classes))){

					if( in_array( 'xoo-el-login-tgr', $action_class ) ){
						$gl_options = get_option('xoo-el-general-options');
						$logout_redirect = !empty( $gl_options['m-logout-url'] ) ? $gl_options['m-logout-url'] : $_SERVER['REQUEST_URI'];
						$items[$key]->url = wp_logout_url($logout_redirect);
						$items[$key]->title = __('Logout','easy-login-woocommerce');
					}
					elseif ( in_array( 'xoo-el-reg-tgr', $action_class ) ) {
						$items[$key]->url = wc_get_page_permalink( 'myaccount' );
						$items[$key]->title = __('My account','easy-login-woocommerce');
					}

					$items[$key]->classes = array_diff($classes, $action_class); //Reset popup trigger classes

					if ( in_array( 'xoo-el-lostpw-tgr', $action_class ) ) {
						unset($items[$key]);
					}
				}
			}
		}

		return $items;
	}
	add_filter('wp_nav_menu_objects','xoo_el_nav_menu_items',11);
endif;

//Internationalization
if( !function_exists( 'xoo_el_load_plugin_textdomain' ) ):
	function xoo_el_load_plugin_textdomain() {
		$domain = 'easy-login-woocommerce';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/'.$domain.'-' . $locale . '.mo' ); //wp-content languages
		load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' ); // Plugin Languages
	}
	add_action('plugins_loaded','xoo_el_load_plugin_textdomain',100);
endif;


//Get tempalte
if( !function_exists( 'xoo_get_template' ) ){
	function xoo_get_template ( $template_name, $path = '', $args = array(), $return = false ) {

	    $located = xoo_locate_template ( $template_name, $path );

	    if ( $args && is_array ( $args ) ) {
	        extract ( $args );
	    }

	    if ( $return ) {
	        ob_start ();
	    }

	    // include file located
	    if ( file_exists ( $located ) ) {
	        include ( $located );
	    }

	    if ( $return ) {
	        return ob_get_clean ();
	    }
	}
}


//Locate template
if( !function_exists( 'xoo_locate_template' ) ){
	function xoo_locate_template ( $template_name, $template_path ) {

	    // Look within passed path within the theme - this is priority.
		$template = locate_template(
			array(
				'templates/' . $template_name,
				$template_name,
			)
		);

		//Check woocommerce directory for older version
		if( !$template && class_exists( 'woocommerce' ) ){
			if( file_exists( WC()->plugin_path() . '/templates/' . $template_name ) ){
				$template = WC()->plugin_path() . '/templates/' . $template_name;
			}
		}

	    if ( ! $template ) {
	        $template = trailingslashit( $template_path ) . $template_name;
	    }

	    return $template;
	}
}

//Print notices
function xoo_el_print_notices( $form_type = null, $notices = null ){
	$notices .= '<div class="xoo-el-notice"></div>';
	echo $notices;
}


//Registration fields
function xoo_el_aff_fields( $html, $field_id, $field_data ){
	if( $field_id === 'xoo_el_reg_username' ){
		if( 'yes' === get_option( 'woocommerce_registration_generate_username' ) ){
			return '';
		}
	}

	return $html;
}
add_filter('xoo_aff_field_html','xoo_el_aff_fields',10,3);


//Inline Form Shortcode
if( !function_exists( 'xoo_el_inline_form' ) ){
	function xoo_el_inline_form_shortcode($user_atts){

		$atts = shortcode_atts( array(
			'active'	=> 'login',
		), $user_atts, 'xoo_el_inline_form');

		if( is_user_logged_in() ) return;

		$args = array(
			'form_class' => 'xoo-el-form-inline',
			'form_active' => $atts['active']
		); 
		
		xoo_get_template( 'xoo-el-form.php', XOO_EL_PATH.'/templates/', $args );

	}
	add_shortcode( 'xoo_el_inline_form', 'xoo_el_inline_form_shortcode' );
}

//Override woocommerce form login template
function xoo_el_override_myaccount_login_form( $located, $template_name, $args, $template_path, $default_path ){

	$gl_options 	  = get_option('xoo-el-general-options');
	$enable_myaccount = $gl_options['m-en-myaccount-page'];

	if( $template_name === 'myaccount/form-login.php' && $enable_myaccount === "yes" ){
		$located = xoo_locate_template( 'xoo-el-wc-form-login.php', XOO_EL_PATH.'/templates/' );
	}
	return $located;
}
add_filter( 'wc_get_template', 'xoo_el_override_myaccount_login_form', 10, 5 );


?>
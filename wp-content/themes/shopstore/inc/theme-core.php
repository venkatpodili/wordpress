<?php
/**
 * shopstore functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package shopstore
 */

if ( ! function_exists( 'shopstore_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function shopstore_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on shopstore, use a find and replace
		 * to change 'shopstore' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'shopstore', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'primary' => esc_html__( 'Primary', 'shopstore' ),
			'top_bar_navigation' => esc_html__( 'Top Bar Navigation', 'shopstore' ),
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		// Set up the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'shopstore_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support( 'custom-logo', array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		) );
		
		/*
		* Enable support for Post Formats.
		* See https://developer.wordpress.org/themes/functionality/post-formats/
		*/
		add_theme_support( 'post-formats', array(
			'image',
			'video',
			'gallery',
			'audio',
			'quote'
		) );
	}
endif;
add_action( 'after_setup_theme', 'shopstore_setup' );



/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function shopstore_content_width() {
	// This variable is intended to be overruled from themes.
	// Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$GLOBALS['content_width'] = apply_filters( 'shopstore_content_width', 640 );
}
add_action( 'after_setup_theme', 'shopstore_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function shopstore_widgets_init() {
	
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'shopstore' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'shopstore' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<div class="widget-title"><h3>',
		'after_title'   => '</h3></div>',
	) );
	
	register_sidebar( array(
		'name'          => esc_html__( 'Footer Widgets', 'shopstore' ),
		'id'            => 'footer',
		'description'   => esc_html__( 'Add widgets here.', 'shopstore' ),
		'before_widget' => '<div id="%1$s" class="col-lg-4 col-md-6"><div class="widget-ft">',
		'after_widget'  => '</div></div>',
		'before_title'  => '<div class="widget-title"><h3>',
		'after_title'   => '</h3></div>',
	) );
	
}
add_action( 'widgets_init', 'shopstore_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function shopstore_scripts() {
	wp_enqueue_style( 'shopstore-google-font','https://fonts.googleapis.com/css?family=Nunito|Open+Sans|Roboto Condensed' );
	
	/* PLUGIN CSS */
	wp_enqueue_style( 'bootstrap', get_theme_file_uri( '/vendors/bootstrap/css/bootstrap.css' ), array(), '4.0.0' );
	wp_enqueue_style( 'font-awesome', get_theme_file_uri( '/vendors/font-awesome/css/fontawesome.css' ), array(), '4.7.0' );
	wp_enqueue_style( 'owl-carousel', get_theme_file_uri( '/vendors/owl-carousel/assets/owl-carousel.css' ), array(), '2.3.4' );
	wp_enqueue_style( 'rd-navbar', get_theme_file_uri( '/vendors/rd-navbar/css/rd-navbar.css' ), array(), '2.2.5' );
	wp_enqueue_style( 'tether', get_theme_file_uri( '/vendors/tether/css/tether.css' ), array(), '1.4.4' );
	
	
	wp_enqueue_style( 'shopstore-style', get_stylesheet_uri() );
	wp_enqueue_style( 'shopstore-responsive', get_theme_file_uri( '/assets/responsive.css' ), array(), '1.0' );

	/* PLUGIN JS */
	wp_enqueue_script( 'tether-js', get_theme_file_uri( '/vendors/tether/js/tether.js' ), array(), '1.4.0', true );

	wp_enqueue_script( 'bootstrap', get_theme_file_uri( '/vendors/bootstrap/js/bootstrap.js' ), 0, '3.3.7', true );
	
	wp_enqueue_script( 'owl-carousel', get_theme_file_uri( '/vendors/owl-carousel/owl-carousel.js' ), 0, '2.3.4', true );
	
	wp_enqueue_script( 'rd-navbar-js', get_theme_file_uri( '/vendors/rd-navbar/js/jquery.rd-navbar.js' ), 0, '', true );
	
	//owl-carousel.css
	wp_enqueue_script( 'shopstore-js', get_theme_file_uri( '/assets/shopstore.js'), array('jquery','masonry','imagesloaded'), '1.0.0', true);
	
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'shopstore_scripts' );


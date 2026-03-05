<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )exit;


function tech_storefront_settings( $values ) {

// Основные цвета: синий, бежевый, белый, чёрный
$values[ 'primary_color' ] = '#0c7ddb';
$values[ 'secondary_color' ] = '#000';
$values[ 'heading_font' ] = 'Inter';
$values[ 'body_font' ] = 'Inter';

$values[ 'woo_bar_color' ] = '#000';
$values[ 'woo_bar_bg_color' ] = '#e9e8cc';

$values[ 'preloader_enabled' ] = false;

$values[ 'logo_width' ] = 130;
$values[ 'layout_width' ] = 1280;

$values[ 'header_layout' ] = 'woocommerce-bar';
$values[ 'menu_layout' ] = 'default';
$values[ 'enable_search' ] = true;
$values[ 'ed_social_links' ] = true;

$values[ 'subscription_shortcode' ] = '';

$values[ 'enable_top_bar' ] = false;
$values[ 'top_bar_left_content' ] = 'contacts';
$values[ 'top_bar_left_text' ] = esc_html__( 'edit top bar text', 'tech-storefront' );
$values[ 'top_bar_right_content' ] = 'menu_social';
$values[ 'enable_top_bar' ] = false;
$values[ 'topbar_bg_color' ] = '#0c7ddb';
$values[ 'topbar_text_color' ] = '#fff';

$values[ 'footer_text_color' ] = '#000';
$values[ 'footer_color' ] = '#e9e8cc';
$values[ 'footer_link' ] = 'https://gradientthemes.com/';
$values[ 'footer_copyright' ] = esc_html__( 'A theme by GradientThemes', 'tech-storefront' );

$values[ 'page_sidebar_layout' ] = 'right-sidebar';
$values[ 'post_sidebar_layout' ] = 'right-sidebar';
$values[ 'layout_style' ] = 'right-sidebar';
$values[ 'woo_sidebar_layout' ] = 'left-sidebar';

return $values;

}


add_filter( 'best_shop_settings', 'tech_storefront_settings' );

/*
* Add default header image
*/

function tech_storefront_header_style() {
add_theme_support(
  'custom-header',
  apply_filters(
    'tech_storefront_custom_header_args',
    array(
      'default-text-color' => '#000000',
      'width' => 1920,
      'height' => 760,
      'flex-height' => true,
      'video' => true,
      'wp-head-callback' => 'tech_storefront_header_style',
    )
  )
);
add_theme_support( 'automatic-feed-links' );
}

add_action( 'after_setup_theme', 'tech_storefront_header_style' );


//  PARENT ACTION

if ( !function_exists( 'tech_storefront_cfg_locale_css' ) ):
function tech_storefront_cfg_locale_css( $uri ) {
  if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
    $uri = get_template_directory_uri() . '/rtl.css';
  return $uri;
}
endif;

add_filter( 'locale_stylesheet_uri', 'tech_storefront_cfg_locale_css' );

if ( !function_exists( 'tech_storefront_cfg_parent_css' ) ):
function tech_storefront_cfg_parent_css() {
  wp_enqueue_style( 'tech_storefront_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array() );
}
endif;

add_action( 'wp_enqueue_scripts', 'tech_storefront_cfg_parent_css', 10 );

// Inter: подключаем явно (на случай если parent не подхватит по настройке)
function tech_storefront_enqueue_inter_font() {
	wp_enqueue_style(
		'tech-storefront-inter',
		'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
		array(),
		null
	);
}
add_action( 'wp_enqueue_scripts', 'tech_storefront_enqueue_inter_font', 9 );

// Add prealoder js
function tech_storefront_custom_scripts() {
wp_enqueue_script( 'tech-storefront', get_stylesheet_directory_uri() . '/assests/preloader.js', array( 'jquery' ), '', true );
}

add_action( 'wp_enqueue_scripts', 'tech_storefront_custom_scripts' );

// MNK7: style bloków karty produktu (parametry, zastosowanie, dostawa)
function tech_storefront_enqueue_mnsk7_product_css() {
	wp_enqueue_style(
		'tech-storefront-mnsk7-product',
		get_stylesheet_directory_uri() . '/assets/css/mnsk7-product.css',
		array(),
		'1.4'
	);
}
add_action( 'wp_enqueue_scripts', 'tech_storefront_enqueue_mnsk7_product_css', 15 );

/**
 * UX: pod "Sklep" dodajemy główne kategorie Woo (header menu).
 * Priorytet: wygoda użytkownika i standard e-commerce.
 */
function tech_storefront_add_product_cats_under_shop_menu( $items, $args ) {
	if ( is_admin() ) {
		return $items;
	}

	if ( isset( $args->theme_location ) && $args->theme_location === 'footer-menu' ) {
		return $items;
	}

	if ( ! taxonomy_exists( 'product_cat' ) || empty( $items ) || ! is_array( $items ) ) {
		return $items;
	}

	$shop_item_id = 0;
	foreach ( $items as $item ) {
		if ( (int) $item->menu_item_parent !== 0 ) {
			continue;
		}
		$title = function_exists( 'mb_strtolower' ) ? mb_strtolower( (string) $item->title ) : strtolower( (string) $item->title );
		if ( strpos( $title, 'sklep' ) !== false || strpos( $title, 'shop' ) !== false ) {
			$shop_item_id = (int) $item->ID;
			$item->classes[] = 'menu-item-has-children';
			break;
		}
	}

	if ( $shop_item_id <= 0 ) {
		return $items;
	}

	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'number'     => 8,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);

	if ( is_wp_error( $categories ) || empty( $categories ) ) {
		return $items;
	}

	$base_id = 900000;
	foreach ( $categories as $index => $category ) {
		$menu_item                  = new stdClass();
		$menu_item->ID              = $base_id + $index;
		$menu_item->db_id           = 0;
		$menu_item->menu_item_parent = $shop_item_id;
		$menu_item->object_id       = (int) $category->term_id;
		$menu_item->object          = 'product_cat';
		$menu_item->type            = 'custom';
		$menu_item->type_label      = 'Custom';
		$menu_item->title           = $category->name;
		$menu_item->url             = get_term_link( $category );
		$menu_item->target          = '';
		$menu_item->attr_title      = '';
		$menu_item->description     = '';
		$menu_item->classes         = array( 'menu-item', 'menu-item-type-taxonomy', 'menu-item-object-product_cat', 'mnsk7-shop-cat-item' );
		$menu_item->xfn             = '';
		$menu_item->status          = '';
		$menu_item->current         = false;
		$menu_item->current_item_ancestor = false;
		$menu_item->current_item_parent   = false;

		if ( ! is_wp_error( $menu_item->url ) ) {
			$items[] = $menu_item;
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'tech_storefront_add_product_cats_under_shop_menu', 20, 2 );

// END ENQUEUE PARENT ACTION

if ( !function_exists( 'tech_storefront_customize_register' ) ):
/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function tech_storefront_customize_register( $wp_customize ) {

  $wp_customize->add_section(
    'subscription_settings',
    array(
      'title' => esc_html__( 'Email Subscription', 'tech-storefront' ),
      'priority' => 199,
      'capability' => 'edit_theme_options',
      'panel' => 'theme_options',
      'description' => __( 'Add email subscription plugin shortcode.', 'tech-storefront' ),

    )
  );

  /** Footer Copyright */
  $wp_customize->add_setting(
    'subscription_shortcode',
    array(
      'default' => best_shop_default_settings( 'subscription_shortcode' ),
      'sanitize_callback' => 'sanitize_text_field',
      'transport' => 'postMessage'
    )
  );

  $wp_customize->add_control(
    'subscription_shortcode',
    array(
      'label' => esc_html__( 'Subscription Plugin Shortcode', 'tech-storefront' ),
      'section' => 'subscription_settings',
      'type' => 'text',
    )
  );

  //preloader
  $wp_customize->add_section(
    'preloader_settings',
    array(
      'title' => esc_html__( 'Preloader', 'tech-storefront' ),
      'priority' => 200,
      'capability' => 'edit_theme_options',
      'panel' => 'theme_options',

    )
  );

  $wp_customize->add_setting(
    'preloader_enabled',
    array(
      'default' => best_shop_default_settings( 'preloader_enabled' ),
      'sanitize_callback' => 'best_shop_sanitize_checkbox',
      'transport' => 'refresh'
    )
  );

  $wp_customize->add_control(
    'preloader_enabled',
    array(
      'label' => esc_html__( 'Enable Preloader', 'tech-storefront' ),
      'section' => 'preloader_settings',
      'type' => 'checkbox',
    )
  );


}
endif;
add_action( 'customize_register', 'tech_storefront_customize_register' );


<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )exit;


function tech_storefront_settings( $values ) {

$values[ 'primary_color' ] = '#6e2eff';
$values[ 'secondary_color' ] = '#000';
$values[ 'heading_font' ] = 'Jost';
$values[ 'body_font' ] = 'Lato';

$values[ 'woo_bar_color' ] = '#000';
$values[ 'woo_bar_bg_color' ] = '#fff';

$values[ 'preloader_enabled' ] = false;

$values[ 'logo_width' ] = 130;
$values[ 'layout_width' ] = 1280;

$values[ 'header_layout' ] = 'woocommerce-bar';
$values[ 'menu_layout' ] = 'default';
$values[ 'enable_search' ] = true;
$values[ 'ed_social_links' ] = true;

$values[ 'subscription_shortcode' ] = '';

$values[ 'enable_top_bar' ] = true;
$values[ 'top_bar_left_content' ] = 'contacts';
$values[ 'top_bar_left_text' ] = esc_html__( 'edit top bar text', 'tech-storefront' );
$values[ 'top_bar_right_content' ] = 'menu_social';
$values[ 'enable_top_bar' ] = true;
$values[ 'topbar_bg_color' ] = '#7345dd';
$values[ 'topbar_text_color' ] = '#fff';


$values[ 'footer_text_color' ] = '#000';
$values[ 'footer_color' ] = '#e3e3e3';
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

// Add prealoder js
function tech_storefront_custom_scripts() {
wp_enqueue_script( 'tech-storefront', get_stylesheet_directory_uri() . '/assests/preloader.js', array( 'jquery' ), '', true );
}

add_action( 'wp_enqueue_scripts', 'tech_storefront_custom_scripts' );

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


<?php
/**
 * Layout Settings
 *
 * @package Best_Shop
 */
if ( ! function_exists( 'best_shop_customizer_widgets_disable_block_editor' ) ) :

/**
 * Add option inside Customizer Widgets section
 */
function best_shop_customizer_widgets_disable_block_editor( $wp_customize ) {

        /** Layout Settings */
    $wp_customize->add_section( 
        'block_widget_settings',
         array(
            'priority'    => 46,
            'capability'  => 'edit_theme_options',
            'title'       => esc_html__( 'Block Settings', 'best-shop' ),
            'description' => esc_html__( 'Block widget settings from here.', 'best-shop' ),
            'panel'    => 'theme_options',
        ) 
    );
    
    // Setting
    $wp_customize->add_setting( 'disable_block_widgets', array(
        'default'           => best_shop_default_settings('disable_block_widgets'),
        'sanitize_callback' => 'wp_validate_boolean',
    ) );

    // Control inside EXISTING "Widgets" section
    $wp_customize->add_control( 'disable_block_widgets', array(
        'type'        => 'checkbox',
        'section'     => 'block_widget_settings', // <-- IMPORTANT
        'label'       => __( 'Disable block editor in sidebar widgets', 'best-shop' ),
        'description' => __( 'Use classic widgets instead of block widgets.', 'best-shop' ),
    ) );

}

endif;
add_action( 'customize_register', 'best_shop_customizer_widgets_disable_block_editor' );
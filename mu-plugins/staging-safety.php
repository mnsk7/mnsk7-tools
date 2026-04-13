<?php
/**
 * Plugin Name: Staging safety (MU)
 * Description: Na stagingu: blokuje maile, ogranicza płatności. Tylko gdy WP_ENVIRONMENT_TYPE === 'staging'.
 */

if (! defined('WP_ENVIRONMENT_TYPE') || WP_ENVIRONMENT_TYPE !== 'staging') {
    return;
}

// Przekierowanie maili na dev-null (nie wysyłaj na stagingu)
add_filter('wp_mail', function ($args) {
    $args['to'] = 'dev-null@localhost';
    $args['subject'] = '[STAGING BLOCKED] ' . ($args['subject'] ?? '');
    return $args;
}, 1);

// Wyłączenie realnych bramek płatności na stagingu (tylko testy)
add_filter('woocommerce_available_payment_gateways', function ($gateways) {
    return []; // Na stagingu brak płatności — zostaw tylko 'cod'/'cheque' jeśli potrzebujesz testów
}, 5);

// Standardowy układ Woo „Moje konto” (motyw) zamiast siatki z Customize My Account
add_action(
    'plugins_loaded',
    static function () {
        if (! function_exists('deactivate_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $slug = 'customize-my-account-for-woocommerce/customize-my-account-for-woocommerce.php';
        if (function_exists('is_plugin_active') && is_plugin_active($slug)) {
            deactivate_plugins($slug, true);
        }
    },
    1
);

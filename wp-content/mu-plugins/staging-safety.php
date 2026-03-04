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

// Opcjonalnie: wyłączyć realne bramki płatności na stagingu
// Odkomentuj i dostosuj, jeśli używasz konkretnych gateway (stripe, paypal itd.)
// add_filter('woocommerce_available_payment_gateways', function ($gateways) {
//     return []; // lub zostaw tylko 'cod' / 'cheque' do testów
// });

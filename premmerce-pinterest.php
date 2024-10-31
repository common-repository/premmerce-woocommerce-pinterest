<?php

use Premmerce\Pinterest\PinterestPlugin;

/**
 *
 * Plugin Name:       Premmerce Pinterest for WooCommerce
 * Plugin URI:        https://premmerce.com/woocommerce-pinterest/
 * Description:       Rich Pins product data and bulk creation and editing of Pins
 * Version:           1.2.3
 * Author:            premmerce
 * Author URI:        https://premmerce.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       premmerce-pinterest
 * Domain Path:       /languages
 *
 * WC requires at least: 3.0
 * WC tested up to: 3.6
 *
 */

// If this file is called directly, abort.
if ( ! defined('WPINC')) {
    die;
}

if ( ! function_exists('premmerce_pwp_fs')) {

    call_user_func(function () {

        require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
        require_once plugin_dir_path(__FILE__) . 'freemius.php';

        $main = new PinterestPlugin(__FILE__);

        register_activation_hook(__FILE__, [$main, 'activate']);

        register_deactivation_hook(__FILE__, [$main, 'deactivate']);

        register_uninstall_hook(__FILE__, [PinterestPlugin::class, 'uninstall']);

        $main->run();
    });

}

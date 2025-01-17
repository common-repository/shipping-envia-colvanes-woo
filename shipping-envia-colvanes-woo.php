<?php
/**
 * Plugin Name: Shipping Envia Colvanes Woo
 * Description: Shipping Envia Colvanes Woocommerce is available for Colombia
 * Version: 4.0.28
 * Author: Saul Morales Pacheco
 * Author URI: https://saulmoralespa.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC tested up to: 8.8.3
 * WC requires at least: 4.0
 * Requires Plugins: woocommerce,departamentos-y-ciudades-de-colombia-para-woocommerce
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(!defined('SHIPPING_ENVIA_COLVANES_EC_VERSION')){
    define('SHIPPING_ENVIA_COLVANES_EC_VERSION', '4.0.28');
}

add_action( 'plugins_loaded', 'shipping_envia_colvanes_ec_init');

function shipping_envia_colvanes_ec_init(){
    if ( !shipping_envia_colvanes_ec_requirements() )
        return;

    shipping_envia_colvanes_ec()->run_envia_colvanes();
}

function shipping_envia_colvanes_ec_notices( $notice ) {
    ?>
    <div class="error notice">
        <p><?php echo $notice; ?></p>
    </div>
    <?php
}

function shipping_envia_colvanes_ec_requirements(){

    if ( ! function_exists( 'is_plugin_active' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

    if ( ! extension_loaded( 'curl' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_envia_colvanes_ec_notices( 'Shipping Envia Colvanes Woocommerce requiere la extensión curl se encuentre instalada' );
                }
            );
        }
        return false;
    }


    if ( ! is_plugin_active(
        'woocommerce/woocommerce.php'
    ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_envia_colvanes_ec_notices( 'Shipping Envia Colvanes Woocommerce requiere que se encuentre instalado y activo el plugin: Woocommerce' );
                }
            );
        }
        return false;
    }

    $plugin_path_departamentos_ciudades_colombia_woo = 'departamentos-y-ciudades-de-colombia-para-woocommerce/departamentos-y-ciudades-de-colombia-para-woocommerce.php';

    if ( ! is_plugin_active(
        $plugin_path_departamentos_ciudades_colombia_woo
    ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    $action = 'install-plugin';
                    $slug = 'departamentos-y-ciudades-de-colombia-para-woocommerce';
                    $plugin_install_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => $action,
                                'plugin' => $slug
                            ),
                            admin_url( 'update.php' )
                        ),
                        $action.'_'.$slug
                    );
                    $plugin = 'Shipping Envia Colvanes Woocommerce requiere que se encuentre instalado y activo el plugin: '  .
                        sprintf(
                            '%s',
                            "<a class='button button-primary' href='$plugin_install_url'>Departamentos y ciudades de Colombia para Woocommerce</a>" );

                    shipping_envia_colvanes_ec_notices( $plugin );
                }
            );
        }
        return false;
    }

    $departamentos_ciudades_colombia_woo_data = get_plugin_data( trailingslashit( WP_PLUGIN_DIR) . $plugin_path_departamentos_ciudades_colombia_woo);

    if (!version_compare ($departamentos_ciudades_colombia_woo_data['Version'] , '2.0.2', '>=')){
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_envia_colvanes_ec_notices( 'Shipping Envia Colvanes Woocommerce requiere que el plugin <strong>Departamentos y Ciudades de Colombia para Woocommerce</strong> se encuentre actualizado' );
                }
            );
        }
        return false;
    }

    $woo_countries   = new WC_Countries();
    $default_country = $woo_countries->get_base_country();

    if ( ! in_array( $default_country, array( 'CO' ), true ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    $country = 'Shipping Envia Colvanes Woocommerce requiere que el país donde se encuentra ubicada la tienda sea Colombia '  .
                        sprintf(
                            '%s',
                            '<a href="' . admin_url() .
                            'admin.php?page=wc-settings&tab=general#s2id_woocommerce_currency">' .
                            'Click para establecer</a>' );
                    shipping_envia_colvanes_ec_notices( $country );
                }
            );
        }
        return false;
    }

    return true;
}

function shipping_envia_colvanes_ec(){
    static  $plugin;
    if(!isset($plugin)){
        require_once ("includes/class-shipping-envia-colvanes-plugin-ec.php");
        $plugin = new Shipping_Envia_Colvanes_Plugin_EC(__FILE__, SHIPPING_ENVIA_COLVANES_EC_VERSION);
    }

    return $plugin;
}

add_action( 'woocommerce_product_options_shipping', array('Shipping_Envia_Colvanes_Plugin_EC', 'add_custom_shipping_option_to_products'), 10);
add_action( 'woocommerce_product_after_variable_attributes', array('Shipping_Envia_Colvanes_Plugin_EC', 'variation_settings_fields'), 10, 3 );
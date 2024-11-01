<?php


class Shipping_Envia_Colvanes_Plugin_EC
{
    /**
     * Filepath of main plugin file.
     *
     * @var string
     */
    public $file;
    /**
     * Plugin version.
     *
     * @var string
     */
    public $version;
    /**
     * Absolute plugin path.
     *
     * @var string
     */
    public $plugin_path;
    /**
     * Absolute plugin URL.
     *
     * @var string
     */
    public $plugin_url;
    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public $includes_path;
    /**
     * Absolute path to plugin lib dir
     *
     * @var string
     */
    public $lib_path;
    /**
     * @var bool
     */
    private $_bootstrapped = false;

    public function __construct($file, $version)
    {
        $this->file = $file;
        $this->version = $version;

        $this->plugin_path   = trailingslashit( plugin_dir_path( $this->file ) );
        $this->plugin_url    = trailingslashit( plugin_dir_url( $this->file ) );
        $this->includes_path = $this->plugin_path . trailingslashit( 'includes' );
        $this->lib_path = $this->plugin_path . trailingslashit( 'lib' );
    }

    public function run_envia_colvanes()
    {
        try{
            if ($this->_bootstrapped){
                throw new Exception( 'Shipping Envia Colvanes Woocommerce can only be called once');
            }
            $this->_run();
            $this->_bootstrapped = true;
        }catch (Exception $e){
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
                add_action('admin_notices', function() use($e) {
                    shipping_envia_colvanes_ec_notices($e->getMessage());
                });
            }
        }
    }

    private function _run()
    {
        if (!class_exists('\EnviaColvanes\Client'))
            require_once ($this->lib_path . 'vendor/autoload.php');
        require_once ($this->includes_path . 'class-method-shipping-envia-colvanes-ec.php');
        require_once ($this->includes_path . 'class-shipping-envia-colvanes-ec.php');

        add_filter( 'plugin_action_links_' . plugin_basename( $this->file), array( $this, 'plugin_action_links' ) );
        add_filter( 'woocommerce_shipping_methods', array( $this, 'shipping_envia_colvanes_ec_add_method') );
        add_filter( 'manage_edit-shop_order_columns', array($this, 'print_label'), 20 );
        add_filter( 'bulk_actions-edit-shop_order', array($this, 'generate_guides_bulk_actions'), 20 );
        add_filter( 'handle_bulk_actions-edit-shop_order', array($this, 'generate_guides_bulk_action_edit_shop_order'), 10, 3 );

        add_action( 'woocommerce_order_status_changed', array('Shipping_Envia_Colvanes_EC', 'generate_guide_dispath'), 20, 4 );
        add_action( 'woocommerce_process_product_meta', array($this, 'save_custom_shipping_option_to_products'), 10 );
        add_action( 'woocommerce_save_product_variation', array($this, 'save_variation_settings_fields'), 10, 2 );
        add_action( 'manage_shop_order_posts_custom_column', array($this, 'content_column_print_label'), 2 );
        add_action( 'wp_ajax_envia_colvanes_generate_guide', array($this, 'envia_colvanes_generate_guide'));
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts_admin') );
        add_action( 'woocommerce_order_details_after_order_table', array($this, 'button_get_status_shipping'), 10, 1 );

        $settings = get_option('woocommerce_shipping_envia_colvanes_ec_settings' );

        if (empty($settings['license_key'])){
            $license = 'Shipping Envia Colvanes Woo: Requiere una licencia para su completo funcionamiento '  .
                sprintf(
                    '%s',
                    '<a target="_blank" class="button button-primary"  href="https://shop.saulmoralespa.com/producto/plugin-shipping-envia-colvanes-woo/">' .
                    'Obtener Licencia</a>' );
            add_action(
                'admin_notices',
                function() use($license) {
                    shipping_envia_colvanes_ec_notices( $license );
                }
            );
        }
    }

    public function plugin_action_links($links)
    {
        $plugin_links = array();
        $plugin_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=shipping_envia_colvanes_ec') . '">' . 'Configuraciones' . '</a>';
        $plugin_links[] = '<a target="_blank" href="https://shop.saulmoralespa.com/shipping-envia-colvanes-woo/">' . 'Documentación' . '</a>';
        return array_merge( $plugin_links, $links );
    }

    public function shipping_envia_colvanes_ec_add_method($methods)
    {
        $methods['shipping_envia_colvanes_ec'] = 'WC_Shipping_Method_Envia_Colvanes_EC';
        return $methods;
    }

    public function print_label($columns)
    {
        $settings = get_option('woocommerce_shipping_envia_colvanes_ec_settings' );

        if(isset($settings['license_key']) && !empty($settings['license_key']))
            $columns['generate_label_envia'] = 'Guía Envia Colvanes';
        return $columns;
    }

    public function content_column_print_label($column)
    {
        global $post;

        $order = new WC_Order($post->ID);
        $order_id_origin = $order->get_id();
        $guide_url = get_post_meta($order_id_origin, 'guide_envia_colvanes', true);

        if(empty($guide_url) && $column === 'generate_label_envia' && $order->get_status() === 'processing'){
            echo "<button class='button-secondary generate_guide' data-orderid='".$order_id_origin."' data-nonce='".wp_create_nonce( "shipping_envia_colvanes_generate_guide") ."'>Generar guía</button>";
        }elseif(!empty($guide_url) && $column == 'generate_label_envia'){
            echo "<a target='_blank' class='button-primary' href='$guide_url'>Ver Guia</a>";
        }
    }

    public function log($message)
    {
        if (is_array($message) || is_object($message))
            $message = print_r($message, true);
        $logger = new WC_Logger();
        $logger->add('shipping-envia-colvanes', $message);
    }

    public function envia_colvanes_generate_guide()
    {
        if ( ! wp_verify_nonce(  $_REQUEST['nonce'], 'shipping_envia_colvanes_generate_guide' ) )
            return;

        $order_id = $_REQUEST['order_id'];

        $order = new WC_Order($order_id);

        $response = Shipping_Envia_Colvanes_EC::guide($order);

        if (isset($response->urlguia) && $response->urlguia){
            update_post_meta($order->get_id(), 'guide_envia_colvanes', $response->urlguia);
        }

        wp_send_json($response);

    }

    public function enqueue_scripts_admin($hook)
    {
        if ($hook === 'woocommerce_page_wc-settings' || $hook === 'edit.php'){
            wp_enqueue_script('sweetalert_shipping_envia_colvanes_ec', $this->plugin_url . 'assets/js/sweetalert2.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( 'shipping_envia_colvanes_ec', $this->plugin_url . 'assets/js/shipping-envia-colvanes-woo.js', array( 'jquery' ), $this->version, true );
        }
    }

    public static function add_custom_shipping_option_to_products()
    {
        global $post;
        global $shipping_custom_price_product_smp_loaded;

        if (!isset($shipping_custom_price_product_smp_loaded)) {
            $shipping_custom_price_product_smp_loaded = false;
        }

        if($shipping_custom_price_product_smp_loaded) return;

        woocommerce_wp_text_input( [
            'id'          => '_shipping_custom_price_product_smp[' . $post->ID . ']',
            'label'       => __( 'Valor declarado del producto'),
            'placeholder' => 'Valor declarado del envío',
            'desc_tip'    => true,
            'description' => __( 'El valor que desea declarar para el envío'),
            'value'       => get_post_meta( $post->ID, '_shipping_custom_price_product_smp', true ),
        ] );
    }

    public static function variation_settings_fields($loop, $variation_data, $variation)
    {
        global ${"shipping_custom_price_product_smp_$variation->ID"};

        if (!isset(${"shipping_custom_price_product_smp_$variation->ID"})) {
            ${"shipping_custom_price_product_smp_$variation->ID"} = false;
        }

        if(${"shipping_custom_price_product_smp_$variation->ID"}) return;

        woocommerce_wp_text_input(
            array(
                'id'          => '_shipping_custom_price_product_smp[' . $variation->ID . ']',
                'label'       => __( 'Valor declarado del producto'),
                'placeholder' => 'Valor declarado del envío',
                'desc_tip'    => true,
                'description' => __( 'El valor que desea declarar para el envío'),
                'value'       => get_post_meta( $variation->ID, '_shipping_custom_price_product_smp', true )
            )
        );

        ${"shipping_custom_price_product_smp_$variation->ID"} = true;
    }

    public function save_custom_shipping_option_to_products($post_id)
    {
        $custom_price_product = esc_attr($_POST['_shipping_custom_price_product_smp'][ $post_id ]);

        if( isset( $custom_price_product ) )
            update_post_meta( $post_id, '_shipping_custom_price_product_smp', $custom_price_product );
    }

    public function save_variation_settings_fields($post_id)
    {
        $custom_variation_price_product = esc_attr($_POST['_shipping_custom_price_product_smp'][ $post_id ]);
        if( ! empty( $custom_variation_price_product ) ) {
            update_post_meta( $post_id, '_shipping_custom_price_product_smp', $custom_variation_price_product );
        }
    }

    public function button_get_status_shipping($order)
    {
        $order_id_origin = $order->get_parent_id() > 0 ? $order->get_parent_id() : $order->get_id();
        $guide_url = get_post_meta($order_id_origin, 'guide_envia_colvanes', true);

        if ($guide_url){
            $parts = parse_url($guide_url);
            parse_str($parts['query'], $query);
            $number_guide = $query['Guia'];
            $tracking_url = "https://portal.envia.co/OnLineRastreo/?Guia=$number_guide#modal_rastrea";

            echo "<p>Código de seguimiento: <a href='$tracking_url' target='_blank'>$number_guide</a></p>";
        }
    }

    public function generate_guides_bulk_actions($bulk_actions)
    {
        $bulk_actions['generate_guides_envia_colvanes'] = 'Generar guías Envia Colvanes';
        return $bulk_actions;
    }

    public function generate_guides_bulk_action_edit_shop_order( $redirect_to, $action, $post_ids )
    {
        if ($action !== 'generate_guides_envia_colvanes') return $redirect_to;

        foreach ( $post_ids as $post_id ) {
            $sub_orders = get_children( array( 'post_parent' => $post_id, 'post_type' => 'shop_order' ) );
            $new_status = 'processing';
            $order = new WC_Order($post_id);

            if ( $sub_orders ) {
                continue;
            }else{
                Shipping_Envia_Colvanes_EC::exec_guide($order, $new_status);
            }
        }

        return $redirect_to;
    }
}
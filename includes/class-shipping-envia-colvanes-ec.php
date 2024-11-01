<?php

use EnviaColvanes\Client;

class Shipping_Envia_Colvanes_EC extends WC_Shipping_Method_Envia_Colvanes_EC
{

    public Client $enviaColvanes;

    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->password = mb_strtoupper($this->password);

        $this->enviaColvanes = new Client($this->user, $this->password, $this->code_account);
        $this->enviaColvanes->sandboxMode($this->isTest);
    }

    public static function test_connection_liquidation(): void
    {
        $instance = new self();
        $params = array(
            'ciudad_origen' => $instance->city_sender,
            'ciudad_destino' => '11001',
            'cod_formapago' => $instance->payment_method,
            'cod_servicio' => $instance->code_service,
            'num_unidades' => 1,
            'mpesoreal_k' => $instance->code_service === '3' ? 10 : 1,
            'mpesovolumen_k' => $instance->code_service === '3' ? 10 : 1,
            'valor_declarado' => 50000,
            'mca_nosabado' => 0,
            'mca_docinternacional' => 0,
            'cod_regional_cta' => $instance->cod_regional_cta,
            'cod_oficina_cta' => $instance->cod_oficina_cta,
            'con_cartaporte' => '0',
            'info_origen' =>
                array(
                    'nom_remitente' => 'JORGE GOMEZ',
                    'dir_remitente' => 'CALLE 13 84 60',
                    'tel_remitente' => '2020202',
                    'ced_remitente' => '79123456',
                ),
            'info_destino' =>
                array(
                    'nom_destinatario' => 'JUAN PEREZ',
                    'dir_destinatario' => 'CARRERA 15 # 15 15',
                    'tel_destinatario' => '3030303',
                ),
            'info_contenido' =>
                array(
                    'dice_contener' => '',
                    'num_documentos' => '12345-67890'
                ),
            'numero_guia' => ''
        );

        if($instance->is_collection === 'yes'){
            $params['info_contenido']['valorproducto'] = 50000;
        }

        try {
            $res = $instance->enviaColvanes->liquidation($params);
        } catch (\Exception $e) {
            shipping_envia_colvanes_ec()->log($params);
            shipping_envia_colvanes_ec_notices("Shipping Envia Colvanes Woocommerce: " . $e->getMessage());
        }
    }

    public static function clean_city($city): string
    {
        return $city === 'Bogota D.C' ? 'Bogota' : $city;
    }

    public static function clean_string(string $string):string
    {
        $not_permitted = array("á", "é", "í", "ó", "ú", "Á", "É", "Í",
            "Ó", "Ú", "ñ");
        $permitted = array("a", "e", "i", "o", "u", "A", "E", "I", "O",
            "U", "n");
        $text = str_replace($not_permitted, $permitted, $string);
        return mb_strtolower($text);
    }

    public static function get_city(string $city_destination): string
    {
        $city_destination = self::clean_string($city_destination);
        $city_destination = self::clean_city($city_destination);

        return $city_destination;
    }

    public static function name_destination($country, $state_destination)
    {
        $countries_obj = new WC_Countries();
        $country_states_array = $countries_obj->get_states();

        $name_state_destination = '';

        if (!isset($country_states_array[$country][$state_destination]))
            return $name_state_destination;

        $name_state_destination = $country_states_array[$country][$state_destination];
        return self::clean_string($name_state_destination);
    }

    public static function clean_cities($cities)
    {
        foreach ($cities as $key => $value) {
            $cities[$key] = self::clean_string($value);
        }

        return $cities;
    }

    public static function dimensions_weight($items, $guide = false)
    {
        $data['total_weight'] = 0;
        $data['cart_prods'] = [];
        $data['name_products'] = [];
        $total_valorization = 0;
        $count = 0;
        $height = 0;
        $length = 0;
        $width = 0;
        $weight = 0;
        $quantityItems = count($items);

        foreach ($items as $item => $values) {
            $_product_id = $guide ? $values['product_id'] : $values['data']->get_id();
            $_product = wc_get_product( $_product_id );

            if ( $values['variation_id'] > 0 &&
                in_array( $values['variation_id'], $_product->get_children() ) &&
                wc_get_product( $values['variation_id'] )->get_weight() &&
                wc_get_product( $values['variation_id'] )->get_length() &&
                wc_get_product( $values['variation_id'] )->get_width() &&
                wc_get_product( $values['variation_id'] )->get_height())
                $_product = wc_get_product( $values['variation_id'] );

            if (!$_product || !$_product->get_weight() || !$_product->get_length()
                || !$_product->get_width() || !$_product->get_height())
                break;

            $data['name_products'][] = $_product->get_name();
            $custom_price_product = get_post_meta($_product_id, '_shipping_custom_price_product_smp', true);
            $total_valorization += $custom_price_product ?: $_product->get_price() * $values['quantity'];
            $height += $_product->get_height() * $values['quantity'];
            $length = $_product->get_length() > $length ? $_product->get_length() : $length;
            $weight += $_product->get_weight() * $values['quantity'];
            $width =  $_product->get_width() > $width ? $_product->get_width() : $width;

            $count++;

            if ($count === $quantityItems){

                $weight = ceil($weight);
                $volume_variant = $weight > 8 ? 400 : 222;
                $total_weight = round($length * $width * $height * $volume_variant / 1000000);

                $data['cart_prods'][] = [
                    'cantidad' => 1,
                    'largo' => $length,
                    'ancho' => $width,
                    'alto' => $height,
                    'peso' => $weight,
                    'declarado' => $total_valorization
                ];

                $data['total_weight']  = max($total_weight, 1);
            }
        }

        return apply_filters('shipping_envia_colvanes_dimensions_weight', $data, $items, $guide);
    }

    public static function get_code_city($state, $city, $country = 'CO')
    {
        $instance = new self();
        $name_state = Shipping_Envia_Colvanes_EC::name_destination($country, $state);

        $address = "$city - $name_state";

        if ($instance->debug === 'yes')
            shipping_envia_colvanes_ec()->log("destino: $address");

        $cities = include dirname(__FILE__) . '/cities.php';

        $destine = array_search($address, $cities);
        if (!$destine){
            $address  = self::clean_string($address);
            $destine = array_search($address, Shipping_Envia_Colvanes_EC::clean_cities($cities));
        }

        return apply_filters( 'shipping_envia_colvanes_code_city', self::set_destine($destine), $address);
    }

    public static function set_destine($destine)
    {
        if (strlen($destine) === 4)
            $destine = '0' . $destine;
        return $destine;
    }
    public static function liquidation(array $params)
    {
        $res = [];

        try{
            $instance = new self();
            $res = $instance->enviaColvanes->liquidation($params);
            return $res;
        }catch (\Exception $exception){
            shipping_envia_colvanes_ec()->log($exception->getMessage());
        }

        return $res;
    }

    public static function generate_guide_dispath($order_id, $old_status, $new_status, WC_Order $order)
    {
        $sub_orders = get_children( array( 'post_parent' => $order_id, 'post_type' => 'shop_order' ) );

        if ( $sub_orders ) {
            foreach ($sub_orders as $sub) {
                $order = new WC_Order($sub->ID);
                self::exec_guide($order, $new_status);
            }
        }else{
            self::exec_guide($order, $new_status);
        }

        return apply_filters( 'envia_colvanes_generate_guide', $order_id, $old_status, $new_status, $order );
    }

    public static function exec_guide(WC_Order $order, $new_status)
    {
        $guide_envia = get_post_meta($order->get_id(), 'guide_envia_colvanes', true);
        $instance = new self();

        $order_id_origin = self::get_parent_id($order);
        $order_parent = new WC_Order($order_id_origin);

        if(($order_parent->has_shipping_method($instance->id) ||
                $order_parent->get_shipping_total() == 0 &&
                $instance->guide_free_shipping) &&
            empty($guide_envia) &&
            !empty($instance->license_key) &&
            $new_status === 'processing'){

            $guide = $instance->guide($order);

            if (isset($guide->urlguia) && !$guide->urlguia) return;

            update_post_meta($order->get_id(), 'guide_envia_colvanes', $guide->urlguia);
        }
    }

    public static function get_parent_id(WC_Order $order)
    {
        return $order->get_parent_id() > 0 ? $order->get_parent_id() : $order->get_id();
    }

    public static function get_shop($product_id): ?array
    {
        $id = get_post_field( 'post_author', $product_id );
        $store = function_exists('dokan_get_store_info') &&  dokan_get_store_info($id) ? dokan_get_store_info($id) : null;

        return apply_filters('shipping_envia_colvanes_get_shop', $store, $product_id);

    }

    public static function get_cod_service($data_products, $destine): int
    {
        $air_destination = [
            '88564',
            '88001',
            '91001'
        ];

        if (in_array($destine, $air_destination))
            return $data_products['total_weight'] > 8 ? 2 : 13;

        return $data_products['total_weight'] > 8 ? 3 : 12;
    }

    public static function guide(WC_Order $order)
    {
        $order_id = $order->get_id();
        $instance = new self();
        $direccion_remitente = get_option( 'woocommerce_store_address' ) .
            " " .  get_option( 'woocommerce_store_address_2' ) .
            " " . get_option( 'woocommerce_store_city' );
        $nombre_remitente = $instance->sender_name ?:  get_bloginfo('name');
        $nombre_destinatario = $order->get_shipping_first_name() ? $order->get_shipping_first_name() .
            " " . $order->get_shipping_last_name() : $order->get_billing_first_name() .
            " " . $order->get_billing_last_name();
        $direccion_destinatario = $order->get_shipping_address_1() ? $order->get_shipping_address_1() .
            " " . $order->get_shipping_address_2() : $order->get_billing_address_1() .
            " " . $order->get_billing_address_2();
        $items = $order->get_items();
        $item = end($items);
        $product_id = $item['product_id'];
        $seller = self::get_shop($product_id);
        $data_products = Shipping_Envia_Colvanes_EC::dimensions_weight($items, true);
        $namesProducts = implode(" ",  $data_products['name_products']);
        $destine_state_code = $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state();
        $destine_city = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
        $destine_city = self::get_city($destine_city);

        if (isset($seller['address']['city']) && !empty($seller['address']['city'])){
            $origin = self::get_code_city($seller['address']['state'], $seller['address']['city']);
        }else{
            $origin = Shipping_Envia_Colvanes_EC::set_destine($instance->city_sender);
        }

        $destine = self::get_code_city($destine_state_code, $destine_city);

        $params = array (
            'ciudad_origen' => $origin,
            'ciudad_destino' => $destine,
            'cod_formapago' => $instance->payment_method,
            'cod_servicio' => self::get_cod_service($data_products, $destine),
            'info_cubicacion' => $data_products['cart_prods'],
            'mca_nosabado' => 0,
            'mca_docinternacional' => 0,
            'cod_regional_cta' => $instance->cod_regional_cta,
            'cod_oficina_cta' => $instance->cod_oficina_cta,
            'con_cartaporte' => '0',
            'info_origen' =>
                array (
                    'nom_remitente' => isset($seller['store_name']) && !empty($seller['store_name']) ? $seller['store_name'] : $nombre_remitente,
                    'dir_remitente' => isset($seller['address']) && !empty($seller['address']['street_1']) ? "{$seller['address']['street_1']}  {$seller['address']['street_2']}" : $direccion_remitente,
                    'tel_remitente' => isset($seller['store_name']) && !empty($seller['phone']) ? $seller['phone'] : $instance->phone_sender,
                ),
            'info_destino' =>
                array (
                    'nom_destinatario' => $nombre_destinatario,
                    'dir_destinatario' => $direccion_destinatario,
                    'tel_destinatario' => $order->get_billing_phone(),
                ),
            'info_contenido' =>
                array (
                    'dice_contener' => $instance->dice_contener ? $order_id : substr($namesProducts, 0, 30),
                    'num_documentos' => $order_id
                ),
            'numero_guia' => ''
        );

        if($instance->is_collection === 'yes'){
            $params['info_contenido']['valorproducto'] = $data_products['cart_prods'][0]['declarado'] ?? 0;
        }

        if ($instance->debug === 'yes')
            shipping_envia_colvanes_ec()->log($params);

        $response = new stdClass();

        try{
            $response = $instance->enviaColvanes->generateGuide($params);
        }catch (\Exception $exception){
            $response->error = $exception->getMessage();
            shipping_envia_colvanes_ec()->log($exception->getMessage());
            shipping_envia_colvanes_ec()->log($params);
        }

        return $response;

    }

    public static function upgrade_working_plugin()
    {
        $instance = new self();

        $secret_key = '5c88321cdb0dc9.43606608';

        $api_params = array(
            'slm_action' => 'slm_check',
            'secret_key' => $secret_key,
            'license_key' => $instance->license_key,
        );

        $siteGet = 'https://shop.saulmoralespa.com';

        $response = wp_remote_get(
            add_query_arg($api_params, $siteGet),
            array('timeout' => 60,
                'sslverify' => true
            )
        );

        if (is_wp_error($response)){
            shipping_envia_colvanes_ec_notices( $response->get_error_message() );
            return;
        }

        $data = json_decode(wp_remote_retrieve_body($response));

        if (is_null($data))
            return;

        //max_allowed_domains

        //registered_domains  array() registered_domain

        if ($data->result === 'error' || $data->status === 'expired'){
            $instance->update_option('license_key', '');
        }elseif($data->result === 'success' && $data->status === 'pending'){

            $api_params = array(
                'slm_action' => 'slm_activate',
                'secret_key' => $secret_key,
                'license_key' => $instance->license_key,
                'registered_domain' => get_bloginfo( 'url' ),
                'item_reference' => urlencode($instance->id),
            );

            $query = esc_url_raw(add_query_arg($api_params, $siteGet));
            $response = wp_remote_get($query,
                array('timeout' => 60,
                    'sslverify' => true
                )
            );

            if (is_wp_error($response)){
                shipping_coordinadora_wc_cswc_notices( $response->get_error_message() );
                return;
            }

            $data = json_decode(wp_remote_retrieve_body($response));

            if($data->result === 'error')
                $instance->update_option('license_key', '');

        }
    }
}
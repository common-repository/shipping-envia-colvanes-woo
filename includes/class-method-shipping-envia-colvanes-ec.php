<?php


class WC_Shipping_Method_Envia_Colvanes_EC extends WC_Shipping_Method
{
    /**
     * @var mixed
     */
    public $debug;
    /**
     * @var mixed
     */
    public $city_sender;
    /**
     * @var boolean
     */
    public $is_collection;
    /**
     * @var mixed
     */
    public $payment_method;
    /**
     * @var mixed
     */
    public $code_service;
    /**
     * @var bool
     */
    public $isTest;
    /**
     * @var mixed
     */
    public $user;
    /**
     * @var mixed
     */
    public $password;
    /**
     * @var mixed
     */
    public $code_account;
    /**
     * @var mixed
     */
    public $sender_name;
    /**
     * @var mixed
     */
    public $phone_sender;
    /**
     * @var mixed
     */
    public $license_key;
    /**
     * @var int
     */
    public $cod_regional_cta = 1;
    /**
     * @var int
     */
    public $cod_oficina_cta = 1;
    /**
     * @var string
     */
    public $guide_free_shipping;
    /**
     * @var int
     */
    public $dice_contener;

    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->id                 = 'shipping_envia_colvanes_ec';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'Envia colvanes' );
        $this->method_description = __( 'Envia Colvanes empresa transportadora de Colombia' );
        $this->title              = __( 'Envia Colvanes' );

        $this->supports = array(
            'settings',
            'shipping-zones'
        );

        $this->init();

        $this->debug = $this->get_option( 'debug' );
        $this->isTest = (bool)$this->get_option( 'environment' );

        if ($this->isTest){
            $this->user = $this->get_option( 'sandbox_user' );
            $this->password = $this->get_option( 'sandbox_password' );
            $this->set_account_codes('sandbox_code_account');
            $this->is_collection = $this->get_option('sandbox_is_collection');
            $this->payment_method = $this->get_option('sandbox_payment_method');
            $this->code_service = $this->get_option('sandbox_code_service');
        }else{
            $this->user = $this->get_option( 'user' );
            $this->password = $this->get_option( 'password' );
            $this->set_account_codes('code_account');
            $this->is_collection = $this->get_option('is_collection');
            $this->payment_method = $this->get_option('payment_method');
            $this->code_service = $this->get_option('code_service');
        }

        $this->guide_free_shipping = $this->get_option('guide_free_shipping');
        $this->dice_contener = (int)$this->get_option('dice_contener');

        $this->city_sender = $this->get_option('city_sender');
        $this->sender_name = $this->get_option('sender_name');
        $this->phone_sender = $this->get_option('phone_sender');
        $this->license_key = $this->get_option('license_key');
    }

    /**
     * Init the class settings
     */
    public function init(): void
    {
        // Load the settings API.
        $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings.
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
        // Save settings in admin if you have any defined.
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Init the form fields for this shipping method
     */
    public function init_form_fields(): void
    {
        $this->form_fields = include(dirname(__FILE__) . '/admin/settings.php');
    }

    public function admin_options(): void
    {
        ?>
        <h3><?php echo $this->title; ?></h3>
        <p><?php echo $this->method_description; ?></p>
        <table class="form-table">
            <?php
            if (!empty($this->user) && !empty($this->password) && !empty($this->code_account))
                Shipping_Envia_Colvanes_EC::test_connection_liquidation();
            if(!empty($this->license_key))
                Shipping_Envia_Colvanes_EC::upgrade_working_plugin();
            $this->generate_settings_html();
            ?>
        </table>
        <?php
    }

    public function is_available($package): bool
    {
        return parent::is_available($package) &&
            !empty($this->user) &&
            !empty($this->password) &&
            !empty($this->code_account);
    }

    public function validate_text_field($key, $value): string
    {

        if ($value && str_contains($key, 'code_account') &&
            !preg_match('/^[0-9]+(-[0-9]+)+(-[0-9]+)$/', $value)
        ){
            WC_Admin_Settings::add_error("Código de cuenta require que cumpla el formato similar: 004-003-00000777");
        }

        return $value;
    }

    public function set_account_codes($key_option): void
    {
        $code_account = $this->get_option($key_option);

        if(!$code_account) return;

        $code_account = explode('-', $code_account);

        if (is_array($code_account) && count($code_account) === 3){
            $this->cod_regional_cta = round($code_account[0]);
            $this->cod_oficina_cta = round($code_account[1]);
            $this->code_account = round($code_account[2]);
        }else{
            $this->code_account = $code_account;
        }
    }

    /***
     * @param $package
     * @return mixed|void
     */
    public function calculate_shipping( $package = array() )
    {
        $country = $package['destination']['country'];

        if($country !== 'CO')
            return;

        $data = $this->calculate_cost($package);

        if (empty($data))
            return;

        if ($this->debug === 'yes')
        shipping_envia_colvanes_ec()->log($data);

        $cost = ($data->valor_flete + $data->valor_costom + $data->valor_otros);

        $rate = array(
            'id'      => $this->id,
            'label'   => $this->title,
            'cost'    => $cost,
            'package' => $package,
        );

        $this->add_rate( $rate );

    }

    public function calculate_cost($package)
    {
        $state_destination = $package['destination']['state'];
        $city_destination = $package['destination']['city'];
        $city_destination  = Shipping_Envia_Colvanes_EC::get_city($city_destination);
        $items = $package['contents'];
        $data_products = Shipping_Envia_Colvanes_EC::dimensions_weight($items);
        $destine = Shipping_Envia_Colvanes_EC::get_code_city($state_destination, $city_destination);

        if(!$destine) return [];

        $item = end($items);
        $product_id = $item['product_id'];
        $seller = Shipping_Envia_Colvanes_EC::get_shop($product_id);

        if (isset($seller['address']['city']) && !empty($seller['address']['city'])){
            $origin = Shipping_Envia_Colvanes_EC::get_code_city($seller['address']['state'], $seller['address']['city']);
        }else{
            $origin = Shipping_Envia_Colvanes_EC::set_destine($this->city_sender);
        }

        if ($this->debug === 'yes')
            shipping_envia_colvanes_ec()->log($data_products);

        $params = array (
            'ciudad_origen' => $origin,
            'ciudad_destino' => $destine,
            'cod_formapago' => $this->payment_method,
            'cod_servicio' => Shipping_Envia_Colvanes_EC::get_cod_service($data_products, $destine),
            'info_cubicacion' => $data_products['cart_prods'],
            'mca_docinternacional' => 0,
            'con_cartaporte' => '0',
            'cod_regional_cta' => $this->cod_regional_cta,
            'cod_oficina_cta' => $this->cod_oficina_cta,
        );

        if($this->is_collection === 'yes'){
            $params['info_contenido']['valorproducto'] = $data_products['cart_prods'][0]['declarado'] ?? 0;
        }

        if ($this->debug === 'yes')
            shipping_envia_colvanes_ec()->log($params);

        $response = Shipping_Envia_Colvanes_EC::liquidation($params);

        return apply_filters( 'shipping_envia_colvanes_calculate_cost', $response, $package );

    }
}
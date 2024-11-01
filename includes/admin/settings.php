<?php

wc_enqueue_js( "
    jQuery( function( $ ) {
	
	let shipping_envia_colvanes_fields = '#woocommerce_shipping_envia_colvanes_ec_user, #woocommerce_shipping_envia_colvanes_ec_password, #woocommerce_shipping_envia_colvanes_ec_code_account, #woocommerce_shipping_envia_colvanes_ec_payment_method, #woocommerce_shipping_envia_colvanes_ec_code_service, #woocommerce_shipping_envia_colvanes_ec_is_collection';
	
	let shipping_envia_colvanes_sandbox_fields = '#woocommerce_shipping_envia_colvanes_ec_sandbox_user, #woocommerce_shipping_envia_colvanes_ec_sandbox_password, #woocommerce_shipping_envia_colvanes_ec_sandbox_code_account, #woocommerce_shipping_envia_colvanes_ec_sandbox_payment_method, #woocommerce_shipping_envia_colvanes_ec_sandbox_code_service, #woocommerce_shipping_envia_colvanes_ec_sandbox_is_collection';

	$( '#woocommerce_shipping_envia_colvanes_ec_environment' ).change(function(){

		$( shipping_envia_colvanes_sandbox_fields + ',' + shipping_envia_colvanes_fields ).closest( 'tr' ).hide();

		if ( '0' === $( this ).val() ) {
			$( shipping_envia_colvanes_fields ).closest( 'tr' ).show();
			
		}else{
		   $( shipping_envia_colvanes_sandbox_fields ).closest( 'tr' ).show();
		}
	}).change();
});	
");

$docs_url = '<a target="_blank" href="https://shop.saulmoralespa.com/shipping-envia-colvanes-woo/">' . __( 'Ver documentación completa del plugin') . '</a>';
$license_key_not_loaded = '<a target="_blank" href="' . esc_url('https://shop.saulmoralespa.com/producto/plugin-shipping-envia-colvanes-woo/') . '">' . __( 'Obtener una licencia desde aquí') . '</a>';
$docs = array(
    'docs'  => array(
        'title' => __( 'Documentación' ),
        'type'  => 'title',
        'description' => $docs_url
    )
);

if (empty($this->get_option( 'license_key' ))){
    $license_key_title = array(
        'license_key_title' => array(
            'title'       => __( 'Se require una licencia para uso completo'),
            'type'        => 'title',
            'description' => $license_key_not_loaded
        )
    );
}else{
    $license_key_title = array();
}

$license_key = array(
    'license_key'  => array(
        'title' => __( 'Licencia' ),
        'type'  => 'password',
        'description' => __( 'La licencia para su uso, según la cantidad de sitios por la cual se haya adquirido' ),
        'desc_tip' => true
    )
);

return apply_filters(
    'envia_colvanes_settings',
    array_merge(
        $docs,
        array(
            'enabled' => array(
                'title' => __('Activar/Desactivar'),
                'type' => 'checkbox',
                'label' => __('Activar Envia Colvanes'),
                'default' => 'no'
            ),
            'title'        => array(
                'title'       => __( 'Título método de envío' ),
                'type'        => 'text',
                'description' => __( 'Esto controla el título que ve el usuario' ),
                'default'     => __( 'Envia Colvanes' ),
                'desc_tip'    => true
            ),
            'debug'        => array(
                'title'       => __( 'Depurador' ),
                'label'       => __( 'Habilitar el modo de desarrollador' ),
                'type'        => 'checkbox',
                'default'     => 'no',
                'description' => __( 'Habilite el modo de depuración para mostrar información de depuración en su carrito / pago' ),
                'desc_tip' => true
            ),
            'environment' => array(
                'title' => __('Entorno'),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __('Entorno de pruebas o producción'),
                'desc_tip' => true,
                'default' => '1',
                'options'     => array(
                    0    => __( 'Producción'),
                    1 => __( 'Pruebas')
                ),
            )
        ),
        $license_key_title,
        $license_key,
        array(
            'sender'  => array(
                'title' => __( 'Remitente' ),
                'type'  => 'title',
                'description' => __( 'Información requerida del remitente' )
            ),
            'city_sender' => array(
                'title' => __('Ciudad del remitente (donde se encuentra ubicada la tienda)'),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __('Se recomienda selecionar ciudadades centrales'),
                'desc_tip' => true,
                'default' => true,
                'options'     => include dirname(__FILE__) . '/../cities.php'
            ),
            'sender_name' => array(
                'title'       => __( 'Nombre remitente' ),
                'type'        => 'text',
                'description' => __( 'Debe ir la razon social o el nombre comercial' ),
                'default'     => get_bloginfo('name'),
                'desc_tip'    => true
            ),
            'phone_sender'      => array(
                'title' => __( 'Teléfono del remitente' ),
                'type'  => 'text',
                'description' => __( 'Necesario para la generación de guías' ),
                'desc_tip' => true
            ),
            'guide_free_shipping' => array(
                'title'       => __( 'Generar guías cuando el envío es gratuito' ),
                'label'       => __( 'Habilitar la generación de guías para envíos gratuitos' ),
                'type'        => 'checkbox',
                'default'     => 'no',
                'description' => __( 'Permite la generación de guías cuando el envío es gratuito' ),
                'desc_tip' => true
            ),
            'dice_contener' => array(
                'title' => __( 'Dice contener' ),
                'type'  => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __( 'Opciones para el campo de dice contener' ),
                'desc_tip' => true,
                'default' => 0,
                'options'  => array(
                    0    => __( 'Nombres de productos'),
                    1    => __( 'Número de pedido')
                )
            ),
            'user' => array(
                'title' => __( 'Usuario' ),
                'type'  => 'text',
                'description' => __( 'Usuario de la cuenta de Envia Colvanes' ),
                'desc_tip' => true
            ),
            'password' => array(
                'title' => __( 'Contraseña' ),
                'type'  => 'password',
                'description' => __( 'Contraseña de la cuenta de Envia Colvanes' ),
                'desc_tip' => true
            ),
            'code_account' => array(
                'title' => __( 'Código de cuenta (004-003-00000777)' ),
                'type'  => 'text',
                'description' => __( 'El código de la cuenta de Envia Colvanes' ),
                'desc_tip' => true
            ),
            'is_collection' => array(
                'title' => __('Activar/Desactivar'),
                'type' => 'checkbox',
                'label' => __('Pago con recaudo'),
                'default' => 'no'
            ),
            'payment_method' => array(
                'title' => __( 'Forma de Pago' ),
                'type'  => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __( 'Condición comercial que indica la forma de Pago.' ),
                'desc_tip' => true,
                'default' => 4,
                'options' => array(
                    6    => __( 'Contado'),
                    7   => __( 'Contraentrega'),
                    4    => __( 'Crédito')
                )
            ),
            'code_service' => array(
                'title' => __( 'Código de servicio' ),
                'type'  => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __( 'Es la modalidad para el manejo y transporte que se utiliza en el despacho.' ),
                'desc_tip' => true,
                'default' => 12,
                'options' => array(
                    1    => __( 'Documento Express'),
                    3   => __( 'Mercancia Terrestre'),
                    12    => __( 'Paquete Terrestre')
                )
            ),
            'sandbox_user' => array(
                'title' => __( 'Usuario' ),
                'type'  => 'text',
                'description' => __( 'Usuario de la cuenta de Envia Colvanes' ),
                'desc_tip' => true,
                'default' => 'EMPCAR01'
            ),
            'sandbox_password' => array(
                'title' => __( 'Contraseña' ),
                'type'  => 'password',
                'description' => __( 'Contraseña de la cuenta de Envia Colvanes' ),
                'desc_tip' => true,
                'default' => 'EMPCAR1'
            ),
            'sandbox_code_account' => array(
                'title' => __( 'Código de cuenta (004-003-00000777)' ),
                'type'  => 'text',
                'description' => __( 'El código de la cuenta de Envia Colvanes' ),
                'desc_tip' => true,
                'default' => '001-001-30'
            ),
            'sandbox_is_collection' => array(
                'title' => __('Activar/Desactivar'),
                'type' => 'checkbox',
                'label' => __('Pago con recaudo'),
                'default' => 'no'
            ),
            'sandbox_payment_method' => array(
                'title' => __( 'Forma de Pago' ),
                'type'  => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __( 'Condición comercial que indica la forma de Pago.' ),
                'desc_tip' => true,
                'default' => 4,
                'options' => array(
                    6    => __( 'Contado'),
                    7   => __( 'Contraentrega'),
                    4    => __( 'Crédito')
                )
            ),
            'sandbox_code_service' => array(
                'title' => __( 'Código de servicio' ),
                'type'  => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __( 'Es la modalidad para el manejo y transporte que se utiliza en el despacho.' ),
                'desc_tip' => true,
                'default' => 12,
                'options' => array(
                    1    => __( 'Documento Express'),
                    3   => __( 'Mercancia Terrestre'),
                    12    => __( 'Paquete Terrestre')
                )
            )
        )
    )
);
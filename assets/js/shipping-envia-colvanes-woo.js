(function($){
    $('button.generate_guide').click(function (e) {
        e.preventDefault();

        $.ajax({
            data: {
                action: 'envia_colvanes_generate_guide',
                nonce: $(this).data("nonce"),
                order_id: $(this).data("orderid")
            },
            type: 'POST',
            url: ajaxurl,
            dataType: "json",
            beforeSend : () => {
                Swal.fire({
                    title: 'Generando guía',
                    onOpen: () => {
                        Swal.showLoading()
                    },
                    allowOutsideClick: false
                });
            },
            success: (r) => {
                if (r.urlguia){
                    Swal.fire({
                        icon: 'success',
                        html: `<a target="_blank" href="${r.urlguia}">Ver guía</a>`,
                        allowOutsideClick: false,
                        showCloseButton: true,
                        showConfirmButton: false
                    })
                }else{
                    Swal.fire(
                        'Error',
                        r.error,
                        'error'
                    );
                }
            }
        });
    });
})(jQuery);
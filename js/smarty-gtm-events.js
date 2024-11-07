jQuery(document).ready(function ($) {
    $(document.body).on('added_to_cart', function (event, fragments, cart_hash, $button) {
        let productID = $button.data('product_id');
        let quantity = $button.data('quantity') || 1;

        $.post(smarty_gtm_ajax.ajax_url, {
            action: 'smarty_gtm_add_to_cart',
            product_id: productID,
            quantity: quantity
        }, function (response) {
            if (response) {
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push(response);
            }
        });
    });
});

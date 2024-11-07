jQuery(document).ready(function ($) {
    if (typeof smartyGTMData !== 'undefined') {
        $(document.body).on('added_to_cart', function (event, fragments, cart_hash, $button) {
            let productID = $button.data('product_id') || 0;
            let quantity = $button.data('quantity') || 1;

            if (productID === 0) {
                console.error("Product ID is missing or undefined");
                return; // Exit if product ID is not found
            }
			
			// Log quantity to check they are correctly set
            console.log("Quantity:", quantity);

            $.post(smartyGTMData.ajax_url, {
                action: 'smarty_gtm_add_to_cart',
                product_id: productID,
                quantity: quantity,
                nonce: smartyGTMData.nonce
            }, function (response) {
                if (response.success) {
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push(response.data);
                } else {
                    console.error("Error:", response.data.error);
                }
            });
        });
    } else {
        console.error('smartyGTMData is not defined');
    }
	
	// Add data-product_id to buttons without it on single product pages
    $('.single_add_to_cart_button').each(function() {
        var productID = $(this).data('product_id');
        if (!productID) {
            // Check the closest form for a product_id input
            var form = $(this).closest('form.cart');
            var idInput = form.find('input[name="add-to-cart"]').val();
            if (idInput) {
                $(this).attr('data-product_id', idInput);
            }
        }
    });
});


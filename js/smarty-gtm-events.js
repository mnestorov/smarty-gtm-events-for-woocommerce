jQuery(document).ready(function ($) {
    if (typeof smartyGTMData !== 'undefined') {
        // Handle add to cart event
        $(document.body).on('added_to_cart', function (event, fragments, cart_hash, $button) {
            var productID = $button.data('product_id') || 0;
            var quantity = $button.data('quantity') || 1;

            if (productID === 0) {
                console.error("Product ID is missing or undefined");
                return; // Exit if product ID is not found
            }

            // AJAX request to get the add_to_cart data
            $.ajax({
                url: smartyGTMData.ajax_url,
                method: 'POST',
                data: {
                    action: 'smarty_gtm_add_to_cart',
                    product_id: productID,
                    quantity: quantity,
                    nonce: smartyGTMData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        window.dataLayer = window.dataLayer || [];
                        window.dataLayer.push(response.data);
                    } else {
                        console.error("Error:", response.data.error);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", error);
                }
            });
        });

        // Ensure data-product_id is set on single product add to cart buttons
        $('.single_add_to_cart_button').each(function () {
            var $button = $(this);
            var productID = $button.data('product_id');
            if (!productID) {
                // Check the closest form for a product_id input
                var form = $button.closest('form.cart');
                var idInput = form.find('input[name="add-to-cart"]').val();
                if (idInput) {
                    $button.attr('data-product_id', idInput);
                }
            }
        });

        // Listen for changes in the payment method selection
        $(document.body).on('change', 'input[name="payment_method"]', function () {
            var paymentMethod = $('input[name="payment_method"]:checked').val();

            // Prepare the dataLayer event
            var data = {
                'event': 'add_payment_info',
                'eventModel': {
                    'payment_type': paymentMethod,
                    'event_source': 'smarty-gtm-events-for-woocommerce'
                }
            };

            // Push the event to the dataLayer
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push(data);
        });

        // Handle remove from cart event immediately on click
        $(document).on('click', 'a.remove', function (e) {
            var $button = $(this);
            var productID = $button.data('product_id');
            console.log('Remove link clicked, product ID:', productID);

            if (!productID) {
                console.error("Product ID is missing or undefined for remove_from_cart event");
                return;
            }

            // Prevent default action (optional, if you want to handle the removal via AJAX)
            // e.preventDefault();

            // AJAX request to get the product data
            $.ajax({
                url: smartyGTMData.ajax_url,
                method: 'POST',
                data: {
                    action: 'smarty_gtm_get_product_data',
                    product_id: productID,
                    nonce: smartyGTMData.nonce
                },
                success: function (response) {
                    if (response.success) {
                        var data = {
                            event: 'remove_from_cart',
                            eventModel: {
                                items: [response.data],
                                event_source: 'smarty-gtm-events-for-woocommerce'
                            }
                        };
                        window.dataLayer = window.dataLayer || [];
                        window.dataLayer.push(data);
                        console.log('remove_from_cart event pushed to dataLayer');
                    } else {
                        console.error("Error fetching product data:", response.data.error);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", error);
                }
            });
        });

        // Capture JavaScript errors
        window.onerror = function (message, source, lineno, colno, error) {
            var errorMessage = message + ' at ' + source + ':' + lineno + ':' + colno;
            $.ajax({
                url: smartyGTMData.ajax_url,
                method: 'POST',
                data: {
                    action: 'smarty_gtm_log_js_error',
                    error_message: errorMessage,
                    nonce: smartyGTMData.nonce
                }
            });
        }
    } else {
        console.error('smartyGTMData is not defined');
    }
});

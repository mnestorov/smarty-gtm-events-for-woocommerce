<?php
/**
 * Plugin Name:             SM - GTM Events for WooCommerce
 * Plugin URI:              https://github.com/mnestorov/smarty-gtm-events-for-woocommerce
 * Description:             Pushes WooCommerce events to Google Tag Manager's dataLayer.
 * Version:                 1.0.0
 * Author:                  Smarty Studio | Martin Nestorov
 * Author URI:              https://github.com/mnestorov
 * License:                 GPL-2.0+
 * License URI:             http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:             smarty-gtm-events-for-woocommerce
 * Domain Path:             /languages
 * WC requires at least:    3.5.0
 * WC tested up to:         9.0.2
 * Requires Plugins:		woocommerce
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Enqueue necessary JavaScript for dataLayer
 */
function smarty_gtm_events_enqueue_scripts() {
    if (is_product() || is_checkout() || is_shop() || is_product_category()) {
        add_action('wp_footer', 'smarty_gtm_view_item');
        add_action('wp_footer', 'smarty_gtm_view_item_list');
    }

    // Register the script
    wp_register_script('smarty-gtm-script', plugins_url('js/smarty-gtm-events.js', __FILE__), ['jquery'], '1.0.0', true);

    // Enqueue the script
    wp_enqueue_script('smarty-gtm-script');

    // Generate a nonce and pass it to JavaScript
    wp_localize_script('smarty-gtm-script', 'smartyGTMData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('smarty_gtm_nonce_action')
    ]);
}
add_action('wp_enqueue_scripts', 'smarty_gtm_events_enqueue_scripts');

/**
 * Get the unique site identifier (URL or name)
 */
function smarty_gtm_get_site_identifier() {
    return [
        'siteUrl' => get_site_url(),
        'siteName' => get_bloginfo('name')
    ];
}

/**
 * Utility function to push data to the dataLayer
 */
function smarty_gtm_push_to_dataLayer($data) {
    echo "\n<!-- SM - GTM Events for WooCommerce Plugin: Start Data Layer Event -->\n";
    echo '<script>window.dataLayer = window.dataLayer || []; dataLayer.push(' . json_encode($data) . ');</script>';
    echo "\n<!-- SM - GTM Events for WooCommerce Plugin: End Data Layer Event -->\n";
}

/**
 * Format event model for GTM data layer.
 */
function smarty_gtm_format_event_model($event, $transaction_id = '', $value = '', $currency = 'USD', $shipping = '', $tax = '', $items = []) {
    $site_info = smarty_gtm_get_site_identifier();
    return [
        'event' => $event,
        'eventModel' => [
            'transaction_id' => $transaction_id,
            'affiliation'    => $site_info['siteUrl'],
            'value'          => $value,
            'currency'       => $currency,
            'shipping'       => $shipping,
            'tax'            => $tax,
            'items'          => $items,
            'event_source'   => $event_source,
        ]
    ];
}

/**
 * Format product item data.
 */
function smarty_gtm_format_product_item($product, $quantity = 1, $list_position = 1) {
    return [
        'id'            => $product->get_id(),
        'name'          => $product->get_name(),
        'list_name'     => 'Order',
        'brand'         => '', // Populate with actual brand if available
        'category'      => wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names'])[0] ?? '',
        'variant'       => '', // Populate with variant if available
        'list_position' => (string) $list_position,
        'price'         => $product->get_price(),
        'quantity'      => (string) $quantity,
        'SKU'           => $product->get_sku() ?: '',
    ];
}

/**
 * Push view_item event on single product pages.
 */
function smarty_gtm_view_item() {
    if (is_product()) {
        global $product;
        $data = smarty_gtm_format_event_model(
            'view_item',
            '',
            $product->get_price(),
            get_woocommerce_currency(),
            '',
            '',
            [smarty_gtm_format_product_item($product)],
            'smarty-gtm-events-for-woocommerce'
        );

        smarty_gtm_push_to_dataLayer($data);
    }
}

/**
 * Push view_item_list event on product list pages.
 */
function smarty_gtm_view_item_list() {
    if (is_shop() || is_product_category() || is_product_tag()) {
        global $wp_query;
        $products = [];
        foreach ($wp_query->posts as $index => $post) {
            $product = wc_get_product($post->ID);
            $products[] = smarty_gtm_format_product_item($product, 1, $index + 1);
        }

        $data = smarty_gtm_format_event_model(
            'view_item_list',
            '',
            '',
            get_woocommerce_currency(),
            '',
            '',
            $products,
            'smarty-gtm-events-for-woocommerce'
        );

        smarty_gtm_push_to_dataLayer($data);
    }
}

/**
 * Push add_to_cart event when product is added to cart via AJAX.
 */
function smarty_gtm_add_to_cart_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smarty_gtm_nonce_action')) {
        wp_send_json_error(['error' => 'Invalid nonce']);
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if ($product_id <= 0) {
        wp_send_json_error(['error' => 'Product ID is missing or invalid']);
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['error' => 'Product not found']);
        return;
    }

    $data = smarty_gtm_format_event_model(
        'add_to_cart',
        '',
        $product->get_price() * $quantity,
        get_woocommerce_currency(),
        '',
        '',
        [smarty_gtm_format_product_item($product, $quantity)],
        'smarty-gtm-events-for-woocommerce'
    );

    wp_send_json_success($data);
}
add_action('wp_ajax_nopriv_smarty_gtm_add_to_cart', 'smarty_gtm_add_to_cart_ajax');
add_action('wp_ajax_smarty_gtm_add_to_cart', 'smarty_gtm_add_to_cart_ajax');

/**
 * Push view_cart event when cart page is viewed.
 */
function smarty_gtm_view_cart() {
    if (is_cart()) {
        $cart_items = [];
        foreach (WC()->cart->get_cart() as $index => $cart_item) {
            $product = $cart_item['data'];
            $cart_items[] = smarty_gtm_format_product_item($product, $cart_item['quantity'], $index + 1);
        }

        $data = smarty_gtm_format_event_model(
            'view_cart',
            '',
            WC()->cart->get_cart_total(),
            get_woocommerce_currency(),
            WC()->cart->get_shipping_total(),
            WC()->cart->get_total_tax(),
            $cart_items,
            'smarty-gtm-events-for-woocommerce'
        );

        smarty_gtm_push_to_dataLayer($data);
    }
}
add_action('woocommerce_before_cart', 'smarty_gtm_view_cart');

/**
 * Push remove_from_cart event when a product is removed from cart.
 */
function smarty_gtm_remove_from_cart($cart_item_key, $cart) {
    $cart_item = $cart->get_cart_item($cart_item_key);
    $product = $cart_item['data'];

    $data = smarty_gtm_format_event_model(
        'remove_from_cart',
        '',
        $product->get_price() * $cart_item['quantity'],
        get_woocommerce_currency(),
        '',
        '',
        [smarty_gtm_format_product_item($product, $cart_item['quantity'])],
        'smarty-gtm-events-for-woocommerce'
    );

    add_action('wp_footer', function() use ($data) {
        smarty_gtm_push_to_dataLayer($data);
    });
}
add_action('woocommerce_remove_cart_item', 'smarty_gtm_remove_from_cart', 10, 2);

/**
 * Push begin_checkout event when checkout is started.
 */
function smarty_gtm_begin_checkout() {
    if (is_checkout() && !is_order_received_page()) {
        $cart_items = [];
        $cart_total = 0.0;

        foreach (WC()->cart->get_cart() as $index => $cart_item) {
            $product = $cart_item['data'];
            $quantity = (int) $cart_item['quantity'];
            $price = (float) $product->get_price();

            // Accumulate the total cart value
            $cart_total += $price * $quantity;

            // Format each product item using smarty_gtm_format_product_item
            $cart_items[] = smarty_gtm_format_product_item($product, $quantity, $index + 1);
        }

        // Format the event model using smarty_gtm_format_event_model
        $data = smarty_gtm_format_event_model(
            'begin_checkout',
            uniqid('order_'), // Generate a unique transaction ID
            number_format($cart_total, 2, '.', ''), // Total value for checkout
            get_woocommerce_currency(),
            number_format((float) WC()->cart->get_shipping_total(), 2, '.', ''),
            number_format((float) WC()->cart->get_total_tax(), 2, '.', ''),
            $cart_items,
            'smarty-gtm-events-for-woocommerce'
        );

        // Push the formatted data to the dataLayer
        smarty_gtm_push_to_dataLayer($data);
    }
}
add_action('woocommerce_before_checkout_form', 'smarty_gtm_begin_checkout');

/**
 * Push purchase event on order confirmation page.
 */
function smarty_gtm_purchase($order_id) {
    $order = wc_get_order($order_id);
    $items = [];
    foreach ($order->get_items() as $index => $item) {
        $product = $item->get_product();
        $items[] = smarty_gtm_format_product_item($product, $item->get_quantity(), $index + 1);
    }

    $data = smarty_gtm_format_event_model(
        'purchase',
        $order->get_id(),
        $order->get_total(),
        get_woocommerce_currency(),
        $order->get_shipping_total(),
        $order->get_total_tax(),
        $items,
        'smarty-gtm-events-for-woocommerce'
    );

    smarty_gtm_push_to_dataLayer($data);
}
add_action('woocommerce_thankyou', 'smarty_gtm_purchase', 10, 1);

/**
 * Push add_payment_info event to dataLayer when payment information is submitted in the checkout process.
 */
function smarty_gtm_add_payment_info() {
    if (is_checkout() && !is_order_received_page()) {
        $cart_items = [];
        $cart_total = 0.0;

        foreach (WC()->cart->get_cart() as $index => $cart_item) {
            $product = $cart_item['data'];
            $quantity = (int) $cart_item['quantity'];
            $price = (float) $product->get_price();

            // Accumulate the total cart value
            $cart_total += $price * $quantity;

            $cart_items[] = smarty_gtm_format_product_item($product, $quantity, $index + 1);
        }

        // Retrieve the payment method, defaulting to 'unknown' if not available
        $payment_method = WC()->session->get('chosen_payment_method') ?: 'unknown';

        // Format the event model using smarty_gtm_format_event_model
        $data = smarty_gtm_format_event_model(
            'add_payment_info',
            uniqid('order_'), // Generate a unique transaction ID
            number_format($cart_total, 2, '.', ''), // Cart total as 'value'
            get_woocommerce_currency(),
            number_format((float) WC()->cart->get_shipping_total(), 2, '.', ''),
            number_format((float) WC()->cart->get_total_tax(), 2, '.', ''),
            $cart_items,
            'smarty-gtm-events-for-woocommerce'
        );

        // Add additional field for payment type
        $data['eventModel']['payment_type'] = $payment_method;

        // Push the formatted data to the dataLayer
        smarty_gtm_push_to_dataLayer($data);
    }
}
add_action('woocommerce_review_order_after_submit', 'smarty_gtm_add_payment_info');
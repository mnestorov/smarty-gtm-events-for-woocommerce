<?php
/**
 * Plugin Name:             SM- GTM Events for WooCommerce
 * Plugin URI:              https://github.com/mnestorov/smarty-google-feed-generator
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
}
add_action('wp_enqueue_scripts', 'smarty_gtm_events_enqueue_scripts');

/**
 * Push view_item event to dataLayer on single product pages
 */
function smarty_gtm_view_item() {
    if (is_product()) {
        global $product;

        $data = [
            'event' => 'view_item',
            'ecommerce' => [
                'currencyCode' => get_woocommerce_currency(),
                'detail' => [
                    'products' => [
                        [
                            'id' => $product->get_id(),
                            'name' => $product->get_name(),
                            'price' => $product->get_price(),
                            'category' => wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names'])[0] ?? '',
                        ]
                    ]
                ]
            ]
        ];

        echo '<script>window.dataLayer = window.dataLayer || []; dataLayer.push(' . json_encode($data) . ');</script>';
    }
}

/**
 * Push view_item_list event to dataLayer on product list pages
 */
function smarty_gtm_view_item_list() {
    if (is_shop() || is_product_category() || is_product_tag()) {
        global $wp_query;

        $products = [];
        foreach ($wp_query->posts as $post) {
            $product = wc_get_product($post->ID);
            $products[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'category' => wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names'])[0] ?? '',
            ];
        }

        $data = [
            'event' => 'view_item_list',
            'ecommerce' => [
                'currencyCode' => get_woocommerce_currency(),
                'items' => $products,
            ]
        ];

        echo '<script>window.dataLayer = window.dataLayer || []; dataLayer.push(' . json_encode($data) . ');</script>';
    }
}

/**
 * Push addToCart event to dataLayer when a product is added to cart
 */
function smarty_gtm_add_to_cart($cart_item_key, $product_id, $quantity) {
    $product = wc_get_product($product_id);

    $data = [
        'event' => 'add_to_cart',
        'ecommerce' => [
            'currencyCode' => get_woocommerce_currency(),
            'add' => [
                'products' => [
                    [
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'price' => $product->get_price(),
                        'quantity' => $quantity,
                    ]
                ]
            ]
        ]
    ];

    echo '<script>window.dataLayer = window.dataLayer || []; dataLayer.push(' . json_encode($data) . ');</script>';
}
add_action('woocommerce_add_to_cart', 'smarty_gtm_add_to_cart', 10, 3);

/**
 * Push begin_checkout event to dataLayer when checkout is started
 */
function smarty_gtm_begin_checkout() {
    if (is_checkout() && !is_order_received_page()) {
        $cart_items = [];
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $cart_items[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'quantity' => $cart_item['quantity'],
            ];
        }

        $data = [
            'event' => 'begin_checkout',
            'ecommerce' => [
                'currencyCode' => get_woocommerce_currency(),
                'items' => $cart_items,
            ]
        ];

        echo '<script>window.dataLayer = window.dataLayer || []; dataLayer.push(' . json_encode($data) . ');</script>';
    }
}
add_action('woocommerce_before_checkout_form', 'smarty_gtm_begin_checkout');

/**
 * Push purchase event to dataLayer on the order confirmation page
 */
function smarty_gtm_purchase($order_id) {
    $order = wc_get_order($order_id);

    $products = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        $products[] = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'quantity' => $item->get_quantity(),
        ];
    }

    $data = [
        'event' => 'purchase',
        'ecommerce' => [
            'currencyCode' => get_woocommerce_currency(),
            'purchase' => [
                'actionField' => [
                    'id' => $order->get_id(),
                    'affiliation' => get_bloginfo('name'),
                    'revenue' => $order->get_total(),
                    'tax' => $order->get_total_tax(),
                    'shipping' => $order->get_shipping_total(),
                ],
                'products' => $products,
            ]
        ]
    ];

    echo '<script>window.dataLayer = window.dataLayer || []; dataLayer.push(' . json_encode($data) . ');</script>';
}
add_action('woocommerce_thankyou', 'smarty_gtm_purchase', 10, 1);
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

// Define your secret key to clear the logs.
define('SMARTY_GTM_CLEAR_LOGS_SECRET', 'your-unique-secret-key');

/**
 * Enqueue necessary JavaScript for dataLayer
 *
 * @return void
 */
function smarty_gtm_events_enqueue_scripts() {
    // Enqueue on WooCommerce pages, cart page, and checkout page
    if (is_woocommerce() || is_cart() || is_checkout()) {
        // Register the script
        wp_register_script(
            'smarty-gtm-script',
            plugins_url('js/smarty-gtm-events.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );

        // Enqueue the script
        wp_enqueue_script('smarty-gtm-script');

        // Generate a nonce and pass it to JavaScript
        wp_localize_script('smarty-gtm-script', 'smartyGTMData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('smarty_gtm_nonce_action'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'smarty_gtm_events_enqueue_scripts');

/**
 * Get the unique site identifier (URL or name)
 *
 * @return array Associative array containing 'siteUrl' and 'siteName'
 */
function smarty_gtm_get_site_identifier() {
    return array(
        'siteUrl'  => get_site_url(),
        'siteName' => get_bloginfo('name'),
    );
}

/**
 * Utility function to push data to the dataLayer
 *
 * @param array $data Data to push to dataLayer
 * @return void
 */
function smarty_gtm_push_to_dataLayer($data) {
    echo "\n<!-- SM - GTM Events for WooCommerce Plugin: Start Data Layer Event -->\n";
    echo '<script type="text/javascript">';
    echo 'window.dataLayer = window.dataLayer || []; window.dataLayer.push(' . wp_json_encode($data) . ');';
    echo '</script>';
    echo "\n<!-- SM - GTM Events for WooCommerce Plugin: End Data Layer Event -->\n";
}

/**
 * Format event model for GTM data layer
 *
 * @param string $event Event name
 * @param string $transaction_id Transaction ID
 * @param float $value Transaction value
 * @param string $currency Currency code
 * @param float $shipping Shipping cost
 * @param float $tax Tax amount
 * @param array $items Array of items
 * @param string $event_source Event source identifier
 * @return array Formatted event model
 */
function smarty_gtm_format_event_model($event, $transaction_id = '', $value = '', $currency = '', $shipping = '', $tax = '', $items = array(), $event_source = 'plugin') {
    $site_info = smarty_gtm_get_site_identifier();
    
    $customer_data = array();
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $customer_data['user_id'] = $user->ID;
        $customer_data['user_role'] = implode(', ', $user->roles);
    }

    return array(
        'event'      => $event,
        'eventModel' => array(
            'transaction_id' => $transaction_id,
            'affiliation'    => $site_info['siteUrl'],
            'value'          => $value,
            'currency'       => $currency,
            'shipping'       => $shipping,
            'tax'            => $tax,
            'items'          => $items,
            'event_source'   => $event_source,
        ),
        'customer' => $customer_data,
    );
}

/**
 * Format product item data
 *
 * @param WC_Product $product WooCommerce product object
 * @param int $quantity Quantity of the product
 * @param int $list_position Position in the list
 * @return array Formatted product item data
 */
function smarty_gtm_format_product_item($product, $quantity = 1, $list_position = 1) {
    // Get product categories
    $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
    $category   = !empty($categories) ? implode('/', $categories) : '';

    // Get product brand if available (assuming it's stored as a custom attribute 'brand')
    $brand = $product->get_attribute('brand');

    // Get product variant if available (for variable products)
    $variant = '';
    if ($product->is_type('variation')) {
        $attributes = $product->get_variation_attributes();
        $variant    = implode(', ', $attributes);
    }

    return array(
        'id'            => $product->get_id(),
        'name'          => $product->get_name(),
        'list_name'     => 'Order',
        'brand'         => $brand ?: '',
        'category'      => $category,
        'variant'       => $variant,
        'list_position' => (string) $list_position,
        'price'         => (float) $product->get_price(),
        'quantity'      => (int) $quantity,
        'SKU'           => $product->get_sku() ?: '',
        'dimensions'    => array(
            'length' => $product->get_length() ?: '',
            'width'  => $product->get_width() ?: '',
            'height' => $product->get_height() ?: '',
        ),
        'weight'        => $product->get_weight(),
        'custom_fields' => get_post_meta( $product->get_id() ),
    );
}

/**
 * Push view_item event on single product pages
 *
 * @return void
 */
function smarty_gtm_view_item() {
    if (is_product()) {
        global $product;
        $data = smarty_gtm_format_event_model(
            'view_item',
            '',
            (float) $product->get_price(),
            get_woocommerce_currency(),
            '',
            '',
            array( smarty_gtm_format_product_item($product)),
            'smarty-gtm-events-for-woocommerce'
        );

        smarty_gtm_push_to_dataLayer($data);

        smarty_gtm_log_event('view_item', $data);
    }
}
add_action('woocommerce_after_single_product', 'smarty_gtm_view_item');

/**
 * Push view_item_list event on product list pages
 *
 * @return void
 */
function smarty_gtm_view_item_list() {
    if (is_shop() || is_product_category() || is_product_tag()) {
        global $wp_query;
        $products = get_transient('smarty_gtm_product_list');

        if (false === $products) {
            $products = array();
            foreach ($wp_query->posts as $index => $post) {
                $product = wc_get_product($post->ID);
                if ($product) {
                    $products[] = smarty_gtm_format_product_item($product, 1, $index + 1);
                }
            }
            // Cache the products data for 10 minutes
            set_transient('smarty_gtm_product_list', $products, 10 * MINUTE_IN_SECONDS);
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

        smarty_gtm_log_event('view_item_list', $data);
    }
}
add_action('woocommerce_after_shop_loop', 'smarty_gtm_view_item_list');

/**
 * Clear product list cache when a product is updated
 *
 * @param int $post_id Post ID
 * @return void
 */
function smarty_gtm_clear_product_list_cache($post_id) {
    if (get_post_type($post_id) == 'product') {
        delete_transient('smarty_gtm_product_list');
    }
}
add_action('save_post', 'smarty_gtm_clear_product_list_cache');

/**
 * Push add_to_cart event when product is added to cart via AJAX
 *
 * @return void
 */
function smarty_gtm_add_to_cart_ajax() {
    check_ajax_referer('smarty_gtm_nonce_action', 'nonce');

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $quantity   = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if ($product_id <= 0) {
        smarty_gtm_log_error($error_message);
        wp_send_json_error(array('error' => __('Product ID is missing or invalid', 'smarty-gtm-events-for-woocommerce')));
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        smarty_gtm_log_error($error_message);
        wp_send_json_error(array('error' => __('Product not found', 'smarty-gtm-events-for-woocommerce')));
        return;
    }

    $data = smarty_gtm_format_event_model(
        'add_to_cart',
        '',
        (float) $product->get_price() * $quantity,
        get_woocommerce_currency(),
        '',
        '',
        array( smarty_gtm_format_product_item($product, $quantity) ),
        'smarty-gtm-events-for-woocommerce'
    );

    smarty_gtm_log_event('add_to_cart', $data);

    wp_send_json_success($data);
}
add_action('wp_ajax_nopriv_smarty_gtm_add_to_cart', 'smarty_gtm_add_to_cart_ajax');
add_action('wp_ajax_smarty_gtm_add_to_cart', 'smarty_gtm_add_to_cart_ajax');

/**
 * Push view_cart event when cart page is viewed
 *
 * @return void
 */
function smarty_gtm_view_cart() {
    if (is_cart()) {
        $cart_items = array();
        $index      = 0;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $index++;
            $cart_items[] = smarty_gtm_format_product_item($product, $cart_item['quantity'], $index);
        }

        $data = smarty_gtm_format_event_model(
            'view_cart',
            '',
            WC()->cart->get_cart_contents_total(),
            get_woocommerce_currency(),
            WC()->cart->get_shipping_total(),
            WC()->cart->get_total_tax(),
            $cart_items,
            'smarty-gtm-events-for-woocommerce'
        );

        smarty_gtm_push_to_dataLayer($data);

        smarty_gtm_log_event('view_cart', $data);
    }
}
add_action('woocommerce_after_cart_table', 'smarty_gtm_view_cart');

/**
 * Push remove_from_cart event when a product is removed from cart
 *
 * @param string   $cart_item_key Key of the cart item being removed
 * @param WC_Cart  $cart          The cart object
 * @return void
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

    smarty_gtm_log_event('remove_from_cart', $data);
}
add_action('woocommerce_remove_cart_item', 'smarty_gtm_remove_from_cart', 10, 2);

/**
 * Add data-product_id attribute to cart item remove links
 *
 * @param string $url The original remove link HTML
 * @param string $cart_item_key The cart item key
 * @return string Modified remove link HTML
 */
function smarty_gtm_add_data_to_remove_link($url, $cart_item_key) {
    $cart_item = WC()->cart->get_cart_item($cart_item_key);
    if ($cart_item && isset($cart_item['product_id'])) {
        $product_id = $cart_item['product_id'];
        // Add data-product_id attribute
        $url = str_replace('<a ', '<a data-product_id="' . $product_id . '" ', $url);
    }
    return $url;
}
add_filter('woocommerce_cart_item_remove_link', 'smarty_gtm_add_data_to_remove_link', 10, 2);

/**
 * AJAX handler to get product data for remove_from_cart event
 */
function smarty_gtm_get_product_data() {
    check_ajax_referer('smarty_gtm_nonce_action', 'nonce');

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

    if ($product_id <= 0) {
        smarty_gtm_log_error($error_message);
        wp_send_json_error(array('error' => __('Product ID is missing or invalid', 'smarty-gtm-events-for-woocommerce')));
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        smarty_gtm_log_error($error_message);
        wp_send_json_error(array('error' => __('Product not found', 'smarty-gtm-events-for-woocommerce')));
        return;
    }

    // Since we don't have quantity info, assume 1
    $product_data = smarty_gtm_format_product_item($product, 1);

    wp_send_json_success($product_data);
}
add_action('wp_ajax_nopriv_smarty_gtm_get_product_data', 'smarty_gtm_get_product_data');
add_action('wp_ajax_smarty_gtm_get_product_data', 'smarty_gtm_get_product_data');

/**
 * Push begin_checkout event when checkout is started
 *
 * @return void
 */
function smarty_gtm_begin_checkout() {
    if (is_checkout() && ! is_order_received_page()) {
        $cart_items = array();
        $cart_total = 0.0;

        $index = 0;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product   = $cart_item['data'];
            $quantity  = (int) $cart_item['quantity'];
            $price     = (float) $product->get_price();
            $cart_total += $price * $quantity;
            $index++;
            $cart_items[] = smarty_gtm_format_product_item($product, $quantity, $index);
        }

        $data = smarty_gtm_format_event_model(
            'begin_checkout',
            uniqid('order_'),
            number_format($cart_total, 2, '.', ''),
            get_woocommerce_currency(),
            number_format((float) WC()->cart->get_shipping_total(), 2, '.', ''),
            number_format((float) WC()->cart->get_total_tax(), 2, '.', ''),
            $cart_items,
            'smarty-gtm-events-for-woocommerce'
        );

        smarty_gtm_push_to_dataLayer($data);

        smarty_gtm_log_event('begin_checkout', $data);
    }
}
add_action('woocommerce_before_checkout_form', 'smarty_gtm_begin_checkout');

/**
 * Push purchase event on order confirmation page
 *
 * @param int $order_id Order ID
 * @return void
 */
function smarty_gtm_purchase($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $items = array();
    $index = 0;
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product) {
            $index++;
            $items[] = smarty_gtm_format_product_item($product, $item->get_quantity(), $index);
        }
    }

    $data = smarty_gtm_format_event_model(
        'purchase',
        $order->get_order_number(),
        (float) $order->get_total(),
        $order->get_currency(),
        (float) $order->get_shipping_total(),
        (float) $order->get_total_tax(),
        $items,
        'smarty-gtm-events-for-woocommerce'
    );

    smarty_gtm_push_to_dataLayer($data);

    smarty_gtm_log_event('purchase', $data);
}
add_action('woocommerce_thankyou', 'smarty_gtm_purchase', 10, 1);

/**
 * Push add_payment_info event to dataLayer when payment information is submitted in the checkout process
 *
 * @param array $posted_data Posted data from checkout form
 * @param WP_Error $errors Validation errors
 * @return void
 */
function smarty_gtm_add_payment_info() {
    if (is_checkout() && !is_order_received_page()) {
        $cart_items = [];
        $cart_total = 0; // Keep it as integer

        foreach (WC()->cart->get_cart() as $index => $cart_item) {
            $product = $cart_item['data'];
            $quantity = intval($cart_item['quantity']);
            $price = floatval($product->get_price());

            $line_total = $price * $quantity;
            $cart_total += $line_total;

            $cart_items[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $price,
                'quantity' => $quantity,
            ];
        }

        $payment_method = WC()->session->get('chosen_payment_method') ?: 'unknown';
		
		$data = smarty_gtm_format_event_model(
			'add_payment_info',
			uniqid('order_'),
			$cart_total,
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

        smarty_gtm_log_event('add_payment_info', $data);
    }
}
add_action('woocommerce_review_order_after_payment', 'smarty_gtm_add_payment_info');

/**
 * Push search event when a search is performed
 *
 * @return void
 */
function smarty_gtm_site_search_tracking() {
    if (is_search()) {
        add_action('wp_footer', 'smarty_gtm_push_search_event');
    }
}
add_action('template_redirect', 'smarty_gtm_site_search_tracking');

/**
 * Outputs the search event script
 *
 * @return void
 */
function smarty_gtm_push_search_event() {
    $search_query = get_search_query();
    $data = array(
        'event' => 'search',
        'search_term' => $search_query,
    );
    smarty_gtm_push_to_dataLayer($data);

    smarty_gtm_log_event('search', $data);
}

/**
 * Push apply_coupon event when a coupon is applied
 *
 * @param string $coupon_code Applied coupon code
 * @return void
 */
function smarty_gtm_coupon_applied($coupon_code) {
    $data = array(
        'event' => 'apply_coupon',
        'coupon' => $coupon_code,
    );
    smarty_gtm_push_to_dataLayer($data);

    smarty_gtm_log_event('apply_coupon', $data);
}
add_action('woocommerce_applied_coupon', 'smarty_gtm_coupon_applied');

/**
 * Push refund event when an order is refunded
 *
 * @param int $order_id Order ID
 * @param int $refund_id Refund ID
 * @return void
 */
function smarty_gtm_order_refunded($order_id, $refund_id) {
    $order = wc_get_order($order_id);
    $refund = wc_get_order($refund_id);

    $data = array(
        'event' => 'refund',
        'transaction_id' => $order->get_order_number(),
        'value' => (float) $refund->get_amount(),
        'currency' => $order->get_currency(),
    );
    smarty_gtm_push_to_dataLayer($data);

    smarty_gtm_log_event('refund', $data);
}
add_action('woocommerce_order_refunded', 'smarty_gtm_order_refunded', 10, 2);

/**
 * Create the event log database table upon plugin activation.
 *
 * @return void
 */
function smarty_gtm_create_event_log_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'smarty_gtm_event_log';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_time DATETIME NOT NULL,
        event_name VARCHAR(255) NOT NULL,
        user_id BIGINT(20) UNSIGNED DEFAULT NULL,
        user_role VARCHAR(255) DEFAULT NULL,
        event_data LONGTEXT DEFAULT NULL,
        PRIMARY KEY (id),
        INDEX (event_time),
        INDEX (event_name)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Create the error log database table upon plugin activation.
 *
 * @return void
 */
function smarty_gtm_create_error_log_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'smarty_gtm_error_log';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        error_time DATETIME NOT NULL,
        error_message TEXT NOT NULL,
        PRIMARY KEY (id),
        INDEX (error_time)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Plugin activation hook to create necessary database tables.
 *
 * @return void
 */
function smarty_gtm_plugin_activation() {
    smarty_gtm_create_event_log_table();
    smarty_gtm_create_error_log_table();
}
register_activation_hook(__FILE__, 'smarty_gtm_plugin_activation');

/**
 * Log an event to the database.
 *
 * @param string $event_name Event name.
 * @param array  $event_data Event data.
 * @return void
 */
function smarty_gtm_log_event($event_name, $event_data = array()) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'smarty_gtm_event_log';

    $user_id = null;
    $user_role = null;
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $user_id = $user->ID;
        $user_role = implode(', ', $user->roles);
    }

    $wpdb->insert(
        $table_name,
        array(
            'event_time' => current_time('mysql'),
            'event_name' => $event_name,
            'user_id' => $user_id,
            'user_role' => $user_role,
            'event_data' => maybe_serialize($event_data),
        ),
        array(
            '%s',
            '%s',
            '%d',
            '%s',
            '%s',
        )
    );
}

/**
 * Log an error message and reset the dismissed flag.
 *
 * @param string $error_message Error message to log.
 * @return void
 */
function smarty_gtm_log_error($error_message) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'smarty_gtm_error_log';

    $wpdb->insert(
        $table_name,
        array(
            'error_time' => current_time('mysql'),
            'error_message' => $error_message,
        ),
        array(
            '%s',
            '%s',
        )
    );

    // Reset the dismissed flag when a new error is logged
    delete_option('smarty_gtm_errors_dismissed');
}

/**
 * Retrieve recent errors from the error log.
 *
 * @param int $limit Number of errors to retrieve.
 * @return array Array of recent errors.
 */
function smarty_gtm_get_recent_errors($limit = 5) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'smarty_gtm_error_log';

    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT error_time, error_message FROM $table_name ORDER BY error_time DESC LIMIT %d", $limit),
        ARRAY_A
    );

    return $results;
}

/**
 * Display an admin notice if there are logged errors.
 *
 * @return void
 */
function smarty_gtm_display_error_notice() {
    // Only show the notice to users who can manage options
    if (!current_user_can('manage_options')) {
        return;
    }

    // Check if the notice has been dismissed
    $dismissed = get_option('smarty_gtm_errors_dismissed');
    if ($dismissed) {
        return;
    }

    // Get recent errors
    $errors = smarty_gtm_get_recent_errors();

    if (!empty($errors)) {
        // Prepare the error messages
        $error_messages = '';
        foreach ($errors as $error) {
            $error_messages .= '<li>' . esc_html($error['error_time'] . ' - ' . $error['error_message']) . '</li>';
        }

        // Display the admin notice
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>SM - GTM Events for WooCommerce:</strong> The following errors have been logged.</p>';
        echo '<ul>' . $error_messages . '</ul>';
        echo '<p>Please check the plugin settings or contact support for assistance.</p>';
        echo '</div>';

        // Include a script to handle the dismiss action
        ?>
        <script type="text/javascript">
            jQuery(document).on('click', '.notice-error.is-dismissible .notice-dismiss', function () {
                jQuery.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'smarty_gtm_dismiss_errors',
                        nonce: '<?php echo wp_create_nonce('smarty_gtm_dismiss_errors_nonce'); ?>'
                    }
                });
            });
        </script>
        <?php
    }
}
add_action('admin_notices', 'smarty_gtm_display_error_notice');

/**
 * Handle the AJAX request to dismiss the error notice.
 *
 * @return void
 */
function smarty_gtm_dismiss_errors() {
    // Verify nonce
    check_ajax_referer('smarty_gtm_dismiss_errors_nonce', 'nonce');

    // Check if the user has the capability to manage options
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    // Update the option to indicate that errors have been dismissed
    update_option('smarty_gtm_errors_dismissed', true);

    wp_send_json_success();
}
add_action('wp_ajax_smarty_gtm_dismiss_errors', 'smarty_gtm_dismiss_errors');

/**
 * Log an error when a specific URL parameter is present.
 *
 * Usage: Visit https://your-site.com/wp-admin/?simulate_error=1 to trigger.
 *
 * @return void
 */
function smarty_gtm_log_error_on_demand() {
    if (isset($_GET['simulate_error'])) {
        $error_message = 'Simulated error triggered via URL parameter.';
        smarty_gtm_log_error($error_message);
    }
}
add_action('admin_init', 'smarty_gtm_log_error_on_demand');

/**
 * Clear the GTM event and error logs when a specific URL parameter is present.
 *
 * Usage: Visit https://your-site.com/wp-admin/?clear_gtm_logs=1&key=your-unique-secret-key to trigger.
 *
 * @return void
 */
function smarty_gtm_clear_logs_on_demand() {
    if (isset($_GET['clear_gtm_logs']) && isset($_GET['key'])) {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_die(__('You are not authorized to perform this action.', 'smarty-gtm-events-for-woocommerce'));
        }

        // Verify the secret key
        if ($_GET['key'] !== SMARTY_GTM_CLEAR_LOGS_SECRET) {
            wp_die(__('Invalid key provided.', 'smarty-gtm-events-for-woocommerce'));
        }

        // Proceed to clear the logs
        smarty_gtm_clear_event_logs();
        smarty_gtm_clear_error_logs();

        // Set a flag to indicate that logs have been cleared
        update_option('smarty_gtm_logs_cleared', true);
    }
}
add_action('admin_init', 'smarty_gtm_clear_logs_on_demand');


/**
 * Display an admin notice when the logs have been cleared.
 *
 * @return void
 */
function smarty_gtm_display_clear_logs_notice() {
    // Check if the logs have been cleared
    if (get_option('smarty_gtm_logs_cleared')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . __('SM - GTM Events for WooCommerce: Event and error logs have been cleared.', 'smarty-gtm-events-for-woocommerce') . '</p>';
        echo '</div>';

        // Delete the flag to prevent the notice from showing again
        delete_option('smarty_gtm_logs_cleared');
    }
}
add_action('admin_notices', 'smarty_gtm_display_clear_logs_notice');

/**
 * Clear event logs.
 */
function smarty_gtm_clear_event_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'smarty_gtm_event_log';
    $wpdb->query("TRUNCATE TABLE $table_name");
}

/**
 * Clear error logs.
 */
function smarty_gtm_clear_error_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'smarty_gtm_error_log';
    $wpdb->query("TRUNCATE TABLE $table_name");
}
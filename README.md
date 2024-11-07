<p align="center"><a href="https://smartystudio.net" target="_blank"><img src="https://smartystudio.net/wp-content/uploads/2023/06/smarty-green-logo-small.png" width="100" alt="SmartyStudio Logo"></a></p>

# Smarty Studio - GTM Events for WooCommerce

[![Licence](https://img.shields.io/badge/LICENSE-GPL2.0+-blue)](./LICENSE)

- Developed by: [Smarty Studio](https://smartystudio.net) | [Martin Nestorov](https://github.com/mnestorov)
- Plugin URI: https://github.com/mnestorov/smarty-gtm-events-for-woocommerce

## Support The Project

Your support is greatly appreciated and will help ensure all of the projects continued development and improvement. Thank you for being a part of the community!
You can send me money on Revolut by following this link: https://revolut.me/mnestorovv

## Overview

This plugin pushes WooCommerce events to Google Tag Manager’s `dataLayer`, enabling enhanced eCommerce tracking.

---

## Description

The **SM - GTM Events for WooCommerce** plugin enables tracking of key WooCommerce events in Google Tag Manager by pushing them to the `dataLayer`. It covers critical eCommerce events such as product views, add to cart actions, and purchases, making it easier to set up tracking for enhanced eCommerce analytics.

---

## Installation

1. Upload the plugin folder to `/wp-content/plugins/` directory, or install it through the WordPress plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Ensure that Google Tag Manager is configured to listen to the custom events from this plugin.

---

## Event Descriptions

The plugin tracks and pushes the following events to the `dataLayer`:

### 1. `view_item`

Triggered on single product pages, this event provides detailed information about the viewed product.

**Data Structure:**
```javascript
{
    "event": "view_item",
    "ecommerce": {
        "currencyCode": "USD",
        "detail": {
            "products": [
                {
                    "id": "123",
                    "name": "Sample Product",
                    "price": "49.99",
                    "category": "Category Name"
                }
            ]
        }
    }
}
```

### 2. `view_item_list`

Triggered on product list pages, such as shop or category pages, to track product impressions.

**Data Structure:**

```javascript
{
    "event": "view_item_list",
    "ecommerce": {
        "currencyCode": "USD",
        "items": [
            {
                "id": "123",
                "name": "Sample Product",
                "price": "49.99",
                "category": "Category Name"
            },
            // Other products...
        ]
    }
}
```

### 3. `add_to_cart`

Fires when a product is added to the cart, capturing the product ID, name, price, and quantity.

**Data Structure:**

```javascript
{
    "event": "add_to_cart",
    "ecommerce": {
        "currencyCode": "USD",
        "add": {
            "products": [
                {
                    "id": "123",
                    "name": "Sample Product",
                    "price": "49.99",
                    "quantity": 1
                }
            ]
        }
    }
}
```

### 4. `view_cart`

Triggered on the cart page to provide an overview of the items in the cart.

**Data Structure:**

```javascript
{
    "event": "view_cart",
    "ecommerce": {
        "currencyCode": "USD",
        "items": [
            {
                "id": "123",
                "name": "Sample Product",
                "price": "49.99",
                "quantity": 1
            },
            // Other cart items...
        ]
    }
}
```

### 5. `remove_from_cart`

Fires when a product is removed from the cart, capturing details of the removed product.

**Data Structure:**

```javascript
{
    "event": "remove_from_cart",
    "ecommerce": {
        "currencyCode": "USD",
        "remove": {
            "products": [
                {
                    "id": "123",
                    "name": "Sample Product",
                    "price": "49.99",
                    "quantity": 1
                }
            ]
        }
    }
}
```

### 6. `begin_checkout`

Triggered when the checkout process begins, capturing details of items currently in the cart.

**Data Structure:**

```javascript
{
    "event": "begin_checkout",
    "ecommerce": {
        "currencyCode": "USD",
        "items": [
            {
                "id": "123",
                "name": "Sample Product",
                "price": "49.99",
                "quantity": 1
            },
            // Other cart items...
        ]
    }
}
```

### 7. `purchase`

Fires on the order confirmation page to log purchase details, including order ID, total revenue, tax, shipping, and items purchased.

**Data Structure:**

```javascript
{
    "event": "purchase",
    "ecommerce": {
        "currencyCode": "USD",
        "purchase": {
            "actionField": {
                "id": "456", // Order ID
                "affiliation": "Your Store Name",
                "revenue": "59.99",
                "tax": "5.00",
                "shipping": "5.00"
            },
            "products": [
                {
                    "id": "123",
                    "name": "Sample Product",
                    "price": "49.99",
                    "quantity": 1
                }
                // Other purchased items...
            ]
        }
    }
}
```

## Requirements

- WordPress 4.7+ or higher.
- WooCommerce 5.1.0 or higher.
- PHP 7.2+

## Changelog

For a detailed list of changes and updates made to this project, please refer to our [Changelog](./CHANGELOG.md).

## Additional Notes

Ensure Google Tag Manager is installed and configured on your site. You may need to add Google Analytics tags in GTM to fully utilize the events pushed to dataLayer.

## Support The Project

If you find this script helpful and would like to support its development and maintenance, please consider the following options:

- **_Star the repository_**: If you're using this script from a GitHub repository, please give the project a star on GitHub. This helps others discover the project and shows your appreciation for the work done.

- **_Share your feedback_**: Your feedback, suggestions, and feature requests are invaluable to the project's growth. Please open issues on the GitHub repository or contact the author directly to provide your input.

- **_Contribute_**: You can contribute to the project by submitting pull requests with bug fixes, improvements, or new features. Make sure to follow the project's coding style and guidelines when making changes.

- **_Spread the word_**: Share the project with your friends, colleagues, and social media networks to help others benefit from the script as well.

- **_Donate_**: Show your appreciation with a small donation. Your support will help me maintain and enhance the script. Every little bit helps, and your donation will make a big difference in my ability to keep this project alive and thriving.

Your support is greatly appreciated and will help ensure all of the projects continued development and improvement. Thank you for being a part of the community!
You can send me money on Revolut by following this link: https://revolut.me/mnestorovv

---

## License

This project is released under the [GPL-2.0+ License](http://www.gnu.org/licenses/gpl-2.0.txt).

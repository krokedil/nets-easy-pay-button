# Nets Easy Pay Button

**Requires [WooCommerce](https://wordpress.org/plugins/woocommerce/) together with either [Nets Easy for WooCommerce](https://wordpress.org/plugins/dibs-easy-for-woocommerce) to be used**

---
### Installation

To install this plugin you first need to have Nets Easy for WooCommerce installed. You can find the links to this above. You install this plugin just like any other WordPress plugin:

1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the ‘Plugins’ menu in WordPress Administration.

---
### Configuration


---
### Display button via shortcode
The button can be displayed on a regular WordPress page via a easy_payment_button shortcode. The parameter needed to tie the button to a specific product is wc-product-id. The shortcode is then added in the following way:

```[easy_payment_button wc-product-id="922092" button-label="Buy now"]```

_Replace 922092 with the actual product ID you want to tie the button to._

---
### Display button on WooCommerce product page
This is a feature that is in development at the moment.



---
### Limitations
* This is a plugin that is currently in alpha mode. Do not use this in a live environment. It is only recommended for testing.
* Nets Easy Pay Button is currently only available to customers from the same country as the store base country. We are working on multi country support.
* Postal number based shipping cost is currently not supported in the plugin.

---
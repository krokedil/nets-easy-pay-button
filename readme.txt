=== Nets Easy Pay Button ===
Contributors: krokedil
Tags: woocommerce, krokedil, ecommerce, e-commerce, nets
Requires at least: 5.0.0
Tested up to: 5.3.2
Requires PHP: 7.0
WC requires at least: 3.5.0
WC tested up to: 3.9.2
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== DESCRIPTION ==

Requires [WooCommerce](https://wordpress.org/plugins/woocommerce/) together with either [Nets Easy for WooCommerce](https://wordpress.org/plugins/dibs-easy-for-woocommerce) to be used.

== Installation ==

To install this plugin you first need to have Nets Easy for WooCommerce installed. You can find the links to this above. You install this plugin just like any other WordPress plugin:

1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the ‘Plugins’ menu in WordPress Administration.


== Configuration ==

### Display button via shortcode
The button can be displayed on a regular WordPress page via a easy_payment_button shortcode. The parameter needed to tie the button to a specific product is wc-product-id. The shortcode is then added in the following way:

```[easy_payment_button wc-product-id="922092" button-label="Buy now"]```

_Replace 922092 with the actual product ID you want to tie the button to._


### Display button on WooCommerce product page
Navigate to the Nets Easy settings. At the bottom of the page there is a new section called "Nets Easy Pay Button Settings". To display the pay button on product pages make sure to tick the checkbox "Button on product page". 

At the moment this feature is not available for variable products.



### Limitations
* This is a plugin that is currently in beta mode. It is primary recommended for testing.
* Nets Easy Pay Button is currently only available to customers from the same country as the store base country. We are working on multi country support.
* Postal number based shipping cost is currently not supported in the plugin.


== CHANGELOG ==

= 2020.03.05        - version 0.2.0 =
* Enhancement       - Added links to documentation.

= 2020.03.05        - version 0.1.0 =
* Initial release.

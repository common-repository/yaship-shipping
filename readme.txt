=== Yaship Shipping ===
Contributors: newhouse77
Tags: shipping, yaship, woocommerce shipping, shipping rates, print label, return shipment, woocommerce
Requires at least: 4.3.3
Tested up to: 4.7
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

YaShip Shipping Plugin for WooCommerce. Displays Live Shipping Rates based on the Shipping Address.

== Description ==

The plugin adds yaship shipping service in woo commerce. It gives real time shipping rates and generate shipping label for shipment. It also allow to print the shipping label. Return shipment fuctionality is also present which will generate return shipment label. This plugin allow to print multiple labels as bulk print to wordpress admin. This plugin uses custom apis of yaship, so user first needs to register to yaship through plugin.

== Installation ==

1. Upload 'yaship' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Yaship tab will be added with other three sub-tabs.
4. Admin can register to Yaship through Yaship->Register.There will be two modes namely - test mode and live mode. One can set the test mode to check the plugin functionality and when done he can use the plugin for live transactions by setting it to live mode.
6. Check the 'Yaship' tab in woo commerce->settings->shipping->Yaship.
7. Admin can set the API credentials and other configuration details.
8. Now Yaship shipping service is become available for end customer. 

== Frequently Asked Questions ==

= How to display label using yaship? =

Once the order is generated at site, the button of 'shipment label' is become available at order page in admin dashboard.

= How to use bulk print functionality? =

In admin dashboard at order summery page the list of order will be displayed.
admin can select some of them and have to select bulk print option from Bulk Action.

= How to use return functionality? =
Check Return Report sub-tab in Yaship tab. Once order is delivered, that order will be seen in the return reports. Click Return, then a pop up box will appear listing order details. Select product which you want to return and set its quantity accordingly and click on submit.

== Screenshots ==

1. Quick Quote

2. Free Shipping Settings

3. Packaging Types

== Upgrade Notice ==

= 1.0 =
* Just released.

== Arbitrary section ==

= Quick Quote =
This pop up window will appears when user will install the yaship module first time. It gives the general idea about how yaship will work, i.e. it will get two destinations(from and to postal codes) with package dimensions as an input and displays real shipment rates of this package for entered destination.

= Print Label Option =
After order is placed, shipment is placed and label or labels will be generated. The plugin user will be able to print label or labels from order page. Also he can print label or labels of more than one order at once using bulk print

= Return functionality =
If for any reasons order needs to be return back then the package can be returned from return shipment sub-tab by selecting approprioate order and its products. A return label will be generated when return shipment is is requested.

== Changelog ==

= 1.0 =
* First release

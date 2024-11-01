=== WooCommerce Email Order Digest ===
Contributors: nwells
Donate link: https://www.apexdigital.co.nz/contact.php
Tags: woocommerce, orders, order emails, daily order email, daily digest
Requires at least: 3.0.1
Requires PHP: 5.5
Tested up to: 6.1.1
Stable tag: 1.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sends out a daily email digest of all orders made the previous day in WooCommerce

== Description ==

Sends out a daily email digest of all orders made the previous day in WooCommerce. This provides a great summary of yesterday's activities as well as helping with quickly checking orders against any payments received overnight without needing to log in. It can also be useful as a packing slip for a warehouse and what stock goes with which order.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-email-orders` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Set the email recipient under WooCommerce->Settings and select the `Email` tab

== Frequently Asked Questions ==

= What time does the email go out =

By default, the emails are set in the WordPress cron scheduler at 1am. This can be adjusted using a cron interface plugin like `WP Control`

= I didn't receive any email overnight =

The email digest currently only gets sent if an order has been made the previous day.

= I didn't receive any email at the scheduled time =

If you are relying on the built-in WordPress scheduler then this only gets triggered by site visitors. Therefore, if no one visits your site at the exact time of your intended schedule then the email won't trigger exactly when you want it. A preferred option, if your hosting supports, is to set up a cron task at server level to trigger the `/wp-cron.php` URL.

== Screenshots ==

1. Example layout for the order email that is sent out

== Changelog ==

= 1.2.2 =
* Allow all orders for a day to be sent rather than the default 10

= 1.2 =
* Added a date selector to test the email digest out for a specific date

= 1.1 =
* Added support for internationalization
* Added support for multiple recipients - comma separated

= 1.0.3 =
* Added sub-total, freight, discounts, and total to each order
* Added grand total for all orders for the day

= 1.0.2 =
* Bug fixes

= 1.0.1 =
* Added new introduction text as an option
* Removed cancelled orders from the email
* Adjusted timestamp to use the correct timezone set in WP

= 1.0.0 =
* Initial release

== Upgrade Notice ==
None

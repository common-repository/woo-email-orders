<?php

namespace WooCommerceEmailOrders\Controllers;

class cronController {

    /**
     * Initiate admin actions for updating settings in WooCommerce
     *
     * @return void
     */
    public function init() {
        add_action( 'woocommerce_settings_tabs_email', [ $this, 'settingsTab' ], 50 );
        add_action( 'woocommerce_update_options_email', [ $this, 'updateSettings' ], 50 );
    }

    /**
     * Get an array of available settings for the plugin
     *
     * @return array
     */
    public function getSettings() {
        $settings = [
            'section_title'           => [
                'name' => __( 'Email Order Digest', 'woocommerce-email-orders' ),
                'type' => 'title',
                'desc' => '',
                'id'   => 'wc_settings_email_digest_section_title'
            ],
            'email_digest_recipient'  => [
                'name'     => __( 'Recipient(s)', 'woocommerce-email-orders' ),
                'type'     => 'text',
                'desc'     => __( 'Email recipient(s) to receive the daily digest email - comma separate to send to multiple contacts', 'woocommerce-email-orders' ),
                'desc_tip' => true,
                'id'       => 'wc_settings_email_digest_recipient',
                'css'      => 'min-width:300px;',
                'default'  => get_option( 'admin_email' ),
            ],
            'email_digest_intro_text' => [
                'name'     => __( 'Introduction Text', 'woocommerce-email-orders' ),
                'type'     => 'text',
                'desc'     => __( 'Any text to display first above the orders i.e. attn: John Smith', 'woocommerce-email-orders' ),
                'desc_tip' => true,
                'id'       => 'wc_settings_email_digest_intro_text',
                'css'      => 'min-width:300px;',
            ],
            'email_digest_test'       => [
                'name'     => __( 'Test', 'woocommerce-email-orders' ),
                'type'     => 'date',
                'desc'     => __( 'Send out a test email - pick a date to base the orders on', 'woocommerce-email-orders' ),
                'desc_tip' => true,
                'id'       => 'wc_settings_email_digest_test',
            ],
            'section_end'             => [
                'type' => 'sectionend',
                'id'   => 'wc_settings_email_digest_section_end'
            ]
        ];

        return $settings;
    }

    /**
     * Updates settings in the database
     *
     * @return void
     */
    public function updateSettings() {
        $settings = $this->getSettings();
        unset( $settings['email_digest_test'] );
        woocommerce_update_options( $settings );
        if ( ! empty( $_POST['wc_settings_email_digest_test'] ) ) {
            $testDate = date( 'Y-m-d', strtotime( $_POST['wc_settings_email_digest_test'] ) );
            if ( count( $this->getOrders( $testDate ) ) > 0 ) {
                $hasOrders = true;
                $this->emailOrderDigest( $testDate );
            } else {
                $hasOrders = false;
            }
            add_action('admin_notices', function() use ($hasOrders, $testDate) {
                echo '<div class="updated notice"><p>';
                if ($hasOrders) {
                    echo _e( 'An email digest has been sent to the nominated recipients', 'woocommerce-email-orders' );
                } else {
                    echo _e( 'No orders were found on <b>' . $testDate . '</b> so no emails have been sent', 'woocommerce-email-orders' );
                }
                echo '</p></div>';
            });
        }
    }

    /**
     * Adds new plugin settings to the `Email` tab on the WooCommerce settings page
     *
     * @return void
     */
    public function settingsTab() {
        woocommerce_admin_fields( $this->getSettings() );
    }

    /**
     * Function is triggered by cron to send an email out for orders places yesterday
     *
     * return void
     */
    public function cron() {
        $yesterday = date( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) );
        $this->emailOrderDigest( $yesterday );
    }

    /**
     * Adjusts the FROM email address in the mail headers
     *
     * @param string $mail_from_email Current email address to send emails from
     *
     * @return string
     */
    public function wp_mail_from( $mail_from_email ) {
        $new_mail_from_email = get_option( 'woocommerce_email_from_address' );
        if ( $new_mail_from_email ) {
            $mail_from_email = $new_mail_from_email;
        }

        return $mail_from_email;
    }

    /**
     * Adjusts the FROM name in the mail headers
     *
     * @param string $mail_from_name Current from name to send emails out as
     *
     * @return string
     */
    public function wp_mail_from_name( $mail_from_name ) {
        $new_mail_from_name = get_option( 'woocommerce_email_from_name' );
        if ( $new_mail_from_name ) {
            $mail_from_name = $new_mail_from_name;
        }

        return html_entity_decode( $mail_from_name );
    }

    /**
     * Change the content type for the email to be HTML
     *
     * @return string
     */
    public function setContentType() {
        return 'text/html';
    }

    /**
     * Get orders made on a specific date
     *
     * @param $date string Date to query orders from
     */
    private function getOrders( $date ) {
        $orderStatus = wc_get_order_statuses();
        unset( $orderStatus['wc-cancelled'] );
        $args = array(
            'date_created'   => $date,
            'order'          => 'ASC',
            'status'         => array_keys( $orderStatus ),
            "posts_per_page" => -1,
        );

        return wc_get_orders( $args );
    }

    /**
     * Sends out emails based on the date supplied
     *
     * @param $date string Date to query orders from
     */
    private function emailOrderDigest( $date ) {
        $orders    = $this->getOrders( $date );
        $mail_body = '<html>
<head>
<style>
body {
    font-family: Arial, Helvetica;
}
td, th {
font-size: 12px;
}
</style>
</head>
<body style="margin: 0;background: ' . get_option( 'woocommerce_email_background_color' ) . ';">
<br />
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
    <td align="center">
    <table border="0" cellpadding="0" cellspacing="0" width="600">
    <tr>
        <td>
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: ' . get_option( 'woocommerce_email_base_color' ) . ';">
        <tr>
            <td style="padding: 36px 48px; display: block;">
                <h1 style="color: #ffffff; font-size: 30px; font-weight: 300; line-height: 150%; margin: 0; text-align: center;">' . __( 'Orders received', 'woocommerce-email-orders' ) . '<br />' . date( 'l j F Y', strtotime( $date ) ) . '</h1>
            </td>
        </tr>
        </table>
        </td>
    </tr>
    <tr>
        <td style="padding: 0 10px 10px 10px; background: #fff; border: 1px solid #cecece;"">';
        if ( count( $orders ) ) {
            if ( $introText = $email_to = get_option( 'wc_settings_email_digest_intro_text' ) ) {
                $mail_body .= '<br />' . $introText . '<br /><br />';
            }
            $totalOrders = 0;
            foreach ( $orders as $order ) {
                $totalOrders += $order->get_total();
            }
            $mail_body .= '
        <h3>' . __( 'Total orders for the day', 'woocommerce-email-orders' ) . ': ' . wc_price( $totalOrders ) . '</h3>
        <table width="100%" cellspacing="0" cellpadding="5">
        <tr>
            <td width="25%"></td>
            <td width="25%"></td>
            <td width="45%"></td>
            <td width="5%"></td>
        </tr>';
            foreach ( $orders as $order ) {
                $billingStates  = WC()->countries->get_states( $order->get_billing_country() );
                $shippingStates = WC()->countries->get_states( $order->get_shipping_country() );
                $mail_body      .= '<tr style="background-color: #eee">
            <td colspan="3" style="border-top: 1px solid #cecece;border-bottom: 1px solid #cecece;border-left: 1px solid #cecece;"><b>' . __( 'Order number', 'woocommerce-email-orders' ) . ': #' . $order->get_id() . ' [<em>' . wc_get_order_status_name( $order->get_status() ) . '</em>]</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $order->get_date_created()->format( 'd, F Y, g:ia' ) . '<br />
                <em>' . __( 'Shipping method', 'woocommerce-email-orders' ) . ': ' . $order->get_shipping_method() . ' | ' . __( 'Payment method', 'woocommerce-email-orders' ) . ': ' . $order->get_payment_method_title() . '</em></td>
            <td align="right" style="border-top: 1px solid #cecece;border-bottom: 1px solid #cecece;border-right: 1px solid #cecece;"><a href="' . get_edit_post_link( $order->get_id() ) . '" target="_blank">View</a></td>
        </tr>
        <tr>
            <td valign="top">
                <b>' . __( 'Billing', 'woocommerce-email-orders' ) . '</b><br />
                ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '<br />
                ' . $order->get_billing_address_1() . '<br />
                ' . ( $order->get_billing_address_2() ? $order->get_billing_address_2() . '<br />' : '' ) . '
                ' . $order->get_billing_city() . ', ' . $order->get_billing_postcode() . '<br />
                ' . ( isset( $billingStates[ $order->get_billing_state() ] ) ? $billingStates[ $order->get_billing_state() ] . '<br />' : '' ) . '
                ' . $order->get_billing_phone() . '<br />
                ' . $order->get_billing_email() . '<br />
            </td>
            <td valign="top">
                <b>' . __( 'Shipping', 'woocommerce-email-orders' ) . '</b><br />
                ' . $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() . '<br />
                ' . $order->get_shipping_address_1() . '<br />
                ' . ( $order->get_shipping_address_2() ? $order->get_shipping_address_2() . '<br />' : '' ) . '
                ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode() . '<br />
                ' . ( isset( $shippingStates[ $order->get_shipping_state() ] ) ? $shippingStates[ $order->get_shipping_state() ] . '<br />' : '' ) . '
            </td>
            <td valign="top" colspan="2">
                <table cellpadding="5" cellspacing="0" style="border: 1px solid #cecece" width="100%">
                    <thead>
                    <tr>
                        <th style="border-bottom: 1px solid #cecece; text-align: left;">#</th>
                        <th style="border-bottom: 1px solid #cecece; text-align: left;">' . __( 'SKU', 'woocommerce-email-orders' ) . '</th>
                        <th style="border-bottom: 1px solid #cecece; text-align: left;">' . __( 'Product', 'woocommerce-email-orders' ) . '</th>
                        <th style="border-bottom: 1px solid #cecece; text-align: left;">' . __( 'Price', 'woocommerce-email-orders' ) . '</th>
                    </tr>
                    </thead>
                    <tbody>';
                foreach ( $order->get_items() as $lineItem ) {
                    $itemData  = $lineItem->get_data();
                    $product   = wc_get_product( $itemData['product_id'] );
                    $mail_body .= '<tr>
                        <td valign="top">' . $lineItem->get_quantity() . 'x</td>
                        <td valign="top">' . ( $product !== false ? $product->get_sku() : '&nbsp;' ) . '</td>
                        <td valign="top">' . $lineItem->get_name() . '</td>
                        <td valign="top" style="text-align: right">' . wc_price( $itemData['total'] ) . '</td>
                    </tr>';
                }
                $mail_body .= '<tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right"><strong>' . __( 'Sub-Total', 'woocommerce-email-orders' ) . '</strong></td>    
                        <td colspan="3" style="text-align: right">' . wc_price( $order->get_subtotal() ) . '</td>    
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right"><strong>' . __( 'Freight', 'woocommerce-email-orders' ) . '</strong></td>    
                        <td colspan="3" style="text-align: right">' . wc_price( $order->get_shipping_total() ) . '</td>    
                    </tr>';
                if ( $order->get_discount_total() ) {
                    $mail_body .= '<tr>
                        <td colspan="3" style="text-align: right"><strong>' . __( 'Discount', 'woocommerce-email-orders' ) . '</strong></td>    
                        <td colspan="3" style="text-align: right">' . wc_price( $order->get_discount_total() ) . '</td>    
                    </tr>';
                }
                $mail_body .= '<tr>
                        <td colspan="3" style="text-align: right"><strong>' . __( 'Tax', 'woocommerce-email-orders' ) . '</strong></td>    
                        <td colspan="3" style="text-align: right">' . wc_price( $order->get_total_tax() ) . '</td>    
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right"><strong>' . __( 'Total', 'woocommerce-email-orders' ) . '</strong></td>    
                        <td colspan="3" style="text-align: right">' . wc_price( $order->get_total() ) . '</td>    
                    </tr>
                    </tfoot>
                    </tbody>
                </table>
             </td>
         </tr>
         <tr>
            <td colspan="4"><hr style="border: 0; border-top: 1px solid #cecece;" /></td>
        </tr>';
            }
            $mail_body .= '</table>';
        } else {
            // TODO: Add option for always providing an email
        }
        $mail_body .= '</table>
        </td>
    </tr>
    <tr>
        <td style="text-align: center"><br />' . get_bloginfo() . ' – ' . __( 'Powered by WooCommerce', 'woocommerce-email-orders' ) . '</td>
    </tr>
    </table>
    </td>
</tr>
</table>
<br />
</body>
</html>';
        if ( count( $orders ) ) {
            add_filter( 'wp_mail_content_type', [ $this, 'setContentType' ] );
            add_filter( 'wp_mail_from', [ $this, 'wp_mail_from' ], 100 );
            add_filter( 'wp_mail_from_name', [ $this, 'wp_mail_from_name' ], 100 );
            $email_to = get_option( 'wc_settings_email_digest_recipient' );
            if ( ! $email_to ) {
                $email_to = get_bloginfo( 'admin_email' );
            }
            $email_to = explode( ',', $email_to );
            $subject  = __( 'Orders received', 'woocommerce-email-orders' ) . ' ' . date( 'l j F Y', strtotime( $date ) );
            foreach ( $email_to as $email ) {
                wp_mail( $email, $subject, $mail_body );
            }
            remove_filter( 'wp_mail_content_type', [ $this, 'setContentType' ] );
            remove_filter( 'wp_mail_from', [ $this, 'wp_mail_from' ] );
            remove_filter( 'wp_mail_from_name', [ $this, 'wp_mail_from_name' ] );
        } else {
            error_log( 'no orders' );
        }
    }
}

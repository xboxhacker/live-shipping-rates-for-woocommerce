<?php
/*
Plugin Name: Live Shipping Rates for WooCommerce
Description: Integrates UPS and USPS live shipping rates into WooCommerce with OAuth 2.0 authentication, including GUI debugging and live rate testing.
Version: 1.1.19
Author: William Hare & Grok3.0
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Check if WooCommerce is active
function lsrwc_is_woocommerce_active() {
    return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
}

// Initialize the plugin
function lsrwc_init() {
    if ( lsrwc_is_woocommerce_active() ) {
        // Register settings
        add_action( 'admin_menu', 'lsrwc_add_admin_menu' );
        add_action( 'admin_init', 'lsrwc_register_settings' );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', 'lsrwc_enqueue_scripts' );

        // AJAX handlers
        add_action( 'wp_ajax_lsrwc_test_rates', 'lsrwc_test_rates_ajax' );
        add_action( 'wp_ajax_lsrwc_clear_debug', 'lsrwc_clear_debug_ajax' );

        // Add shipping methods
        add_filter( 'woocommerce_shipping_methods', 'lsrwc_add_shipping_methods' );

        // Filter shipping methods based on cart contents
        add_filter( 'woocommerce_package_rates', 'lsrwc_filter_shipping_methods', 10, 2 );

        // Honor WooCommerce free-shipping coupons by zeroing out shipping rates
        add_filter( 'woocommerce_package_rates', 'lsrwc_apply_free_shipping_coupon', 99, 2 );
    }
}
add_action( 'plugins_loaded', 'lsrwc_init' );

// Enqueue scripts and styles
function lsrwc_enqueue_scripts( $hook ) {
    if ( $hook !== 'toplevel_page_live-shipping-rates' ) {
        return;
    }
    wp_enqueue_script( 'lsrwc-admin', plugin_dir_url( __FILE__ ) . 'admin.js', array( 'jquery' ), '1.1.19', true );
    wp_localize_script( 'lsrwc-admin', 'lsrwc', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'lsrwc_nonce' ),
    ) );
    wp_enqueue_style( 'lsrwc-admin', plugin_dir_url( __FILE__ ) . 'admin.css', array(), '1.1.19' );
}

// Admin menu
function lsrwc_add_admin_menu() {
    add_menu_page(
        'Live Shipping Rates',
        'Live Shipping Rates',
        'manage_options',
        'live-shipping-rates',
        'lsrwc_settings_page',
        'dashicons-admin-site',
        56
    );
}

// Register settings
function lsrwc_register_settings() {
    register_setting( 'lsrwc_settings_group', 'lsrwc_settings' );

    add_settings_section( 'lsrwc_main_section', 'Main Settings', '__return_false', 'live-shipping-rates' );

    add_settings_field( 'ups_client_id', 'UPS Client ID', 'lsrwc_ups_client_id_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'ups_client_secret', 'UPS Client Secret', 'lsrwc_ups_client_secret_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'ups_account_number', 'UPS Account Number', 'lsrwc_ups_account_number_field', 'live-shipping-rates', 'lsrwc_main_section' );

    add_settings_field( 'usps_consumer_key', 'USPS Consumer Key', 'lsrwc_usps_consumer_key_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'usps_consumer_secret', 'USPS Consumer Secret', 'lsrwc_usps_consumer_secret_field', 'live-shipping-rates', 'lsrwc_main_section' );

    add_settings_field( 'origin_zip', 'Origin ZIP Code', 'lsrwc_origin_zip_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'origin_city', 'Origin City', 'lsrwc_origin_city_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'origin_state', 'Origin State', 'lsrwc_origin_state_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'origin_address1', 'Origin Address Line 1', 'lsrwc_origin_address1_field', 'live-shipping-rates', 'lsrwc_main_section' );

    add_settings_field( 'ups_percentage', 'UPS Percentage Increase (%)', 'lsrwc_ups_percentage_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'usps_percentage', 'USPS Percentage Increase (%)', 'lsrwc_usps_percentage_field', 'live-shipping-rates', 'lsrwc_main_section' );

    add_settings_field( 'ups_shipping_class_slug', 'UPS Shipping Class Slug', 'lsrwc_ups_shipping_class_slug_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'usps_shipping_class_slug', 'USPS Shipping Class Slug', 'lsrwc_usps_shipping_class_slug_field', 'live-shipping-rates', 'lsrwc_main_section' );

    add_settings_field( 'debug_enabled', 'Enable Extensive Debugging', 'lsrwc_debug_enabled_field', 'live-shipping-rates', 'lsrwc_main_section' );
}

// Settings fields renderers
function lsrwc_ups_client_id_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['ups_client_id'] ?? '';
    echo "<input type='text' name='lsrwc_settings[ups_client_id]' value='" . esc_attr( $value ) . "' class='regular-text'>";
}
function lsrwc_ups_client_secret_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['ups_client_secret'] ?? '';
    echo "<input type='password' name='lsrwc_settings[ups_client_secret]' value='" . esc_attr( $value ) . "' class='regular-text'>";
}
function lsrwc_ups_account_number_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['ups_account_number'] ?? '';
    echo "<input type='text' name='lsrwc_settings[ups_account_number]' value='" . esc_attr( $value ) . "' class='regular-text'>";
}

function lsrwc_usps_consumer_key_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['usps_consumer_key'] ?? '';
    echo "<input type='text' name='lsrwc_settings[usps_consumer_key]' value='" . esc_attr( $value ) . "' class='regular-text'>";
}
function lsrwc_usps_consumer_secret_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['usps_consumer_secret'] ?? '';
    echo "<input type='text' name='lsrwc_settings[usps_consumer_secret]' value='" . esc_attr( $value ) . "' class='regular-text'>";
}

function lsrwc_origin_zip_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['origin_zip'] ?? '';
    echo "<input type='text' name='lsrwc_settings[origin_zip]' value='" . esc_attr( $value ) . "' class='regular-text'>";
}
function lsrwc_origin_city_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['origin_city'] ?? '';
    echo "<input type='text' name='lsrwc_settings[origin_city]' value='" . esc_attr( $value ) . "' class='regular-text'>";
}
function lsrwc_origin_state_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['origin_state'] ?? '';
    echo "<input type='text' name='lsrwc_settings[origin_state]' value='" . esc_attr( $value ) . "' class='regular-text'>";
}
function lsrwc_origin_address1_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['origin_address1'] ?? '';
    echo "<input type='text' name='lsrwc_settings[origin_address1]' value='" . esc_attr( $value ) . "' class='regular-text'>";
}

function lsrwc_ups_percentage_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = isset( $settings['ups_percentage'] ) ? (float) $settings['ups_percentage'] : 0;
    echo "<input type='number' step='0.01' name='lsrwc_settings[ups_percentage]' value='" . esc_attr( $value ) . "' class='small-text'> %";
}
function lsrwc_usps_percentage_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = isset( $settings['usps_percentage'] ) ? (float) $settings['usps_percentage'] : 0;
    echo "<input type='number' step='0.01' name='lsrwc_settings[usps_percentage]' value='" . esc_attr( $value ) . "' class='small-text'> %";
}
function lsrwc_ups_shipping_class_slug_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['ups_shipping_class_slug'] ?? '';
    echo "<input type='text' name='lsrwc_settings[ups_shipping_class_slug]' value='" . esc_attr( $value ) . "' class='regular-text' placeholder='ups-shipping'>";
}
function lsrwc_usps_shipping_class_slug_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['usps_shipping_class_slug'] ?? '';
    echo "<input type='text' name='lsrwc_settings[usps_shipping_class_slug]' value='" . esc_attr( $value ) . "' class='regular-text' placeholder='usps-shipping'>";
}
function lsrwc_debug_enabled_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $enabled = ! empty( $settings['debug_enabled'] );
    echo "<label><input type='checkbox' name='lsrwc_settings[debug_enabled]' value='1' " . checked( $enabled, true, false ) . "> Enable extensive debugging</label>";
}

// Settings page
function lsrwc_settings_page() {
    ?>
    <div class="wrap">
        <h1>Live Shipping Rates for WooCommerce</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'lsrwc_settings_group' );
            do_settings_sections( 'live-shipping-rates' );
            submit_button();
            ?>
        </form>
        <hr>
        <h2>Test Live Rates</h2>
        <div id="lsrwc-test-rates-ui">
            <p>Enter destination and package details to test UPS/USPS live rates.</p>
            <table class="form-table">
                <tr><th>City</th><td><input type="text" id="lsrwc_test_city" class="regular-text" value=""></td></tr>
                <tr><th>State</th><td><input type="text" id="lsrwc_test_state" class="regular-text" value=""></td></tr>
                <tr><th>ZIP</th><td><input type="text" id="lsrwc_test_zip" class="regular-text" value=""></td></tr>
                <tr><th>Country</th><td><input type="text" id="lsrwc_test_country" class="regular-text" value="US"></td></tr>
                <tr><th>Weight (lbs)</th><td><input type="number" step="0.01" id="lsrwc_test_weight" class="small-text" value="1"></td></tr>
                <tr><th>Length (in)</th><td><input type="number" step="0.01" id="lsrwc_test_length" class="small-text" value="12"></td></tr>
                <tr><th>Width (in)</th><td><input type="number" step="0.01" id="lsrwc_test_width" class="small-text" value="12"></td></tr>
                <tr><th>Height (in)</th><td><input type="number" step="0.01" id="lsrwc_test_height" class="small-text" value="12"></td></tr>
            </table>
            <p><button class="button button-primary" id="lsrwc_get_rates">Get Rates</button> <button class="button" id="lsrwc_clear_debug">Clear Debug</button></p>
            <pre id="lsrwc_rates_output" style="max-height:400px; overflow:auto;"></pre>
        </div>
        <hr>
        <h2>Debug Information</h2>
        <div>
            <?php
            $debug = get_option( 'lsrwc_debug_log', '' );
            echo '<pre style="max-height:400px; overflow:auto;">' . esc_html( $debug ) . '</pre>';
            ?>
        </div>
    </div>
    <?php
}

// Debug logging helper
function lsrwc_log( $message ) {
    $settings = get_option( 'lsrwc_settings', array() );
    if ( empty( $settings['debug_enabled'] ) ) {
        return;
    }
    $log = get_option( 'lsrwc_debug_log', '' );
    if ( is_array( $message ) || is_object( $message ) ) {
        $message = print_r( $message, true );
    }
    $log .= '[' . current_time( 'mysql' ) . '] ' . $message . "\n";
    update_option( 'lsrwc_debug_log', $log );
}

// AJAX: Clear debug
function lsrwc_clear_debug_ajax() {
    check_ajax_referer( 'lsrwc_nonce', 'nonce' );
    update_option( 'lsrwc_debug_log', '' );
    wp_send_json_success( array( 'message' => 'Debug log cleared.' ) );
}

// AJAX: Test rates (simplified example that calls the same internals as shipping methods)
function lsrwc_test_rates_ajax() {
    check_ajax_referer( 'lsrwc_nonce', 'nonce' );

    $city    = sanitize_text_field( $_POST['city'] ?? '' );
    $state   = sanitize_text_field( $_POST['state'] ?? '' );
    $zip     = sanitize_text_field( $_POST['zip'] ?? '' );
    $country = sanitize_text_field( $_POST['country'] ?? 'US' );
    $weight  = (float) ($_POST['weight'] ?? 0 );
    $length  = (float) ($_POST['length'] ?? 0 );
    $width   = (float) ($_POST['width'] ?? 0 );
    $height  = (float) ($_POST['height'] ?? 0 );

    // Build a mock package similar to WooCommerce structure
    $package = array(
        'destination' => array(
            'city'     => $city,
            'state'    => $state,
            'postcode' => $zip,
            'country'  => $country,
        ),
        'contents' => array(), // not used in this tester
        'contents_cost' => 0,
        'applied_coupons' => array(),
        'cart_subtotal' => 0,
    );

    $results = array(
        'ups'  => lsrwc_get_ups_ground_rate( $package, $weight, $length, $width, $height ),
        'usps' => lsrwc_get_usps_ground_advantage_rate( $package, $weight, $length, $width, $height ),
    );

    lsrwc_log( array( 'test_rates_request' => compact( 'city','state','zip','country','weight','length','width','height' ), 'test_rates_response' => $results ) );

    wp_send_json_success( $results );
}

// Add shipping methods
function lsrwc_add_shipping_methods( $methods ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-lsrwc-shipping-method.php';
    $methods['lsrwc_ups']                = 'LSRWC_UPS_Shipping_Method';
    $methods['lsrwc_usps']               = 'LSRWC_USPS_Shipping_Method';
    $methods['lsrwc_ups_international']  = 'LSRWC_UPS_International_Shipping_Method';
    return $methods;
}

// Filter shipping methods based on cart contents and destination
function lsrwc_filter_shipping_methods( $rates, $package ) {
    // Guard: ensure expected structure
    if ( empty( $package ) || empty( $package['destination'] ) || empty( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
        return $rates;
    }

    $settings  = get_option( 'lsrwc_settings', array() );
    $ups_slug  = $settings['ups_shipping_class_slug']  ?? '';
    $usps_slug = $settings['usps_shipping_class_slug'] ?? '';

    // Canada: only show UPS International
    $destination_country = $package['destination']['country'] ?? 'US';
    if ( $destination_country === 'CA' ) {
        $filtered_rates = array();
        foreach ( $rates as $rate_id => $rate ) {
            if ( strpos( $rate_id, 'lsrwc_ups_international' ) === 0 ) {
                $filtered_rates[ $rate_id ] = $rate;
            }
        }
        lsrwc_log( "Filtered rates for country $destination_country (Canada): " . print_r( array_keys( $filtered_rates ), true ) );
        return $filtered_rates;
    }

    // Non-Canada logic (e.g., US)
    $has_usps_slug = false;
    $has_ups_slug  = false;

    // Check each item in the cart for shipping classes
    foreach ( $package['contents'] as $item ) {
        if ( empty( $item['data'] ) || ! is_object( $item['data'] ) ) {
            continue;
        }
        $product        = $item['data'];
        $shipping_class = (string) $product->get_shipping_class();
        if ( $shipping_class === $ups_slug ) {
            $has_ups_slug = true;
        }
        if ( $shipping_class === $usps_slug ) {
            $has_usps_slug = true;
        }
        // Optimization: stop looping if both slugs are found
        if ( $has_ups_slug && $has_usps_slug ) {
            break;
        }
    }

    $filtered_rates = array();

    // If any item requires UPS, show only UPS Ground (domestic) and hide USPS
    if ( $has_ups_slug && ! $has_usps_slug ) {
        foreach ( $rates as $rate_id => $rate ) {
            if ( strpos( $rate_id, 'lsrwc_ups' ) === 0 && strpos( $rate_id, 'lsrwc_ups_international' ) !== 0 ) {
                $filtered_rates[ $rate_id ] = $rate;
            }
        }
    } else {
        // Otherwise, show both UPS Ground and USPS Ground Advantage if present
        foreach ( $rates as $rate_id => $rate ) {
            $is_ups_ground   = ( strpos( $rate_id, 'lsrwc_ups' ) === 0 && strpos( $rate_id, 'lsrwc_ups_international' ) !== 0 );
            $is_usps_ground  = ( strpos( $rate_id, 'lsrwc_usps' ) === 0 );
            if ( $is_ups_ground || $is_usps_ground ) {
                $filtered_rates[ $rate_id ] = $rate;
            }
        }
    }

    lsrwc_log( "Filtered rates for country $destination_country: Has USPS: " . ( $has_usps_slug ? 'Yes' : 'No' ) . ", Has UPS: " . ( $has_ups_slug ? 'Yes' : 'No' ) . ", Rates: " . print_r( array_keys( $filtered_rates ), true ) );
    return ! empty( $filtered_rates ) ? $filtered_rates : $rates;
}

// Apply free-shipping coupon: force all offered rates to $0.00 and zero taxes
function lsrwc_apply_free_shipping_coupon( $rates, $package ) {
    if ( function_exists( 'WC' ) && WC()->cart ) {
        foreach ( WC()->cart->get_coupons() as $coupon ) {
            if ( is_a( $coupon, 'WC_Coupon' ) && method_exists( $coupon, 'get_free_shipping' ) && $coupon->get_free_shipping() ) {
                foreach ( $rates as $rate_id => $rate ) {
                    if ( $rate instanceof WC_Shipping_Rate ) {
                        if ( method_exists( $rate, 'set_cost' ) ) {
                            $rate->set_cost( 0 );
                        } else {
                            $rates[ $rate_id ]->cost = 0;
                        }

                        $existing_taxes = is_callable( [ $rate, 'get_taxes' ] ) ? (array) $rate->get_taxes() : (array) ( $rates[ $rate_id ]->taxes ?? [] );
                        $zero_taxes     = array_fill( 0, count( $existing_taxes ), 0 );

                        if ( method_exists( $rate, 'set_taxes' ) ) {
                            $rate->set_taxes( $zero_taxes );
                        } else {
                            $rates[ $rate_id ]->taxes = $zero_taxes;
                        }

                        if ( is_callable( [ $rate, 'get_label' ] ) && is_callable( [ $rate, 'set_label' ] ) ) {
                            $rate->set_label( sprintf( '%s (Free with coupon)', $rate->get_label() ) );
                        }
                    }
                }

                lsrwc_log( 'Applied free-shipping coupon; set all shipping rates to $0.00.' );
                return $rates;
            }
        }
    }
    return $rates;
}

/**
 * Helpers used by the test UI to compute rates directly.
 * The production shipping methods call their own rate APIs; these functions mirror the logic.
 * Note: In production, the concrete shipping classes in includes/class-lsrwc-shipping-method.php are used.
 */
function lsrwc_get_ups_ground_rate( $package, $weight, $length, $width, $height ) {
    $settings = get_option( 'lsrwc_settings', array() );
    $city     = $package['destination']['city'] ?? '';
    $state    = $package['destination']['state'] ?? '';
    $zip      = $package['destination']['postcode'] ?? '';
    $country  = $package['destination']['country'] ?? 'US';

    if ( $weight <= 0 ) {
        return array( 'error' => 'Invalid weight.' );
    }

    // Build UPS Rate request payload (Ground)
    $payload = array(
        "RateRequest" => array(
            "Request" => array(
                "RequestOption" => "Rate",
                "TransactionReference" => array(
                    "CustomerContext"       => "Live Rate",
                    "TransactionIdentifier" => "TransactionIdentifier"
                )
            ),
            "Shipment" => array(
                "Shipper" => array(
                    "Name" => "Shipper Name",
                    "ShipperNumber" => $settings['ups_account_number'] ?? '',
                    "Address" => array(
                        "AddressLine" => array( $settings['origin_address1'] ?? '' ),
                        "City" => $settings['origin_city'] ?? '',
                        "StateProvinceCode" => $settings['origin_state'] ?? '',
                        "PostalCode" => $settings['origin_zip'] ?? '',
                        "CountryCode" => "US"
                    )
                ),
                "ShipTo" => array(
                    "Name" => "Recipient Name",
                    "Address" => array(
                        "AddressLine" => array( "" ),
                        "City" => $city,
                        "StateProvinceCode" => $state,
                        "PostalCode" => $zip,
                        "CountryCode" => $country
                    )
                ),
                "ShipFrom" => array(
                    "Name" => "Ship From Name",
                    "Address" => array(
                        "AddressLine" => array( $settings['origin_address1'] ?? '' ),
                        "City" => $settings['origin_city'] ?? '',
                        "StateProvinceCode" => $settings['origin_state'] ?? '',
                        "PostalCode" => $settings['origin_zip'] ?? '',
                        "CountryCode" => "US"
                    )
                ),
                "Service" => array(
                    "Code" => "03", // UPS Ground
                    "Description" => "Ground"
                ),
                "Package" => array(
                    "PackagingType" => array(
                        "Code" => "02", // Package
                        "Description" => "Package"
                    ),
                    "Dimensions" => array(
                        "UnitOfMeasurement" => array( "Code" => "IN" ),
                        "Length" => max( 1, round( $length ) ),
                        "Width"  => max( 1, round( $width ) ),
                        "Height" => max( 1, round( $height ) ),
                    ),
                    "PackageWeight" => array(
                        "UnitOfMeasurement" => array( "Code" => "LBS" ),
                        "Weight" => max( 0.1, round( $weight, 2 ) )
                    )
                )
            )
        )
    );

    // Here you would call UPS API (omitted in this UI helper). We log and return a placeholder structure.
    lsrwc_log( array( 'ups_rate_request' => $payload ) );

    return array(
        'service' => 'UPS Ground',
        'cost'    => 0, // Placeholder for UI; production methods calculate real cost
    );
}

function lsrwc_get_usps_ground_advantage_rate( $package, $weight, $length, $width, $height ) {
    $settings = get_option( 'lsrwc_settings', array() );
    $city     = $package['destination']['city'] ?? '';
    $state    = $package['destination']['state'] ?? '';
    $zip      = $package['destination']['postcode'] ?? '';

    if ( $weight <= 0 ) {
        return array( 'error' => 'Invalid weight.' );
    }

    // Build USPS request payload (simplified)
    $payload = array(
        'city'   => $city,
        'state'  => $state,
        'zip'    => $zip,
        'weight' => max( 0.1, round( $weight, 2 ) ),
        'dims'   => array(
            'l' => max( 1, round( $length ) ),
            'w' => max( 1, round( $width ) ),
            'h' => max( 1, round( $height ) ),
        ),
    );

    lsrwc_log( array( 'usps_rate_request' => $payload ) );

    return array(
        'service' => 'USPS Ground Advantage',
        'cost'    => 0, // Placeholder for UI; production methods calculate real cost
    );
}

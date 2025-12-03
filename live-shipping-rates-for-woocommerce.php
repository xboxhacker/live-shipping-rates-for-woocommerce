<?php
/*
Plugin Name: Live Shipping Rates for WooCommerce
Description: Integrates UPS and USPS live shipping rates into WooCommerce with OAuth 2.0 authentication, including GUI debugging and live rate testing.
Version: 1.1.20
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
    }
}
add_action( 'plugins_loaded', 'lsrwc_init' );

// Enqueue scripts and styles
function lsrwc_enqueue_scripts( $hook ) {
    if ( $hook !== 'toplevel_page_live-shipping-rates' ) {
        return;
    }
    wp_enqueue_script( 'lsrwc-admin', plugin_dir_url( __FILE__ ) . 'admin.js', array( 'jquery' ), '1.1.20', true );
    wp_localize_script( 'lsrwc-admin', 'lsrwc', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'lsrwc_nonce' ),
    ));
    wp_enqueue_style( 'lsrwc-admin', plugin_dir_url( __FILE__ ) . 'admin.css', array(), '1.1.20' );
}

// Add admin menu
function lsrwc_add_admin_menu() {
    add_menu_page(
        'Live Shipping Rates',
        'Live Shipping',
        'manage_options',
        'live-shipping-rates',
        'lsrwc_settings_page',
        'dashicons-admin-generic'
    );
}

// Register settings
function lsrwc_register_settings() {
    register_setting( 'lsrwc_settings_group', 'lsrwc_settings', 'lsrwc_sanitize_settings' );
    add_settings_section( 'lsrwc_main_section', 'Shipping Settings', null, 'live-shipping-rates' );
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
    add_settings_field( 'ups_international_percentage', 'UPS International Percentage Increase (%)', 'lsrwc_ups_international_percentage_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'usps_percentage', 'USPS Percentage Increase (%)', 'lsrwc_usps_percentage_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'ups_shipping_class_slug', 'UPS Shipping Class Slug', 'lsrwc_ups_shipping_class_slug_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'usps_shipping_class_slug', 'USPS Shipping Class Slug', 'lsrwc_usps_shipping_class_slug_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'free_shipping_class_slug', 'Free Shipping Class Slug', 'lsrwc_free_shipping_class_slug_field', 'live-shipping-rates', 'lsrwc_main_section' );
    add_settings_field( 'debug_mode', 'Enable Extensive Debugging', 'lsrwc_debug_mode_field', 'live-shipping-rates', 'lsrwc_main_section' );
}

// Sanitize settings
function lsrwc_sanitize_settings( $input ) {
    $sanitized = array();
    $sanitized['ups_client_id'] = sanitize_text_field( $input['ups_client_id'] ?? '' );
    $sanitized['ups_client_secret'] = sanitize_text_field( $input['ups_client_secret'] ?? '' );
    $sanitized['ups_account_number'] = sanitize_text_field( $input['ups_account_number'] ?? '' );
    $sanitized['usps_consumer_key'] = sanitize_text_field( $input['usps_consumer_key'] ?? '' );
    $sanitized['usps_consumer_secret'] = sanitize_text_field( $input['usps_consumer_secret'] ?? '' );
    $sanitized['origin_zip'] = sanitize_text_field( $input['origin_zip'] ?? '' );
    $sanitized['origin_city'] = sanitize_text_field( $input['origin_city'] ?? '' );
    $sanitized['origin_state'] = sanitize_text_field( $input['origin_state'] ?? '' );
    $sanitized['origin_address1'] = sanitize_text_field( $input['origin_address1'] ?? '' );
    $sanitized['ups_percentage'] = floatval( $input['ups_percentage'] ?? 0 );
    $sanitized['ups_international_percentage'] = floatval( $input['ups_international_percentage'] ?? 0 );
    $sanitized['usps_percentage'] = floatval( $input['usps_percentage'] ?? 0 );
    $sanitized['ups_shipping_class_slug'] = sanitize_text_field( $input['ups_shipping_class_slug'] ?? '' );
    $sanitized['usps_shipping_class_slug'] = sanitize_text_field( $input['usps_shipping_class_slug'] ?? '' );
    $sanitized['free_shipping_class_slug'] = sanitize_text_field( $input['free_shipping_class_slug'] ?? '' );
    $sanitized['debug_mode'] = isset( $input['debug_mode'] ) ? 1 : 0;
    return $sanitized;
}

// Settings page callback
function lsrwc_settings_page() {
    $settings = get_option( 'lsrwc_settings', array() );
    $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
    $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'settings';
    ?>
    <div class="wrap">
        <h1>Live Shipping Rates</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=live-shipping-rates&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=live-shipping-rates&tab=test-rates" class="nav-tab <?php echo $tab === 'test-rates' ? 'nav-tab-active' : ''; ?>">Test Live Rates</a>
            <a href="?page=live-shipping-rates&tab=debug-log" class="nav-tab <?php echo $tab === 'debug-log' ? 'nav-tab-active' : ''; ?>">Debug Log</a>
        </h2>
        <?php if ( $tab === 'settings' ) : ?>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'lsrwc_settings_group' );
                do_settings_sections( 'live-shipping-rates' );
                submit_button( 'Save Settings', 'primary', 'submit', true );
                ?>
            </form>
            <h2>Debug Information</h2>
            <div id="lsrwc-debug">
                <?php
                if ( ! empty( $debug_info ) ) {
                    echo '<h3>Latest API Debug Output</h3>';
                    echo '<pre>' . esc_html( print_r( $debug_info, true ) ) . '</pre>';
                } else {
                    echo '<p>No debug information available. Click "Get Rates" in the Test Live Rates tab to generate debug output.</p>';
                }
                ?>
                <button id="lsrwc-clear-debug" class="button">Clear Debug</button>
            </div>
        <?php elseif ( $tab === 'test-rates' ) : ?>
            <div id="test-rates-tab">
                <h2>Test Live Rates</h2>
                <form id="test-rates-form">
                    <label for="city">City:</label>
                    <input type="text" id="city" name="city"><br>
                    <label for="state">State/Province:</label>
                    <input type="text" id="state" name="state"><br>
                    <label for="zip">ZIP/Postal Code:</label>
                    <input type="text" id="zip" name="zip"><br>
                    <label for="country">Country:</label>
                    <input type="text" id="country" name="country" placeholder="e.g., CA for Canada"><br>
                    <label for="weight">Weight (lbs):</label>
                    <input type="number" step="0.01" id="weight" name="weight"><br>
                    <label for="length">Length (in):</label>
                    <input type="number" step="0.01" id="length" name="length"><br>
                    <label for="width">Width (in):</label>
                    <input type="number" step="0.01" id="width" name="width"><br>
                    <label for="height">Height (in):</label>
                    <input type="number" step="0.01" id="height" name="height"><br>
                    <button type="submit" class="button">Get Rates</button>
                </form>
                <div id="rates-result"></div>
            </div>
        <?php elseif ( $tab === 'debug-log' ) : ?>
            <div id="debug-log-tab">
                <h2>Debug Log File</h2>
                <?php
                $log_file = plugin_dir_path( __FILE__ ) . 'lsrwc_debug.log';
                if ( file_exists( $log_file ) ) {
                    echo '<h3>Debug Log Contents</h3>';
                    echo '<pre>' . esc_html( file_get_contents( $log_file ) ) . '</pre>';
                    echo '<a href="' . esc_url( plugin_dir_url( __FILE__ ) . 'lsrwc_debug.log' ) . '" download class="button">Download Debug Log</a>';
                    echo '<button id="lsrwc-clear-log-file" class="button" style="margin-left: 10px;">Clear Log File</button>';
                } else {
                    echo '<p>No debug log file found. Enable debugging and perform actions to generate logs.</p>';
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Field callbacks
function lsrwc_ups_client_id_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['ups_client_id'] ?? '';
    echo "<input type='text' name='lsrwc_settings[ups_client_id]' value='" . esc_attr( $value ) . "' class='regular-text'>";
}

function lsrwc_ups_client_secret_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['ups_client_secret'] ?? '';
    echo "<input type='text' name='lsrwc_settings[ups_client_secret]' value='" . esc_attr( $value ) . "' class='regular-text'>";
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
    $value = $settings['ups_percentage'] ?? 0;
    echo "<input type='number' step='0.01' name='lsrwc_settings[ups_percentage]' value='" . esc_attr( $value ) . "' class='small-text'> %";
}

function lsrwc_ups_international_percentage_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['ups_international_percentage'] ?? 0;
    echo "<input type='number' step='0.01' name='lsrwc_settings[ups_international_percentage]' value='" . esc_attr( $value ) . "' class='small-text'> %";
}

function lsrwc_usps_percentage_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['usps_percentage'] ?? 0;
    echo "<input type='number' step='0.01' name='lsrwc_settings[usps_percentage]' value='" . esc_attr( $value ) . "' class='small-text'> %";
}

function lsrwc_ups_shipping_class_slug_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['ups_shipping_class_slug'] ?? '';
    echo "<input type='text' name='lsrwc_settings[ups_shipping_class_slug]' value='" . esc_attr( $value ) . "' class='regular-text' placeholder='e.g., ups-shipping'>";
}

function lsrwc_usps_shipping_class_slug_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['usps_shipping_class_slug'] ?? '';
    echo "<input type='text' name='lsrwc_settings[usps_shipping_class_slug]' value='" . esc_attr( $value ) . "' class='regular-text' placeholder='e.g., usps-shipping'>";
}

function lsrwc_free_shipping_class_slug_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $value = $settings['free_shipping_class_slug'] ?? '';
    echo "<input type='text' name='lsrwc_settings[free_shipping_class_slug]' value='" . esc_attr( $value ) . "' class='regular-text' placeholder='e.g., free-shipping'>";
    echo '<p class="description">If any item in the cart uses this shipping class, all shipping will be offered for free and carrier API calls will be skipped.</p>';
}

function lsrwc_debug_mode_field() {
    $settings = get_option( 'lsrwc_settings', array() );
    $checked = isset( $settings['debug_mode'] ) && $settings['debug_mode'] ? 'checked' : '';
    echo "<input type='checkbox' name='lsrwc_settings[debug_mode]' value='1' $checked>";
}

// OAuth token functions
function lsrwc_get_ups_access_token() {
    $settings = get_option( 'lsrwc_settings', array() );
    $debug_mode = $settings['debug_mode'] ?? 0;
    $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
    $client_id = trim( $settings['ups_client_id'] ?? '' );
    $client_secret = trim( $settings['ups_client_secret'] ?? '' );
    $token = get_transient( 'lsrwc_ups_access_token' );

    if ( $token ) {
        if ( $debug_mode ) {
            $debug_info['ups_token'] = 'Using cached UPS access token.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'Using cached UPS access token.' );
        }
        return $token;
    }

    if ( empty( $client_id ) || empty( $client_secret ) ) {
        if ( $debug_mode ) {
            $debug_info['ups_token_error'] = 'UPS credentials missing. Please check Client ID and Client Secret in settings.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'UPS token request failed: Missing Client ID or Client Secret.', 'ERROR' );
        }
        return false;
    }

    $url = 'https://onlinetools.ups.com/security/v1/oauth/token';
    $auth_string = $client_id . ':' . $client_secret;
    $auth = base64_encode( $auth_string );
    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'x-merchant-id' => 'string',
            'Authorization' => 'Basic ' . $auth,
        ),
        'body' => 'grant_type=client_credentials',
    );

    if ( $debug_mode ) {
        $debug_info['ups_token_request'] = "UPS token request URL: $url\nHeaders: " . print_r( $args['headers'], true ) . "\nBody: " . $args['body'] . "\nAuth String (before encoding): $auth_string\nBase64 Encoded Auth: $auth";
    }
    $response = wp_remote_post( $url, $args );

    if ( is_wp_error( $response ) ) {
        if ( $debug_mode ) {
            $debug_info['ups_token_error'] = 'UPS token request failed: ' . $response->get_error_message();
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'UPS token request failed: ' . $response->get_error_message(), 'ERROR' );
        }
        return false;
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( $debug_mode ) {
        $debug_info['ups_token_response'] = "UPS token response (Code: $response_code): " . print_r( $body, true );
    }

    if ( $response_code !== 200 ) {
        $error_message = isset( $body['response']['errors'][0]['message'] ) ? $body['response']['errors'][0]['message'] : 'Unknown error';
        if ( $debug_mode ) {
            $debug_info['ups_token_error'] = 'UPS token request failed with status ' . $response_code . ': ' . $error_message;
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'UPS token request failed: ' . $error_message, 'ERROR' );
        }
        return false;
    }

    $token = $body['access_token'] ?? false;
    if ( $token ) {
        set_transient( 'lsrwc_ups_access_token', $token, $body['expires_in'] - 60 );
        if ( $debug_mode ) {
            $debug_info['ups_token'] = 'UPS token retrieved successfully.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'UPS token retrieved successfully.' );
        }
    } else {
        if ( $debug_mode ) {
            $debug_info['ups_token_error'] = 'UPS token not found in response.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'UPS token not found in response.', 'ERROR' );
        }
    }
    return $token;
}

function lsrwc_get_usps_access_token() {
    $settings = get_option( 'lsrwc_settings', array() );
    $debug_mode = $settings['debug_mode'] ?? 0;
    $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
    $client_id = trim( $settings['usps_consumer_key'] ?? '' );
    $client_secret = trim( $settings['usps_consumer_secret'] ?? '' );
    $token = get_transient( 'lsrwc_usps_access_token' );

    if ( $token ) {
        if ( $debug_mode ) {
            $debug_info['usps_token'] = 'Using cached USPS access token.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'Using cached USPS access token.' );
        }
        return $token;
    }

    if ( empty( $client_id ) || empty( $client_secret ) ) {
        if ( $debug_mode ) {
            $debug_info['usps_token_error'] = 'USPS credentials missing. Please check Consumer Key and Consumer Secret in settings.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'USPS token request failed: Missing Consumer Key or Consumer Secret.', 'ERROR' );
        }
        return false;
    }

    $url = 'https://apis.usps.com/oauth2/v3/token';
    $body = array(
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'client_credentials',
    );
    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode( $body ),
    );

    if ( $debug_mode ) {
        $debug_info['usps_token_request'] = "USPS token request URL: $url\nBody: " . print_r( $body, true );
    }
    $response = wp_remote_post( $url, $args );

    if ( is_wp_error( $response ) ) {
        if ( $debug_mode ) {
            $debug_info['usps_token_error'] = 'USPS token request failed: ' . $response->get_error_message();
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'USPS token request failed: ' . $response->get_error_message(), 'ERROR' );
        }
        return false;
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( $debug_mode ) {
        $debug_info['usps_token_response'] = "USPS token response (Code: $response_code): " . print_r( $body, true );
    }

    if ( $response_code !== 200 ) {
        $error_message = $body['error_description'] ?? 'Unknown error';
        if ( $debug_mode ) {
            $debug_info['usps_token_error'] = 'USPS token request failed with status ' . $response_code . ': ' . $error_message;
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'USPS token request failed: ' . $error_message, 'ERROR' );
        }
        return false;
    }

    $token = $body['access_token'] ?? false;
    if ( $token ) {
        set_transient( 'lsrwc_usps_access_token', $token, $body['expires_in'] - 60 );
        if ( $debug_mode ) {
            $debug_info['usps_token'] = 'USPS token retrieved successfully.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'USPS token retrieved successfully.' );
        }
    } else {
        if ( $debug_mode ) {
            $debug_info['usps_token_error'] = 'USPS token not found in response.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'USPS token not found in response.', 'ERROR' );
        }
    }
    return $token;
}

// AJAX handler for testing live rates
function lsrwc_test_rates_ajax() {
    check_ajax_referer( 'lsrwc_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized access.' );
        lsrwc_log( 'Test rates failed: Unauthorized access.', 'ERROR' );
        return;
    }

    $settings = get_option( 'lsrwc_settings', array() );
    $debug_mode = $settings['debug_mode'] ?? 0;
    $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();

    $city = sanitize_text_field( $_POST['city'] ?? '' );
    $state = sanitize_text_field( $_POST['state'] ?? '' );
    $zip = sanitize_text_field( $_POST['zip'] ?? '' );
    $country = sanitize_text_field( $_POST['country'] ?? 'US' );
    $weight = floatval( $_POST['weight'] ?? 0 );
    $length = floatval( $_POST['length'] ?? 0 );
    $width = floatval( $_POST['width'] ?? 0 );
    $height = floatval( $_POST['height'] ?? 0 );

    if ( $debug_mode ) {
        $debug_info['test_rates_inputs'] = "City: $city, State: $state, ZIP: $zip, Country: $country, Weight: $weight, Length: $length, Width: $width, Height: $height";
        set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
        lsrwc_log( "Test rates inputs: " . $debug_info['test_rates_inputs'] );
    }

    if ( empty( $city ) || empty( $state ) || empty( $zip ) || empty( $country ) || $weight <= 0 || $length <= 0 || $width <= 0 || $height <= 0 ) {
        $error_message = 'All fields are required and must be valid positive numbers.';
        if ( $debug_mode ) {
            $debug_info['test_rates_error'] = $error_message;
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( "Test rates failed: $error_message", 'ERROR' );
        }
        wp_send_json_error( $error_message );
        return;
    }

    $rates = array();
    if ( $country === 'CA' ) {
        // For Canada, test UPS International
        $rates['ups_international'] = lsrwc_fetch_ups_rates( $city, $state, $zip, $country, $weight, $length, $width, $height, '11' );
    } else {
        // For US, test UPS Ground and USPS
        $rates['ups'] = lsrwc_fetch_ups_rates( $city, $state, $zip, $country, $weight, $length, $width, $height, '03' );
        $rates['usps'] = lsrwc_fetch_usps_rates( $city, $state, $zip, $weight, $length, $width, $height );
    }

    if ( $debug_mode ) {
        $debug_info['test_rates_result'] = print_r( $rates, true );
        set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
        lsrwc_log( "Test rates result: " . $debug_info['test_rates_result'] );
    }

    // Refresh debug info to include any notices recorded during rate calls.
    $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
    $usps_notice = $debug_info['usps_rates_last_notice'] ?? '';

    ob_start();
    echo '<h3>Live Shipping Rates</h3>';
    if ( ! empty( $rates['ups'] ) ) {
        echo '<h4>UPS Rates</h4>';
        echo '<table>';
        echo '<tr><th>Service</th><th>Original Cost</th><th>Adjusted Cost</th></tr>';
        foreach ( $rates['ups'] as $service => $cost ) {
            echo "<tr><td>$service</td><td>{$cost['original']}</td><td>{$cost['adjusted']}</td></tr>";
        }
        echo '</table>';
    } elseif ( ! empty( $rates['ups_international'] ) ) {
        echo '<h4>UPS International Rates</h4>';
        echo '<table>';
        echo '<tr><th>Service</th><th>Original Cost</th><th>Adjusted Cost</th></tr>';
        foreach ( $rates['ups_international'] as $service => $cost ) {
            echo "<tr><td>$service</td><td>{$cost['original']}</td><td>{$cost['adjusted']}</td></tr>";
        }
        echo '</table>';
    } else {
        echo '<p class="lsrwc-error">No UPS rates available. Check debug information.</p>';
    }

    if ( ! empty( $rates['usps'] ) ) {
        echo '<h4>USPS Rates</h4>';
        echo '<table>';
        echo '<tr><th>Service</th><th>Original Cost</th><th>Adjusted Cost</th></tr>';
        foreach ( $rates['usps'] as $service => $cost ) {
            echo "<tr><td>$service</td><td>{$cost['original']}</td><td>{$cost['adjusted']}</td></tr>";
        }
        echo '</table>';
    } elseif ( $country !== 'CA' ) {
        $reason_suffix = $usps_notice ? ' Reason: ' . esc_html( $usps_notice ) : '';
        echo '<p class="lsrwc-error">No USPS rates available. Check debug information.' . $reason_suffix . '</p>';
        lsrwc_log(
            'Test Rates: USPS rates not returned for manual request.',
            'WARNING',
            array(
                'city' => $city,
                'state' => $state,
                'zip' => $zip,
                'country' => $country,
                'weight' => $weight,
                'dimensions' => compact( 'length', 'width', 'height' ),
                'notice' => $usps_notice
            )
        );
    }
    $html = ob_get_clean();

    wp_send_json_success( $html );
}

// Fetch UPS rates
function lsrwc_fetch_ups_rates( $city, $state, $zip, $country, $weight, $length, $width, $height, $service_code = '03' ) {
    $settings = get_option( 'lsrwc_settings', array() );
    $debug_mode = $settings['debug_mode'] ?? 0;
    $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
    $token = lsrwc_get_ups_access_token();
    if ( ! $token ) {
        if ( $debug_mode ) {
            $debug_info['ups_rates_error'] = 'Failed to get UPS access token for rates.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'UPS rate request failed: No token.', 'ERROR' );
        }
        return array();
    }

    // Validate weight
    if ( $weight <= 0 ) {
        if ( $debug_mode ) {
            $debug_info['ups_rates_error'] = 'Invalid package weight: Weight must be greater than zero.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'UPS rate request skipped: Invalid package weight.', 'WARNING' );
        }
        return array();
    }

    $version = "v1";
    $requestoption = "Rate";
    $url = "https://onlinetools.ups.com/api/rating/{$version}/{$requestoption}";

    $payload = array(
        "RateRequest" => array(
            "Request" => array(
                "TransactionReference" => array(
                    "CustomerContext" => "CustomerContext",
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
                    "Code" => $service_code,
                    "Description" => $service_code === '11' ? 'UPS Standard' : 'Ground'
                ),
                "Package" => array(
                    array(
                        "PackagingType" => array(
                            "Code" => "02", // Box
                            "Description" => "Box"
                        ),
                        "Dimensions" => array(
                            "UnitOfMeasurement" => array(
                                "Code" => "IN",
                                "Description" => "Inches"
                            ),
                            "Length" => (string) $length,
                            "Width" => (string) $width,
                            "Height" => (string) $height
                        ),
                        "PackageWeight" => array(
                            "UnitOfMeasurement" => array(
                                "Code" => "LBS",
                                "Description" => "Pounds"
                            ),
                            "Weight" => (string) $weight
                        )
                    )
                )
            )
        )
    );

    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'transId' => uniqid(),
            'transactionSrc' => 'LiveShippingRates'
        ),
        'body' => json_encode( $payload ),
    );

    if ( $debug_mode ) {
        $debug_info['ups_rate_request'] = "UPS rate request URL: $url\nBody: " . print_r( $payload, true );
        set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
        lsrwc_log( "UPS rate request: URL=$url, Body=" . print_r( $payload, true ) );
    }
    $response = wp_remote_post( $url, $args );

    if ( is_wp_error( $response ) ) {
        if ( $debug_mode ) {
            $debug_info['ups_rates_error'] = 'UPS rates fetch failed: ' . $response->get_error_message();
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'UPS rates fetch failed: ' . $response->get_error_message(), 'ERROR' );
        }
        return array();
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( $debug_mode ) {
        $debug_info['ups_rate_response'] = "UPS rate response (Code: $response_code): " . print_r( $response_body, true );
        set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
        lsrwc_log( "UPS rate response: Code=$response_code, Body=" . print_r( $response_body, true ) );
    }

    if ( $response_code !== 200 ) {
        $error_message = $response_body['response']['errors'][0]['message'] ?? 'Unknown error';
        if ( $debug_mode ) {
            $debug_info['ups_rates_error'] = "UPS API error: $error_message";
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( "UPS API error: $error_message", 'ERROR' );
        }
        return array();
    }

    $rates = array();
    if ( isset( $response_body['RateResponse']['RatedShipment']['TotalCharges']['MonetaryValue'] ) ) {
        $original_rate = floatval( $response_body['RateResponse']['RatedShipment']['TotalCharges']['MonetaryValue'] );
        $percentage = $service_code === '11' ? ($settings['ups_international_percentage'] ?? 0) : ($settings['ups_percentage'] ?? 0);
        $adjusted_rate = $original_rate * ( 1 + $percentage / 100 );
        $service_name = $service_code === '11' ? 'UPS Standard' : 'UPS Ground';
        $rates[$service_name] = array(
            'original' => '$' . number_format( $original_rate, 2 ),
            'adjusted' => '$' . number_format( $adjusted_rate, 2 )
        );
        if ( $debug_mode ) {
            $debug_info['ups_rates_calculated'] = "Service: $service_name, Original Rate: $original_rate, Percentage: $percentage%, Adjusted Rate: $adjusted_rate";
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( "UPS rates calculated: Service=$service_name, Original Rate=$original_rate, Percentage=$percentage%, Adjusted Rate=$adjusted_rate" );
        }
    }

    if ( empty( $rates ) ) {
        if ( $debug_mode ) {
            $debug_info['ups_rates_error'] = 'No rates found in UPS response.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'No rates found in UPS response.', 'WARNING' );
        }
    }

    return $rates;
}

function lsrwc_normalize_dimensions( $length, $width, $height ) {
    $dimensions = array(
        max( 0, floatval( $length ) ),
        max( 0, floatval( $width ) ),
        max( 0, floatval( $height ) )
    );
    rsort( $dimensions, SORT_NUMERIC );
    return array(
        'length' => $dimensions[0] ?? 0,
        'width' => $dimensions[1] ?? 0,
        'height' => $dimensions[2] ?? 0,
    );
}

function lsrwc_get_usps_processing_category( $weight, $length, $width, $height ) {
    // USPS machinable parcels max out at 22" x 18" x 15" and 25 lbs (QSG 201e / Domestic Label API sheet).
    $category = 'MACHINABLE';
    $reasons = array();

    if ( $weight > 25 ) {
        $category = 'NONSTANDARD';
        $reasons[] = 'Weight exceeds 25 lb machinable limit';
    }

    if ( $length > 22 ) {
        $category = 'NONSTANDARD';
        $reasons[] = 'Length exceeds 22 inches machinable limit';
    }

    if ( $width > 18 ) {
        $category = 'NONSTANDARD';
        $reasons[] = 'Width exceeds 18 inches machinable limit';
    }

    if ( $height > 15 ) {
        $category = 'NONSTANDARD';
        $reasons[] = 'Height exceeds 15 inches machinable limit';
    }

    $length_plus_girth = $length + 2 * ( $width + $height );
    if ( $length_plus_girth > 108 ) {
        $category = 'NONSTANDARD';
        $reasons[] = 'Length plus girth exceeds 108 inches machinable limit';
    }

    return array(
        'category' => $category,
        'reasons' => $reasons,
        'length_plus_girth' => $length_plus_girth,
    );
}

// Fetch USPS rates
function lsrwc_fetch_usps_rates( $city, $state, $zip, $weight, $length, $width, $height ) {
    $settings = get_option( 'lsrwc_settings', array() );
    $debug_mode = $settings['debug_mode'] ?? 0;
    $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
    $token = lsrwc_get_usps_access_token();
    if ( ! $token ) {
        if ( $debug_mode ) {
            $debug_info['usps_rates_error'] = 'Failed to get USPS access token for rates.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'USPS rates fetch failed: No token.', 'ERROR' );
        }
        lsrwc_set_last_notice( 'usps_rates_last_notice', 'Failed to get USPS access token.' );
        return array();
    }

    $normalized_dimensions = lsrwc_normalize_dimensions( $length, $width, $height );
    $length = $normalized_dimensions['length'];
    $width = $normalized_dimensions['width'];
    $height = $normalized_dimensions['height'];

    $processing_category_data = lsrwc_get_usps_processing_category( $weight, $length, $width, $height );
    $processing_category = $processing_category_data['category'];

    $url = 'https://apis.usps.com/prices/v3/base-rates/search';
    $body = array(
        'originZIPCode' => $settings['origin_zip'] ?? '78664',
        'destinationZIPCode' => $zip,
        'weight' => $weight,
        'length' => $length,
        'width' => $width,
        'height' => $height,
        'mailClass' => 'USPS_GROUND_ADVANTAGE',
        'processingCategory' => $processing_category,
        'destinationEntryFacilityType' => 'NONE',
        'rateIndicator' => 'DR',
        'priceType' => 'COMMERCIAL',
    );

    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode( $body ),
    );

    if ( $debug_mode ) {
        $category_note = empty( $processing_category_data['reasons'] ) ? 'Package within machinable thresholds.' : 'Reasons: ' . implode( '; ', $processing_category_data['reasons'] );
        $debug_info['usps_processing_category'] = "Processing category: $processing_category. $category_note Length+Girth: " . number_format( $processing_category_data['length_plus_girth'], 2 );
        $debug_info['usps_rate_request'] = "USPS rate request URL: $url\nBody: " . print_r( $body, true );
        set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
        lsrwc_log( "USPS rate request: URL=$url, Body=" . print_r( $body, true ) );
    }
    $response = wp_remote_post( $url, $args );

    if ( is_wp_error( $response ) ) {
        if ( $debug_mode ) {
            $debug_info['usps_rates_error'] = 'USPS rates fetch failed: ' . $response->get_error_message();
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'USPS rates fetch failed: ' . $response->get_error_message(), 'ERROR' );
        }
        lsrwc_set_last_notice( 'usps_rates_last_notice', 'USPS request error: ' . $response->get_error_message() );
        return array();
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( $debug_mode ) {
        $debug_info['usps_rate_response'] = "USPS rate response (Code: $response_code): " . print_r( $response_body, true );
        set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
        lsrwc_log( "USPS rate response: Code=$response_code, Body=" . print_r( $response_body, true ) );
    }

    if ( $response_code !== 200 ) {
        $error_message = $response_body['error']['message'] ?? 'Unknown error';
        if ( $debug_mode ) {
            $debug_info['usps_rates_error'] = "USPS API error: $error_message";
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( "USPS API error: $error_message", 'ERROR' );
        }
        lsrwc_set_last_notice( 'usps_rates_last_notice', 'USPS API error: ' . $error_message );
        return array();
    }

    $rates = array();
    if ( isset( $response_body['totalBasePrice'] ) ) {
        $original_rate = floatval( $response_body['totalBasePrice'] );
        $percentage = $settings['usps_percentage'] ?? 0;
        $adjusted_rate = $original_rate * ( 1 + $percentage / 100 );
        $rates['USPS Ground Advantage'] = array(
            'original' => '$' . number_format( $original_rate, 2 ),
            'adjusted' => '$' . number_format( $adjusted_rate, 2 )
        );
        if ( $debug_mode ) {
            $debug_info['usps_rates_calculated'] = "Service: USPS Ground Advantage, Original Rate: $original_rate, Percentage: $percentage%, Adjusted Rate: $adjusted_rate";
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( "USPS rates calculated: Service=USPS Ground Advantage, Original Rate=$original_rate, Percentage=$percentage%, Adjusted Rate=$adjusted_rate" );
        }
    }

    if ( empty( $rates ) ) {
        if ( $debug_mode ) {
            $debug_info['usps_rates_error'] = 'No rate found in USPS response (totalBasePrice missing).';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'No rate found in USPS response (totalBasePrice missing).', 'WARNING' );
        }
        lsrwc_set_last_notice( 'usps_rates_last_notice', 'USPS response did not include totalBasePrice for Ground Advantage.' );
    } else {
        lsrwc_set_last_notice( 'usps_rates_last_notice', 'USPS returned ' . count( $rates ) . ' rate(s).' );
    }

    return $rates;
}

// Clear Debug AJAX Handler
function lsrwc_clear_debug_ajax() {
    check_ajax_referer( 'lsrwc_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized access.' );
        lsrwc_log( 'Clear debug failed: Unauthorized access.', 'ERROR' );
        return;
    }
    delete_transient( 'lsrwc_ups_access_token' );
    delete_transient( 'lsrwc_usps_access_token' );
    delete_transient( 'lsrwc_debug_info' );
    $log_file = plugin_dir_path( __FILE__ ) . 'lsrwc_debug.log';
    if ( file_exists( $log_file ) ) {
        file_put_contents( $log_file, '' );
    }
    wp_send_json_success( 'Debug information and cached tokens cleared.' );
    lsrwc_log( 'Debug information and log file cleared.' );
}

// Logging function
function lsrwc_log( $message, $level = 'INFO', $context = array() ) {
    $settings = get_option( 'lsrwc_settings', array() );
    $debug_mode = $settings['debug_mode'] ?? 0;
    if ( $debug_mode ) {
        $log_file = plugin_dir_path( __FILE__ ) . 'lsrwc_debug.log';
        $timestamp = gmdate( 'Y-m-d H:i:s' );
        $level = strtoupper( $level );
        $context_string = '';
        if ( ! empty( $context ) ) {
            $encoded_context = wp_json_encode( $context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
            if ( $encoded_context ) {
                $context_string = ' ' . $encoded_context;
            }
        }
        $log_message = "[$timestamp] [LSRWC][$level] $message$context_string\n";
        error_log( $log_message );
        file_put_contents( $log_file, $log_message, FILE_APPEND );
    }
}

function lsrwc_set_last_notice( $key, $message ) {
    $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
    $debug_info[$key] = $message;
    set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
}

function lsrwc_get_free_shipping_class_slug() {
    $settings = get_option( 'lsrwc_settings', array() );
    return $settings['free_shipping_class_slug'] ?? '';
}

function lsrwc_package_has_free_shipping_class( $package ) {
    if ( empty( $package['contents'] ) ) {
        return false;
    }
    $slug = lsrwc_get_free_shipping_class_slug();
    if ( empty( $slug ) ) {
        return false;
    }
    foreach ( $package['contents'] as $item ) {
        $product = $item['data'] ?? null;
        if ( ! $product || ! method_exists( $product, 'get_shipping_class' ) ) {
            continue;
        }
        if ( $product->get_shipping_class() === $slug ) {
            return true;
        }
    }
    return false;
}

function lsrwc_package_has_chargeable_items( $package ) {
    if ( empty( $package['contents'] ) ) {
        return false;
    }
    $slug = lsrwc_get_free_shipping_class_slug();
    foreach ( $package['contents'] as $item ) {
        $product = $item['data'] ?? null;
        if ( ! $product || ! method_exists( $product, 'get_shipping_class' ) ) {
            continue;
        }
        if ( empty( $slug ) || $product->get_shipping_class() !== $slug ) {
            return true;
        }
    }
    return false;
}

function lsrwc_cart_has_free_shipping_coupon() {
    if ( ! function_exists( 'WC' ) ) {
        return false;
    }
    $cart = WC()->cart ?? null;
    if ( ! $cart || ! method_exists( $cart, 'get_coupons' ) ) {
        return false;
    }
    foreach ( $cart->get_coupons() as $coupon ) {
        if ( $coupon && method_exists( $coupon, 'get_free_shipping' ) && $coupon->get_free_shipping() ) {
            return true;
        }
    }
    return false;
}

function lsrwc_generate_free_shipping_rate() {
    $label = __( 'Free Shipping', 'lsrwc' );
    $rate_id = 'lsrwc_free_shipping';
    if ( class_exists( 'WC_Shipping_Rate' ) ) {
        $rate_object = new WC_Shipping_Rate( $rate_id, $label, 0, array(), 'lsrwc_free_shipping' );
    } else {
        $rate_object = array( 'label' => $label, 'cost' => 0 );
    }
    return array( $rate_id => $rate_object );
}

// Register shipping methods
function lsrwc_add_shipping_methods( $methods ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-lsrwc-shipping-method.php';
    $methods['lsrwc_ups'] = 'LSRWC_UPS_Shipping_Method';
    $methods['lsrwc_usps'] = 'LSRWC_USPS_Shipping_Method';
    $methods['lsrwc_ups_international'] = 'LSRWC_UPS_International_Shipping_Method';
    return $methods;
}

// Filter shipping methods based on cart contents
function lsrwc_filter_shipping_methods( $rates, $package ) {
    $settings = get_option( 'lsrwc_settings', array() );
    $debug_mode = $settings['debug_mode'] ?? 0;
    $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
    $ups_slug = $settings['ups_shipping_class_slug'] ?? '';
    $usps_slug = $settings['usps_shipping_class_slug'] ?? '';
    $has_free_items = lsrwc_package_has_free_shipping_class( $package );
    $has_chargeable_items = lsrwc_package_has_chargeable_items( $package );
    $has_free_shipping_coupon = lsrwc_cart_has_free_shipping_coupon();

    if ( $has_free_shipping_coupon ) {
        if ( $debug_mode ) {
            $debug_info['filter_shipping'] = 'Free-shipping coupon applied. Overriding carrier rates with free shipping.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'Free-shipping coupon detected; returning only free shipping rate.', 'INFO' );
        }
        return lsrwc_generate_free_shipping_rate();
    }

    if ( $has_free_items && ! $has_chargeable_items ) {
        if ( $debug_mode ) {
            $debug_info['filter_shipping'] = 'All items qualify for free shipping. Only offering free shipping rate.';
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( 'All cart items use the free-shipping class. Returning only free shipping rate.', 'INFO' );
        }
        return lsrwc_generate_free_shipping_rate();
    }

    // Check for Canada first
    $destination_country = $package['destination']['country'] ?? 'US';
    if ( $destination_country === 'CA' ) {
        // For Canada, only keep UPS International rates
        $filtered_rates = array();
        foreach ( $rates as $rate_id => $rate ) {
            if ( strpos( $rate_id, 'lsrwc_ups_international' ) === 0 ) {
                $filtered_rates[$rate_id] = $rate;
            }
        }
        if ( $debug_mode ) {
            $debug_info['filter_shipping'] = "Filtered rates for country $destination_country: Rates: " . print_r( array_keys( $filtered_rates ), true );
            set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
            lsrwc_log( "Filtered rates for country $destination_country: Rates: " . print_r( array_keys( $filtered_rates ), true ) );
        }
        return $filtered_rates;
    }

    // For non-Canada (e.g., US), apply updated logic
    $has_usps_slug = false;
    $has_ups_slug = false;

    // Check each item in the cart for shipping classes
    foreach ( $package['contents'] as $item ) {
        $product = $item['data'];
        $shipping_class = $product->get_shipping_class();
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

    // Filter shipping rates based on shipping class presence
    $filtered_rates = array();
    if ( $has_usps_slug && !$has_ups_slug ) {
        // Only show USPS rates
        foreach ( $rates as $rate_id => $rate ) {
            if ( strpos( $rate_id, 'lsrwc_usps' ) === 0 ) {
                $filtered_rates[$rate_id] = $rate;
            }
        }
    } elseif ( $has_ups_slug && !$has_usps_slug ) {
        // Only show UPS rates
        foreach ( $rates as $rate_id => $rate ) {
            if ( strpos( $rate_id, 'lsrwc_ups' ) === 0 ) {
                $filtered_rates[$rate_id] = $rate;
            }
        }
    } else {
        // Show all available rates
        $filtered_rates = $rates;
    }

    if ( $debug_mode ) {
        $debug_info['filter_shipping'] = "Filtered rates for country $destination_country: Has USPS: " . ($has_usps_slug ? 'Yes' : 'No') . ", Has UPS: " . ($has_ups_slug ? 'Yes' : 'No') . ", Rates: " . print_r( array_keys( $filtered_rates ), true );
        set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
        lsrwc_log( "Filtered rates for country $destination_country: Has USPS: " . ($has_usps_slug ? 'Yes' : 'No') . ", Has UPS: " . ($has_ups_slug ? 'Yes' : 'No') . ", Rates: " . print_r( array_keys( $filtered_rates ), true ) );
    }
    return $filtered_rates;
}
add_filter( 'woocommerce_package_rates', 'lsrwc_filter_shipping_methods', 10, 2 );
?>
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'LSRWC_Shipping_Method' ) ) {
    abstract class LSRWC_Shipping_Method extends WC_Shipping_Method {
        public $carrier;

        public function __construct( $instance_id = 0 ) {
            $this->instance_id = absint( $instance_id );
            $this->supports = array( 'shipping-zones', 'instance-settings' );
            $this->init();
        }

        public function init() {
            $this->init_settings();
        }

        public function calculate_shipping( $package = array() ) {
            $settings = get_option( 'lsrwc_settings', array() );
            $rates = $this->fetch_shipping_rates( $package );
            foreach ( $rates as $rate ) {
                $cost = $rate['cost'];
                $percentage = $this->carrier === 'ups' ? ( $settings['ups_percentage'] ?? 0 ) : ( $settings['usps_percentage'] ?? 0 );
                $cost = $cost * ( 1 + $percentage / 100 );
                $this->add_rate( array(
                    'id' => $this->id . '_' . $rate['id'],
                    'label' => $rate['label'],
                    'cost' => $cost
                ) );
            }
        }

        protected function get_package_weight( $package ) {
            $total_weight = 0;
            foreach ( $package['contents'] as $item ) {
                $product = $item['data'];
                $weight = $product->get_weight() ? floatval( $product->get_weight() ) : 0;
                $quantity = isset( $item['quantity'] ) ? max( 1, intval( $item['quantity'] ) ) : 1;
                $total_weight += $weight * $quantity;
            }
            return $total_weight > 0 ? $total_weight : 0; // Return 0 if total weight is invalid
        }

        protected function get_package_dimension( $package, $dimension ) {
            $max_dimension = 0;
            foreach ( $package['contents'] as $item ) {
                $product = $item['data'];
                $dim_value = $product->get_data()[$dimension] ? floatval( $product->get_data()[$dimension] ) : 0;
                $quantity = isset( $item['quantity'] ) ? max( 1, intval( $item['quantity'] ) ) : 1;
                // Use the maximum dimension across all items, adjusted for quantity
                $max_dimension = max( $max_dimension, $dim_value * $quantity );
            }
            return $max_dimension > 0 ? $max_dimension : 1; // Fallback to 1 inch
        }
    }
}

if ( ! class_exists( 'LSRWC_UPS_Shipping_Method' ) ) {
    class LSRWC_UPS_Shipping_Method extends LSRWC_Shipping_Method {
        public function __construct( $instance_id = 0 ) {
            $this->id = 'lsrwc_ups';
            $this->method_title = 'UPS Live Rates (Ground)';
            $this->method_description = 'Fetches live UPS Ground shipping rates.';
            $this->carrier = 'ups';
            parent::__construct( $instance_id );
        }

        protected function fetch_shipping_rates( $package ) {
            $token = lsrwc_get_ups_access_token();
            if ( ! $token ) {
                lsrwc_log( 'Failed to get UPS access token.', true );
                return array();
            }

            $settings = get_option( 'lsrwc_settings', array() );
            $weight = $this->get_package_weight( $package );
            $length = $this->get_package_dimension( $package, 'length' );
            $width = $this->get_package_dimension( $package, 'width' );
            $height = $this->get_package_dimension( $package, 'height' );
            $city = $package['destination']['city'] ?? '';
            $state = $package['destination']['state'] ?? '';
            $zip = $package['destination']['postcode'] ?? '';

            // Validate weight
            if ( $weight <= 0 ) {
                $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
                $debug_info['ups_rates_error'] = 'Invalid package weight: Weight must be greater than zero.';
                set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                lsrwc_log( 'UPS rate request skipped: Invalid package weight.', true );
                return array();
            }

            // Call the UPS rate fetching function with aggregated weight
            $rates = lsrwc_fetch_ups_rates( $city, $state, $zip, $weight, $length, $width, $height );

            $formatted_rates = array();
            foreach ( $rates as $service => $cost ) {
                $formatted_rates[] = array(
                    'id' => sanitize_title( $service ),
                    'label' => $service,
                    'cost' => floatval( str_replace( '$', '', $cost ) )
                );
            }

            return $formatted_rates;
        }
    }
}

if ( ! class_exists( 'LSRWC_USPS_Shipping_Method' ) ) {
    class LSRWC_USPS_Shipping_Method extends LSRWC_Shipping_Method {
        public function __construct( $instance_id = 0 ) {
            $this->id = 'lsrwc_usps';
            $this->method_title = 'USPS Live Rates (Ground Advantage)';
            $this->method_description = 'Fetches live USPS Ground Advantage shipping rates.';
            $this->carrier = 'usps';
            parent::__construct( $instance_id );
        }

        protected function fetch_shipping_rates( $package ) {
            $settings = get_option( 'lsrwc_settings', array() );
            $weight = $this->get_package_weight( $package );
            $length = $this->get_package_dimension( $package, 'length' );
            $width = $this->get_package_dimension( $package, 'width' );
            $height = $this->get_package_dimension( $package, 'height' );
            $city = $package['destination']['city'] ?? '';
            $state = $package['destination']['state'] ?? '';
            $zip = $package['destination']['postcode'] ?? '';

            // Validate weight
            if ( $weight <= 0 ) {
                $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
                $debug_info['usps_rates_error'] = 'Invalid package weight: Weight must be greater than zero.';
                set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                lsrwc_log( 'USPS rate request skipped: Invalid package weight.', true );
                return array();
            }

            // Call the USPS rate fetching function with aggregated weight
            $rates = lsrwc_fetch_usps_rates( $city, $state, $zip, $weight, $length, $width, $height );

            $formatted_rates = array();
            foreach ( $rates as $service => $cost ) {
                $formatted_rates[] = array(
                    'id' => sanitize_title( $service ),
                    'label' => $service,
                    'cost' => floatval( str_replace( '$', '', $cost ) )
                );
            }

            return $formatted_rates;
        }
    }
}
?>
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
            $debug_mode = $settings['debug_mode'] ?? 0;
            $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
            $has_chargeable_items = lsrwc_package_has_chargeable_items( $package );
            if ( ! $has_chargeable_items ) {
                if ( $debug_mode && lsrwc_package_has_free_shipping_class( $package ) ) {
                    $debug_info['free_shipping_class_match'] = 'Only free-shipping items detected. Carrier ' . $this->carrier . ' skipped.';
                    set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                    lsrwc_log( 'Only free-shipping items in cart; skipping carrier ' . $this->carrier, 'INFO' );
                }
                return;
            }
            $rates = $this->fetch_shipping_rates( $package );
            if ( $debug_mode ) {
                $debug_info['calculate_shipping_rates'] = "Carrier: {$this->carrier}, Rates: " . print_r( $rates, true );
                set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                lsrwc_log( "Calculate shipping for carrier {$this->carrier}: Rates: " . print_r( $rates, true ) );
            }
            foreach ( $rates as $rate ) {
                $cost = $rate['cost'];
                $percentage = $this->carrier === 'ups' ? ($this->id === 'lsrwc_ups_international' ? ($settings['ups_international_percentage'] ?? 0) : ($settings['ups_percentage'] ?? 0)) : ($settings['usps_percentage'] ?? 0);
                $cost = $cost * ( 1 + $percentage / 100 );
                $this->add_rate( array(
                    'id' => $this->id . '_' . $rate['id'],
                    'label' => $rate['label'],
                    'cost' => $cost
                ) );
            }
        }

        protected function get_package_weight( $package ) {
            $settings = get_option( 'lsrwc_settings', array() );
            $debug_mode = $settings['debug_mode'] ?? 0;
            $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
            $free_slug = $settings['free_shipping_class_slug'] ?? '';
            $total_weight = 0;
            foreach ( $package['contents'] as $item ) {
                $product = $item['data'];
                if ( $free_slug && method_exists( $product, 'get_shipping_class' ) && $product->get_shipping_class() === $free_slug ) {
                    continue;
                }
                $weight = $product->get_weight() ? floatval( $product->get_weight() ) : 0;
                $quantity = isset( $item['quantity'] ) ? max( 1, intval( $item['quantity'] ) ) : 1;
                $total_weight += $weight * $quantity;
                if ( $debug_mode ) {
                    $debug_info['package_weight_item'] = "Product: {$product->get_name()}, Weight: $weight, Quantity: $quantity, Total: " . ($weight * $quantity);
                    set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                    lsrwc_log( "Package weight item: Product={$product->get_name()}, Weight=$weight, Quantity=$quantity, Total=" . ($weight * $quantity) );
                }
            }
            if ( $debug_mode ) {
                $debug_info['cart_package_weight'] = $total_weight;
                set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                lsrwc_log( "Total package weight: $total_weight" );
            }
            return $total_weight > 0 ? $total_weight : 0; // Return 0 if total weight is invalid
        }

        protected function get_package_dimension( $package, $dimension ) {
            $settings = get_option( 'lsrwc_settings', array() );
            $debug_mode = $settings['debug_mode'] ?? 0;
            $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
            $free_slug = $settings['free_shipping_class_slug'] ?? '';
            $max_dimension = 0;
            foreach ( $package['contents'] as $item ) {
                $product = $item['data'];
                if ( $free_slug && method_exists( $product, 'get_shipping_class' ) && $product->get_shipping_class() === $free_slug ) {
                    continue;
                }
                $dim_value = $product->get_data()[$dimension] ? floatval( $product->get_data()[$dimension] ) : 0;
                $quantity = isset( $item['quantity'] ) ? max( 1, intval( $item['quantity'] ) ) : 1;
                // Use the maximum dimension across all items, adjusted for quantity
                $max_dimension = max( $max_dimension, $dim_value * $quantity );
                if ( $debug_mode ) {
                    $debug_info['package_dimension_item'] = "Product: {$product->get_name()}, Dimension ($dimension): $dim_value, Quantity: $quantity, Adjusted: " . ($dim_value * $quantity);
                    set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                    lsrwc_log( "Package dimension item ($dimension): Product={$product->get_name()}, Dimension=$dim_value, Quantity=$quantity, Adjusted=" . ($dim_value * $quantity) );
                }
            }
            $result = $max_dimension > 0 ? $max_dimension : 1; // Fallback to 1 inch
            if ( $debug_mode ) {
                $debug_info['cart_package_dimension_' . $dimension] = $result;
                set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                lsrwc_log( "Total package dimension ($dimension): $result" );
            }
            return $result;
        }
    }
}

if ( ! class_exists( 'LSRWC_UPS_Shipping_Method' ) ) {
    class LSRWC_UPS_Shipping_Method extends LSRWC_Shipping_Method {
        public function __construct( $instance_id = 0 ) {
            $this->id = 'lsrwc_ups';
            $this->carrier = 'ups';
            $this->method_title = 'UPS Live Rates (Ground)';
            $this->method_description = 'Fetches live UPS Ground shipping rates.';
            $this->title = $this->method_title;
            parent::__construct( $instance_id );
        }

        public function init() {
            parent::init();
        }

        public function get_method_title() {
            return 'UPS Live Rates (Ground)';
        }

        protected function fetch_shipping_rates( $package ) {
            $settings = get_option( 'lsrwc_settings', array() );
            $debug_mode = $settings['debug_mode'] ?? 0;
            $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
            $token = lsrwc_get_ups_access_token();
            if ( ! $token ) {
                lsrwc_log( 'Failed to get UPS access token.', 'ERROR' );
                return array();
            }

            $weight = $this->get_package_weight( $package );
            $length = $this->get_package_dimension( $package, 'length' );
            $width = $this->get_package_dimension( $package, 'width' );
            $height = $this->get_package_dimension( $package, 'height' );
            $city = $package['destination']['city'] ?? '';
            $state = $package['destination']['state'] ?? '';
            $zip = $package['destination']['postcode'] ?? '';
            $country = $package['destination']['country'] ?? 'US';

            // Validate weight
            if ( $weight <= 0 ) {
                if ( $debug_mode ) {
                    $debug_info['ups_rates_error'] = 'Invalid package weight: Weight must be greater than zero.';
                    set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                    lsrwc_log( 'UPS rate request skipped: Invalid package weight.', 'WARNING' );
                }
                return array();
            }

            // Call the UPS rate fetching function with aggregated weight and service code '03' (Ground)
            $rates = lsrwc_fetch_ups_rates( $city, $state, $zip, $country, $weight, $length, $width, $height, '03' );

            $formatted_rates = array();
            foreach ( $rates as $service => $cost ) {
                $formatted_rates[] = array(
                    'id' => sanitize_title( $service ),
                    'label' => $service,
                    'cost' => floatval( str_replace( '$', '', $cost['adjusted'] ) )
                );
            }

            if ( $debug_mode ) {
                $debug_info['ups_fetch_rates'] = "Fetched UPS rates: City=$city, State=$state, ZIP=$zip, Country=$country, Weight=$weight, Rates=" . print_r( $formatted_rates, true );
                set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                lsrwc_log( "Fetched UPS rates: City=$city, State=$state, ZIP=$zip, Country=$country, Weight=$weight, Rates=" . print_r( $formatted_rates, true ) );
            }

            return $formatted_rates;
        }
    }
}

if ( ! class_exists( 'LSRWC_USPS_Shipping_Method' ) ) {
    class LSRWC_USPS_Shipping_Method extends LSRWC_Shipping_Method {
        public function __construct( $instance_id = 0 ) {
            $this->id = 'lsrwc_usps';
            $this->carrier = 'usps';
            $this->method_title = 'USPS Live Rates (Ground Advantage)';
            $this->method_description = 'Fetches live USPS Ground Advantage shipping rates.';
            $this->title = $this->method_title;
            parent::__construct( $instance_id );
        }

        public function init() {
            parent::init();
        }

        public function get_method_title() {
            return 'USPS Live Rates (Ground Advantage)';
        }

        protected function fetch_shipping_rates( $package ) {
            $settings = get_option( 'lsrwc_settings', array() );
            $debug_mode = $settings['debug_mode'] ?? 0;
            $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
            $weight = $this->get_package_weight( $package );
            $length = $this->get_package_dimension( $package, 'length' );
            $width = $this->get_package_dimension( $package, 'width' );
            $height = $this->get_package_dimension( $package, 'height' );
            $city = $package['destination']['city'] ?? '';
            $state = $package['destination']['state'] ?? '';
            $zip = $package['destination']['postcode'] ?? '';

            // Validate weight
            if ( $weight <= 0 ) {
                if ( $debug_mode ) {
                    $debug_info['usps_rates_error'] = 'Invalid package weight: Weight must be greater than zero.';
                    set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                    lsrwc_log( 'USPS rate request skipped: Invalid package weight.', 'WARNING' );
                }
                lsrwc_set_last_notice( 'usps_rates_last_notice', 'Invalid package weight (product weight missing or zero).' );
                return array();
            }

            // Call the USPS rate fetching function with aggregated weight
            $rates = lsrwc_fetch_usps_rates( $city, $state, $zip, $weight, $length, $width, $height );

            $formatted_rates = array();
            foreach ( $rates as $service => $cost ) {
                $formatted_rates[] = array(
                    'id' => sanitize_title( $service ),
                    'label' => $service,
                    'cost' => floatval( str_replace( '$', '', $cost['adjusted'] ) )
                );
            }

            if ( $debug_mode ) {
                $debug_info['usps_fetch_rates'] = "Fetched USPS rates: City=$city, State=$state, ZIP=$zip, Weight=$weight, Rates=" . print_r( $formatted_rates, true );
                set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                lsrwc_log( "Fetched USPS rates: City=$city, State=$state, ZIP=$zip, Weight=$weight, Rates=" . print_r( $formatted_rates, true ) );
            }

            return $formatted_rates;
        }
    }
}

if ( ! class_exists( 'LSRWC_UPS_International_Shipping_Method' ) ) {
    class LSRWC_UPS_International_Shipping_Method extends LSRWC_Shipping_Method {
        public function __construct( $instance_id = 0 ) {
            $this->id = 'lsrwc_ups_international';
            $this->carrier = 'ups';
            $this->method_title = 'UPS Live Rates (International)';
            $this->method_description = 'Fetches live UPS Standard shipping rates for international destinations.';
            $this->title = $this->method_title;
            parent::__construct( $instance_id );
        }

        public function init() {
            parent::init();
        }

        public function get_method_title() {
            return 'UPS Live Rates (International)';
        }

        protected function fetch_shipping_rates( $package ) {
            $settings = get_option( 'lsrwc_settings', array() );
            $debug_mode = $settings['debug_mode'] ?? 0;
            $debug_info = get_transient( 'lsrwc_debug_info' ) ?: array();
            $token = lsrwc_get_ups_access_token();
            if ( ! $token ) {
                lsrwc_log( 'Failed to get UPS access token.', 'ERROR' );
                return array();
            }

            $weight = $this->get_package_weight( $package );
            $length = $this->get_package_dimension( $package, 'length' );
            $width = $this->get_package_dimension( $package, 'width' );
            $height = $this->get_package_dimension( $package, 'height' );
            $city = $package['destination']['city'] ?? '';
            $state = $package['destination']['state'] ?? '';
            $zip = $package['destination']['postcode'] ?? '';
            $country = $package['destination']['country'] ?? 'CA';

            // Validate weight
            if ( $weight <= 0 ) {
                if ( $debug_mode ) {
                    $debug_info['ups_rates_error'] = 'Invalid package weight: Weight must be greater than zero.';
                    set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                    lsrwc_log( 'UPS rate request skipped: Invalid package weight.', 'WARNING' );
                }
                return array();
            }

            // Call the UPS rate fetching function with aggregated weight and service code '11' (Standard)
            $rates = lsrwc_fetch_ups_rates( $city, $state, $zip, $country, $weight, $length, $width, $height, '11' );

            $formatted_rates = array();
            foreach ( $rates as $service => $cost ) {
                $formatted_rates[] = array(
                    'id' => sanitize_title( $service ),
                    'label' => $service,
                    'cost' => floatval( str_replace( '$', '', $cost['adjusted'] ) )
                );
            }

            if ( $debug_mode ) {
                $debug_info['ups_international_fetch_rates'] = "Fetched UPS International rates: City=$city, State=$state, ZIP=$zip, Country=$country, Weight=$weight, Rates=" . print_r( $formatted_rates, true );
                set_transient( 'lsrwc_debug_info', $debug_info, HOUR_IN_SECONDS );
                lsrwc_log( "Fetched UPS International rates: City=$city, State=$state, ZIP=$zip, Country=$country, Weight=$weight, Rates=" . print_r( $formatted_rates, true ) );
            }

            return $formatted_rates;
        }
    }
}
?>
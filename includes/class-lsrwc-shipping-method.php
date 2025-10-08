<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'LSRWC_Shipping_Method' ) ) {
    abstract class LSRWC_Shipping_Method extends WC_Shipping_Method {
        public $carrier;

        public function __construct( $instance_id = 0 ) {
            $this->instance_id = absint( $instance_id );
            $this->supports    = array( 'shipping-zones', 'instance-settings' );
            $this->init();
        }

        public function init() {
            $this->init_settings();
        }

        public function calculate_shipping( $package = array() ) {
            $rates    = $this->fetch_shipping_rates( $package );
            foreach ( $rates as $rate ) {
                $cost  = $rate['cost'];
                $label = $rate['label'];

                // Build WooCommerce rate
                $wc_rate = array(
                    'id'    => $this->id . ':' . sanitize_title( $label ),
                    'label' => $label,
                    'cost'  => max( 0, floatval( $cost ) ),
                    'calc_tax' => 'per_item',
                );

                $this->add_rate( $wc_rate );
            }
        }

        protected function get_package_weight( $package ) {
            $weight = 0;
            if ( ! empty( $package['contents'] ) && is_array( $package['contents'] ) ) {
                foreach ( $package['contents'] as $item ) {
                    if ( isset( $item['data'] ) && method_exists( $item['data'], 'get_weight' ) ) {
                        $item_weight = (float) $item['data']->get_weight();
                        $quantity    = (int) ( $item['quantity'] ?? 1 );
                        $weight     += max( 0, $item_weight ) * max( 1, $quantity );
                    }
                }
            }
            lsrwc_log( array( 'cart_package_weight' => $weight ) );
            return $weight;
        }

        protected function get_package_dimension( $package, $dim ) {
            $value = 0;
            if ( ! empty( $package['contents'] ) && is_array( $package['contents'] ) ) {
                foreach ( $package['contents'] as $item ) {
                    if ( isset( $item['data'] ) ) {
                        switch ( $dim ) {
                            case 'length':
                                $value = max( $value, (float) $item['data']->get_length() );
                                break;
                            case 'width':
                                $value = max( $value, (float) $item['data']->get_width() );
                                break;
                            case 'height':
                                $value = max( $value, (float) $item['data']->get_height() );
                                break;
                        }
                    }
                }
            }
            return $value ?: 12; // sensible default
        }

        abstract protected function fetch_shipping_rates( $package );
    }
}

if ( ! class_exists( 'LSRWC_UPS_Shipping_Method' ) ) {
    class LSRWC_UPS_Shipping_Method extends LSRWC_Shipping_Method {
        public function __construct( $instance_id = 0 ) {
            $this->id                 = 'lsrwc_ups';
            $this->method_title       = 'UPS Live Rates (Ground)';
            $this->method_description = 'Fetches live UPS Ground shipping rates.';
            $this->title              = $this->method_title;
            parent::__construct( $instance_id );
        }

        public function get_method_title() {
            return 'UPS Live Rates (Ground)';
        }

        protected function fetch_shipping_rates( $package ) {
            $settings = get_option( 'lsrwc_settings', array() );
            $weight   = $this->get_package_weight( $package );
            $length   = $this->get_package_dimension( $package, 'length' );
            $width    = $this->get_package_dimension( $package, 'width' );
            $height   = $this->get_package_dimension( $package, 'height' );
            $city     = $package['destination']['city'] ?? '';
            $state    = $package['destination']['state'] ?? '';
            $zip      = $package['destination']['postcode'] ?? '';
            $country  = $package['destination']['country'] ?? 'US';

            if ( $weight <= 0 ) {
                lsrwc_log( 'UPS: invalid weight, skipping rate.' );
                return array();
            }

            // ... perform UPS API request, parse cost ...
            // For brevity, assume $base_cost is the fetched cost
            $base_cost = 10.00;

            $percent = isset( $settings['ups_percentage'] ) ? (float) $settings['ups_percentage'] : 0;
            $cost    = $base_cost * ( 1 + ( $percent / 100 ) );

            return array(
                array(
                    'label' => 'UPS Ground',
                    'cost'  => round( $cost, 2 ),
                ),
            );
        }
    }
}

if ( ! class_exists( 'LSRWC_USPS_Shipping_Method' ) ) {
    class LSRWC_USPS_Shipping_Method extends LSRWC_Shipping_Method {
        public function __construct( $instance_id = 0 ) {
            $this->id                 = 'lsrwc_usps';
            $this->method_title       = 'USPS Live Rates (Ground Advantage)';
            $this->method_description = 'Fetches live USPS Ground Advantage shipping rates.';
            $this->title              = $this->method_title;
            parent::__construct( $instance_id );
        }

        public function get_method_title() {
            return 'USPS Live Rates (Ground Advantage)';
        }

        protected function fetch_shipping_rates( $package ) {
            $settings = get_option( 'lsrwc_settings', array() );
            $weight   = $this->get_package_weight( $package );
            $length   = $this->get_package_dimension( $package, 'length' );
            $width    = $this->get_package_dimension( $package, 'width' );
            $height   = $this->get_package_dimension( $package, 'height' );
            $city     = $package['destination']['city'] ?? '';
            $state    = $package['destination']['state'] ?? '';
            $zip      = $package['destination']['postcode'] ?? '';

            if ( $weight <= 0 ) {
                lsrwc_log( 'USPS: invalid weight, skipping rate.' );
                return array();
            }

            // ... perform USPS API request, parse cost ...
            // For brevity, assume $base_cost is the fetched cost
            $base_cost = 8.50;

            $percent = isset( $settings['usps_percentage'] ) ? (float) $settings['usps_percentage'] : 0;
            $cost    = $base_cost * ( 1 + ( $percent / 100 ) );

            return array(
                array(
                    'label' => 'USPS Ground Advantage',
                    'cost'  => round( $cost, 2 ),
                ),
            );
        }
    }
}

if ( ! class_exists( 'LSRWC_UPS_International_Shipping_Method' ) ) {
    class LSRWC_UPS_International_Shipping_Method extends LSRWC_Shipping_Method {
        public function __construct( $instance_id = 0 ) {
            $this->id                 = 'lsrwc_ups_international';
            $this->method_title       = 'UPS International (Canada)';
            $this->method_description = 'Fetches live UPS international rates for Canada.';
            $this->title              = $this->method_title;
            parent::__construct( $instance_id );
        }

        public function get_method_title() {
            return 'UPS International (Canada)';
        }

        protected function fetch_shipping_rates( $package ) {
            $settings = get_option( 'lsrwc_settings', array() );
            $weight   = $this->get_package_weight( $package );

            if ( $weight <= 0 ) {
                lsrwc_log( 'UPS INTL: invalid weight, skipping rate.' );
                return array();
            }

            // ... perform UPS INTL API request, parse cost ...
            $base_cost = 25.00;

            $cost = $base_cost; // adjust as needed

            return array(
                array(
                    'label' => 'UPS Standard to Canada',
                    'cost'  => round( $cost, 2 ),
                ),
            );
        }
    }
}

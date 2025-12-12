# Live Shipping Rates for WooCommerce

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/live-shipping-rates-for-woocommerce)](https://wordpress.org/plugins/live-shipping-rates-for-woocommerce/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/rating/live-shipping-rates-for-woocommerce)](https://wordpress.org/plugins/live-shipping-rates-for-woocommerce/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/live-shipping-rates-for-woocommerce)](https://wordpress.org/plugins/live-shipping-rates-for-woocommerce/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Contributors:** xboxhacker  
**Tags:** woocommerce, shipping, ups, usps, live-rates, oauth, api  
**Requires at least:** 5.0  
**Tested up to:** 6.6  
**Stable tag:** 1.1.22  
**Requires PHP:** 7.2  
**License:** GPL v2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

The Live Shipping Rates for WooCommerce plugin integrates real-time shipping rates from UPS and USPS directly into your WooCommerce store using OAuth 2.0 authentication.

## Description

Live Shipping Rates for WooCommerce seamlessly fetches live shipping rates from UPS (Ground) and USPS (Ground Advantage) carriers directly within your WooCommerce cart and checkout pages. The plugin features OAuth 2.0 authentication for secure API connections, intelligent conditional logic based on cart contents, and comprehensive debugging tools.

### Key Features

- **Real-Time Rate Calculation**: Fetches live shipping rates from UPS Ground and USPS Ground Advantage APIs
- **OAuth 2.0 Authentication**: Secure token-based authentication for both UPS and USPS APIs
- **Smart Shipping Logic**: Automatically prioritizes UPS when cart contains UPS-only items, falls back to USPS otherwise
- **Free Shipping Support**: Honors WooCommerce free-shipping coupons and shipping classes
- **GitHub Auto-Updates**: Optional automatic updates from GitHub releases
- **Admin Testing Interface**: Built-in rate testing with custom parameters
- **Comprehensive Debugging**: Detailed logging with severity levels and debug transients
- **Shipping Zone Integration**: Full WooCommerce shipping zone compatibility
- **Dynamic Checkout Updates**: Real-time shipping rate updates as customers modify addresses
- **Weight & Dimension Aggregation**: Accurate package calculations excluding free shipping items

### Use Cases

- **E-commerce Stores**: Perfect for online retailers needing accurate shipping costs
- **Multi-Carrier Shipping**: Support for UPS and USPS with intelligent routing
- **Veeqo Integration**: Designed for seamless integration with Veeqo shipping workflows
- **Complex Shipping Rules**: Handle mixed carts with different shipping requirements

## Installation

### Automatic Installation

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "Live Shipping Rates for WooCommerce"
3. Click **Install Now** and **Activate**

### Manual Installation

1. **Download the Plugin**:
   - Download the latest release from [GitHub](https://github.com/xboxhacker/live-shipping-rates-for-woocommerce/releases)

2. **Upload to WordPress**:
   - Navigate to **Plugins > Add New > Upload Plugin**
   - Select the downloaded ZIP file and click **Install Now**

3. **Alternative Upload**:
   - Extract the plugin folder to `/wp-content/plugins/live-shipping-rates-for-woocommerce/`

4. **Activate the Plugin**:
   - Go to **Plugins > Installed Plugins**
   - Find "Live Shipping Rates for WooCommerce" and click **Activate**

5. **Set File Permissions** (if manually uploaded):
   ```bash
   chmod 644 /path/to/wp-content/plugins/live-shipping-rates-for-woocommerce/*.php
   chmod 644 /path/to/wp-content/plugins/live-shipping-rates-for-woocommerce/*.css
   chmod 644 /path/to/wp-content/plugins/live-shipping-rates-for-woocommerce/*.js
   chmod 644 /path/to/wp-content/plugins/live-shipping-rates-for-woocommerce/includes/*.php
   chmod 755 /path/to/wp-content/plugins/live-shipping-rates-for-woocommerce/includes
   ```

## Configuration

### API Credentials Setup

#### UPS Configuration
1. Visit the [UPS Developer Portal](https://developer.ups.com/)
2. Create an account and register your application
3. Obtain your **Client ID**, **Client Secret**, and **Account Number**

#### USPS Configuration
1. Visit the [USPS Shipping API Portal](https://www.usps.com/business/web-tools-apis/)
2. Register for API access
3. Obtain your **Consumer Key** and **Consumer Secret**

### Plugin Settings

1. Navigate to **Live Shipping Rates > Settings** in your WordPress admin
2. Configure the following required fields:

#### Required Settings
- **UPS Client ID**: Your UPS OAuth client ID
- **UPS Client Secret**: Your UPS OAuth client secret
- **UPS Account Number**: Your UPS account number for rate calculations
- **USPS Consumer Key**: Your USPS API consumer key
- **USPS Consumer Secret**: Your USPS API consumer secret
- **Origin ZIP Code**: Your store's shipping origin ZIP code
- **Origin City**: Your store's shipping origin city
- **Origin State**: Your store's shipping origin state (2-letter code)
- **Origin Address Line 1**: Your store's shipping origin street address

#### Optional Settings
- **UPS Percentage Increase (%)**: Markup percentage for UPS rates (default: 0)
- **UPS International Percentage Increase (%)**: Additional markup for international UPS rates (default: 0)
- **USPS Percentage Increase (%)**: Markup percentage for USPS rates (default: 0)
- **UPS Shipping Class Slug**: Slug for products requiring UPS shipping (default: `ups-shipping`)
- **USPS Shipping Class Slug**: Slug for products requiring USPS shipping (default: `usps-shipping`)
- **Free Shipping Class Slug**: Slug for free shipping products (default: `free-shipping`)
- **Enable Extensive Debugging**: Enable detailed logging (default: disabled)
- **Enable GitHub Auto Updates**: Enable automatic updates from GitHub (default: disabled)
- **GitHub Repository**: Repository in `owner/repo` format (default: `xboxhacker/live-shipping-rates-for-woocommerce`)
- **GitHub Access Token**: Personal access token for private repos or higher rate limits (optional)

### Shipping Zone Configuration

1. **Access Shipping Zones**:
   - Go to **WooCommerce > Settings > Shipping > Shipping Zones**

2. **Add Shipping Methods**:
   - Click on a shipping zone (e.g., "Domestic USA") or create a new one
   - Click **Add Shipping Method**
   - Select **UPS Live Rates (Ground)** for UPS shipping
   - Select **USPS Live Rates (Ground Advantage)** for USPS shipping
   - Configure instance settings if needed

3. **Zone Coverage**:
   - Ensure zones cover all your customer locations
   - Methods appear as "UPS Live Rates (Ground)" and "USPS Live Rates (Ground Advantage)"

### Product Configuration

1. **Create Shipping Classes**:
   - Go to **WooCommerce > Settings > Shipping > Shipping Classes**
   - Create classes like:
     - "UPS Shipping" with slug `ups-shipping`
     - "USPS Shipping" with slug `usps-shipping`
     - "Free Shipping" with slug `free-shipping`

2. **Assign to Products**:
   - Edit individual products via **Products > All Products**
   - In the **Shipping** tab, assign the appropriate shipping class
   - Ensure products have accurate **Weight** and **Dimensions** set

### GitHub Auto-Updates

1. **Enable Auto-Updates**:
   - In plugin settings, check **Enable GitHub Auto Updates**
   - Enter repository as `owner/repository` (leave blank for default)

2. **Access Token (Optional)**:
   - Generate a Personal Access Token in your GitHub settings
   - Paste the token to avoid rate limits or access private repositories

3. **Update Process**:
   - Visit **Dashboard > Updates**
   - Click **Check Again** to poll for new releases
   - WordPress will automatically download and install tagged releases

## Usage

### Testing Shipping Rates

1. **Access Test Interface**:
   - Go to **Live Shipping Rates > Test Live Rates**

2. **Configure Test Parameters**:
   - **City**: Destination city (e.g., "New York")
   - **State**: Destination state (e.g., "NY")
   - **ZIP**: Destination ZIP code (e.g., "10001")
   - **Weight**: Package weight in lbs (e.g., "5.5")
   - **Length**: Package length in inches (e.g., "12")
   - **Width**: Package width in inches (e.g., "8")
   - **Height**: Package height in inches (e.g., "6")

3. **Run Test**:
   - Click **Get Rates** to fetch live rates
   - View results in the interface
   - Check debug logs for detailed API interactions

### Cart and Checkout Behavior

#### Weight Calculation
- Plugin aggregates weights from all cart items
- Multiplies individual weights by quantities
- Example: 2 × 2lb items + 1 × 3lb item = 7lb total

#### Conditional Shipping Logic
- **UPS Priority**: If any cart item has UPS shipping class, only UPS rates display
- **USPS Fallback**: If no UPS items, both UPS and USPS rates available
- **Free Shipping**: Carrier APIs bypassed when free shipping applies

#### Dynamic Updates
- Shipping rates update automatically as customers change addresses
- Debounced updates prevent excessive API calls
- Cache management ensures fresh rate calculations

### Debugging and Monitoring

#### Enable Debugging
```php
// Add to wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

#### Debug Locations
- **Plugin Debug Section**: View in **Live Shipping Rates > Settings > Debug Information**
- **WordPress Debug Log**: Located at `/wp-content/debug.log`
- **Plugin Log File**: Located at `/wp-content/lsrwc_debug.log`

#### Debug Information Includes
- API request/response details
- Package weight and dimension calculations
- Shipping class detection
- Rate filtering logic
- Token caching status

#### Clearing Debug Data
- Use the **Clear Debug** button in plugin settings
- Clears cached tokens and debug transients
- Resets shipping rate caches

## API Reference

### Helper Functions

#### Free Shipping Detection
```php
lsrwc_cart_has_free_shipping_coupon() // Check for active free shipping coupons
lsrwc_package_has_free_shipping_class($package) // Check if package qualifies for free shipping
lsrwc_package_has_chargeable_items($package) // Check if package has items requiring carrier shipping
```

#### Shipping Class Helpers
```php
lsrwc_get_free_shipping_class_slug() // Get configured free shipping class slug
```

#### Logging Functions
```php
lsrwc_log($message, $severity, $context) // Log with severity levels (INFO, WARNING, ERROR)
lsrwc_set_last_notice($message, $type) // Set admin notices for user feedback
```

#### GitHub Integration
```php
lsrwc_parse_github_repository($repo_string) // Parse owner/repo format
lsrwc_fetch_latest_github_release($owner, $repo, $token) // Fetch latest release info
lsrwc_check_github_update($transient) // WordPress update check hook
lsrwc_github_plugin_information($result, $action, $args) // Plugin info hook
```

### Filters and Hooks

#### Shipping Filters
- `woocommerce_package_rates` - Filter final shipping rates
- `woocommerce_package_rates` - Main rate filtering hook
- `woocommerce_shipping_packages` - Modify shipping packages

#### Checkout Hooks
- `woocommerce_checkout_update_order_review` - Force rate recalculation
- `woocommerce_before_checkout_form` - Clear shipping cache

#### Admin Hooks
- `admin_enqueue_scripts` - Enqueue admin assets
- `wp_enqueue_scripts` - Enqueue frontend assets

## Troubleshooting

### Common Issues

#### "No rates available" errors
- **Cause**: API credentials incorrect or expired
- **Solution**: Verify API keys in plugin settings, check token validity

#### Only UPS rates showing
- **Cause**: Products incorrectly assigned UPS shipping class
- **Solution**: Check product shipping classes, verify slug configuration

#### Rates not updating on checkout
- **Cause**: JavaScript conflicts or caching issues
- **Solution**: Clear browser cache, check for JavaScript errors

#### API rate limits exceeded
- **Cause**: Too many API calls, missing access token
- **Solution**: Add GitHub access token, implement caching

### Debug Checklist

1. **Enable Debugging**: Check "Enable Extensive Debugging" in settings
2. **Check Logs**: Review debug information in plugin settings
3. **Verify Credentials**: Ensure API keys are correct and active
4. **Test API Access**: Use "Test Live Rates" interface
5. **Check Product Setup**: Verify weights, dimensions, and shipping classes
6. **Review Shipping Zones**: Confirm methods added to correct zones

### Error Messages

#### USPS Errors
- `INVALID_CONSUMER_KEY`: Check USPS Consumer Key
- `INVALID_CONSUMER_SECRET`: Verify USPS Consumer Secret
- `PROCESSING_CATEGORY_INVALID`: Review package dimensions and weight

#### UPS Errors
- `Invalid Client ID`: Verify UPS Client ID
- `Invalid Client Secret`: Check UPS Client Secret
- `Invalid Account Number`: Confirm UPS Account Number

### Performance Optimization

- **Caching**: API responses cached for 1 hour
- **Debouncing**: Checkout updates debounced to reduce API calls
- **Conditional Logic**: Carrier APIs only called when necessary
- **Background Processing**: Consider moving heavy calculations to background

## Frequently Asked Questions

### General Questions

**Q: Do I need separate accounts for UPS and USPS?**  
A: Yes, you need active developer accounts with both carriers to obtain API credentials.

**Q: Can I use this plugin with other shipping carriers?**  
A: Currently supports only UPS and USPS. Additional carriers may be added in future versions.

**Q: Does the plugin handle international shipping?**  
A: Yes, both UPS and USPS support international rates. Configure appropriate shipping zones.

### Technical Questions

**Q: How does the plugin handle package dimensions?**  
A: Uses maximum dimensions across all items, excluding free shipping products.

**Q: Can I modify the shipping rate markup?**  
A: Yes, configure percentage increases in plugin settings for each carrier.

**Q: Does the plugin support multiple packages?**  
A: Currently processes as single package. Multi-package support may be added later.

### Configuration Questions

**Q: What if I don't want to use shipping classes?**  
A: Leave class slugs blank. Plugin will show both carrier rates for all products.

**Q: Can I disable one carrier?**  
A: Don't add that carrier's method to shipping zones, or leave API credentials blank.

**Q: How do I set up free shipping?**  
A: Create shipping class with configured free shipping slug, or use WooCommerce coupons.

## Screenshots

1. **Settings Page** - Configure API credentials and plugin options
2. **Test Rates Interface** - Test shipping rates with custom parameters
3. **Debug Information** - View detailed API logs and debug data
4. **Shipping Zone Configuration** - Add methods to WooCommerce zones
5. **Product Shipping Setup** - Configure shipping classes and dimensions

## Changelog

### 1.1.22 - December 12, 2025
- Added plugin version bump and repository metadata for WordPress update compatibility
- Exposed GitHub Plugin URI in plugin header for third-party updater support
- Polished README documentation with comprehensive setup instructions

### 1.1.21 - December 12, 2025
- Added optional GitHub auto-update support with repository configuration
- Integrated WordPress update hooks for automatic release polling
- Implemented GitHub API response caching and basic update logging
- Added Personal Access Token support for private repositories and rate limit avoidance

### 1.1.20 - December 3, 2025
- Added native WooCommerce free-shipping coupon support
- Implemented automatic carrier bypass when free shipping coupons active
- Refactored free-shipping logic into reusable helper functions
- Enhanced documentation with free shipping workflow details

### 1.1.19 - November 15, 2025
- Improved USPS processing category calculation for better rate accuracy
- Enhanced error handling and logging for API failures
- Added debug transient storage for better troubleshooting
- Fixed shipping class detection and logging

### 1.1.18 - October 20, 2025
- Added UPS priority logic for mixed cart scenarios
- Implemented shipping class-based rate filtering
- Enhanced debug logging with contextual information
- Improved checkout shipping recalculation reliability

### 1.1.17 - September 10, 2025
- Initial public release with UPS and USPS OAuth integration
- Basic admin interface for settings and testing
- Shipping zone integration and rate calculation
- Comprehensive debugging and logging system

## Upgrade Notice

### 1.1.22
This version adds GitHub auto-update support and improves documentation. Update for better maintenance workflow.

### 1.1.21
Adds GitHub integration for automatic updates. Requires GitHub repository access for full functionality.

### 1.1.20
Adds free shipping coupon support. Update to ensure proper handling of WooCommerce coupons.

## Contributing

We welcome contributions to improve the Live Shipping Rates for WooCommerce plugin!

### Development Setup
1. Fork the repository on GitHub
2. Clone your fork locally
3. Install development dependencies
4. Create a feature branch
5. Make your changes
6. Test thoroughly
7. Submit a pull request

### Coding Standards
- Follow WordPress Coding Standards
- Use PHP 7.2+ compatible syntax
- Include comprehensive documentation
- Add unit tests for new features

### Reporting Issues
- Use GitHub Issues for bug reports
- Include detailed steps to reproduce
- Provide debug logs when possible
- Specify WordPress and WooCommerce versions

## Support

### Documentation
- Complete setup guide available in this README
- API reference with all helper functions
- Troubleshooting section with common solutions

### Community Support
- GitHub Issues for technical questions
- WordPress.org forums for general discussion
- Plugin review system for feedback

### Professional Support
For custom development, priority support, or consulting services, contact the plugin author.

## License

This plugin is licensed under the GNU General Public License v2 (GPL2) or later.

```
Live Shipping Rates for WooCommerce
Copyright (C) 2025, William Hare

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
```

## Credits

**Plugin Author**: William Hare  
**GitHub**: [@xboxhacker](https://github.com/xboxhacker)  
**Contributors**: Community contributors welcome!

### Third-Party Libraries
- WordPress Core APIs
- WooCommerce Framework
- UPS OAuth API
- USPS Web Tools API

### Acknowledgments
- Thanks to the WooCommerce community for framework support
- UPS and USPS for API access and documentation
- WordPress.org for plugin hosting and review system


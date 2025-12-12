# Live Shipping Rates for WooCommerce
 

## Overview

The Live Shipping Rates for WooCommerce plugin allows online stores to fetch real-time shipping rates from UPS and USPS directly within the WooCommerce cart and checkout pages. It supports OAuth 2.0 authentication for secure API access, includes a user-friendly admin interface for testing rates, and provides extensive debugging tools. The plugin also features conditional logic to display shipping methods based on cart contents, ensuring a seamless customer experience.

I used this for integration with Veeqo shipping. This plugin gets the rates, then orders are sent to Veeqo, with shipping rates, for shipment.

## Features

- **Real-Time Rates**: Fetches live shipping rates from UPS (Ground) and USPS (Ground Advantage) using their respective APIs.
- **OAuth 2.0 Authentication**: Securely connects to UPS and USPS APIs with token-based authentication.
- **Conditional Shipping Logic**: Automatically prioritizes UPS whenever any cart item requires it, falling back to USPS only when no UPS-only products remain.
- **Free Shipping Coupons & Classes**: Honors WooCommerce free-shipping coupons and shipping classes, automatically short-circuiting carrier calls when every item qualifies for free shipping.
- **GitHub Auto Updates**: Optional toggle that polls the configured GitHub repository for the latest tagged release and installs it directly from the WordPress dashboard.
- **Total Cart Weight Calculation**: Aggregates the weight of all cart items (including quantities) for accurate rate fetching.
- **Admin Testing Interface**: Allows administrators to test live rates with custom inputs (city, state, ZIP, weight, dimensions).
- **Debugging Tools**: Provides detailed debug logs for API requests and responses, with a "Clear Debug" button to reset logs.
- **Shipping Zone Integration**: Seamlessly integrates with WooCommerce shipping zones for flexible rate configuration.
- **Customizable Slugs**: Supports custom shipping class slugs to control which products trigger UPS or USPS rates.

## Installation

1. **Download the Plugin**:
   - Obtain the plugin files (e.g., from GitHub or a ZIP archive).

2. **Upload to WordPress**:
   - Navigate to **Plugins > Add New** in your WordPress admin panel.
   - Click **Upload Plugin** and select the plugin ZIP file.
   - Alternatively, extract the plugin folder to `/wp-content/plugins/live-shipping-rates-for-woocommerce/`.

3. **Activate the Plugin**:
   - Go to **Plugins > Installed Plugins** and activate "Live Shipping Rates for WooCommerce".

4. **Set File Permissions** (if manually uploaded):
   ```bash
   chmod 644 /path/to/wp-content/plugins/live-shipping-rates-for-woocommerce/*.php
   chmod 644 /path/to/wp-content/plugins/live-shipping-rates-for-woocommerce/*.css
   chmod 644 /path/to/wp-content/plugins/live-shipping-rates-for-woocommerce/*.js
   chmod 644 /path/to/wp-content/plugins/live-shipping-rates-for-woocommerce/includes/*.php
   chmod 755 /path/to/wp-content/plugins/live-shipping-rates-for-woocommerce/includes
   ```

## Configuration

### Plugin Settings
1. Navigate to **Live Shipping Rates > Settings** in the WordPress admin panel.
2. Configure the following fields:
   - **UPS Client ID**, **UPS Client Secret**, **UPS Account Number**: Obtain from the UPS Developer Portal.
   - **USPS Consumer Key**, **USPS Consumer Secret**: Obtain from the USPS Shipping API portal.
   - **Origin ZIP Code**, **Origin City**, **Origin State**, **Origin Address Line 1**: Set your store’s shipping origin details (e.g., `33915`, `ANY TOWN`, `FL`, `555 Main St.`).
   - **UPS/USPS Percentage Increase (%)**: Optional markup for shipping rates.
   - **UPS Shipping Class Slug**, **USPS Shipping Class Slug**: Match the slugs defined in WooCommerce (e.g., `ups-shipping`, `usps-shipping`).
   - **Enable Extensive Debugging**: Check to log detailed API requests/responses.
   - **GitHub Auto Updates**: Enable the toggle, enter the repository in `owner/repo` format, and optionally paste a Personal Access Token for private repositories or higher API limits.

### Shipping Zones
The plugin integrates with WooCommerce’s shipping zones to control where and how rates are offered.

1. **Access Shipping Zones**:
   - Go to **WooCommerce > Settings > Shipping > Shipping Zones**.

2. **Add Shipping Methods**:
   - Add or edit a shipping zone (e.g., "Domestic USA").
   - Click **Add Shipping Method** and select:
     - **UPS Live Rates (Ground)** for UPS Ground rates.
     - **USPS Live Rates (Ground Advantage)** for USPS Ground Advantage rates.
   - Ensure both methods are added to the zones covering your customers’ locations.

3. **Verify Visibility**:
   - The methods appear as "UPS Live Rates (Ground)" and "USPS Live Rates (Ground Advantage)" in the zone settings, making them easy to identify.

### Product Configuration
1. **Set Shipping Classes**:
   - Go to **WooCommerce > Settings > Shipping > Shipping Classes**.
   - Create or verify classes (e.g., "UPS Shipping" with slug `ups-shipping`, "USPS Shipping" with slug `usps-shipping`).

2. **Assign to Products**:
   - Edit products via **Products > All Products**.
   - In the **Shipping** tab, assign the appropriate shipping class (e.g., `ups-shipping` for UPS-only items).
   - Set valid weights and dimensions for accurate rate calculations.

### GitHub Auto Updates
1. Open **Live Shipping Rates > Settings** and enable **GitHub Auto Updates**.
2. Enter the repository in `owner/repository` format (default is `xboxhacker/live-shipping-rates-for-woocommerce`, so you can leave the field blank unless you are using a fork).
3. (Optional) Paste a GitHub Personal Access Token if the repository is private or if you want to avoid GitHub’s anonymous rate limits.
4. Save the settings, then visit **Dashboard > Updates** and click “Check Again.” WordPress will download and install the latest tagged release from GitHub whenever a newer version is available.

## Usage

### Testing Live Rates
1. Go to **Live Shipping Rates > Test Live Rates** in the admin panel.
2. Enter test inputs (e.g., City: `ANY TOWN`, State: `FL`, ZIP: `33915`, Weight: `12`, Length: `12`, Width: `12`, Height: `12`).
3. Click **Get Rates** to view UPS and USPS rates.
4. Check the **Debug Information** section in **Settings** for API request/response logs.

### Cart and Checkout
- **Weight Aggregation**: The plugin sums the weights of all cart items (e.g., 2 lbs * 2 + 3 lbs * 1 = 7 lbs) for rate fetching.
- **Conditional Logic**:
  - If any cart item has the `ups-shipping` class, only UPS Ground rates are shown.
  - Otherwise, both UPS Ground and USPS Ground Advantage rates are displayed.
- Rates update dynamically based on the customer’s shipping address and cart contents.

### Debugging
- Enable debugging in `wp-config.php`:
  ```php
  define( 'WP_DEBUG', true );
  define( 'WP_DEBUG_LOG', true );
  define( 'WP_DEBUG_DISPLAY', false );
  ```
- View logs in `/wp-content/debug.log` or the **Debug Information** section in the plugin settings.
- Use the **Clear Debug** button to reset debug data and cached tokens.

## Shipping Zones

Shipping zones are managed through WooCommerce’s built-in system, allowing you to define where and how shipping methods are offered.

- **Configuration**:
  - Each zone can include multiple shipping methods (e.g., both UPS and USPS).
  - Add "UPS Live Rates (Ground)" and "USPS Live Rates (Ground Advantage)" to relevant zones (e.g., "Domestic USA" for US customers).
- **Visibility**:
  - The plugin uses descriptive titles to ensure clarity in the WooCommerce settings interface.
  - Example: In a zone named "Domestic USA", you’ll see "UPS Live Rates (Ground)" and "USPS Live Rates (Ground Advantage)" listed as available methods.
- **Behavior**:
  - Rates are fetched only for zones that match the customer’s shipping address.
  - The plugin’s conditional logic ensures that UPS rates take precedence if any cart item requires UPS shipping.

## Troubleshooting

- **Rates Not Displaying**:
  - Verify that products have valid weights and dimensions in **Products > Edit Product > Shipping**.
  - Ensure shipping class slugs match those in **Live Shipping Rates > Settings**.
  - Check that the correct shipping methods are added to the relevant shipping zones.
- **Only UPS Rates Showing**:
  - Confirm no cart items have the `ups-shipping` class unless intended.
- **Debugging**:
  - Enable **Extensive Debugging** in plugin settings.
  - Review logs in **Live Shipping Rates > Settings > Debug Information** or `/wp-content/debug.log`.
  - Look for errors in `ups_rate_response`, `usps_rate_response`, or `cart_package_weight`.

## Support

For issues or feature requests, contact the authors via the plugin’s support channel or repository. Provide debug logs and detailed descriptions to expedite resolution.

## License

This plugin is licensed under the GNU General Public License v2 (GPL2). See the license file for details.

## Changelog

### 1.1.22 - 2025-12-12
- Added the plugin version bump and repository metadata so WordPress aligns with the latest GitHub release tag.
- Exposed the GitHub Plugin URI in the plugin header for compatibility with third-party updaters.
- Polished README instructions covering auto-update configuration and defaults.

### 1.1.21 - 2025-12-12
- Added optional GitHub auto-update support, including settings for enabling updates, selecting the repository, and providing an access token.
- Integrated release polling with the WordPress update screen and documented the workflow in this README.
- Cached GitHub responses and surfaced basic logging to help debug update checks.

### 1.1.20 - 2025-12-03
- Added native support for WooCommerce free-shipping coupons so carrier rates are suppressed and a single free rate is returned when coupons are applied.
- Refactored the free-shipping logic into reusable helpers to ensure consistent behavior between coupon-driven and shipping-class-driven scenarios.
- Documented the free-shipping workflow in the feature list.

## Quick Start - Pay with Amazon for Magento

[View the more detailed User Guide here.](https://github.com/paywithamazon/amazon-payments-magento-plugin/raw/gh-pages/resources/Pay%20with%20Amazon%20for%20Magento%20User%20Guide.pdf)


### Pre-Requisites
* Magento CE 1.5-1.9 or EE 1.12+.
* SSL is installed on your site and active on Checkout and Login pages


### Installation
* Click the Download Zip button and save to your local machine
* Transfer the zip file to your Magento webserver
* Unpack the archive in the root directory of your Magento instance
* Flush your Magento caches
    * In the admin page for your Magento instance, navigate to System->Cache Management
    * Click the 'Flush Magento Cache'
    * More information on Magento Cache Management [here](http://www.magentocommerce.com/knowledge-base/entry/cache-storage-management)
* Log out of the admin page and then log back in to ensure activation of the module


### Configure Magento
* The plugin is configured under "System" (top-nav) -> "Configuration" (menu) -> "Payment Methods" (left nav) -> "Amazon Payments" (main page).
* If you haven't already registered, use the link in the module to create an Amazon Payments account.
* Apply your Amazon account keys, shown during registration or find them in Seller Central:
    * Merchant/Seller ID
       * Switch to "Amazon Payments Advanced" in the top nav
       * Navigate to "Settings" (top-nav) -> "Integration Settings" (menu)
    * API Access and Secret Key
       * Navigate to "Integration" (top-nav) -> "MWS Access Keys" (menu)
    * Client and Secret Key (under "Login with Amazon")
       * Switch to "Login with Amazon" in the top-nav
       * Click "Register a New Application" if you haven't done so already
       * Find the client and secret key under "Web Settings"


### Configure Amazon Payments
 * Allow your site to use your Login with Amazon account:
    * Switch to "Login with Amazon" in the top-nav
    * Add any domains your site is hosted on under "Allowed Javascript Origins" (i.e., https://www.yourdomain.com, https://yourdomain.com)
 * Add your store logo to the Amazon Login screen
    * Upload a 150x150 image under "Application Information" -> "Logo Image"
 * Complete your store setup
    * Switch to "Amazon Payments Advanced" in the top nav
    * Navigate to "Settings" (top-nav) -> "Account Info" (menu)
    * Complete the "Seller Information" information which is used in Buyer-facing communication
    * Complete the tax interview under "Legal Entity"
    * Add your bank account for disbursements under "Deposit Information"

## Release Notes
0.9.1-beta
* Added configuration option to modify intial order status.

0.9.0-beta
* Initial version.

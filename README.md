![Payer Logotype](http://payer.se/public/PAYER_SUSTAINABLE_PAYMENTS_LOGO.png)


# Payer Magento 2 Module

## Quickstart

1. Go to Magento2 root folder

2. Enter following commands to install module:

    ```bash
    composer require payer/magento2
    ```
   Wait while dependencies are updated.

3. Enter following commands to enable module:

    ```bash
    php bin/magento module:enable Payer_Checkout --clear-static-content
    php bin/magento setup:upgrade
    ```
4. Enable and configure Payer Checkout in Magento Admin under Stores/Configuration/Payment Methods/Payer Checkout

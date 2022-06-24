# CRM Wallet Pay module

This module provides integration of the Apple Pay and Google Pay payment gateways into the sales funnels.

Currently the module supports _gateway_ implementation using Tatrabanka provider. This provider currently does not support recurrent payments.

## Installation

To install the module, run:

```bash
composer require remp/crm-wallet-pay-module
```

Enable installed extension in your `app/config/config.neon` file:

```neon
extensions:
	# ...
	- Crm\WalletPayModule\DI\WalletPayModuleExtension
```

To have the module functionality available in sales funnels, copy assets by running this command in the application root folder (or run `composer install`, which install assets as well):

```bash
# run in CRM root folder
php bin/command.php application:install_assets 
```

## Integration

### Apple Pay button

#### Configuration

In CRM settings (`crm.press/admin/config-admin/`) _Payments_ section, configure Apple Pay options. These include paths to Apple Pay merchant ID certificate and key (including password, if encrypted).
For more information, consult the official documentation on how to [Set Up Apple Pay](https://developer.apple.com/documentation/passkit/apple_pay/setting_up_apple_pay). 

#### Requirements

Apple Pay requires HTTPS webpage with valid TLS certificate.

#### Usage in sales funnel

First, add "ApplePay Wallet" payment gateway to the list of allowed gateways in the sales funnel settings in CRM admin.

Next, include the WalletPay JS library in the sales funnel `<head>` tag (if not already included):
```html
<script src="/layouts/wallet-pay-module/js/wallet-pay.js"></script> 
```

To display the button itself, insert the following code snippet somewhere in your HTML document. Note the `hidden` class - button should be hidden at first, before we check Apple Pay availability. 

```html
<apple-pay-button  buttonstyle="black" type="buy" locale="sk_SK"
  class="hidden" 
  style="--apple-pay-button-width: 100%;
    --apple-pay-button-height: 40px;
    --apple-pay-button-border-radius: 5px;
    --apple-pay-button-padding: 5px 0px;">
</apple-pay-button>
```
Properties of the button are described in the [official documentation](https://developer.apple.com/documentation/apple_pay_on_the_web/displaying_apple_pay_buttons_using_css), including the [CSS styling](https://developer.apple.com/documentation/apple_pay_on_the_web/displaying_apple_pay_buttons_using_css/styling_the_apple_pay_button_using_css).

Next, make sure to check Apple Pay is available in the current browser context:

```js
// MERCHANT_ID is ID assigned in the Apple Pay settings
RempWalletPay.checkApplePayAvailability(MERCHANT_ID, function () {
    // called only if button should be displayed
    // remove 'hidden' class to display the button
    document.getElementsByTagName('apple-pay-button')[0].classList.remove('hidden');
});
```
To initialize the button, run the `initApplePayButton`. The most basic configuration:

```js
const config = {
  totalPrice: '0.10',
  merchantName: 'Example Merchant s.r.o',
  productName: 'Example Product Name',

  // Function to retrieve sales funnel payment form data (will be sent together with Apple Pay token to backend).
  // Library takes care of response processing and redirect.
  salesFunnelFormData: () => {
    return new FormData(document.getElementById('form'));
  },

  // Optional, function returning true/false depending on whether Apple Pay execution should continue after clicking on the button.
  isValid: () => true, 
};
RempWalletPay.initApplePayButton(config);
```

Alternatively, you can provide `onSuccess` callback, which gives you control of processing of the Apple Pay token issued during the payment:

```js
const config = {
    totalPrice: '0.10',
    merchantName: 'Example Merchant s.r.o',
    productName: 'Example Product Name',

    // Callback, called after user confirms the payment in the Apple modal (e.g. by fingerprint).
    // It receives two arguments:
    // - token - Apple Pay token
    // - completePaymentCallback - callback, should be called after backend (un/successfully) ackowledges the payment 
    onSuccess: (token, completePaymentCallback) =>  {
        var fd = new FormData(document.getElementById('form'));
        fd.append('apple_pay_token', token);
          
        fetch('/sales-funnel/sales-funnel-frontend/submit', {
            method: 'POST',
            body: fd,
        })
            .then(response => response.json())
            .then(data => {
                completePaymentCallback(true);
                window.top.location.href = data.apple_pay.redirect_url; // redirect to success page
            })
            .catch(e => completePaymentCallback(false));
    },
    
    // optional, function returning true/false depending on whether Apple Pay execution should continue after clicking on the Pay button.
    isValid: () => some_check_to_see_if_payment_form_is_valid(),
};
RempWalletPay.initApplePayButton(config);
```

#### Pay button update

To update price or product name of the button, call:

```js
RempWalletPay.updateApplePayButton({
    totalPrice: '0.20', // new price
    // productName: 'new product name',
});
```

### Google Pay

#### Configuration

To set up Google Pay and obtain required credentials, please follow the official documentation on [Google Pay for web](https://developers.google.com/pay/api/web/guides/setup).

#### Requirements

Google Pay requires HTTPS webpage with valid TLS certificate. 

#### Usage in sales funnel

First, add "GooglePay Wallet" payment gateway to the list of allowed gateways in the sales funnel settings in CRM admin.

Next, include the WalletPay JS library in the sales funnel `<head>` tag (if not already included):
```html
<script src="/layouts/wallet-pay-module/js/wallet-pay.js"></script> 
```

To display the button itself, insert the following code snippet somewhere in your HTML document.

```html
<google-pay-button environment="PRODUCTION" 
                   button-locale="sk"
                   button-color="white" 
                   button-size-mode="fill" 
                   style="width:100%">
</google-pay-button>
```
All properties of the button are described in the [GitHub documentation](https://github.com/google-pay/google-pay-button/tree/main/src/button-element#properties).

To initialize the button, run the `initGooglePayButton`. The most basic configuration:

```js
const config = {
    // price shown to user
    totalPrice: '0.10',
    // merchantId is one of the credentials obtain during configuration
    merchantId: '1234567890',
    // some merchant name to show to user
    merchantName: 'Example merchant',

    // Function to retrieve sales funnel payment form data (will be sent together with Google Pay token to backend).
    // Library takes care of response processing and redirect.
    salesFunnelFormData: () => {
        return new FormData(document.getElementById('form'));
    },

    // Optional, function returning true/false depending on whether Google Pay execution should continue after clicking on the button.
    isValid: () => true, 
    
    // Optional, callback function to show progress during the payment   
    inProgress: (promise) => {
        console.log("Google payment starts"); // here, display some animation 
        promise.then(() => {
            console.log("Google payment starts"); // here, stop the animation
        });
    }
};
RempWalletPay.initGooglePayButton(config);
```

Alternatively, provide `onSuccess` callback, which gives control of processing of the Google Pay token issued during the payment and location to put 3DS dialog HTML code:

```js
const config = {
    // price shown to user
    totalPrice: '0.10',
    // merchantId is one of the credentials obtain during configuration
    merchantId: '1234567890',
    // some merchant name to show to user
    merchantName: 'Example merchant',

    // Callback, called after user confirms the payment in the Google Pay dialog.
    // Argument:
    // - token - Google Pay token
    onSuccess: (token) =>  {
        var fd = new FormData(document.getElementById('form'));
        fd.append('google_pay_token', token);

        fetch('/sales-funnel/sales-funnel-frontend/submit', {
            method: 'POST',
            body: fd,
        })
            .then(response => response.json())
            .then(data => {
                // if 'tds_html' attribute is present, 3DS is required
                if (data.google_pay.tds_html) {
                    // helper function to display 3DS html in an iframe modal
                    RempWalletPay.show3ds(data.google_pay.tds_html);
                } else {
                    // redirect to success page (no 3DS requirement to finish the payment)
                    window.top.location.href = data.google_pay.redirect_url;
                }
            });
    },
    
    // Optional, function returning true/false depending on whether Google Pay execution should continue after clicking on the button.
    isValid: () => true,

    // Optional, callback function to show progress during the payment   
    inProgress: (promise) => {
        console.log("Google payment starts"); // here, display some animation 
        promise.then(() => {
            console.log("Google payment starts"); // here, stop the animation
        });
    }
};
RempWalletPay.initGooglePayButton(config);
```

#### Pay button update

To update price of the button, call:

```js
RempWalletPay.updateGooglePayButton({
    totalPrice: '0.20', // new price
});
```

## Extension

If you want to use your own gateway provider (instead of Tatrabanka), you can extend this module with your own implementation. Please note that this module does not provide support for _direct_ wallet pay payments.

First, you need to implement the integration by providing the implementation of the Google/Apple pay interfaces:

- `Crm\WalletPayModule\Model\GooglePayWalletInterface`
- `Crm\WalletPayModule\Model\ApplePayWalletInterface`

When they're ready, register them in your `config.neon` file and override the default implementation provided by this module:

```neon
services:
	applePayWallet: Crm\FooModule\Model\YourProviderApplePayWallet
	googlePayWallet: Crm\FooModule\Model\YourProviderGooglePayWallet
```

## API documentation

All examples use `http://crm.press` as a base domain. Please change the host to the one you use
before executing the examples.

API responses can contain following HTTP codes:

| Value | Description |
| --- | --- |
| 200 OK | Successful response, default value |
| 400 Bad Request | Invalid request (missing required parameters) |
| 403 Forbidden | The authorization failed (provided token was not valid) |
| 404 Not found | Referenced resource wasn't found |

If possible, the response includes `application/json` encoded payload with message explaining
the error further.

---

#### GET `/api/v1/apple-pay/merchant-validation`

API call that validates Apple Pay merchant identity.


##### *Params:*

| Name | Value | Required | Description                                                                                                                                                                                                                                                                                                                                                                               |
|------|---| --- |-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| url  | *String* | yes | Validation URL, as described in the Apple Pay Merchant Validation [docs](https://developer.apple.com/documentation/apple_pay_on_the_web/apple_pay_js_api/providing_merchant_validation). It should be retrieved from `ApplePaySession`'s [`onvalidatemerchant`](https://developer.apple.com/documentation/apple_pay_on_the_web/applepaysession/1778021-onvalidatemerchant) event handler. |


##### *Example:*

```shell
curl -v –X GET http://crm.press/api/v1/apple-pay/merchant-identity?url=VALIDATION_URL
```

Response:

```json5
{
  "epochTimestamp": 1655206603128,
  "expiresAt": 1655210203128,
  "merchantSessionIdentifier": "...",
  "nonce": "SOME_NONCE",
  "merchantIdentifier": "...",
  "domainName": "crm.press",
  "displayName": "CRM",
  "signature": "SIGNATURE_DATA_BASE64",
  "operationalAnalyticsIdentifier": "DEVEL CRM:SOME_ID",
  "retries": 0,
  "pspId": "..."
}
```

Response may vary, since it contains data returned by Merchant Validation service provided by Apple. 
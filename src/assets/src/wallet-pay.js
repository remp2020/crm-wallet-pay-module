import {show3ds} from "./3ds-modal";
import {GooglePayButton} from "./google-pay";
import {ApplePayButton} from "./apple-pay"
import {loadScript} from "./load-script";

function RempWalletPay() {
    this.googlePayButton = null;
    this.applePayButton = null;

    // Google

    /**
     * Please call this function first to initialize Google pay button.
     *
     * @param config Sample config below
     * const config =  {
     *   merchantId: '1234567890', // required
     *   totalPrice: '122.60',     // required
     *   merchantName: 'N Press',  // required
     *   salesFunnelFormData: functionName // required (or provide "onSuccess")
     *   onSuccess: functionName,  // required (or provide "salesFunnelFormData")
     *   inProgress: functionName // optional, provide only if "salesFunnelFormData" is used.
     *                            // function receives Promise parameter, which resolves when payment response is returned from server
     *                            // can be used to display loading indicator
     *   isValid: functionName(),  // optional, function should return true/false
     * }
     */
    this.initGooglePayButton = function(config) {
        const button = document.getElementsByTagName("google-pay-button")[0];
        if (!button) {
            console.warn("WalletPay - unable to find <google-pay-button> element, make sure it's present in DOM.");
        }
        this.googlePayButton = new GooglePayButton(button, config);
    };

    /**
     * Currently, only price can be updated
     * @param config Sample config below
     * const config = {
     *     totalPrice: '60',
     * }
     */
    this.updateGooglePayButton = function (config) {
        this.googlePayButton.update(config);
    }

    this.show3ds = (content) => show3ds(content);

    // APPLE

    this.checkApplePayAvailability = async function(merchantId, callback) {
        if (window.ApplePaySession) {
            const available = await ApplePaySession.canMakePaymentsWithActiveCard(merchantId);
            if (available) {
                callback();
            }
        }
    }

    /**
     * Please call this function first to initialize Apple pay button.
     *
     * @param config Sample config below
     * const config =  {
     *   totalPrice: '122.60',     // required
     *   merchantName: 'N Press',  // required
     *   productName: 'Subscription',  // required
     *   salesFunnelFormData: functionName // required (or provide "onSuccess")
     *   onSuccess: functionName,  // required (or provide "salesFunnelFormData")
     *   isValid: functionName(),  // optional, function should return true/false
     * }
     */
    this.initApplePayButton = function(config) {
        const button = document.getElementsByTagName("apple-pay-button")[0];
        if (!button) {
            console.warn("WalletPay - unable to find <apple-pay-button> element, make sure it's present in DOM.");
        }
        this.applePayButton = new ApplePayButton(button, config);
    }

    this.updateApplePayButton = function (config) {
        this.applePayButton.update(config);
    }
}

// available everywhere as global variable
window.RempWalletPay = new RempWalletPay();

// wait to have document.body ready (required by `loadScript`)
document.addEventListener('DOMContentLoaded', function () {
    loadScript('https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js');
});

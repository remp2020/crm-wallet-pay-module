// do not remove, import initializes the button
import GoogleButtonElement from '@google-pay/button-element';
import {show3ds} from "./3ds-modal";

export function GooglePayButton(buttonElement, config) {
    if (!config.totalPrice) {
        console.warn("GooglePayButton - error, missing required config value 'totalPrice'");
    }
    if (!config.merchantName) {
        console.warn("GooglePayButton - error, missing required config value 'merchantName'");
    }
    if (!config.merchantId) {
        console.warn("GooglePayButton - error, missing required config value 'merchantName'");
    }
    if (!config.onSuccess && !config.salesFunnelFormData) {
        console.warn("GooglePayButton - error, please provide one of the config values, either 'onSuccess' or 'salesFunnelFormData'.");
    }

    let finalConfig = googlePayConfig(config);
    Object.assign(buttonElement, finalConfig);

    // save variables
    this.buttonElement = buttonElement;
    this.config = finalConfig;

    // currently, only price can be updated
    this.update = function(config) {
        this.config.paymentRequest.transactionInfo.totalPrice = config.totalPrice;
        Object.assign(this.buttonElement, this.config);
    };
}

const defaultPaymentRequest = {
    apiVersion: 2,
    apiVersionMinor: 0,
    allowedPaymentMethods: [
        {
            type: 'CARD',
            parameters: {
                allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                allowedCardNetworks: ["AMEX", "DISCOVER", "INTERAC", "JCB", "MASTERCARD", "VISA"],
            },
            tokenizationSpecification: {
                type: 'PAYMENT_GATEWAY',
                parameters: {
                    gateway: 'tatrabanka',
                    gatewayMerchantId: '5120',
                },
            },
        },
    ],
    merchantInfo: {
        merchantId: '12345678901234567890',
        merchantName: 'Example Merchant',
    },
    transactionInfo: {
        totalPriceStatus: 'FINAL',
        totalPriceLabel: 'Celkovo',
        totalPrice: '1.00',
        // TODO: load from config
        currencyCode: 'EUR',
        countryCode: 'SK',
    },
};

function googlePayConfig(config) {
    return {
        paymentRequest: {
            ...defaultPaymentRequest,
            transactionInfo: {
                ...defaultPaymentRequest.transactionInfo,
                totalPrice: config.totalPrice,
            },
            merchantInfo: {
                ...defaultPaymentRequest.merchantInfo,
                merchantId: config.merchantId,
                merchantName: config.merchantName,
            }
        },
        onClick(event) {
            if (config.isValid && !config.isValid()) {
                event.preventDefault();
            }
        },
        onLoadPaymentData(paymentData) {
            const token = paymentData.paymentMethodData.tokenizationData.token;

            if (config.salesFunnelFormData) {
                const promise = new Promise(async (resolve, reject) => {
                    let formData;
                    if (typeof config.salesFunnelFormData === 'function') {
                        formData = config.salesFunnelFormData();
                    } else {
                        formData = config.salesFunnelFormData;
                    }
                    formData.append('google_pay_token', token);

                    try {
                        const response = await fetch('/sales-funnel/sales-funnel-frontend/submit', {
                            method: 'POST',
                            mode: 'cors',
                            cache: 'no-cache',
                            body: formData,
                        });

                        const responseData = await response.json();
                        resolve(); // finish promise here

                        if (responseData.google_pay.tds_html) {
                            show3ds(responseData.google_pay.tds_html);
                        } else {
                            window.top.location.href = responseData.google_pay.redirect_url;
                        }
                    } catch (e) {
                        console.error("GooglePay - error during payment submission: " + e);
                        reject("GooglePay - error during payment submission: " + e);
                    }
                });
                if (config.inProgress) {
                    config.inProgress(promise);
                }
            } else { // onSuccess
                config.onSuccess(token);
            }
        },
        onError(reason) {
            console.error("Google Pay - error has occurred", reason);
        },
    };
}
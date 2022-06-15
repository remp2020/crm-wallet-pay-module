export function ApplePayButton(element, config) {
    if (!config.productName) {
        console.warn("ApplePayButton - error, missing required config value 'productName'");
    }
    if (!config.totalPrice) {
        console.warn("ApplePayButton - error, missing required config value 'totalPrice'");
    }
    if (!config.merchantName) {
        console.warn("ApplePayButton - error, missing required config value 'merchantName'");
    }
    if (!config.onSuccess && !config.salesFunnelFormData) {
        console.warn("ApplePayButton - error, please provide one of the config values, either 'onSuccess' or 'salesFunnelFormData'.");
    }

    this.config = config;

    this.paymentRequest = {
        currencyCode: 'EUR',
        countryCode: 'SK',
        lineItems: [
            {label: config.productName, amount: config.totalPrice},
        ],
        total: {
            // TODO: load title from 'site_title' config
            label: config.merchantName,
            amount: config.totalPrice,
        },
        supportedNetworks: ['amex', 'masterCard', 'visa' ],
        merchantCapabilities: ['supports3DS', 'supportsEMV', 'supportsCredit', 'supportsDebit']
    };

    this.update = (newConfig) => {
        this.config = {
            ...this.config, //old config
            ...newConfig
        };

        this.paymentRequest.lineItems = [
            {label: this.config.productName, amount: this.config.totalPrice}
        ];
        this.paymentRequest.total = {
            label: this.config.merchantName,
            amount: this.config.totalPrice,
        };
    };

    element.onclick = (event) => {
        if (!config.isValid()) {
            return;
        }

        const session = new ApplePaySession(1, this.paymentRequest);

        session.onvalidatemerchant = async function (event) {
            const merchantSession = await performValidation(event.validationURL);
            session.completeMerchantValidation(merchantSession);
        }

        session.onshippingcontactselected = function(event) {
            console.warn("'onshippingcontactselected' not implemented");
        }
        session.onshippingmethodselected = function(event) {
            console.warn("'onshippingmethodselected' not implemented");
        }

        session.onpaymentmethodselected = (event) => {
            session.completePaymentMethodSelection(this.paymentRequest.total, this.paymentRequest.lineItems);
        }

        session.onpaymentauthorized = async function (event) {
            const token = JSON.stringify(event.payment.token.paymentData);

            if (config.salesFunnelFormData) {
                let formData;
                if (typeof config.salesFunnelFormData === 'function') {
                    formData = config.salesFunnelFormData();
                } else {
                    formData = config.salesFunnelFormData;
                }
                formData.append('apple_pay_token', token);

                try {
                    const response = await fetch('/sales-funnel/sales-funnel-frontend/submit', {
                        method: 'POST',
                        mode: 'cors',
                        cache: 'no-cache',
                        body: formData,
                    });
                    const responseData = await response.json();
                    if (responseData.status === 'ok') {
                        session.completePayment(ApplePaySession.STATUS_SUCCESS);
                        window.top.location.href = responseData.apple_pay.redirect_url;
                    } else {
                        session.completePayment(ApplePaySession.STATUS_FAILURE);
                    }
                } catch (e) {
                    session.completePayment(ApplePaySession.STATUS_FAILURE);
                }

            } else { // onSuccess
                config.onSuccess(token, (paymentCompleted) => {
                    return session.completePayment(paymentCompleted ? ApplePaySession.STATUS_SUCCESS : ApplePaySession.STATUS_FAILURE);
                });
            }
        }

        session.oncancel = function(event) {
            // nothing
        }

        session.begin();
    }
}

async function performValidation(validationURL) {
    return new Promise(function(resolve, reject) {
        var xhr = new XMLHttpRequest();
        xhr.onload = function() {
            const data = JSON.parse(this.responseText);
            resolve(data);
        };
        xhr.onerror = reject;
        xhr.open('GET', '/api/v1/apple-pay/merchant-validation?url=' + validationURL);
        xhr.send();
    });
}
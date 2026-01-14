define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/full-screen-loader',
], function ($, wrapper, fullScreenLoader) {
    'use strict';

    return function (errorProcessor) {

        function getMethodCode(quote) {
            var code = (quote.paymentMethod() && quote.paymentMethod().method)
                ? quote.paymentMethod().method
                : 'chcybersource';

            return code.replace(/_(\d+)$/, '');
        }
        function placeOrder(additionalAttributes, messageContainer) {
            require(['Magento_Checkout/js/action/place-order', 'Magento_Checkout/js/model/quote', 'Magento_Checkout/js/action/redirect-on-success'],
                function (placeOrderAction, quote, redirectOnSuccessAction) {
                    let attributes = { 'method': getMethodCode(quote) };
                    if (additionalAttributes) {
                        attributes = { ...attributes, ...additionalAttributes };
                    }
                    placeOrderAction(attributes, messageContainer).done(
                        function () {
                            redirectOnSuccessAction.execute();
                        }
                    ).fail(
                        function () {
                            fullScreenLoader.stopLoader();
                        }
                    );
                }
            );
        }
        navigator.browser= (function(){
            var agent= navigator.userAgent,
            data= agent.match(/(opera|chrome|safari|firefox|msie)\/?\s*([\d\.]+)/i);
            return data[1];
        })();

        errorProcessor.process = wrapper.wrap(
            errorProcessor.process,
            function (originalProcess, response, messageContainer) {
                if (response.responseJSON && response.responseJSON.code) {
                    if (response.responseJSON.code === 475) {
                        var mdData = document.getElementById('md-data') 
                        mdData.value = navigator.browser;
                        var responseObj = JSON.parse(response.responseText);
                        const windowsize = responseObj.parameters.cca.Payload;
                        var formIframe = document.getElementById('step-up-iframe');
                        try {
                            const decodedString = atob(windowsize);
                            const jsonObject = JSON.parse(decodedString);
                            const challengeWindowSize = jsonObject.challengeWindowSize;

                            formIframe.width =
                                challengeWindowSize === '01' ? '250px' :
                                    challengeWindowSize === '02' ? '390px' :
                                        challengeWindowSize === '03' ? '500px' :
                                            challengeWindowSize === '04' ? '600px' :
                                                '100%';

                            formIframe.height =
                                challengeWindowSize === '01' ? '400px' :
                                    challengeWindowSize === '02' ? '400px' :
                                        challengeWindowSize === '03' ? '600px' :
                                            challengeWindowSize === '04' ? '400px' :
                                                '100%';
                        } catch (error) {
                            formIframe.width = '400px';
                            formIframe.height = '400px';
                        }
                        var stepUpForm = document.getElementById('step-up-form');
                        stepUpForm.setAttribute('action', responseObj.parameters.cca.stepUpUrl);
                        var stepup_form_input = document.getElementById('step-up-form-input');
                        stepup_form_input.value = responseObj.parameters.cca.accessToken;
                        var stepUpIframe = document.querySelector('#step-up-iframe');
                        stepUpIframe.style.display = "block";
                        var overlay = document.getElementById('overlay');
                        overlay.style.display = "block";
                        if (stepUpForm) {
                            stepUpForm.submit();
                            var checkTransactionId = setInterval(function () {
                                var element = document.querySelector('#step-up-iframe').contentWindow.length;
                                if (element != '1') {
                                    clearInterval(checkTransactionId);
                                    stepUpIframe.style.display = "none";
                                    overlay.style.display = "none";
                                    placeOrder({
                                        'extension_attributes': { 'cca_response': responseObj.parameters.cca.accessToken }
                                    }, messageContainer);
                                }
                            }, 2000);
                        }
                    }
                    else if (response.responseJSON.code === 478) {
                        placeOrder({
                            'extension_attributes': { 'cca_response': '' }
                        }, messageContainer);
                    }
                    else {
                        return originalProcess(response, messageContainer);
                    }
                }
                else if (response.status == 400) {
                    fullScreenLoader.stopLoader();
                    return originalProcess(response, messageContainer);
                }
                else {
                    return originalProcess(response, messageContainer);
                }
            });

        return errorProcessor;
    };
});
